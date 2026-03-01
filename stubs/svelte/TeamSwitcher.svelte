<script lang="ts">
    import { router, useForm, page } from '@inertiajs/svelte';
    import { ChevronsUpDown, Check, Plus } from 'lucide-svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
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
    import { switchTeam, edit as editTeam, store as storeTeam } from '@/actions/Coollabsio/LaravelSaas/Http/Controllers/TeamController';
    import type { Team } from '@/types';

    const currentTeam = $derived($page.props.currentTeam as Team | null);
    const teams = $derived($page.props.teams as Team[] | null);

    const { isMobile, state: sidebarState } = useSidebar();

    let showCreateDialog = $state(false);
    const createTeamForm = useForm({ name: '' });

    function handleTeamClick(team: Team) {
        if (team.id === currentTeam?.id) {
            router.visit(editTeam().url);
        } else {
            router.put(switchTeam(team.id).url, {}, { preserveState: false });
        }
    }

    function handleCreateTeam(e: SubmitEvent) {
        e.preventDefault();
        $createTeamForm.post(storeTeam().url, {
            onSuccess: () => {
                $createTeamForm.reset();
                showCreateDialog = false;
            },
        });
    }
</script>

<SidebarMenu>
    <SidebarMenuItem>
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                {#snippet children(triggerProps)}
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        {...triggerProps}
                    >
                        <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                            <AppLogoIcon class="size-5 fill-current" />
                        </div>
                        <div class="grid flex-1 text-left text-sm leading-tight">
                            <span class="truncate font-semibold text-black dark:text-white">
                                {currentTeam?.name}
                            </span>
                        </div>
                        <ChevronsUpDown class="ml-auto size-4" />
                    </SidebarMenuButton>
                {/snippet}
            </DropdownMenuTrigger>
            <DropdownMenuContent
                class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                side={isMobile ? 'bottom' : sidebarState === 'collapsed' ? 'right' : 'bottom'}
                align="start"
                sideOffset={4}
            >
                <DropdownMenuLabel class="text-xs text-muted-foreground">
                    Teams
                </DropdownMenuLabel>
                {#each teams ?? [] as team (team.id)}
                    <DropdownMenuItem
                        class="cursor-pointer p-2"
                        onclick={() => handleTeamClick(team)}
                    >
                        {team.name}
                        {#if team.id === currentTeam?.id}
                            <Check class="ml-auto size-4" />
                        {/if}
                    </DropdownMenuItem>
                {/each}
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    class="cursor-pointer gap-2 p-2"
                    onclick={() => showCreateDialog = true}
                >
                    <Plus class="size-4" />
                    Create Team
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </SidebarMenuItem>
</SidebarMenu>

<Dialog bind:open={showCreateDialog}>
    <DialogContent class="sm:max-w-md">
        <div class="flex flex-col space-y-1.5 text-center sm:text-left">
            <DialogTitle>Create Team</DialogTitle>
            <DialogDescription>
                Create a new team to collaborate with others.
            </DialogDescription>
        </div>
        <form onsubmit={handleCreateTeam} class="space-y-4">
            <div class="space-y-2">
                <Label for="team-name">Team name</Label>
                <Input
                    id="team-name"
                    bind:value={$createTeamForm.name}
                    placeholder="My Team"
                    autofocus
                />
                {#if $createTeamForm.errors.name}
                    <p class="text-sm text-destructive">
                        {$createTeamForm.errors.name}
                    </p>
                {/if}
            </div>
            <DialogFooter>
                <button
                    type="button"
                    class="inline-flex h-9 items-center justify-center rounded-sm border border-input bg-background px-4 text-sm font-medium hover:bg-accent hover:text-accent-foreground"
                    onclick={() => showCreateDialog = false}
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="inline-flex h-9 items-center justify-center rounded-sm bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    disabled={$createTeamForm.processing || !$createTeamForm.name.trim()}
                >
                    {$createTeamForm.processing ? 'Creating...' : 'Create'}
                </button>
            </DialogFooter>
        </form>
    </DialogContent>
</Dialog>
