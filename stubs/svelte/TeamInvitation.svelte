<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import { Button } from '@/components/ui/button';
    import TeamInvitationController from '@/actions/Coollabsio/LaravelSaas/Http/Controllers/TeamInvitationController';
    import type { TeamInvitation } from '@/types';

    interface Props {
        invitation: TeamInvitation;
    }

    let { invitation }: Props = $props();

    const isAuthenticated = $derived(!!$page.props.auth?.user);
</script>

<svelte:head><title>Team Invitation</title></svelte:head>

<div class="flex min-h-screen items-center justify-center">
    <div class="w-full max-w-md space-y-6 rounded-lg border p-8">
        <div class="space-y-2 text-center">
            <h1 class="text-2xl font-semibold tracking-tight">
                Team Invitation
            </h1>
            <p class="text-sm text-muted-foreground">
                You've been invited to join
                <strong>{invitation.team.name}</strong>
                as a <strong class="capitalize">{invitation.role}</strong>.
            </p>
        </div>

        {#if isAuthenticated}
            <div class="space-y-4">
                <Form
                    {...TeamInvitationController.process.form({ token: invitation.token })}
                >
                    {#snippet children({ processing })}
                        <Button class="w-full" disabled={processing}>
                            Accept Invitation
                        </Button>
                    {/snippet}
                </Form>
            </div>
        {:else}
            <div class="space-y-4 text-center">
                <p class="text-sm text-muted-foreground">
                    Please log in or create an account to accept this invitation.
                </p>
                <div class="flex gap-2">
                    <Button href="/login" variant="outline" class="flex-1">Log in</Button>
                    <Button href="/register" class="flex-1">Register</Button>
                </div>
            </div>
        {/if}
    </div>
</div>
