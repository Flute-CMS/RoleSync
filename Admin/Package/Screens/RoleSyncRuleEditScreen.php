<?php

declare(strict_types = 1);

namespace Flute\Modules\RoleSync\Admin\Package\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\Toggle;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\Role;
use Flute\Modules\GiveCore\Contracts\CheckDriverInterface;
use Flute\Modules\RoleSync\database\Entities\RoleSyncRule;
use Flute\Modules\RoleSync\Services\RoleSyncManager;

class RoleSyncRuleEditScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.rolesync';

    public ?RoleSyncRule $rule = null;

    public bool $isNew = true;

    public array $roles = [];

    public ?int $ruleId = null;

    /** @var array<string, CheckDriverInterface> */
    public array $drivers = [];

    /** @var array<string, array> Driver alias => checkFields() definitions */
    public array $driverFieldDefs = [];

    public function mount(): void
    {
        $this->loadJS('app/Modules/RoleSync/Admin/Package/Resources/assets/js/conditions-editor.js');

        $this->ruleId = (int) request()->input('id');

        /** @var RoleSyncManager $manager */
        $manager = app(RoleSyncManager::class);
        $this->drivers = $manager->getCheckRegistry()->all();

        $iconFinder = app(\Flute\Core\Modules\Icons\Services\IconFinder::class);

        $this->driverFieldDefs = [];
        foreach ($this->drivers as $alias => $driver) {
            $available = $driver->isAvailable();
            $iconSvg = '';
            try {
                $iconSvg = $iconFinder->loadFile($driver->icon());
            } catch (\Throwable $e) {
            }

            $this->driverFieldDefs[$alias] = [
                'name' => $driver->name(),
                'description' => $driver->description(),
                'icon' => $driver->icon(),
                'iconSvg' => $iconSvg,
                'category' => $driver->category(),
                'available' => $available,
                'unavailableReason' => $driver->unavailableReason(),
                'fields' => $available ? $driver->checkFields() : [],
            ];
        }

        $this->roles = [];
        foreach (rep(Role::class)->findAll() as $role) {
            $this->roles[$role->id] = $role->name;
        }

        if ($this->ruleId > 0) {
            $this->rule = RoleSyncRule::query()
                ->load('role')
                ->where('id', $this->ruleId)
                ->fetchOne();

            if ($this->rule) {
                $this->isNew = false;
                $this->name = __('rolesync.admin.edit_rule');
            }
        }

        if (!$this->rule) {
            $this->rule = new RoleSyncRule();
            $this->name = __('rolesync.admin.add_rule');
        }

        $this->description = __('rolesync.admin.rule_description');

        breadcrumb()->add(__('def.admin_panel'), url('/admin')->get())->add(
            __('rolesync.admin.title'),
            url('/admin/rolesync')->get(),
        )->add($this->name);
    }

    public function commandBar(): array
    {
        $buttons = [
            Button::make(__('rolesync.buttons.save'))
                ->icon('ph.bold.floppy-disk-bold')
                ->type(Color::PRIMARY)
                ->method('save'),

            Button::make(__('rolesync.buttons.cancel'))
                ->icon('ph.bold.x-bold')
                ->type(Color::OUTLINE_SECONDARY)
                ->redirect(url('/admin/rolesync')->get()),
        ];

        if (!$this->isNew) {
            $buttons[] = Button::make(__('rolesync.buttons.delete'))
                ->icon('ph.bold.trash-bold')
                ->type(Color::OUTLINE_DANGER)
                ->method('delete')
                ->confirm(__('rolesync.confirms.delete_rule'));
        }

        return $buttons;
    }

    public function layout(): array
    {
        // Preserve submitted values on validation error (Yoyo re-renders mount+layout)
        $r = request();
        $name = $r->get('rule_name', $this->rule->name ?? '');
        $priority = $r->get('rule_priority', $this->rule->priority ?? 0);
        $roleId = $r->get('rule_role_id', $this->rule->role?->id ?? '');
        $isActive = $r->has('rule_is_active')
            ? filter_var($r->get('rule_is_active'), FILTER_VALIDATE_BOOLEAN)
            : $this->rule->isActive ?? true;

        // Conditions: prefer submitted JSON, fallback to entity
        $conditionsJson = $r->get('conditions_json', '');
        $conditionGroups = !empty($conditionsJson)
            ? ( json_decode($conditionsJson, true) ?: [] )
            : $this->rule->getConditionGroups();

        return [
            LayoutFactory::block([
                LayoutFactory::columns([
                    LayoutFactory::field(
                        Input::make('rule.name')
                            ->value($name)
                            ->required()
                            ->placeholder(__('rolesync.fields.name_placeholder')),
                    )->label(__('rolesync.fields.name')),

                    LayoutFactory::field(
                        Input::make('rule.priority')
                            ->type('number')
                            ->value($priority)
                            ->min(0)
                            ->max(1000),
                    )
                        ->label(__('rolesync.fields.priority'))
                        ->popover(__('rolesync.fields.priority_help')),
                ]),

                LayoutFactory::columns([
                    LayoutFactory::field(
                        Select::make('rule.role_id')
                            ->fromDatabase('roles', 'name', 'id', ['name', 'id', 'priority'])
                            ->aligned()
                            ->value($roleId)
                            ->required()
                            ->empty(__('rolesync.fields.select_role')),
                    )->label(__('rolesync.fields.role')),

                    LayoutFactory::field(Toggle::make('rule.is_active')->value($isActive))->label(__(
                        'rolesync.fields.is_active',
                    )),
                ]),
            ])->title(__('rolesync.admin.basic_settings')),

            LayoutFactory::view('rolesync::admin.conditions-editor', [
                'conditionGroups' => $conditionGroups,
                'driverFieldDefs' => $this->driverFieldDefs,
            ]),
        ];
    }

    public function save(): void
    {
        // Yoyo converts dots to underscores: rule.name → rule_name
        $r = request();
        $data = [
            'rule.name' => $r->get('rule_name', ''),
            'rule.priority' => $r->get('rule_priority', 0),
            'rule.role_id' => $r->get('rule_role_id', 0),
            'rule.is_active' => $r->get('rule_is_active', false),
            'conditions_json' => $r->get('conditions_json', '[]'),
        ];

        $validation = $this->validate([
            'rule.name' => ['required', 'string', 'max-str-len:128'],
            'rule.role_id' => ['required'],
            'conditions_json' => ['required', 'string'],
        ], $data);

        if (!$validation) {
            return;
        }

        // Check conditions not empty
        $groups = json_decode($data['conditions_json'], true);
        if (empty($groups) || !is_array($groups)) {
            toast()->error(__('rolesync.validation.conditions_required'))->push();

            return;
        }

        $this->rule->name = trim($data['rule.name']);
        $this->rule->isActive = filter_var($data['rule.is_active'], FILTER_VALIDATE_BOOLEAN);
        $this->rule->priority = (int) $data['rule.priority'];

        $roleId = (int) $data['rule.role_id'];
        $this->rule->role = rep(Role::class)->findByPK($roleId);

        $this->rule->setConditionGroups($groups);

        $this->rule->saveOrFail();

        toast()->success(__('rolesync.status.saved'))->push();

        $this->redirect('/admin/rolesync');
    }

    public function delete(): void
    {
        if ($this->rule && $this->rule->id) {
            $this->rule->delete();
            toast()->success(__('rolesync.status.deleted'))->push();
        }

        $this->redirect('/admin/rolesync');
    }
}
