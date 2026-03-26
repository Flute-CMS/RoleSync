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

#[Entity(table: 'role_sync_rules')]
#[Table(indexes: [
    new Index(columns: ['is_active']),
    new Index(columns: ['role_id']),
])]
#[Behavior\CreatedAt(field: 'createdAt', column: 'created_at')]
#[Behavior\UpdatedAt(field: 'updatedAt', column: 'updated_at')]
class RoleSyncRule extends ActiveRecord
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string(128)', nullable: false)]
    public string $name;

    #[BelongsTo(target: Role::class, nullable: false)]
    public Role $role;

    /**
     * JSON condition groups.
     * Format: [ [{"driver":"vip","server_id":1,"group":"gold"}, ...], [...] ]
     * Outer array = OR groups, inner array = AND conditions.
     */
    #[Column(type: 'text', nullable: true)]
    public ?string $conditions = null;

    #[Column(type: 'boolean', name: 'is_active', default: true)]
    public bool $isActive = true;

    #[Column(type: 'integer', default: 0)]
    public int $priority = 0;

    #[Column(type: 'datetime', name: 'last_sync_at', nullable: true)]
    public ?DateTimeImmutable $lastSyncAt = null;

    #[Column(type: 'integer', name: 'users_affected', default: 0)]
    public int $usersAffected = 0;

    #[Column(type: 'datetime', name: 'created_at')]
    public DateTimeImmutable $createdAt;

    #[Column(type: 'datetime', name: 'updated_at', nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    /**
     * Get condition groups (OR array of AND arrays).
     *
     * @return array<int, array<int, array>>
     */
    public function getConditionGroups(): array
    {
        if (empty($this->conditions)) {
            return [];
        }

        return json_decode($this->conditions, true) ?? [];
    }

    /**
     * Set condition groups.
     *
     * @param array<int, array<int, array>> $groups
     */
    public function setConditionGroups(array $groups): void
    {
        $this->conditions = empty($groups) ? null : json_encode($groups, JSON_UNESCAPED_UNICODE);
    }

    public function toggle(bool $value): void
    {
        $this->isActive = $value;
    }

    public function updateSyncStats(int $usersAffected): void
    {
        $this->lastSyncAt = new DateTimeImmutable();
        $this->usersAffected = $usersAffected;
    }
}
