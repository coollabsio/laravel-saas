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

it('updates with vue framework by default', function () {
    $this->artisan('saas:install --update')
        ->expectsOutputToContain('framework: vue');
});

it('updates with svelte framework via --svelte flag', function () {
    $this->artisan('saas:install --update --svelte')
        ->expectsOutputToContain('framework: svelte');
});

it('updates with vue framework via --vue flag', function () {
    $this->artisan('saas:install --update --vue')
        ->expectsOutputToContain('framework: vue');
});

it('uses config value when no flag is provided', function () {
    config(['saas.frontend' => 'svelte']);

    $this->artisan('saas:install --update')
        ->expectsOutputToContain('framework: svelte');
});

it('flag overrides config value', function () {
    config(['saas.frontend' => 'svelte']);

    $this->artisan('saas:install --update --vue')
        ->expectsOutputToContain('framework: vue');
});
