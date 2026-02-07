<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    settings: {
        registration_enabled: boolean;
    };
};

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Instance settings',
        href: '/settings/instance',
    },
];

const form = useForm({
    registration_enabled: props.settings.registration_enabled,
});

const submit = () => {
    form.patch('/settings/instance');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Instance settings" />

        <h1 class="sr-only">Instance Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading variant="small" title="Registration" description="Control whether new users can register on this instance" />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="flex items-center gap-3">
                        <input
                            id="registration_enabled"
                            type="checkbox"
                            v-model="form.registration_enabled"
                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <Label for="registration_enabled">Registration enabled</Label>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
