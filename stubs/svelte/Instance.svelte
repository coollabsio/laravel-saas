<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import Heading from '@/components/Heading.svelte';
    import { Label } from '@/components/ui/label';
    import NativeCheckbox from '@/components/NativeCheckbox.svelte';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import SettingsLayout from '@/layouts/settings/Layout.svelte';
    import type { BreadcrumbItem } from '@/types';

    interface Props {
        settings: {
            registration_enabled: boolean;
        };
    }

    let { settings }: Props = $props();

    const breadcrumbItems: BreadcrumbItem[] = [
        {
            title: 'Instance settings',
            href: '/settings/instance',
        },
    ];

    let registrationEnabled = $state(settings.registration_enabled);
    let recentlySuccessful = $state(false);

    function submit() {
        router.patch('/settings/instance', { registration_enabled: registrationEnabled }, {
            preserveScroll: true,
            onSuccess: () => {
                recentlySuccessful = true;
                setTimeout(() => recentlySuccessful = false, 2000);
            },
        });
    }
</script>

<svelte:head><title>Instance settings</title></svelte:head>

<AppLayout breadcrumbs={breadcrumbItems}>
    <h1 class="sr-only">Instance Settings</h1>

    <SettingsLayout>
        <div class="flex flex-col space-y-6">
            <Heading variant="small" title="Registration" description="Control whether new users can register on this instance" />

            <div class="flex items-center gap-3">
                <Label for="registration_enabled">Registration enabled</Label>
                <NativeCheckbox id="registration_enabled" bind:checked={registrationEnabled} onchange={submit} />
            </div>

            {#if recentlySuccessful}
                <p class="text-sm text-neutral-600">Saved.</p>
            {/if}
        </div>
    </SettingsLayout>
</AppLayout>
