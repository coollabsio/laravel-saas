<?php

namespace Coollabsio\LaravelSaas\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'saas:install';

    protected $description = 'Install the Laravel SaaS package';

    public function handle(): void
    {
        $this->info('Installing Laravel SaaS...');

        $this->call('vendor:publish', ['--tag' => 'saas-config']);

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
        $this->line('  5. Publish Vue stubs: <comment>php artisan vendor:publish --tag=saas-vue</comment>');
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
