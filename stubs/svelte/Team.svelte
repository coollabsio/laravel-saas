<script lang="ts">
    import { useForm, router } from '@inertiajs/svelte';
    import { Trash2 } from 'lucide-svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Badge } from '@/components/ui/badge';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import SettingsLayout from '@/layouts/settings/Layout.svelte';
    import TeamController from '@/actions/Coollabsio/LaravelSaas/Http/Controllers/TeamController';
    import TeamInvitationController from '@/actions/Coollabsio/LaravelSaas/Http/Controllers/TeamInvitationController';
    import TeamMemberController from '@/actions/Coollabsio/LaravelSaas/Http/Controllers/TeamMemberController';
    import { edit } from '@/actions/Coollabsio/LaravelSaas/Http/Controllers/TeamController';
    import type { BreadcrumbItem, Team, TeamMember, TeamInvitation } from '@/types';

    interface Props {
        team: Team;
        members: TeamMember[];
        invitations: TeamInvitation[];
        isOwner: boolean;
    }

    let { team, members, invitations, isOwner }: Props = $props();

    const breadcrumbItems: BreadcrumbItem[] = [
        {
            title: 'Team settings',
            href: edit().url,
        },
    ];

    const updateTeamForm = useForm({ name: team.name });
    const inviteForm = useForm({ email: '', role: 'member' });

    let showDeleteDialog = $state(false);
    let deleting = $state(false);
    let deleteConfirmation = $state('');

    function handleUpdateTeam(e: SubmitEvent) {
        e.preventDefault();
        $updateTeamForm.patch(
            TeamController.update.url({ team: team.id }),
        );
    }

    function handleRoleChange(memberId: number, event: Event) {
        const role = (event.target as HTMLSelectElement).value;
        router.patch(
            TeamMemberController.update.url({ team: team.id, user: memberId }),
            { role },
            { preserveScroll: true },
        );
    }

    function handleRemoveMember(memberId: number) {
        if (confirm('Are you sure you want to remove this member?')) {
            router.delete(
                TeamMemberController.destroy.url({
                    team: team.id,
                    user: memberId,
                }),
            );
        }
    }

    function cancelInvitation(invitationId: number) {
        router.delete(
            TeamInvitationController.destroy.url({
                team: team.id,
                invitation: invitationId,
            }),
        );
    }

    function handleInvite(e: SubmitEvent) {
        e.preventDefault();
        $inviteForm.post(
            TeamInvitationController.store.url({ team: team.id }),
            {
                onSuccess: () => $inviteForm.reset(),
            },
        );
    }

    function deleteTeam() {
        deleting = true;
        router.delete(TeamController.destroy.url(team.id), {
            onFinish: () => {
                deleting = false;
            },
        });
    }
</script>

<svelte:head><title>Team settings</title></svelte:head>

<AppLayout breadcrumbs={breadcrumbItems}>
    <h1 class="sr-only">Team Settings</h1>

    <SettingsLayout>
        <!-- Team Name -->
        <div class="flex flex-col space-y-6">
            <Heading variant="small" title="Team name" description="Update your team's name" />

            <form onsubmit={handleUpdateTeam} class="space-y-6">
                <div class="grid gap-3">
                    <Label for="name">Name</Label>
                    <Input id="name" bind:value={$updateTeamForm.name} required disabled={!isOwner} />
                    <InputError message={$updateTeamForm.errors.name} />
                </div>

                {#if isOwner}
                    <div class="flex items-center gap-4">
                        <Button disabled={$updateTeamForm.processing}>Save</Button>

                        {#if $updateTeamForm.recentlySuccessful}
                            <p class="text-sm text-neutral-600">Saved.</p>
                        {/if}
                    </div>
                {/if}
            </form>
        </div>

        <!-- Members -->
        <div class="flex flex-col space-y-6">
            <Heading variant="small" title="Team members" description="Manage your team's members" />

            <div class="space-y-3">
                {#each members as member (member.id)}
                    <div class="flex items-center justify-between rounded-sm border p-3">
                        <div>
                            <p class="text-sm font-medium">{member.name}</p>
                            <p class="text-xs text-muted-foreground">{member.email}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            {#if isOwner && member.id !== team.owner_id}
                                <select
                                    value={member.role}
                                    onchange={(e) => handleRoleChange(member.id, e)}
                                    class="appearance-none rounded-sm border-2 border-input py-1 px-2 text-xs capitalize bg-white dark:bg-coolgray-100 dark:text-white focus-visible:outline-none focus:border-input transition-shadow"
                                >
                                    <option value="member">Member</option>
                                    <option value="owner">Owner</option>
                                </select>
                            {:else}
                                <Badge variant="secondary" class="capitalize">{member.role}</Badge>
                            {/if}
                            {#if isOwner && member.id !== team.owner_id}
                                <button
                                    class="text-muted-foreground hover:text-destructive"
                                    onclick={() => handleRemoveMember(member.id)}
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            {/if}
                        </div>
                    </div>
                {/each}
            </div>
        </div>

        <!-- Pending Invitations -->
        {#if isOwner && invitations.length > 0}
            <div class="flex flex-col space-y-6">
                <Heading variant="small" title="Pending invitations" description="Invitations that have been sent but not yet accepted" />

                <div class="space-y-4">
                    {#each invitations as invitation (invitation.id)}
                        <div class="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <p class="text-sm font-medium">{invitation.email}</p>
                                <p class="text-sm text-muted-foreground capitalize">{invitation.role}</p>
                            </div>
                            <Button variant="ghost" size="icon" onclick={() => cancelInvitation(invitation.id)}>
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    {/each}
                </div>
            </div>
        {/if}

        <!-- Invite Member -->
        {#if isOwner}
            <div class="flex flex-col space-y-6">
                <Heading variant="small" title="Invite team member" description="Invite a new member to your team by email" />

                <form onsubmit={handleInvite} class="space-y-6">
                    <div class="grid gap-3">
                        <Label for="email">Email address</Label>
                        <Input id="email" type="email" bind:value={$inviteForm.email} placeholder="email@example.com" required />
                        <InputError message={$inviteForm.errors.email} />
                    </div>

                    <div class="grid gap-3">
                        <Label for="role">Role</Label>
                        <select
                            id="role"
                            bind:value={$inviteForm.role}
                            class="appearance-none block w-full min-w-0 rounded-sm border-2 border-input py-1.5 px-2 text-sm text-black bg-white dark:bg-coolgray-100 dark:text-white focus-visible:outline-none focus:border-input transition-shadow"
                        >
                            <option value="member">Member</option>
                            <option value="owner">Owner</option>
                        </select>
                        <InputError message={$inviteForm.errors.role} />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button disabled={$inviteForm.processing}>Send Invitation</Button>

                        {#if $inviteForm.recentlySuccessful}
                            <p class="text-sm text-neutral-600">Invitation sent.</p>
                        {/if}
                    </div>
                </form>
            </div>
        {/if}

        <!-- Delete Team -->
        {#if isOwner && !team.personal_team}
            <div class="flex flex-col space-y-6">
                <Heading variant="small" title="Delete team" description="Permanently delete this team and all of its data" />

                <Button variant="destructive" onclick={() => showDeleteDialog = true}>
                    Delete Team
                </Button>
            </div>
        {/if}
    </SettingsLayout>

    <Dialog bind:open={showDeleteDialog} onOpenChange={(open) => { if (!open) deleteConfirmation = ''; }}>
        <DialogContent class="sm:max-w-md">
            <div class="flex flex-col space-y-1.5 text-center sm:text-left">
                <DialogTitle>Delete team</DialogTitle>
                <DialogDescription>
                    This will permanently delete the team and all of its data. This action cannot be undone.
                </DialogDescription>
            </div>
            <div class="space-y-2">
                <Label for="delete-confirmation">
                    Type <span class="font-semibold">{team.name}</span> to confirm
                </Label>
                <Input
                    id="delete-confirmation"
                    value={deleteConfirmation}
                    oninput={(e) => deleteConfirmation = e.currentTarget.value}
                    placeholder={team.name}
                    autocomplete="off"
                />
            </div>
            <DialogFooter class="pt-4">
                <button
                    type="button"
                    class="inline-flex h-9 items-center justify-center rounded-sm border border-input bg-background px-4 text-sm font-medium hover:bg-accent hover:text-accent-foreground"
                    onclick={() => showDeleteDialog = false}
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="inline-flex h-9 items-center justify-center rounded-sm bg-destructive px-4 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50"
                    disabled={deleting || deleteConfirmation !== team.name}
                    onclick={deleteTeam}
                >
                    {deleting ? 'Deleting...' : 'Delete'}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</AppLayout>
