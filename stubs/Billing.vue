<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { index, checkout, portal } from '@/actions/Coollabsio/LaravelSaas/Http/Controllers/BillingController';
import { type BreadcrumbItem } from '@/types';

type AvailablePlan = {
    value: string;
    label: string;
};

type PriceEntry = {
    amount: number;
    formatted: string;
};

type PlanPrices = {
    monthly: PriceEntry | null;
    yearly: PriceEntry | null;
    yearlySavingsPercent: number | null;
};

type Props = {
    plan: string;
    planLabel: string;
    subscribed: boolean;
    billingMode: 'tiered' | 'dynamic';
    dynamicQuantity: number | null;
    availablePlans: AvailablePlan[];
    prices: Record<string, PlanPrices> | null;
};

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Billing',
        href: index().url,
    },
];

const interval = ref<'monthly' | 'yearly'>('monthly');

const maxYearlySavings = computed(() => {
    if (!props.prices) return null;
    const percents = Object.values(props.prices)
        .map((p) => p.yearlySavingsPercent)
        .filter((v): v is number => v !== null && v > 0);
    return percents.length ? Math.max(...percents) : null;
});

function upgradeTo(plan: string) {
    const form = useForm({ plan, interval: interval.value });
    form.post(checkout.url());
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Billing" />

        <h1 class="sr-only">Billing</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Billing"
                    description="Manage your team's subscription plan"
                />

                <!-- Dynamic billing mode -->
                <template v-if="billingMode === 'dynamic'">
                    <div class="rounded-lg border p-4">
                        <p class="text-sm text-muted-foreground">Subscription status</p>
                        <p class="text-lg font-semibold">
                            {{ subscribed ? 'Active' : 'No active subscription' }}
                        </p>
                        <p v-if="dynamicQuantity !== null && subscribed" class="mt-1 text-sm text-muted-foreground">
                            Current quantity: {{ dynamicQuantity }}
                        </p>
                    </div>

                    <div v-if="subscribed">
                        <Button as="a" :href="portal.url()" variant="outline">
                            Manage Subscription
                        </Button>
                    </div>
                    <div v-else>
                        <Button @click="upgradeTo('dynamic')">
                            Subscribe
                        </Button>
                    </div>
                </template>

                <!-- Tiered billing mode -->
                <template v-else>
                    <div class="rounded-lg border p-4">
                        <p class="text-sm text-muted-foreground">Current plan</p>
                        <p class="text-lg font-semibold">{{ planLabel }}</p>
                    </div>

                    <div v-if="!subscribed">
                        <!-- Interval toggle -->
                        <div class="mb-4 inline-flex items-center rounded-lg border p-1">
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="interval === 'monthly' ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="interval = 'monthly'"
                            >
                                Monthly
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="interval === 'yearly' ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="interval = 'yearly'"
                            >
                                Yearly
                                <span
                                    v-if="maxYearlySavings"
                                    class="ml-1.5 inline-flex items-center rounded-full px-1.5 py-0.5 text-xs font-semibold"
                                    :class="interval === 'yearly' ? 'bg-primary-foreground/20 text-primary-foreground' : 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'"
                                >
                                    -{{ maxYearlySavings }}%
                                </span>
                            </button>
                        </div>

                        <div class="flex flex-col gap-4 sm:flex-row">
                            <div v-for="availablePlan in availablePlans" :key="availablePlan.value" class="flex-1 rounded-lg border p-4">
                                <h3 class="font-semibold">{{ availablePlan.label }}</h3>
                                <p v-if="prices?.[availablePlan.value]?.[interval]" class="mt-1 text-2xl font-bold">
                                    {{ prices[availablePlan.value][interval]!.formatted }}<span class="text-sm font-normal text-muted-foreground">/{{ interval === 'monthly' ? 'mo' : 'yr' }}</span>
                                </p>
                                <p
                                    v-if="interval === 'yearly' && prices?.[availablePlan.value]?.yearlySavingsPercent"
                                    class="mt-1 text-sm font-medium text-green-600 dark:text-green-400"
                                >
                                    Save {{ prices[availablePlan.value].yearlySavingsPercent }}% vs monthly
                                </p>
                                <Button class="mt-4" @click="upgradeTo(availablePlan.value)">
                                    Upgrade to {{ availablePlan.label }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div v-else>
                        <Button as="a" :href="portal.url()" variant="outline">
                            Manage Subscription
                        </Button>
                    </div>
                </template>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
