<div class="rolesync-sync-result">
    <div class="rolesync-sync-stats">
        <div class="rolesync-stat-item">
            <span class="rolesync-stat-value">{{ $result['processed'] }}</span>
            <span class="rolesync-stat-label">{{ __('rolesync.sync.processed') }}</span>
        </div>
        <div class="rolesync-stat-item text-success">
            <span class="rolesync-stat-value">{{ $result['added'] }}</span>
            <span class="rolesync-stat-label">{{ __('rolesync.sync.added') }}</span>
        </div>
        <div class="rolesync-stat-item text-warning">
            <span class="rolesync-stat-value">{{ $result['removed'] }}</span>
            <span class="rolesync-stat-label">{{ __('rolesync.sync.removed') }}</span>
        </div>
        @if($result['errors'] > 0)
            <div class="rolesync-stat-item text-danger">
                <span class="rolesync-stat-value">{{ $result['errors'] }}</span>
                <span class="rolesync-stat-label">{{ __('rolesync.sync.errors') }}</span>
            </div>
        @endif
    </div>
</div>
