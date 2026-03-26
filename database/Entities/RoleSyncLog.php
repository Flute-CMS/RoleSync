<?php

declare(strict_types = 1);

namespace Flute\Modules\RoleSync\database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Database\Entities\User;

#[Entity(table: 'role_sync_logs')]
#[Table(indexes: [
    new Index(columns: ['user_id']),
    new Index(columns: ['rule_id']),
    new Index(columns: ['action']),
    new Index(columns: ['created_at']),
])]
#[Behavior\CreatedAt(field: 'createdAt', column: 'created_at')]
class RoleSyncLog extends ActiveRecord
{
    #[Column(type: 'primary')]
    public int $id;

    #[BelongsTo(target: User::class, nullable: false)]
    public User $user;

    #[BelongsTo(target: RoleSyncRule::class, nullable: true)]
    public ?RoleSyncRule $rule = null;

    #[BelongsTo(target: Role::class, nullable: false)]
    public Role $role;

    #[Column(type: 'string(16)', nullable: false)]
    public string $action;

    #[Column(type: 'string(16)', default: 'success')]
    public string $status = 'success';

    #[Column(type: 'text', nullable: true)]
    public ?string $details = null;

    #[Column(type: 'datetime', name: 'created_at')]
    public DateTimeImmutable $createdAt;

    public function setDetails(array $details): void
    {
        $this->details = json_encode($details, JSON_UNESCAPED_UNICODE);
    }

    public function getDetails(): array
    {
        if (empty($this->details)) {
            return [];
        }

        return json_decode($this->details, true) ?? [];
    }

    public static function log(
        User $user,
        Role $role,
        string $action,
        ?RoleSyncRule $rule = null,
        string $status = 'success',
        array $details = [],
    ): self {
        $log = new self();
        $log->user = $user;
        $log->role = $role;
        $log->action = $action;
        $log->rule = $rule;
        $log->status = $status;

        if (!empty($details)) {
            $log->setDetails($details);
        }

        $log->save();

        return $log;
    }
}
