<?php

return [
    'admin' => [
        'title' => 'Role Synchronization',
        'description' => 'Automatic synchronization of user roles with game privileges',
        'menu' => [
            'title' => 'Role Sync',
        ],
        'sync_all' => 'Sync All Users',
        'sync_confirm' => 'Are you sure you want to synchronize roles for all users?',
        'sync_completed' => 'Synchronization completed. Processed: :processed, added: :added, removed: :removed',
        'sync_result' => 'Sync Result',
        'add_rule' => 'Add Rule',
        'edit_rule' => 'Edit Rule',
        'rule_description' => 'Role is automatically added when conditions are met and removed when they are not',
        'basic_settings' => 'Basic Settings',
        'conditions' => 'Conditions',
        'rules' => 'Sync Rules',
        'logs' => 'Sync Log',
        'logs_description' => 'History of all role synchronization operations',
        'clear_logs' => 'Clear Logs',
        'clear_logs_confirm' => 'Are you sure you want to clear the sync log?',
        'logs_cleared' => 'Deleted entries: :count',
        'add_or_group' => 'Add OR group',
        'add_and_condition' => 'Add AND condition',
        'select_condition_driver' => 'Select system',
    ],

    'fields' => [
        'name' => 'Name',
        'name_placeholder' => 'e.g.: For VIP players',
        'role' => 'Role',
        'select_role' => 'Select role',
        'is_active' => 'Active',
        'priority' => 'Priority',
        'priority_help' => 'Rules with higher priority are executed first',
        'last_sync' => 'Last Sync',
        'conditions' => 'Conditions',
        'conditions_count' => 'conditions',
        'groups_count' => 'groups',
    ],

    'action' => [
        'add' => 'Added',
        'remove' => 'Removed',
    ],

    'log' => [
        'date' => 'Date',
        'user' => 'User',
        'role' => 'Role',
        'action' => 'Action',
        'rule' => 'Rule',
        'status' => 'Status',
    ],

    'sync' => [
        'processed' => 'Processed',
        'added' => 'Added',
        'removed' => 'Removed',
        'errors' => 'Errors',
    ],

    'buttons' => [
        'edit' => 'Edit',
        'delete' => 'Delete',
        'enable' => 'Enable',
        'disable' => 'Disable',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'view_logs' => 'Logs',
        'back' => 'Back',
    ],

    'confirms' => [
        'delete_rule' => 'Are you sure you want to delete this rule?',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'saved' => 'Rule saved',
        'deleted' => 'Rule deleted',
    ],

    'table' => [
        'actions' => 'Actions',
    ],

    'log_status' => [
        'success' => 'Success',
        'error' => 'Error',
    ],

    'driver_available' => 'Available',
    'driver_unavailable' => 'Unavailable',

    'validation' => [
        'name_required' => 'Please enter a rule name',
        'role_required' => 'Please select a role',
        'conditions_required' => 'Please add at least one condition',
    ],
];
