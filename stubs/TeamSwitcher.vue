<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ChevronsUpDown, Check, Plus } from 'lucide-vue-next';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { computed, ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { switchMethod } from '@/routes/teams';
import { edit as editTeam } from '@/actions/App/Http/Controllers/TeamController';
import { store as storeTeam } from '@/actions/App/Http/Controllers/TeamController';
import type { Team } from '@/types';

const page = usePage();
const { isMobile, state } = useSidebar();

const currentTeam = computed(() => page.props.currentTeam as Team | null);
const teams = computed(() => page.props.teams as Team[] | null);
const instance = computed(() => page.props.instance as { selfHosted: boolean; isRootUser: boolean; registrationEnabled: boolean } | null);

const showCreateDialog = ref(false);
const createTeamForm = useForm({ name: '' });

const handleCreateTeam = () => {
    createTeamForm.post(storeTeam().url, {
        onSuccess: () => {
            createTeamForm.reset();
            showCreateDialog.value = false;
        },
    });
};
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                    >
                        <div
                            class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground"
                        >
                            <AppLogoIcon class="size-5 fill-current" />
                        </div>
                        <div
                            class="grid flex-1 text-left text-sm leading-tight"
                        >
                            <span class="truncate font-semibold text-black dark:text-white">
                                {{ currentTeam?.name }}
                            </span>
                        </div>
                        <ChevronsUpDown class="ml-auto size-4" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                    :side="
                        isMobile
                            ? 'bottom'
                            : state === 'collapsed'
                              ? 'right'
                              : 'bottom'
                    "
                    align="start"
                    :side-offset="4"
                >
                    <DropdownMenuLabel class="text-xs text-muted-foreground">
                        Teams
                    </DropdownMenuLabel>
                    <DropdownMenuItem
                        v-for="team in teams"
                        :key="team.id"
                        class="cursor-pointer p-2"
                        @click="
                            team.id === currentTeam?.id
                                ? router.visit(editTeam().url)
                                : router.put(
                                      switchMethod(team.id).url,
                                      {},
                                      { preserveState: false },
                                  )
                        "
                    >
                        {{ team.name }}
                        <Check
                            v-if="team.id === currentTeam?.id"
                            class="ml-auto size-4"
                        />
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        v-if="instance?.isRootUser"
                        class="cursor-pointer gap-2 p-2"
                        @click="router.visit('/settings/instance')"
                    >
                        Instance Settings
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        class="cursor-pointer gap-2 p-2"
                        @click="showCreateDialog = true"
                    >
                        <Plus class="size-4" />
                        Create Team
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>

    <Dialog v-model:open="showCreateDialog">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Create Team</DialogTitle>
                <DialogDescription>
                    Create a new team to collaborate with others.
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="handleCreateTeam" class="space-y-4">
                <div class="space-y-2">
                    <Label for="team-name">Team name</Label>
                    <Input
                        id="team-name"
                        v-model="createTeamForm.name"
                        placeholder="My Team"
                        autofocus
                    />
                    <p v-if="createTeamForm.errors.name" class="text-sm text-destructive">
                        {{ createTeamForm.errors.name }}
                    </p>
                </div>
                <DialogFooter>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-sm border border-input bg-background px-4 text-sm font-medium hover:bg-accent hover:text-accent-foreground"
                        @click="showCreateDialog = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex h-9 items-center justify-center rounded-sm bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                        :disabled="createTeamForm.processing || !createTeamForm.name.trim()"
                    >
                        {{ createTeamForm.processing ? 'Creating...' : 'Create' }}
                    </button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
