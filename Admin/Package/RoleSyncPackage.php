<?php

declare(strict_types = 1);

namespace Flute\Modules\RoleSync\Admin\Package;

use Flute\Admin\Support\AbstractAdminPackage;

class RoleSyncPackage extends AbstractAdminPackage
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');
        $this->loadViews('Resources/views', 'rolesync');
        $this->registerScss('Resources/assets/scss/admin.scss');
    }

    public function getPermissions(): array
    {
        return ['admin', 'admin.rolesync'];
    }

    public function getMenuItems(): array
    {
        return [
            [
                'type' => 'header',
                'title' => __('rolesync.admin.menu.title'),
            ],
            [
                'title' => __('rolesync.admin.rules'),
                'icon' => 'ph.bold.arrows-clockwise-bold',
                'url' => url('/admin/rolesync'),
                'permission' => ['admin.rolesync'],
                'permission_mode' => 'any',
            ],
            [
                'title' => __('rolesync.admin.logs'),
                'icon' => 'ph.bold.list-bullets-bold',
                'url' => url('/admin/rolesync/logs'),
                'permission' => ['admin.rolesync'],
                'permission_mode' => 'any',
            ],
        ];
    }

    public function getPriority(): int
    {
        return 95;
    }
}
