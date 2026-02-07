<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import TeamInvitationController from '@/actions/App/Http/Controllers/TeamInvitationController';
import type { TeamInvitation } from '@/types';

type Props = {
    invitation: TeamInvitation;
};

const props = defineProps<Props>();

const page = usePage();
const isAuthenticated = !!page.props.auth?.user;
</script>

<template>
    <Head title="Team Invitation" />

    <div class="flex min-h-screen items-center justify-center">
        <div class="w-full max-w-md space-y-6 rounded-lg border p-8">
            <div class="space-y-2 text-center">
                <h1 class="text-2xl font-semibold tracking-tight">
                    Team Invitation
                </h1>
                <p class="text-sm text-muted-foreground">
                    You've been invited to join
                    <strong>{{ invitation.team.name }}</strong>
                    as a <strong class="capitalize">{{ invitation.role }}</strong
                    >.
                </p>
            </div>

            <div v-if="isAuthenticated" class="space-y-4">
                <Form
                    v-bind="
                        TeamInvitationController.process.form({
                            token: invitation.token,
                        })
                    "
                    v-slot="{ processing }"
                >
                    <Button class="w-full" :disabled="processing">
                        Accept Invitation
                    </Button>
                </Form>
            </div>

            <div v-else class="space-y-4 text-center">
                <p class="text-sm text-muted-foreground">
                    Please log in or create an account to accept this
                    invitation.
                </p>
                <div class="flex gap-2">
                    <Button as-child class="flex-1" variant="outline">
                        <a href="/login">Log in</a>
                    </Button>
                    <Button as-child class="flex-1">
                        <a href="/register">Register</a>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
