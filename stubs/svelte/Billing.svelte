<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import SettingsLayout from '@/layouts/settings/Layout.svelte';
    import { index, checkout, portal } from '@/actions/Coollabsio/LaravelSaas/Http/Controllers/BillingController';
    import type { BreadcrumbItem } from '@/types';

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

    interface Props {
        plan: string;
        planLabel: string;
        subscribed: boolean;
        billingMode: 'tiered' | 'dynamic';
        dynamicQuantity: number | null;
        availablePlans: AvailablePlan[];
        prices: Record<string, PlanPrices> | null;
    }

    let { plan, planLabel, subscribed, billingMode, dynamicQuantity, availablePlans, prices }: Props = $props();

    const breadcrumbItems: BreadcrumbItem[] = [
        {
            title: 'Billing',
            href: index().url,
        },
    ];

    let interval = $state<'monthly' | 'yearly'>('monthly');

    const maxYearlySavings = $derived.by(() => {
        if (!prices) return null;
        const percents = Object.values(prices)
            .map((p) => p.yearlySavingsPercent)
            .filter((v): v is number => v !== null && v > 0);
        return percents.length ? Math.max(...percents) : null;
    });

    function upgradeTo(selectedPlan: string) {
        const form = useForm({ plan: selectedPlan, interval });
        $form.post(checkout.url());
    }
</script>

<AppLayout breadcrumbs={breadcrumbItems}>
    <svelte:head><title>Billing</title></svelte:head>

    <h1 class="sr-only">Billing</h1>

    <SettingsLayout>
        <div class="flex flex-col space-y-6">
            <Heading
                variant="small"
                title="Billing"
                description="Manage your team's subscription plan"
            />

            {#if billingMode === 'dynamic'}
                <!-- Dynamic billing mode -->
                <div class="rounded-lg border p-4">
                    <p class="text-sm text-muted-foreground">Subscription status</p>
                    <p class="text-lg font-semibold">
                        {subscribed ? 'Active' : 'No active subscription'}
                    </p>
                    {#if dynamicQuantity !== null && subscribed}
                        <p class="mt-1 text-sm text-muted-foreground">
                            Current quantity: {dynamicQuantity}
                        </p>
                    {/if}
                </div>

                {#if subscribed}
                    <div>
                        <Button href={portal.url()} variant="outline">
                            Manage Subscription
                        </Button>
                    </div>
                {:else}
                    <div>
                        <Button onclick={() => upgradeTo('dynamic')}>
                            Subscribe
                        </Button>
                    </div>
                {/if}
            {:else}
                <!-- Tiered billing mode -->
                <div class="rounded-lg border p-4">
                    <p class="text-sm text-muted-foreground">Current plan</p>
                    <p class="text-lg font-semibold">{planLabel}</p>
                </div>

                {#if !subscribed}
                    <!-- Interval toggle -->
                    <div class="mb-4 inline-flex items-center rounded-lg border p-1">
                        <button
                            type="button"
                            class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors {interval === 'monthly' ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'}"
                            onclick={() => interval = 'monthly'}
                        >
                            Monthly
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors {interval === 'yearly' ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'}"
                            onclick={() => interval = 'yearly'}
                        >
                            Yearly
                            {#if maxYearlySavings}
                                <span
                                    class="ml-1.5 inline-flex items-center rounded-full px-1.5 py-0.5 text-xs font-semibold {interval === 'yearly' ? 'bg-primary-foreground/20 text-primary-foreground' : 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'}"
                                >
                                    -{maxYearlySavings}%
                                </span>
                            {/if}
                        </button>
                    </div>

                    <div class="flex flex-col gap-4 sm:flex-row">
                        {#each availablePlans as availablePlan (availablePlan.value)}
                            <div class="flex-1 rounded-lg border p-4">
                                <h3 class="font-semibold">{availablePlan.label}</h3>
                                {#if prices?.[availablePlan.value]?.[interval]}
                                    <p class="mt-1 text-2xl font-bold">
                                        {prices[availablePlan.value][interval]!.formatted}<span class="text-sm font-normal text-muted-foreground">/{interval === 'monthly' ? 'mo' : 'yr'}</span>
                                    </p>
                                {/if}
                                {#if interval === 'yearly' && prices?.[availablePlan.value]?.yearlySavingsPercent}
                                    <p class="mt-1 text-sm font-medium text-green-600 dark:text-green-400">
                                        Save {prices[availablePlan.value].yearlySavingsPercent}% vs monthly
                                    </p>
                                {/if}
                                <Button class="mt-4" onclick={() => upgradeTo(availablePlan.value)}>
                                    Upgrade to {availablePlan.label}
                                </Button>
                            </div>
                        {/each}
                    </div>
                {:else}
                    <div>
                        <Button href={portal.url()} variant="outline">
                            Manage Subscription
                        </Button>
                    </div>
                {/if}
            {/if}
        </div>
    </SettingsLayout>
</AppLayout>
