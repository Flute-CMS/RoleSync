@php
    $driverOptionsByCategory = [];
    foreach ($driverFieldDefs as $alias => $def) {
        $cat = $def['category'] ?? 'other';
        $driverOptionsByCategory[$cat][$alias] = $def;
    }

    $editorConfig = json_encode([
        'drivers' => $driverFieldDefs,
        'i18n' => [
            'driverPlaceholder' => __('rolesync.admin.select_condition_driver'),
            'driverLabel' => __('rolesync.fields.conditions'),
        ],
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

    $categoryLabels = [
        'vip' => __('givecore.categories.vip'),
        'admin' => __('givecore.categories.admin'),
        'ban' => __('givecore.categories.ban'),
        'stats' => __('givecore.categories.stats'),
        'rcon' => __('givecore.categories.rcon'),
        'Minecraft' => __('givecore.categories.Minecraft'),
        'CS 1.6' => __('givecore.categories.CS 1.6'),
        'other' => __('givecore.categories.other'),
    ];
@endphp

<div class="card mb-3 mt-3">
    <div class="card-header">
        <div>
            <span class="card-title">{{ __('rolesync.admin.conditions') }}</span>
            <small class="d-block text-muted mt-1">{{ __('rolesync.admin.rule_description') }}</small>
        </div>
    </div>
    <div class="card-body" id="conditions-editor" data-config="{{ $editorConfig }}">
        @forelse ($conditionGroups as $g => $conditions)
            @if ($g > 0)
                <div class="ce-separator"><span class="ce-badge ce-or">OR</span></div>
            @endif
            <div class="ce-group" data-group="{{ $g }}">
                @foreach ($conditions as $c => $cond)
                    @if ($c > 0)
                        <div class="ce-connector"><span class="ce-badge ce-and">AND</span></div>
                    @endif
                    <div class="ce-row" data-condition="{{ $c }}">
                        <div class="ce-fields">
                            <div class="ce-field ce-field-driver form-field">
                                <label class="form__label">{{ __('rolesync.admin.select_condition_driver') }}</label>
                                <div class="select-wrapper">
                                    <div class="select__field-container" data-controller="select"
                                        data-select-placeholder="{{ __('rolesync.admin.select_condition_driver') }}"
                                        data-select-allow-empty="1">
                                        <select name="cond_{{ $g }}_{{ $c }}_driver" class="select__field" data-select
                                            data-allow-empty="true" data-initial-value="{{ json_encode($cond['driver'] ?? '') }}">
                                            <option value="" @if(empty($cond['driver'])) selected @endif disabled>{{ __('rolesync.admin.select_condition_driver') }}</option>
                                            @foreach ($driverOptionsByCategory as $cat => $catDrivers)
                                                <optgroup label="{{ $categoryLabels[$cat] ?? $cat }}">
                                                    @foreach ($catDrivers as $alias => $def)
                                                        <option value="{{ $alias }}"
                                                                @selected(($cond['driver'] ?? '') === $alias)>{{ $def['name'] }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="ce-field ce-field-params">
                                @if (!empty($cond['driver']) && isset($driverFieldDefs[$cond['driver']]))
                                    @foreach ($driverFieldDefs[$cond['driver']]['fields'] as $fname => $fdef)
                                        @php $fval = $cond[$fname] ?? ($fdef['default'] ?? ''); @endphp
                                        <div class="ce-param form-field" data-param-name="{{ $fname }}">
                                                <label class="form__label">{{ $fdef['label'] ?? $fname }}</label>
                                            @if (($fdef['type'] ?? 'text') === 'select')
                                                <div class="select-wrapper">
                                                    <div class="select__field-container" data-controller="select"
                                                        data-select-placeholder="{{ $fdef['placeholder'] ?? '' }}"
                                                        data-select-allow-empty="1">
                                                        <select name="cond_{{ $g }}_{{ $c }}_{{ $fname }}" class="select__field" data-select data-param="{{ $fname }}"
                                                            data-allow-empty="true" data-initial-value="{{ json_encode((string)$fval) }}">
                                                            <option value="">—</option>
                                                            @foreach ($fdef['options'] ?? [] as $k => $v)
                                                                <option value="{{ $k }}" @selected((string)$fval === (string)$k)>{{ $v }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            @elseif (($fdef['type'] ?? 'text') === 'number')
                                                <div class="input-wrapper">
                                                    <div class="input__field-container">
                                                        <input type="number" name="cond_{{ $g }}_{{ $c }}_{{ $fname }}" class="input__field" data-param="{{ $fname }}"
                                                            value="{{ $fval }}" placeholder="{{ $fdef['label'] ?? '' }}"
                                                            @if(isset($fdef['min'])) min="{{ $fdef['min'] }}" @endif
                                                            @if(isset($fdef['max'])) max="{{ $fdef['max'] }}" @endif>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="input-wrapper">
                                                    <div class="input__field-container">
                                                        <input type="text" name="cond_{{ $g }}_{{ $c }}_{{ $fname }}" class="input__field" data-param="{{ $fname }}"
                                                            value="{{ $fval }}" placeholder="{{ $fdef['placeholder'] ?? $fdef['label'] ?? '' }}">
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        <div class="ce-actions">
                            <button type="button" class="btn btn-outline-primary btn-tiny ce-btn-and" data-tooltip="AND">And</button>
                            <button type="button" class="btn btn-outline-warning btn-tiny ce-btn-or" data-tooltip="OR">Or</button>
                            <button type="button" class="btn btn-outline-error btn-tiny ce-btn-remove">
                                <x-icon path="ph.bold.x-bold" />
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @empty
            <div class="ce-empty">
                <button type="button" class="btn btn-outline-primary btn-small" id="ce-add-first">
                    <x-icon class="me-1" path="ph.bold.plus-bold" />
                    <span class="btn-label">{{ __('rolesync.admin.add_or_group') }}</span>
                </button>
            </div>
        @endforelse
    </div>
</div>
<input type="hidden" name="conditions_json" id="conditions-json-input" value="">
