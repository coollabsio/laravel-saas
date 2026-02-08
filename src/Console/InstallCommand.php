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

        $this->configureModels();
        $this->publishPlanEnum();
        $this->publishAiDocs();
        $this->injectAgentSections();
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
        $this->configureModels();
        $this->publishPlanEnum();
        $this->publishAiDocs();
        $this->injectAgentSections();

        $this->registerTestSuite();
        $this->registerPestDirectory();

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
            $base.'/Team.vue' => resource_path('js/pages/settings/Team.vue'),
            $base.'/Billing.vue' => resource_path('js/pages/settings/Billing.vue'),
            $base.'/Instance.vue' => resource_path('js/pages/settings/Instance.vue'),
            $base.'/TeamInvitation.vue' => resource_path('js/pages/TeamInvitation.vue'),
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

        return [];
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

    /**
     * @return array<string, array{package_class: string, app_path: string}>
     */
    protected function modelStubs(): array
    {
        return [
            'Team' => [
                'package_class' => \Coollabsio\LaravelSaas\Models\Team::class,
                'app_path' => app_path('Models/Team.php'),
            ],
            'TeamInvitation' => [
                'package_class' => \Coollabsio\LaravelSaas\Models\TeamInvitation::class,
                'app_path' => app_path('Models/TeamInvitation.php'),
            ],
            'InstanceSettings' => [
                'package_class' => \Coollabsio\LaravelSaas\Models\InstanceSettings::class,
                'app_path' => app_path('Models/InstanceSettings.php'),
            ],
        ];
    }

    protected function configureModels(): void
    {
        foreach ($this->modelStubs() as $name => $stub) {
            $path = $stub['app_path'];
            $packageClass = $stub['package_class'];

            if (! file_exists($path)) {
                $this->createModelStub($name, $packageClass, $path);

                continue;
            }

            $contents = file_get_contents($path);

            if (str_contains($contents, $packageClass)) {
                $this->line("{$name} model already extends package model.");

                continue;
            }

            if (str_contains($contents, "extends Model")) {
                $contents = str_replace(
                    "use Illuminate\\Database\\Eloquent\\Model;\n",
                    "use {$packageClass} as Base{$name};\n",
                    $contents,
                );
                $contents = str_replace('extends Model', "extends Base{$name}", $contents);
                file_put_contents($path, $contents);
                $this->info("Updated {$name} model to extend package model.");
            } else {
                $this->warn("{$path} exists but does not extend Model. Please extend {$packageClass} manually.");
            }
        }
    }

    protected function createModelStub(string $name, string $packageClass, string $path): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $stub = <<<PHP
        <?php

        namespace App\Models;

        use {$packageClass} as Base{$name};

        class {$name} extends Base{$name}
        {
            //
        }
        PHP;

        file_put_contents($path, $this->unindentStub($stub));
        $this->info("Created {$path}");
    }

    protected function unindentStub(string $stub): string
    {
        return preg_replace('/^        /m', '', $stub);
    }

    protected function publishPlanEnum(): void
    {
        $source = dirname(__DIR__, 2).'/stubs/Plan.php';
        $target = app_path('Enums/Plan.php');

        if (file_exists($target)) {
            $this->line('Plan enum already exists at app/Enums/Plan.php.');

            return;
        }

        $dir = dirname($target);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        copy($source, $target);
        $this->info("Published Plan enum to {$target}");
    }

    protected function publishAiDocs(): void
    {
        $source = dirname(__DIR__, 2).'/.ai';
        $target = base_path('.ai/laravel-saas');

        if (! is_dir($source)) {
            return;
        }

        if (! is_dir($target)) {
            mkdir($target, 0755, true);
        }

        foreach (glob($source.'/*.md') as $file) {
            $dest = $target.'/'.basename($file);
            copy($file, $dest);
            $this->line("Updated: {$dest}");
        }
    }

    protected function injectAgentSections(): void
    {
        $section = $this->buildAgentSection();

        foreach (['CLAUDE.md', 'AGENTS.md'] as $filename) {
            $path = base_path($filename);

            if (! file_exists($path)) {
                continue;
            }

            $contents = file_get_contents($path);
            $startTag = '<laravel-saas>';
            $endTag = '</laravel-saas>';

            if (str_contains($contents, $startTag)) {
                $contents = preg_replace(
                    '/'.preg_quote($startTag, '/').'.*?'.preg_quote($endTag, '/').'/s',
                    $startTag."\n".$section."\n".$endTag,
                    $contents,
                );
            } else {
                $contents = rtrim($contents)."\n\n".$startTag."\n".$section."\n".$endTag."\n";
            }

            file_put_contents($path, $contents);
            $this->info("Updated {$filename} with laravel-saas section.");
        }
    }

    protected function buildAgentSection(): string
    {
        return <<<'MD'
## Laravel SaaS Package

This app uses `coollabsio/laravel-saas` for teams, billing, and self-hosted mode.

- Package docs: `.ai/laravel-saas/` (BILLING.md, EMAILS.md, PLAN_GATING.md, SELF_HOSTED.md)
- Config: `config/saas.php`
- Managed Vue stubs (do not edit directly — overwritten on `saas:install --update`):
  - `resources/js/pages/settings/Team.vue`
  - `resources/js/pages/settings/Billing.vue`
  - `resources/js/pages/settings/Instance.vue`
  - `resources/js/pages/TeamInvitation.vue`
  - `resources/js/components/TeamSwitcher.vue`
  - `resources/js/components/NativeCheckbox.vue`
- User model must use `Coollabsio\LaravelSaas\Concerns\HasTeams` trait
- Registration action must use `Coollabsio\LaravelSaas\Concerns\CreatesPersonalTeam` trait
- `ShareSaasProps` middleware shares `currentTeam`, `teams`, `billing`, and `instance` Inertia props
- Self-hosted mode: `SELF_HOSTED=true` disables billing, first user becomes root
- Root users bypass `plan` and `subscribed` middleware
MD;
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
