<?php

declare(strict_types = 1);

namespace Flute\Modules\RoleSync\Listeners;

use Flute\Core\Modules\Auth\Events\SocialLoggedInEvent;
use Flute\Core\Modules\Auth\Events\UserLoggedInEvent;
use Flute\Modules\RoleSync\Services\RoleSyncManager;
use Throwable;

class UserLoginListener
{
    public static function onUserLoggedIn(UserLoggedInEvent $event): void
    {
        self::syncUserRoles($event->getUser());
    }

    public static function onSocialLoggedIn(SocialLoggedInEvent $event): void
    {
        self::syncUserRoles($event->getUser());
    }

    protected static function syncUserRoles($user): void
    {
        if (!$user) {
            return;
        }

        try {
            /** @var RoleSyncManager $manager */
            $manager = app(RoleSyncManager::class);
            $manager->syncUser($user);
        } catch (Throwable $e) {
            logs()->warning("RoleSync failed for user {$user->id}: " . $e->getMessage());
        }
    }
}
