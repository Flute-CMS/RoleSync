<?php

declare(strict_types = 1);

namespace Flute\Modules\RoleSync\Admin\Package\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Modules\RoleSync\database\Entities\RoleSyncLog;
use Flute\Modules\RoleSync\Services\RoleSyncManager;

class RoleSyncLogsScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.rolesync';

    /** @var \Cycle\ORM\Select|array */
    public $logs = [];

    public function mount(): void
    {
        $this->name = __('rolesync.admin.logs');
        $this->description = __('rolesync.admin.logs_description');

        breadcrumb()->add(__('def.admin_panel'), url('/admin')->get())->add(
            __('rolesync.admin.title'),
            url('/admin/rolesync')->get(),
        )->add(__('rolesync.admin.logs'));

        $this->logs = RoleSyncLog::query()
            ->load('user')
            ->load('role')
            ->load('rule')
            ->orderBy('createdAt', 'DESC');
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('rolesync.admin.clear_logs'))
                ->icon('ph.bold.trash-bold')
                ->type(Color::OUTLINE_DANGER)
                ->method('clearLogs')
                ->confirm(__('rolesync.admin.clear_logs_confirm')),

            Button::make(__('rolesync.buttons.back'))
                ->icon('ph.bold.arrow-left-bold')
                ->type(Color::OUTLINE_SECONDARY)
                ->redirect(url('/admin/rolesync')->get()),
        ];
    }

    public function layout(): array
    {
        return [
            LayoutFactory::table('logs', [
                TD::make('created_at', __('rolesync.log.date'))->render(
                    static fn(RoleSyncLog $log) => $log->createdAt->format('d.m.Y H:i:s'),
                ),

                TD::make('user', __('rolesync.log.user'))->render(
                    static fn(RoleSyncLog $log) => $log->user?->name ?? '—',
                ),

                TD::make('role', __('rolesync.log.role'))->render(
                    static fn(RoleSyncLog $log) => $log->role?->name ?? '—',
                ),

                TD::make('action', __('rolesync.log.action'))->render(static fn(RoleSyncLog $log) => $log->action
                === 'add'
                    ? '<span class="badge success">' . __('rolesync.action.add') . '</span>'
                    : '<span class="badge warning">' . __('rolesync.action.remove') . '</span>'),

                TD::make('rule', __('rolesync.log.rule'))->render(
                    static fn(RoleSyncLog $log) => $log->rule?->name ?? '—',
                ),

                TD::make('status', __('rolesync.log.status'))->render(static fn(RoleSyncLog $log) => $log->status
                === 'success'
                    ? '<span class="badge success">' . __('rolesync.log_status.success') . '</span>'
                    : '<span class="badge danger">' . __('rolesync.log_status.error') . '</span>'),
            ])->title(__('rolesync.admin.logs')),
        ];
    }

    public function clearLogs(): void
    {
        /** @var RoleSyncManager $manager */
        $manager = app(RoleSyncManager::class);
        $count = $manager->clearOldLogs(0);

        toast()->success(__('rolesync.admin.logs_cleared', ['count' => $count]))->push();

        $this->mount();
    }
}
