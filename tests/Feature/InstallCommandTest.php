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

it('patchUserModel adds HasTeams trait', function () {
    $dir = app_path('Models');
    @mkdir($dir, 0755, true);

    file_put_contents(app_path('Models/User.php'), <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
}
PHP);

    $command = new InstallCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new \Illuminate\Console\OutputStyle(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\NullOutput));
    (new ReflectionMethod($command, 'patchUserModel'))->invoke($command);

    $contents = file_get_contents(app_path('Models/User.php'));

    expect($contents)->toContain('use Coollabsio\LaravelSaas\Concerns\HasTeams;')
        ->and($contents)->toContain('use HasTeams, HasFactory, Notifiable;');

    @unlink(app_path('Models/User.php'));
});

it('patchUserModel is idempotent', function () {
    $dir = app_path('Models');
    @mkdir($dir, 0755, true);

    file_put_contents(app_path('Models/User.php'), <<<'PHP'
<?php

namespace App\Models;

use Coollabsio\LaravelSaas\Concerns\HasTeams;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasTeams, HasFactory;
}
PHP);

    $command = new InstallCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new \Illuminate\Console\OutputStyle(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\NullOutput));
    (new ReflectionMethod($command, 'patchUserModel'))->invoke($command);

    $contents = file_get_contents(app_path('Models/User.php'));

    expect(substr_count($contents, 'HasTeams'))->toBe(2); // import + use

    @unlink(app_path('Models/User.php'));
});

it('patchCreateNewUser adds CreatesPersonalTeam trait and call', function () {
    $dir = app_path('Actions/Fortify');
    @mkdir($dir, 0755, true);

    file_put_contents(app_path('Actions/Fortify/CreateNewUser.php'), <<<'PHP'
<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
PHP);

    $command = new InstallCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new \Illuminate\Console\OutputStyle(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\NullOutput));
    (new ReflectionMethod($command, 'patchCreateNewUser'))->invoke($command);

    $contents = file_get_contents(app_path('Actions/Fortify/CreateNewUser.php'));

    expect($contents)->toContain('use Coollabsio\LaravelSaas\Concerns\CreatesPersonalTeam;')
        ->and($contents)->toContain('use CreatesPersonalTeam, PasswordValidationRules;')
        ->and($contents)->toContain('$user = User::create(')
        ->and($contents)->toContain('$this->createPersonalTeam($user);')
        ->and($contents)->toContain('return $user;');

    @unlink(app_path('Actions/Fortify/CreateNewUser.php'));
});

it('patchUserFactory adds configure method with afterCreating', function () {
    $dir = database_path('factories');
    @mkdir($dir, 0755, true);

    file_put_contents(database_path('factories/UserFactory.php'), <<<'PHP'
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
PHP);

    $command = new InstallCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new \Illuminate\Console\OutputStyle(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\NullOutput));
    (new ReflectionMethod($command, 'patchUserFactory'))->invoke($command);

    $contents = file_get_contents(database_path('factories/UserFactory.php'));

    expect($contents)->toContain('public function configure(): static')
        ->and($contents)->toContain('afterCreating')
        ->and($contents)->toContain('Billing::teamModel()')
        ->and($contents)->toContain('forceFill');

    @unlink(database_path('factories/UserFactory.php'));
});

it('patchFortifyConfig changes home to /', function () {
    @mkdir(config_path(), 0755, true);

    file_put_contents(config_path('fortify.php'), <<<'PHP'
<?php

return [
    'home' => '/dashboard',
];
PHP);

    $command = new InstallCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new \Illuminate\Console\OutputStyle(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\NullOutput));
    (new ReflectionMethod($command, 'patchFortifyConfig'))->invoke($command);

    $contents = file_get_contents(config_path('fortify.php'));

    expect($contents)->toContain("'home' => '/'")
        ->and($contents)->not->toContain('/dashboard');

    @unlink(config_path('fortify.php'));
});

it('patchBootstrapMiddleware adds ShareSaasProps', function () {
    file_put_contents(base_path('bootstrap/app.php'), <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->create();
PHP);

    $command = new InstallCommand;
    $command->setLaravel($this->app);
    $command->setOutput(new \Illuminate\Console\OutputStyle(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\NullOutput));
    (new ReflectionMethod($command, 'patchBootstrapMiddleware'))->invoke($command);

    $contents = file_get_contents(base_path('bootstrap/app.php'));

    expect($contents)->toContain('use Coollabsio\LaravelSaas\Http\Middleware\ShareSaasProps;')
        ->and($contents)->toContain('ShareSaasProps::class');

    // Restore original
    file_put_contents(base_path('bootstrap/app.php'), <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->create();
PHP);
});
