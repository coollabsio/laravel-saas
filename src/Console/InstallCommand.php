<?php

namespace Coollabsio\LaravelSaas\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'saas:install {--update : Update existing installation with new/changed stubs}';

    protected $description = 'Install the Laravel SaaS package';

    public function handle(): void
    {
        if ($this->option('update')) {
            $this->handleUpdate();

            return;
        }

        $this->info('Installing Laravel SaaS...');

        $this->call('vendor:publish', ['--tag' => 'saas-config']);
        $this->call('vendor:publish', ['--tag' => 'saas-vue']);
        $this->call('vendor:publish', ['--tag' => 'saas-routes']);

        $this->registerTestSuite();
        $this->registerPestDirectory();

        $this->newLine();
        $this->info('Laravel SaaS installed successfully.');
        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Add <comment>use HasTeams</comment> to your User model');
        $this->line('  2. Add <comment>use CreatesPersonalTeam</comment> to your CreateNewUser action');
        $this->line('  3. Add <comment>ShareSaasProps::class</comment> to web middleware in bootstrap/app.php');
        $this->line('  4. Run <comment>php artisan migrate</comment>');
    }

    protected function handleUpdate(): void
    {
        $this->info('Updating Laravel SaaS stubs...');

        $this->publishIfMissing('saas-vue', $this->vueStubs());
        $this->publishIfMissing('saas-routes', $this->routeStubs());
        $this->forcePublish($this->managedStubs());

        $this->call('vendor:publish', ['--tag' => 'saas-config', '--force' => true]);

        $this->newLine();
        $this->info('Update complete. Run `php artisan migrate` to apply any new migrations.');
    }

    protected function forcePublish(array $files): void
    {
        foreach ($files as $source => $target) {
            $dir = dirname($target);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            copy($source, $target);
            $this->line("Updated: {$target}");
        }
    }

    protected function managedStubs(): array
    {
        $base = dirname(__DIR__, 2).'/stubs';

        return [
            $base.'/Instance.vue' => resource_path('js/pages/settings/Instance.vue'),
            $base.'/TeamSwitcher.vue' => resource_path('js/components/TeamSwitcher.vue'),
            $base.'/components/NativeCheckbox.vue' => resource_path('js/components/NativeCheckbox.vue'),
        ];
    }

    protected function publishIfMissing(string $tag, array $files): void
    {
        $missing = array_filter($files, fn (string $path) => ! file_exists($path));

        if (empty($missing)) {
            $this->line("No new files to publish for [{$tag}].");

            return;
        }

        foreach ($missing as $source => $target) {
            $dir = dirname($target);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            copy($source, $target);
            $this->line("Published: {$target}");
        }
    }

    protected function vueStubs(): array
    {
        $base = dirname(__DIR__, 2).'/stubs';

        return [
            $base.'/Team.vue' => resource_path('js/pages/settings/Team.vue'),
            $base.'/Billing.vue' => resource_path('js/pages/settings/Billing.vue'),
            $base.'/TeamInvitation.vue' => resource_path('js/pages/TeamInvitation.vue'),
            $base.'/components/NativeCheckbox.vue' => resource_path('js/components/NativeCheckbox.vue'),
        ];
    }

    protected function routeStubs(): array
    {
        $base = dirname(__DIR__, 2).'/routes';

        return [
            $base.'/teams.php' => base_path('routes/saas-teams.php'),
            $base.'/billing.php' => base_path('routes/saas-billing.php'),
            $base.'/instance.php' => base_path('routes/saas-instance.php'),
        ];
    }

    protected function registerTestSuite(): void
    {
        $phpunitPath = base_path('phpunit.xml');

        if (! file_exists($phpunitPath)) {
            $this->warn('phpunit.xml not found, skipping test suite registration.');

            return;
        }

        $contents = file_get_contents($phpunitPath);
        $needle = 'vendor/coollabsio/laravel-saas/tests/Feature';

        if (str_contains($contents, $needle)) {
            return;
        }

        $replacement = "<directory>tests/Feature</directory>\n            <directory>{$needle}</directory>";
        $contents = str_replace('<directory>tests/Feature</directory>', $replacement, $contents);

        file_put_contents($phpunitPath, $contents);
        $this->info('Registered package test suite in phpunit.xml.');
    }

    protected function registerPestDirectory(): void
    {
        $pestPath = base_path('tests/Pest.php');

        if (! file_exists($pestPath)) {
            $this->warn('tests/Pest.php not found, skipping Pest configuration.');

            return;
        }

        $contents = file_get_contents($pestPath);
        $needle = '../vendor/coollabsio/laravel-saas/tests/Feature';

        if (str_contains($contents, $needle)) {
            return;
        }

        $contents = preg_replace(
            "/->in\('Feature'\)/",
            "->in('Feature', '{$needle}')",
            $contents,
        );

        file_put_contents($pestPath, $contents);
        $this->info('Registered package test directory in tests/Pest.php.');
    }
}
