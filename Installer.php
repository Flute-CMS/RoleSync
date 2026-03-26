<?php

declare(strict_types = 1);

namespace Flute\Modules\RoleSync;

use Flute\Core\Database\Entities\Permission;
use Flute\Core\ModulesManager\ModuleInformation;

class Installer extends \Flute\Core\Support\AbstractModuleInstaller
{
    public function install(ModuleInformation &$module): bool
    {
        $permission = Permission::findOne(['name' => 'admin.rolesync']);

        if (!$permission) {
            $permission = new Permission();
            $permission->name = 'admin.rolesync';
            $permission->desc = 'rolesync.admin.menu';
            $permission->save();
        }

        return true;
    }

    public function uninstall(ModuleInformation &$module): bool
    {
        $permission = Permission::findOne(['name' => 'admin.rolesync']);

        if ($permission) {
            $permission->delete();
        }

        return true;
    }

    public function getKey(): ?string
    {
        return 'RoleSync';
    }
}
