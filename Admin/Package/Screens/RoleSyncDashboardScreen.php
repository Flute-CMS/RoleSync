<?php

declare(strict_types = 1);

namespace Flute\Modules\RoleSync\Admin\Package\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Modules\RoleSync\database\Entities\RoleSyncRule;
use Flute\Modules\RoleSync\Services\RoleSyncManager;

class RoleSyncDashboardScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.rolesync';

    /** @var \Cycle\ORM\Select|array */
    public $rules = [];

    public ?array $syncResult = null;

    public function mount(): void
    {
        $this->name = __('rolesync.admin.title');
        $this->description = __('rolesync.admin.description');

        breadcrumb()->add(__('def.admin_panel'), url('/admin')->get())->add(__('rolesync.admin.title'));

        $this->rules = RoleSyncRule::query()->load('role')->orderBy('priority', 'DESC');
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('rolesync.admin.sync_all'))
                ->icon('ph.bold.arrows-clockwise-bold')
                ->type(Color::PRIMARY)
                ->method('syncAllUsers')
                ->confirm(__('rolesync.admin.sync_confirm')),

            Button::make(__('rolesync.admin.add_rule'))
                ->icon('ph.bold.plus-bold')
                ->type(Color::OUTLINE_PRIMARY)
                ->redirect(url('/admin/rolesync/rule')->get()),
        ];
    }

    public function layout(): array
    {
        $layouts = [];

        if ($this->syncResult) {
            $layouts[] = LayoutFactory::block([
                LayoutFactory::view('rolesync::admin.sync-result', [
                    'result' => $this->syncResult,
                ]),
            ])->title(__('rolesync.admin.sync_result'));
        }

        $layouts[] = $this->rulesLayout();

        return $layouts;
    }

    public function syncAllUsers(): void
    {
        /** @var RoleSyncManager $manager */
        $manager = app(RoleSyncManager::class);

        $result = $manager->syncAllUsers();
        $this->syncResult = $result;

        toast()
            ->success(__('rolesync.admin.sync_completed', [
                'processed' => $result['processed'],
                'added' => $result['added'],
                'removed' => $result['removed'],
            ]))
            ->push();

        $this->mount();
    }

    public function toggleRule(): void
    {
        $ruleId = (int) request()->input('rule');
        $rule = RoleSyncRule::findByPK($ruleId);

        if ($rule) {
            $rule->isActive = !$rule->isActive;
            $rule->saveOrFail();
            toast()->success(__('rolesync.status.saved'))->push();
        }

        $this->mount();
    }

    public function deleteRule(): void
    {
        $ruleId = (int) request()->input('rule');
        $rule = RoleSyncRule::findByPK($ruleId);

        if ($rule) {
            $rule->delete();
            toast()->success(__('rolesync.status.deleted'))->push();
        }

        $this->mount();
    }

    protected function rulesLayout()
    {
        $registry = app(RoleSyncManager::class)->getCheckRegistry();

        return LayoutFactory::table('rules', [
            TD::make('priority', __('rolesync.fields.priority'))
                ->width('60px')
                ->render(static fn(RoleSyncRule $rule) => $rule->priority),

            TD::make('name', __('rolesync.fields.name'))->render(static fn(RoleSyncRule $rule) => $rule->name),

            TD::make('role', __('rolesync.fields.role'))->render(
                static fn(RoleSyncRule $rule) => $rule->role?->name ?? '—',
            ),

            TD::make('conditions', __('rolesync.fields.conditions'))->render(static function (RoleSyncRule $rule) use (
                $registry,
            ) {
                $groups = $rule->getConditionGroups();
                $count = 0;
                foreach ($groups as $group) {
                    $count += count($group);
                }
                $groupCount = count($groups);

                return (
                    "{$count} "
                    . __('rolesync.fields.conditions_count')
                    . ( $groupCount > 1 ? " ({$groupCount} " . __('rolesync.fields.groups_count') . ')' : '' )
                );
            }),

            TD::make('is_active', __('rolesync.fields.is_active'))->render(
                static fn(RoleSyncRule $rule) => $rule->isActive
                    ? '<span class="badge success">' . __('rolesync.status.active') . '</span>'
                    : '<span class="badge error">' . __('rolesync.status.inactive') . '</span>',
            ),

            TD::make('last_sync', __('rolesync.fields.last_sync'))->render(
                static fn(RoleSyncRule $rule) => $rule->lastSyncAt ? $rule->lastSyncAt->format('d.m.Y H:i') : '—',
            ),

            TD::make('actions', __('rolesync.table.actions'))
                ->width('120px')
                ->render(static fn(RoleSyncRule $rule) => DropDown::make()
                    ->icon('ph.regular.dots-three-outline-vertical')
                    ->list([
                        DropDownItem::make(__('rolesync.buttons.edit'))
                            ->icon('ph.regular.pencil')
                            ->type(Color::OUTLINE_PRIMARY)
                            ->size('small')
                            ->fullWidth()
                            ->redirect(url('/admin/rolesync/rule/' . $rule->id . '/edit')->get()),

                        DropDownItem::make(
                            $rule->isActive ? __('rolesync.buttons.disable') : __('rolesync.buttons.enable'),
                        )
                            ->icon($rule->isActive ? 'ph.regular.pause' : 'ph.regular.play')
                            ->type($rule->isActive ? Color::OUTLINE_WARNING : Color::OUTLINE_SUCCESS)
                            ->size('small')
                            ->fullWidth()
                            ->method('toggleRule', ['rule' => $rule->id]),

                        DropDownItem::make(__('rolesync.buttons.delete'))
                            ->icon('ph.regular.trash')
                            ->type(Color::OUTLINE_DANGER)
                            ->size('small')
                            ->fullWidth()
                            ->method('deleteRule', ['rule' => $rule->id])
                            ->confirm(__('rolesync.confirms.delete_rule')),
                    ])),
        ])->title(__('rolesync.admin.rules'));
    }
}
