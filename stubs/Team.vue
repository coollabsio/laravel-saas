<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import TeamController from '@/actions/Coollabsio/LaravelSaas/Http/Controllers/TeamController';
import TeamInvitationController from '@/actions/Coollabsio/LaravelSaas/Http/Controllers/TeamInvitationController';
import TeamMemberController from '@/actions/Coollabsio/LaravelSaas/Http/Controllers/TeamMemberController';
import { edit } from '@/actions/Coollabsio/LaravelSaas/Http/Controllers/TeamController';
import type { BreadcrumbItem, Team, TeamMember, TeamInvitation } from '@/types';
import { ref } from 'vue';

type Props = {
    team: Team;
    members: TeamMember[];
    invitations: TeamInvitation[];
    isOwner: boolean;
};

const props = defineProps<Props>();
const showDeleteDialog = ref(false);
const deleting = ref(false);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Team settings',
        href: edit().url,
    },
];

const handleRemoveMember = (memberId: number) => {
    if (confirm('Are you sure you want to remove this member?')) {
        router.delete(
            TeamMemberController.destroy.url({
                team: props.team.id,
                user: memberId,
            }),
        );
    }
};

const cancelInvitation = (invitationId: number) => {
    router.delete(
        TeamInvitationController.destroy.url({
            team: props.team.id,
            invitation: invitationId,
        }),
    );
};

const deleteTeam = () => {
    deleting.value = true;
    router.delete(TeamController.destroy.url(props.team.id), {
        onFinish: () => {
            deleting.value = false;
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Team settings" />

        <h1 class="sr-only">Team Settings</h1>

        <SettingsLayout>
            <!-- Team Name -->
            <div class="flex flex-col space-y-6">
                <Heading variant="small" title="Team name" description="Update your team's name" />

                <Form v-bind="TeamController.update.form({
                    team: team.id,
                })
                    " class="space-y-6" v-slot="{ errors, processing, recentlySuccessful }">
                    <div class="grid gap-3">
                        <Label for="name">Name</Label>
                        <Input id="name" name="name" :default-value="team.name" required :disabled="!isOwner" />
                        <InputError :message="errors.name" />
                    </div>

                    <div v-if="isOwner" class="flex items-center gap-4">
                        <Button :disabled="processing">Save</Button>

                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                            <p v-show="recentlySuccessful" class="text-sm text-neutral-600">
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <!-- Members -->
            <div class="flex flex-col space-y-6">
                <Heading variant="small" title="Team members" description="Manage your team's members" />

                <div class="space-y-3">
                    <div v-for="member in members" :key="member.id"
                        class="flex items-center justify-between rounded-sm border p-3">
                        <div>
                            <p class="text-sm font-medium">{{ member.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ member.email }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <select v-if="isOwner && member.id !== team.owner_id"
                                :value="member.role"
                                @change="router.patch(TeamMemberController.update.url({ team: team.id, user: member.id }), { role: ($event.target as HTMLSelectElement).value }, { preserveScroll: true })"
                                class="appearance-none rounded-sm border-2 border-input py-1 px-2 text-xs capitalize bg-white dark:bg-coolgray-100 dark:text-white focus-visible:outline-none focus:border-input transition-shadow">
                                <option value="member">Member</option>
                                <option value="owner">Owner</option>
                            </select>
                            <Badge v-else variant="secondary" class="capitalize">{{ member.role }}</Badge>
                            <button v-if="isOwner && member.id !== team.owner_id"
                                class="text-muted-foreground hover:text-destructive"
                                @click="handleRemoveMember(member.id)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Invitations -->
            <div v-if="isOwner && invitations.length > 0" class="flex flex-col space-y-6">
                <Heading variant="small" title="Pending invitations"
                    description="Invitations that have been sent but not yet accepted" />

                <div class="space-y-4">
                    <div v-for="invitation in invitations" :key="invitation.id"
                        class="flex items-center justify-between rounded-lg border p-4">
                        <div>
                            <p class="text-sm font-medium">
                                {{ invitation.email }}
                            </p>
                            <p class="text-sm text-muted-foreground capitalize">
                                {{ invitation.role }}
                            </p>
                        </div>
                        <Button variant="ghost" size="icon" @click="cancelInvitation(invitation.id)">
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Invite Member -->
            <div v-if="isOwner" class="flex flex-col space-y-6">
                <Heading variant="small" title="Invite team member"
                    description="Invite a new member to your team by email" />

                <Form v-bind="TeamInvitationController.store.form({
                    team: team.id,
                })
                    " class="space-y-6" v-slot="{ errors, processing, recentlySuccessful }">
                    <div class="grid gap-3">
                        <Label for="email">Email address</Label>
                        <Input id="email" type="email" name="email" placeholder="email@example.com" required />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-3">
                        <Label for="role">Role</Label>
                        <select id="role" name="role"
                            class="appearance-none block w-full min-w-0 rounded-sm border-2 border-input py-1.5 px-2 text-sm text-black bg-white dark:bg-coolgray-100 dark:text-white focus-visible:outline-none focus:border-input transition-shadow">
                            <option value="member">Member</option>
                            <option value="owner">Owner</option>
                        </select>
                        <InputError :message="errors.role" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="processing">Send Invitation</Button>

                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                            <p v-show="recentlySuccessful" class="text-sm text-neutral-600">
                                Invitation sent.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <!-- Delete Team -->
            <div v-if="isOwner && !team.personal_team" class="flex flex-col space-y-6">
                <Heading variant="small" title="Delete team"
                    description="Permanently delete this team and all of its data" />

                <Button variant="destructive" @click="showDeleteDialog = true">
                    Delete Team
                </Button>
            </div>
        </SettingsLayout>

        <Dialog v-model:open="showDeleteDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Delete team</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this team? This will permanently delete the team and all of its data. This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-sm border border-input bg-background px-4 text-sm font-medium hover:bg-accent hover:text-accent-foreground"
                        @click="showDeleteDialog = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-sm bg-destructive px-4 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50"
                        :disabled="deleting"
                        @click="deleteTeam"
                    >
                        {{ deleting ? 'Deleting...' : 'Delete' }}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
