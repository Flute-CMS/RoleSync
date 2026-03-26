<?php

use Flute\Core\Router\Router;
use Flute\Modules\RoleSync\Admin\Package\Screens\RoleSyncDashboardScreen;
use Flute\Modules\RoleSync\Admin\Package\Screens\RoleSyncLogsScreen;
use Flute\Modules\RoleSync\Admin\Package\Screens\RoleSyncRuleEditScreen;

Router::screen('/admin/rolesync', RoleSyncDashboardScreen::class);
Router::screen('/admin/rolesync/logs', RoleSyncLogsScreen::class);
Router::screen('/admin/rolesync/rule', RoleSyncRuleEditScreen::class);
Router::screen('/admin/rolesync/rule/{id}/edit', RoleSyncRuleEditScreen::class);
