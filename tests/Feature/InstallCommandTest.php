<?php

use Coollabsio\LaravelSaas\Console\InstallCommand;

it('has the correct managed stubs for vue framework', function () {
    $command = new InstallCommand;

    $stubs = (new ReflectionMethod($command, 'managedStubs'))->invoke($command, 'vue');

    expect($stubs)->toHaveCount(6);

    foreach ($stubs as $source => $target) {
        expect($source)->toEndWith('.vue');
        expect($target)->toEndWith('.vue');
    }
});

it('has the correct managed stubs for svelte framework', function () {
    $command = new InstallCommand;

    $stubs = (new ReflectionMethod($command, 'managedStubs'))->invoke($command, 'svelte');

    expect($stubs)->toHaveCount(6);

    foreach ($stubs as $source => $target) {
        expect($source)->toEndWith('.svelte');
        expect($target)->toEndWith('.svelte');
        expect($source)->toContain('/stubs/svelte/');
    }
});

it('has matching stub files for vue and svelte', function () {
    $command = new InstallCommand;
    $method = new ReflectionMethod($command, 'managedStubs');

    $vueStubs = $method->invoke($command, 'vue');
    $svelteStubs = $method->invoke($command, 'svelte');

    expect($vueStubs)->toHaveCount(count($svelteStubs));

    foreach ($vueStubs as $source => $target) {
        expect(file_exists($source))->toBeTrue("Vue stub missing: {$source}");
    }

    foreach ($svelteStubs as $source => $target) {
        expect(file_exists($source))->toBeTrue("Svelte stub missing: {$source}");
    }
});

it('defaults frontend config to vue', function () {
    expect(config('saas.frontend'))->toBe('vue');
});

it('reads frontend config for update flow', function () {
    config(['saas.frontend' => 'svelte']);

    $command = new InstallCommand;
    $method = new ReflectionMethod($command, 'frontend');

    expect($method->invoke($command))->toBe('svelte');
});

it('builds agent section with vue references by default', function () {
    $command = new InstallCommand;
    $method = new ReflectionMethod($command, 'buildAgentSection');

    $section = $method->invoke($command);

    expect($section)
        ->toContain('Frontend framework: Vue')
        ->toContain('Team.vue')
        ->toContain('Billing.vue')
        ->toContain('Managed Vue stubs');
});

it('builds agent section with svelte references when configured', function () {
    config(['saas.frontend' => 'svelte']);

    $command = new InstallCommand;
    $method = new ReflectionMethod($command, 'buildAgentSection');

    $section = $method->invoke($command);

    expect($section)
        ->toContain('Frontend framework: Svelte')
        ->toContain('Team.svelte')
        ->toContain('Billing.svelte')
        ->toContain('Managed Svelte stubs');
});
