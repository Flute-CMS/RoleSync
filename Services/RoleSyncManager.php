<?php

declare(strict_types = 1);

namespace Flute\Modules\RoleSync\Services;

use DateTimeImmutable;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Database\Entities\User;
use Flute\Modules\GiveCore\Check\CheckRegistry;
use Flute\Modules\RoleSync\database\Entities\RoleSyncLog;
use Flute\Modules\RoleSync\database\Entities\RoleSyncRule;
use Throwable;

class RoleSyncManager
{
    public function __construct(
        protected CheckRegistry $checkRegistry,
    ) {
    }

    public function getCheckRegistry(): CheckRegistry
    {
        return $this->checkRegistry;
    }

    // ── Rule CRUD ──────────────────────────────────────────────────

    /**
     * @return RoleSyncRule[]
     */
    public function getActiveRules(): array
    {
        return RoleSyncRule::query()
            ->where('isActive', true)
            ->orderBy('priority', 'DESC')
            ->fetchAll();
    }

    public function getRule(int $id): ?RoleSyncRule
    {
        return rep(RoleSyncRule::class)->findByPK($id);
    }

    public function deleteRule(int $id): bool
    {
        $rule = $this->getRule($id);
        if (!$rule) {
            return false;
        }

        $rule->delete();

        return true;
    }

    // ── Condition evaluation ───────────────────────────────────────

    /**
     * Evaluate a rule's condition groups for a user.
     * OR between groups, AND within each group.
     */
    public function evaluateRule(RoleSyncRule $rule, User $user): bool
    {
        $groups = $rule->getConditionGroups();

        if (empty($groups)) {
            return false;
        }

        // OR: any group matching = true
        foreach ($groups as $group) {
            if ($this->evaluateGroup($group, $user)) {
                return true;
            }
        }

        return false;
    }

    // ── Synchronization ────────────────────────────────────────────

    /**
     * Sync roles for a single user.
     */
    public function syncUser(User $user): array
    {
        $results = ['added' => [], 'removed' => [], 'errors' => []];

        foreach ($this->getActiveRules() as $rule) {
            try {
                $action = $this->processRuleForUser($rule, $user);
                if ($action === 'added') {
                    $results['added'][] = ['rule_id' => $rule->id, 'role_id' => $rule->role->id];
                } elseif ($action === 'removed') {
                    $results['removed'][] = ['rule_id' => $rule->id, 'role_id' => $rule->role->id];
                }
            } catch (Throwable $e) {
                $results['errors'][] = [
                    'rule_id' => $rule->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Sync roles for all users in batches.
     */
    public function syncAllUsers(int $batchSize = 100): array
    {
        $results = ['processed' => 0, 'added' => 0, 'removed' => 0, 'errors' => 0];

        $rules = $this->getActiveRules();
        if (empty($rules)) {
            return $results;
        }

        $offset = 0;

        while (true) {
            $users = User::query()
                ->limit($batchSize)
                ->offset($offset)
                ->fetchAll();

            if (empty($users)) {
                break;
            }

            foreach ($rules as $rule) {
                try {
                    $ruleResults = $this->processRuleForUsers($rule, $users);
                    $results['added'] += $ruleResults['added'];
                    $results['removed'] += $ruleResults['removed'];
                } catch (Throwable $e) {
                    $results['errors']++;
                    logs()->error("RoleSync batch error for rule {$rule->id}: " . $e->getMessage());
                }
            }

            $results['processed'] += count($users);
            $offset += $batchSize;

            if (count($users) < $batchSize) {
                break;
            }
        }

        foreach ($rules as $rule) {
            $rule->updateSyncStats($results['processed']);
            $rule->save();
        }

        return $results;
    }

    // ── Logs ───────────────────────────────────────────────────────

    public function clearOldLogs(int $daysOld = 30): int
    {
        if ($daysOld === 0) {
            $logs = RoleSyncLog::query()->fetchAll();
        } else {
            $cutoff = new DateTimeImmutable("-{$daysOld} days");
            $logs = RoleSyncLog::query()->where('createdAt', '<', $cutoff)->fetchAll();
        }

        $count = count($logs);
        foreach ($logs as $log) {
            $log->delete();
        }

        return $count;
    }

    /**
     * Evaluate a single condition group (AND logic).
     */
    protected function evaluateGroup(array $conditions, User $user): bool
    {
        if (empty($conditions)) {
            return false;
        }

        // AND: all conditions must match
        foreach ($conditions as $condition) {
            $driverAlias = $condition['driver'] ?? '';
            $driver = $this->checkRegistry->get($driverAlias);

            if (!$driver || !$driver->isAvailable()) {
                return false;
            }

            $params = $condition;
            unset($params['driver']);

            if (!$driver->check($user, $params)) {
                return false;
            }
        }

        return true;
    }

    // ── Processing ─────────────────────────────────────────────────

    /**
     * Process a rule for a single user. Returns 'added', 'removed', or 'none'.
     */
    protected function processRuleForUser(RoleSyncRule $rule, User $user): string
    {
        $conditionMet = $this->evaluateRule($rule, $user);
        $hasRole = $this->userHasRole($user, $rule->role);

        if ($conditionMet && !$hasRole) {
            $this->addRoleToUser($user, $rule->role);
            RoleSyncLog::log($user, $rule->role, 'add', $rule);

            return 'added';
        }

        if (!$conditionMet && $hasRole) {
            $this->removeRoleFromUser($user, $rule->role);
            RoleSyncLog::log($user, $rule->role, 'remove', $rule);

            return 'removed';
        }

        return 'none';
    }

    protected function processRuleForUsers(RoleSyncRule $rule, array $users): array
    {
        $results = ['added' => 0, 'removed' => 0];

        foreach ($users as $user) {
            try {
                $action = $this->processRuleForUser($rule, $user);
                if ($action === 'added') {
                    $results['added']++;
                } elseif ($action === 'removed') {
                    $results['removed']++;
                }
            } catch (Throwable $e) {
                logs()->warning("RoleSync error for user {$user->id}, rule {$rule->id}: " . $e->getMessage());
            }
        }

        return $results;
    }

    protected function userHasRole(User $user, Role $role): bool
    {
        foreach ($user->roles as $userRole) {
            if ($userRole->id === $role->id) {
                return true;
            }
        }

        return false;
    }

    protected function addRoleToUser(User $user, Role $role): void
    {
        $user->addRole($role);
        $user->save();
    }

    protected function removeRoleFromUser(User $user, Role $role): void
    {
        $user->removeRole($role);
        $user->save();
    }
}
