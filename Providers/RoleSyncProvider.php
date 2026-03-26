<?php

declare(strict_types = 1);

namespace Flute\Modules\RoleSync\Providers;

use Flute\Core\Modules\Auth\Events\SocialLoggedInEvent;
use Flute\Core\Modules\Auth\Events\UserLoggedInEvent;
use Flute\Core\Support\ModuleServiceProvider;
use Flute\Modules\GiveCore\Check\CheckRegistry;
use Flute\Modules\RoleSync\Admin\Package\RoleSyncPackage;
use Flute\Modules\RoleSync\Listeners\UserLoginListener;
use Flute\Modules\RoleSync\Services\RoleSyncManager;

class RoleSyncProvider extends ModuleServiceProvider
{
    private const SYNC_INTERVAL = 300; // 5 minutes

    protected ?string $moduleName = 'RoleSync';

    public function boot(\DI\Container $container): void
    {
        $this->bootstrapModule();

        /** @var CheckRegistry $checkRegistry */
        $checkRegistry = $container->get(CheckRegistry::class);

        $manager = new RoleSyncManager($checkRegistry);
        $container->set(RoleSyncManager::class, $manager);

        $this->loadTranslations();

        // Sync on login
        events()->addListener(UserLoggedInEvent::NAME, [UserLoginListener::class, 'onUserLoggedIn']);
        events()->addListener(SocialLoggedInEvent::NAME, [UserLoginListener::class, 'onSocialLoggedIn']);

        // Periodic sync: cron or HTTP fallback
        $this->setupPeriodicSync($manager);

        if (is_admin_path()) {
            $this->loadPackage(new RoleSyncPackage());
        }
    }

    public function register(\DI\Container $container): void
    {
    }

    protected function setupPeriodicSync(RoleSyncManager $manager): void
    {
        if (config('app.cron_mode')) {
            scheduler()->call(static function () use ($manager) {
                $manager->syncAllUsers();
            })->everyMinute(5);
        } else {
            cache()->callback(
                'rolesync.periodic_sync',
                static function () use ($manager) {
                    $manager->syncAllUsers();

                    return true;
                },
                self::SYNC_INTERVAL,
            );
        }
    }
}
