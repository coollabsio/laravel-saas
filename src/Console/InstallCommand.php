<?php

namespace Coollabsio\LaravelSaas\Console;

use Illuminate\Console\Command;

use function Laravel\Prompts\select;

class InstallCommand extends Command
{
    protected $signature = 'saas:install
        {--update : Update existing installation with new/changed stubs}
        {--vue : Use Vue 3 as the frontend framework}
        {--svelte : Use Svelte 5 as the frontend framework}
        {--design : Publish the design system for AI agents}
        {--only-design-system : Only publish/update the design system file}';

    protected $description = 'Install the Laravel SaaS package';

    public function handle(): void
    {
        if ($this->option('only-design-system')) {
            $this->publishDesignSystem();
            $this->newLine();
            $this->info('Design system published successfully.');

            return;
        }

        if ($this->option('update')) {
            $this->handleUpdate();

            return;
        }

        $this->info('Installing Laravel SaaS...');

        $framework = $this->option('vue') || $this->option('svelte')
            ? $this->frontend()
            : select(
                label: 'Which frontend framework are you using?',
                options: [
                    'vue' => 'Vue 3 (Inertia + shadcn-vue)',
                    'svelte' => 'Svelte 5 (Inertia + shadcn-svelte)',
                ],
                default: 'vue',
            );

        $this->call('vendor:publish', ['--tag' => 'saas-config']);
        $this->setFrontendConfig($framework);
        $this->call('vendor:publish', ['--tag' => "saas-{$framework}"]);
        $this->call('vendor:publish', ['--tag' => 'saas-routes']);

        $this->configureModels();
        $this->patchUserModel();
        $this->patchCreateNewUser();
        $this->patchUserFactory();
        $this->patchFortifyConfig();
        $this->patchBootstrapMiddleware();
        $this->patchWebRoutes();
        $this->publishPlanEnum();
        $this->patchSidebar($framework);
        $this->patchSettingsLayout($framework);

        $this->publishAiDocs();

        if ($this->option('design')) {
            $this->publishDesignSystem();
        }

        $this->injectAgentSections();
        $this->registerTestSuite();
        $this->registerPestDirectory();
        $this->generateWayfinder();

        $this->newLine();
        $this->info('Laravel SaaS installed successfully.');
        $this->newLine();
        $this->line('Next step: Run <comment>php artisan migrate</comment>');
    }

    protected function handleUpdate(): void
    {
        $framework = $this->frontend();

        $this->info("Updating Laravel SaaS stubs (framework: {$framework})...");

        $this->publishIfMissing("saas-{$framework}", $this->frontendStubs($framework));
        $this->publishIfMissing('saas-routes', $this->routeStubs());
        $this->forcePublish($this->managedStubs($framework));
        $this->configureModels();
        $this->patchUserModel();
        $this->patchCreateNewUser();
        $this->patchUserFactory();
        $this->patchFortifyConfig();
        $this->patchBootstrapMiddleware();
        $this->patchWebRoutes();
        $this->publishPlanEnum();
        $this->patchSidebar($framework);
        $this->patchSettingsLayout($framework);

        $this->publishAiDocs();

        if ($this->option('design')) {
            $this->publishDesignSystem();
        }

        $this->injectAgentSections();

        $this->registerTestSuite();
        $this->registerPestDirectory();

        $this->call('vendor:publish', ['--tag' => 'saas-config', '--force' => true]);
        $this->generateWayfinder();

        $this->newLine();
        $this->info('Update complete. Run `php artisan migrate` to apply any new migrations.');
    }

    protected function frontend(): string
    {
        if ($this->option('svelte')) {
            return 'svelte';
        }

        if ($this->option('vue')) {
            return 'vue';
        }

        return config('saas.frontend', 'vue');
    }

    protected function setFrontendConfig(string $framework): void
    {
        $path = config_path('saas.php');

        if (! file_exists($path)) {
            return;
        }

        $contents = file_get_contents($path);

        $contents = preg_replace(
            "/'frontend'\s*=>\s*env\('SAAS_FRONTEND',\s*'[^']+'\)/",
            "'frontend' => env('SAAS_FRONTEND', '{$framework}')",
            $contents,
        );

        file_put_contents($path, $contents);
        $this->info("Frontend framework set to [{$framework}].");
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

    /**
     * @return array<string, string>
     */
    protected function managedStubs(string $framework = 'vue'): array
    {
        $base = dirname(__DIR__, 2).'/stubs';
        $ext = $framework === 'svelte' ? 'svelte' : 'vue';
        $dir = $framework === 'svelte' ? $base.'/svelte' : $base;

        return [
            $dir."/Team.{$ext}" => resource_path("js/pages/settings/Team.{$ext}"),
            $dir."/Billing.{$ext}" => resource_path("js/pages/settings/Billing.{$ext}"),
            $dir."/Instance.{$ext}" => resource_path("js/pages/settings/Instance.{$ext}"),
            $dir."/TeamInvitation.{$ext}" => resource_path("js/pages/TeamInvitation.{$ext}"),
            $dir."/TeamSwitcher.{$ext}" => resource_path("js/components/TeamSwitcher.{$ext}"),
            $dir."/components/NativeCheckbox.{$ext}" => resource_path("js/components/NativeCheckbox.{$ext}"),
            $dir."/Login.{$ext}" => resource_path("js/pages/auth/Login.{$ext}"),
            $dir."/Profile.{$ext}" => resource_path("js/pages/settings/Profile.{$ext}"),
            $dir."/components/DeleteUser.{$ext}" => resource_path("js/components/DeleteUser.{$ext}"),
            $dir."/components/UserMenuContent.{$ext}" => resource_path("js/components/UserMenuContent.{$ext}"),
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

    /**
     * @return array<string, string>
     */
    protected function frontendStubs(string $framework = 'vue'): array
    {
        return [];
    }

    protected function routeStubs(): array
    {
        $base = dirname(__DIR__, 2).'/routes';

        return [
            $base.'/teams.php' => base_path('routes/saas-teams.php'),
            $base.'/billing.php' => base_path('routes/saas-billing.php'),
            $base.'/instance.php' => base_path('routes/saas-instance.php'),
            $base.'/profile.php' => base_path('routes/saas-profile.php'),
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

    protected function generateWayfinder(): void
    {
        if (! class_exists(\Laravel\Wayfinder\WayfinderServiceProvider::class)) {
            return;
        }

        $this->call('wayfinder:generate');
        $this->info('Wayfinder routes generated.');
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
            if (basename($file) === 'DESIGN_SYSTEM.md') {
                continue;
            }

            $dest = $target.'/'.basename($file);
            copy($file, $dest);
            $this->line("Updated: {$dest}");
        }
    }

    protected function publishDesignSystem(): void
    {
        $source = dirname(__DIR__, 2).'/.ai/DESIGN_SYSTEM.md';

        if (! file_exists($source)) {
            $this->warn('Design system source file not found.');

            return;
        }

        $target = base_path('.ai/laravel-saas/DESIGN_SYSTEM.md');
        $dir = dirname($target);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        copy($source, $target);
        $this->info("Published design system to {$target}");

        $this->patchAppCss();
        $this->publishDesignComponents();
    }

    protected function patchAppCss(): void
    {
        $cssPath = resource_path('css/app.css');

        if (! file_exists($cssPath)) {
            $this->warn('resources/css/app.css not found, skipping CSS patch.');

            return;
        }

        $stub = dirname(__DIR__, 2).'/stubs/design-system.css';
        copy($stub, $cssPath);
        $this->info('Replaced resources/css/app.css with Coolify design system.');
    }

    protected function publishDesignComponents(): void
    {
        $framework = $this->frontend();
        $base = dirname(__DIR__, 2).'/stubs';

        // Publish Coolify-themed shadcn UI components
        $uiSource = $framework === 'svelte'
            ? $base.'/ui-svelte'
            : $base.'/ui';

        if (is_dir($uiSource)) {
            $this->copyDirectory($uiSource, resource_path('js/components/ui'));
            $this->info('Published Coolify-themed UI components.');
        }

        // Publish Coolify-themed non-UI components (Heading, AppSidebarHeader, etc.)
        $designSource = $framework === 'svelte'
            ? $base.'/design-svelte'
            : $base.'/design';

        if (! is_dir($designSource)) {
            return;
        }

        $componentsSource = $designSource.'/components';

        if (is_dir($componentsSource)) {
            foreach (scandir($componentsSource) as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $target = resource_path('js/components/'.$file);

                if (! file_exists($target)) {
                    continue;
                }

                copy($componentsSource.'/'.$file, $target);
                $this->line("Updated: resources/js/components/{$file}");
            }
        }

        $layoutsSource = $designSource.'/layouts';

        if (is_dir($layoutsSource)) {
            $this->copyDirectory($layoutsSource, resource_path('js/layouts'), true);
        }

        $this->info('Published Coolify-themed layout and component overrides.');
    }

    protected function copyDirectory(string $source, string $target, bool $onlyExisting = false): void
    {
        if (! is_dir($target)) {
            if ($onlyExisting) {
                return;
            }

            mkdir($target, 0755, true);
        }

        foreach (scandir($source) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $sourcePath = $source.'/'.$item;
            $targetPath = $target.'/'.$item;

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $targetPath, $onlyExisting);
            } elseif (! $onlyExisting || file_exists($targetPath)) {
                copy($sourcePath, $targetPath);
            }
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
        $framework = $this->frontend();
        $ext = $framework === 'svelte' ? 'svelte' : 'vue';
        $label = $framework === 'svelte' ? 'Svelte' : 'Vue';

        return <<<MD
## Laravel SaaS Package

This app uses `coollabsio/laravel-saas` for teams, billing, and self-hosted mode.

- Package docs: `.ai/laravel-saas/` (BILLING.md, EMAILS.md, PLAN_GATING.md, SELF_HOSTED.md)
- Config: `config/saas.php`
- Frontend framework: {$label}
- Managed {$label} stubs (do not edit directly — overwritten on `saas:install --update`):
  - `resources/js/pages/settings/Team.{$ext}`
  - `resources/js/pages/settings/Billing.{$ext}`
  - `resources/js/pages/settings/Instance.{$ext}`
  - `resources/js/pages/TeamInvitation.{$ext}`
  - `resources/js/components/TeamSwitcher.{$ext}`
  - `resources/js/components/NativeCheckbox.{$ext}`
  - `resources/js/pages/auth/Login.{$ext}`
  - `resources/js/pages/settings/Profile.{$ext}`
  - `resources/js/components/DeleteUser.{$ext}`
  - `resources/js/components/UserMenuContent.{$ext}`
- User model must use `Coollabsio\LaravelSaas\Concerns\HasTeams` trait
- Registration action must use `Coollabsio\LaravelSaas\Concerns\CreatesPersonalTeam` trait
- `ShareSaasProps` middleware shares `currentTeam`, `teams`, `billing`, `instance`, and `dev` (local env only) Inertia props
- Self-hosted mode: `SELF_HOSTED=true` disables billing, first user becomes root
- Root users bypass `plan` and `subscribed` middleware
MD;
    }

    protected function patchUserModel(): void
    {
        $path = app_path('Models/User.php');

        if (! file_exists($path)) {
            $this->warn('User model not found, skipping HasTeams patch.');

            return;
        }

        $contents = file_get_contents($path);

        if (str_contains($contents, 'HasTeams')) {
            $this->line('User model already uses HasTeams.');

            return;
        }

        // Add import before the class declaration
        $contents = preg_replace(
            '/(use [^;]+;\n)((\s*\n)*class\s)/s',
            "$1use Coollabsio\\LaravelSaas\\Concerns\\HasTeams;\n$2",
            $contents,
            1,
        );

        // Add trait to existing use statement (indented = inside class body)
        if (preg_match('/^(    use\s+)(.+?)(;)\s*$/m', $contents, $m)) {
            $contents = str_replace(
                $m[0],
                $m[1].'HasTeams, '.$m[2].$m[3],
                $contents,
            );
        }

        file_put_contents($path, $contents);
        $this->info('Added HasTeams trait to User model.');
    }

    protected function patchCreateNewUser(): void
    {
        $path = app_path('Actions/Fortify/CreateNewUser.php');

        if (! file_exists($path)) {
            $this->warn('CreateNewUser action not found, skipping CreatesPersonalTeam patch.');

            return;
        }

        $contents = file_get_contents($path);

        if (str_contains($contents, 'CreatesPersonalTeam')) {
            $this->line('CreateNewUser already uses CreatesPersonalTeam.');

            return;
        }

        // Add import before the class declaration
        $contents = preg_replace(
            '/(use [^;]+;\n)((\s*\n)*class\s)/s',
            "$1use Coollabsio\\LaravelSaas\\Concerns\\CreatesPersonalTeam;\n$2",
            $contents,
            1,
        );

        // Add trait to existing use statement (indented = inside class body)
        if (preg_match('/^(    use\s+)(.+?)(;)\s*$/m', $contents, $m)) {
            $contents = str_replace(
                $m[0],
                $m[1].'CreatesPersonalTeam, '.$m[2].$m[3],
                $contents,
            );
        }

        // Replace "return User::create([...])" with "$user = ...; createPersonalTeam; return $user;"
        $contents = preg_replace(
            '/return\s+(User::create\(\[.*?\]\))\s*;/s',
            "\$user = $1;\n\n        \$this->createPersonalTeam(\$user);\n\n        return \$user;",
            $contents,
        );

        file_put_contents($path, $contents);
        $this->info('Added CreatesPersonalTeam trait and call to CreateNewUser action.');
    }

    protected function patchUserFactory(): void
    {
        $path = database_path('factories/UserFactory.php');

        if (! file_exists($path)) {
            $this->warn('UserFactory not found, skipping factory patch.');

            return;
        }

        $contents = file_get_contents($path);

        if (str_contains($contents, 'createPersonalTeam') || str_contains($contents, 'afterCreating')) {
            $this->line('UserFactory already has afterCreating hook.');

            return;
        }

        // Add configure() method before the last closing brace
        $configureMethod = <<<'PHP'

    /**
     * Configure the model factory to create a personal team.
     */
    public function configure(): static
    {
        return $this->afterCreating(function ($user) {
            $teamModel = \Coollabsio\LaravelSaas\Support\Billing::teamModel();
            $isRoot = config('saas.self_hosted') && $teamModel::count() === 0;

            $team = $teamModel::forceCreate([
                'name' => $user->name . "'s Team",
                'personal_team' => true,
                'owner_id' => $user->id,
                'is_root' => $isRoot,
            ]);

            $team->users()->attach($user, ['role' => 'owner']);
            $user->forceFill(['current_team_id' => $team->id])->save();
        });
    }
PHP;

        $contents = preg_replace('/\n}\s*$/', $configureMethod."\n}\n", $contents);

        file_put_contents($path, $contents);
        $this->info('Added afterCreating hook to UserFactory for personal team creation.');
    }

    protected function patchFortifyConfig(): void
    {
        $path = config_path('fortify.php');

        if (! file_exists($path)) {
            $this->warn('config/fortify.php not found, skipping home path patch.');

            return;
        }

        $contents = file_get_contents($path);

        $updated = preg_replace(
            "/'home'\s*=>\s*'[^']*'/",
            "'home' => '/'",
            $contents,
        );

        if ($updated === $contents) {
            $this->line('Fortify home config already set or not found.');

            return;
        }

        file_put_contents($path, $updated);
        $this->info("Updated Fortify home config to '/'.");
    }

    protected function patchBootstrapMiddleware(): void
    {
        $path = base_path('bootstrap/app.php');

        if (! file_exists($path)) {
            $this->warn('bootstrap/app.php not found, skipping middleware patch.');

            return;
        }

        $contents = file_get_contents($path);

        if (str_contains($contents, 'ShareSaasProps')) {
            $this->line('bootstrap/app.php already contains ShareSaasProps.');

            return;
        }

        // Add import at top of file after existing use statements
        if (preg_match_all('/^use [^;]+;\n/m', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            $lastUse = end($matches[0]);
            $insertPos = $lastUse[1] + strlen($lastUse[0]);
            $contents = substr($contents, 0, $insertPos)
                ."use Coollabsio\\LaravelSaas\\Http\\Middleware\\ShareSaasProps;\n"
                .substr($contents, $insertPos);
        }

        // Case 1: web(append: [...]) already exists — add to the array
        if (preg_match('/\$middleware->web\(\s*append:\s*\[/', $contents)) {
            $contents = preg_replace(
                '/(\$middleware->web\(\s*append:\s*\[)\n/',
                "$1\n            ShareSaasProps::class,\n",
                $contents,
            );
        }
        // Case 2: withMiddleware callback exists but no web(append:) — add the call
        elseif (preg_match('/->withMiddleware\(/', $contents)) {
            $contents = preg_replace(
                '/(->withMiddleware\(\s*function\s*\(\s*Middleware\s+\$middleware\s*\)\s*(?::\s*void\s*)?\{)\n/',
                "$1\n        \$middleware->web(append: [\n            ShareSaasProps::class,\n        ]);\n\n",
                $contents,
            );
        }

        file_put_contents($path, $contents);
        $this->info('Added ShareSaasProps middleware to bootstrap/app.php.');
    }

    protected function patchWebRoutes(): void
    {
        $path = base_path('routes/web.php');

        if (! file_exists($path)) {
            $this->warn('routes/web.php not found, skipping dashboard route patch.');

            return;
        }

        $contents = file_get_contents($path);

        // Change Route::get('dashboard', ...) or Route::inertia('dashboard', ...) to use '/' instead
        $updated = preg_replace(
            "/Route::(get|inertia)\(\s*'dashboard'/",
            "Route::$1('/'",
            $contents,
        );

        // Rename ->name('dashboard') to ->name('home') so Wayfinder generates a `home` export
        $updated = preg_replace(
            "/->name\(\s*'dashboard'\s*\)/",
            "->name('home')",
            $updated,
        );

        if ($updated === $contents) {
            $this->line('Dashboard route already patched or not found.');

            return;
        }

        file_put_contents($path, $updated);
        $this->info("Updated dashboard route to '/' with name 'home'.");
    }

    protected function patchSidebar(string $framework): void
    {
        $ext = $framework === 'svelte' ? 'svelte' : 'vue';
        $path = resource_path("js/components/AppSidebar.{$ext}");

        if (! file_exists($path)) {
            $this->warn("AppSidebar.{$ext} not found, skipping sidebar patch.");

            return;
        }

        $contents = file_get_contents($path);

        if (str_contains($contents, 'TeamSwitcher')) {
            $this->line('AppSidebar already contains TeamSwitcher.');

            return;
        }

        $importLine = $framework === 'svelte'
            ? "    import TeamSwitcher from '@/components/TeamSwitcher.svelte';"
            : "import TeamSwitcher from '@/components/TeamSwitcher.vue';";

        // Inject import after the last existing component import
        $lastImportPattern = $framework === 'svelte'
            ? "/(    import [^;]+from '@\/components\/[^']+';)/s"
            : "/(import [^;]+from '@\/components\/[^']+';)/s";

        if (preg_match_all($lastImportPattern, $contents, $matches, PREG_OFFSET_CAPTURE)) {
            $lastMatch = end($matches[0]);
            $insertPos = $lastMatch[1] + strlen($lastMatch[0]);
            $contents = substr($contents, 0, $insertPos)."\n".$importLine.substr($contents, $insertPos);
        }

        // Replace <SidebarHeader> block with TeamSwitcher
        $contents = preg_replace(
            '/<SidebarHeader>.*?<\/SidebarHeader>/s',
            "<SidebarHeader>\n        <TeamSwitcher />\n    </SidebarHeader>",
            $contents,
        );

        file_put_contents($path, $contents);
        $this->info("Patched AppSidebar.{$ext} with TeamSwitcher.");
    }

    protected function patchSettingsLayout(string $framework): void
    {
        $ext = $framework === 'svelte' ? 'svelte' : 'vue';
        $path = resource_path("js/layouts/settings/Layout.{$ext}");

        if (! file_exists($path)) {
            $this->warn("settings/Layout.{$ext} not found, skipping settings layout patch.");

            return;
        }

        $contents = file_get_contents($path);

        if (str_contains($contents, 'editTeam')) {
            $this->line('Settings layout already contains team nav items.');

            return;
        }

        if ($framework === 'svelte') {
            $contents = $this->patchSvelteSettingsLayout($contents);
        } else {
            $contents = $this->patchVueSettingsLayout($contents);
        }

        file_put_contents($path, $contents);
        $this->info("Patched settings/Layout.{$ext} with Team, Billing, and Instance nav items.");
    }

    protected function patchSvelteSettingsLayout(string $contents): string
    {
        // Add page import
        $contents = str_replace(
            "import { Link } from '@inertiajs/svelte';",
            "import { Link, page } from '@inertiajs/svelte';",
            $contents,
        );

        // Add route imports after the last existing route import
        $routeImports = <<<'IMPORTS'
    import { index as billingIndex } from '@/routes/billing';
    import { edit as editInstance } from '@/routes/instance-settings';
    import { edit as editTeam } from '@/routes/teams';
IMPORTS;

        $contents = preg_replace(
            "/(    import [^;]+from '@\/routes\/[^']+';)(?!.*    import [^;]+from '@\/routes\/)/s",
            "$1\n".$routeImports,
            $contents,
        );

        // Replace static array with $derived.by()
        $newNav = <<<'NAV'
    const sidebarNavItems: NavItem[] = $derived.by(() => {
        const items: NavItem[] = [
            {
                title: 'Profile',
                href: editProfile(),
            },
            {
                title: 'Team',
                href: editTeam(),
            },
            {
                title: 'Password',
                href: editPassword(),
            },
            {
                title: 'Two-factor auth',
                href: show(),
            },
            {
                title: 'Appearance',
                href: editAppearance(),
            },
        ];

        if ($page.props.billing?.enabled) {
            items.push({
                title: 'Billing',
                href: billingIndex(),
            });
        }

        if ($page.props.instance?.isRootUser) {
            items.push({
                title: 'Instance',
                href: editInstance(),
            });
        }

        return items;
    });
NAV;

        $contents = preg_replace(
            '/    const sidebarNavItems: NavItem\[\] = \[.*?\];/s',
            $newNav,
            $contents,
        );

        return $contents;
    }

    protected function patchVueSettingsLayout(string $contents): string
    {
        // Add usePage import
        if (! str_contains($contents, 'usePage')) {
            $contents = str_replace(
                "import { Link } from '@inertiajs/vue3';",
                "import { Link, usePage } from '@inertiajs/vue3';",
                $contents,
            );
        }

        // Add computed import if not present
        if (! str_contains($contents, 'computed')) {
            $contents = str_replace(
                "import { Link, usePage } from '@inertiajs/vue3';",
                "import { Link, usePage } from '@inertiajs/vue3';\nimport { computed } from 'vue';",
                $contents,
            );
        }

        // Add route imports after the last existing route import
        $routeImports = <<<'IMPORTS'
import { index as billingIndex } from '@/routes/billing';
import { edit as editInstance } from '@/routes/instance-settings';
IMPORTS;

        // Add editTeam import if not present
        if (! str_contains($contents, 'editTeam')) {
            $routeImports .= "\nimport { edit as editTeam } from '@/routes/teams';";
        }

        $contents = preg_replace(
            "/(import [^;]+from '@\/routes\/[^']+';)(?!.*import [^;]+from '@\/routes\/)/s",
            "$1\n".$routeImports,
            $contents,
        );

        // Add page const if not present
        if (! str_contains($contents, 'const page = usePage()')) {
            $contents = str_replace(
                "import { type NavItem } from '@/types';",
                "import { type NavItem } from '@/types';\n\nconst page = usePage();",
                $contents,
            );
        }

        // Replace static array with computed
        $newNav = <<<'NAV'
const sidebarNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Profile',
            href: editProfile(),
        },
        {
            title: 'Team',
            href: editTeam(),
        },
        {
            title: 'Password',
            href: editPassword(),
        },
        {
            title: 'Two-Factor Auth',
            href: show(),
        },
        {
            title: 'Appearance',
            href: editAppearance(),
        },
    ];

    if (page.props.billing?.enabled) {
        items.push({
            title: 'Billing',
            href: billingIndex(),
        });
    }

    if (page.props.instance?.isRootUser) {
        items.push({
            title: 'Instance',
            href: editInstance(),
        });
    }

    return items;
});
NAV;

        $contents = preg_replace(
            '/const sidebarNavItems:?\s*(?:NavItem\[\]|) = (?:computed<NavItem\[\]>\()?\[.*?\];?\s*(?:\);\s*)?/s',
            $newNav,
            $contents,
        );

        return $contents;
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
