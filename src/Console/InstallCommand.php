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
        $this->line('Next steps:');
        $this->line('  1. Add <comment>use HasTeams</comment> to your User model');
        $this->line('  2. Add <comment>use CreatesPersonalTeam</comment> to your CreateNewUser action');
        $this->line('  3. Add <comment>ShareSaasProps::class</comment> to web middleware in bootstrap/app.php');
        $this->line('  4. Run <comment>php artisan migrate</comment>');
    }

    protected function handleUpdate(): void
    {
        $framework = $this->frontend();

        $this->info("Updating Laravel SaaS stubs (framework: {$framework})...");

        $this->publishIfMissing("saas-{$framework}", $this->frontendStubs($framework));
        $this->publishIfMissing('saas-routes', $this->routeStubs());
        $this->forcePublish($this->managedStubs($framework));
        $this->configureModels();
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
    }

    protected function patchAppCss(): void
    {
        $cssPath = resource_path('css/app.css');

        if (! file_exists($cssPath)) {
            $this->warn('resources/css/app.css not found, skipping CSS patch.');

            return;
        }

        $stub = file_get_contents(dirname(__DIR__, 2).'/stubs/design-system.css');
        $contents = file_get_contents($cssPath);

        $contents = $this->injectThemeTokens($contents, $stub);
        $contents = $this->replaceRootAndDark($contents, $stub);
        $contents = $this->appendComponentStyles($contents, $stub);

        file_put_contents($cssPath, $contents);
        $this->info('Patched resources/css/app.css with Coolify design system.');
    }

    protected function injectThemeTokens(string $contents, string $stub): string
    {
        $startTag = '/* <coolify-design-system:theme-tokens>';
        $endTag = '/* </coolify-design-system:theme-tokens> */';

        $tokens = $this->extractBetween($stub, $startTag, $endTag);

        if ($tokens === null) {
            return $contents;
        }

        $block = $startTag."\n".$tokens."\n".$endTag;

        // Replace existing block or inject before closing } of @theme inline
        if (str_contains($contents, $startTag)) {
            return preg_replace(
                '/'.preg_quote($startTag, '/').'.*?'.preg_quote($endTag, '/').'/s',
                $block,
                $contents,
            );
        }

        // Inject before the closing } of @theme inline
        $pos = $this->findThemeInlineClose($contents);

        if ($pos === false) {
            $this->warn('Could not find @theme inline closing brace, skipping token injection.');

            return $contents;
        }

        return substr($contents, 0, $pos)."\n".$block."\n".substr($contents, $pos);
    }

    protected function replaceRootAndDark(string $contents, string $stub): string
    {
        $startTag = '/* <coolify-design-system:root>';
        $endTag = '/* </coolify-design-system:root> */';

        $rootDark = $this->extractBetween($stub, $startTag, $endTag);

        if ($rootDark === null) {
            return $contents;
        }

        $block = $startTag."\n".$rootDark."\n".$endTag;

        // Replace existing managed block
        if (str_contains($contents, $startTag)) {
            return preg_replace(
                '/'.preg_quote($startTag, '/').'.*?'.preg_quote($endTag, '/').'/s',
                $block,
                $contents,
            );
        }

        // Replace existing :root { ... } and .dark { ... } blocks
        $contents = preg_replace('/^:root\s*\{[^}]*\}\s*/ms', '', $contents);
        $contents = preg_replace('/^\.dark\s*\{[^}]*\}\s*/ms', '', $contents);

        // Find the @layer base block that contains border-border and insert before it
        if (preg_match('/@layer base\s*\{\s*\*.*?border-border/s', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            $pos = $matches[0][1];

            return substr($contents, 0, $pos)."\n".$block."\n\n".substr($contents, $pos);
        }

        // Fallback: append before end
        return $contents."\n".$block."\n";
    }

    protected function appendComponentStyles(string $contents, string $stub): string
    {
        $startTag = '/* <coolify-design-system:components>';
        $endTag = '/* </coolify-design-system:components> */';

        $components = $this->extractBetween($stub, $startTag, $endTag);

        if ($components === null) {
            return $contents;
        }

        $block = $startTag."\n".$components."\n".$endTag;

        // Replace existing block or append
        if (str_contains($contents, $startTag)) {
            return preg_replace(
                '/'.preg_quote($startTag, '/').'.*?'.preg_quote($endTag, '/').'/s',
                $block,
                $contents,
            );
        }

        return rtrim($contents)."\n\n".$block."\n";
    }

    protected function extractBetween(string $haystack, string $startTag, string $endTag): ?string
    {
        $startPos = strpos($haystack, $startTag);

        if ($startPos === false) {
            return null;
        }

        $contentStart = strpos($haystack, "\n", $startPos);

        if ($contentStart === false) {
            return null;
        }

        $endPos = strpos($haystack, $endTag, $contentStart);

        if ($endPos === false) {
            return null;
        }

        return rtrim(substr($haystack, $contentStart + 1, $endPos - $contentStart - 1));
    }

    /**
     * Find the position of the closing } of the @theme inline { ... } block.
     */
    protected function findThemeInlineClose(string $contents): int|false
    {
        $themePos = strpos($contents, '@theme inline');

        if ($themePos === false) {
            return false;
        }

        $braceStart = strpos($contents, '{', $themePos);

        if ($braceStart === false) {
            return false;
        }

        $depth = 1;
        $len = strlen($contents);

        for ($i = $braceStart + 1; $i < $len; $i++) {
            if ($contents[$i] === '{') {
                $depth++;
            } elseif ($contents[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return false;
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
- User model must use `Coollabsio\LaravelSaas\Concerns\HasTeams` trait
- Registration action must use `Coollabsio\LaravelSaas\Concerns\CreatesPersonalTeam` trait
- `ShareSaasProps` middleware shares `currentTeam`, `teams`, `billing`, and `instance` Inertia props
- Self-hosted mode: `SELF_HOSTED=true` disables billing, first user becomes root
- Root users bypass `plan` and `subscribed` middleware
MD;
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
