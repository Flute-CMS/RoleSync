if (!window._rsCeRequestHooked) {
    window._rsCeRequestHooked = true;
    document.body.addEventListener('htmx:configRequest', function (e) {
        var editor = document.getElementById('conditions-editor');
        if (!editor) return;

        var groups = [];
        editor.querySelectorAll('.ce-group').forEach(function (grp) {
            var conditions = [];
            grp.querySelectorAll('.ce-row').forEach(function (row) {
                var driverSelect = row.querySelector('[data-param="driver"]') || row.querySelector('.ce-field-driver select[data-select]');
                if (!driverSelect) return;
                var driverVal = driverSelect.value;
                if (!driverVal) return;

                var cond = { driver: driverVal };
                row.querySelectorAll('[data-param]').forEach(function (input) {
                    var key = input.dataset.param;
                    if (key === 'driver') return;
                    var val = input.type === 'checkbox' ? input.checked : input.value;
                    if (val !== '' && val !== false) cond[key] = val;
                });
                conditions.push(cond);
            });
            if (conditions.length > 0) groups.push(conditions);
        });

        e.detail.parameters['conditions_json'] = JSON.stringify(groups);
    });
}

function initRoleSyncConditionsEditor() {
    var editor = document.getElementById('conditions-editor');
    if (!editor || editor.dataset.ceInit) return;
    editor.dataset.ceInit = '1';

    var config = JSON.parse(editor.dataset.config || '{}');
    var drivers = config.drivers || {};
    var i18n = config.i18n || {};

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    var driverOptionHtmlMap = {};
    Object.keys(drivers).forEach(function (alias) {
        var d = drivers[alias];
        var cat = d.category || 'other';
        driverOptionHtmlMap[alias] = '<div style="display:flex;align-items:center;gap:6px;width:100%">'
            + '<span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + esc(d.name) + '</span>'
            + '<span style="font-size:10px;opacity:.45;flex-shrink:0">' + esc(cat) + '</span>'
            + '</div>';
    });

    function driverSelectHtml(name, selectedVal) {
        var html = '<option value="" selected disabled>' + esc(i18n.driverPlaceholder || 'Select') + '</option>';

        var categories = {};
        Object.keys(drivers).forEach(function (alias) {
            var cat = drivers[alias].category || 'other';
            if (!categories[cat]) categories[cat] = [];
            categories[cat].push(alias);
        });

        Object.keys(categories).forEach(function (cat) {
            html += '<optgroup label="' + esc(cat) + '">';
            categories[cat].forEach(function (alias) {
                var d = drivers[alias];
                var sel = selectedVal === alias ? ' selected' : '';
                var dis = d.available === false ? ' disabled' : '';
                html += '<option value="' + esc(alias) + '"' + sel + dis + '>' + esc(d.name) + '</option>';
            });
            html += '</optgroup>';
        });

        return '<div class="select-wrapper"><div class="select__field-container" data-controller="select"'
            + ' data-select-placeholder="' + esc(i18n.driverPlaceholder) + '" data-select-allow-empty="1">'
            + '<select name="' + esc(name) + '" class="select__field" data-select data-param="driver"'
            + ' data-allow-empty="true" data-initial-value="">' + html + '</select></div></div>';
    }

    function applyDriverOptionHtml(container) {
        var selectEl = container.querySelector('.ce-field-driver select[data-select]');
        if (!selectEl) return;
        selectEl.querySelectorAll('option').forEach(function (opt) {
            if (opt.value && driverOptionHtmlMap[opt.value]) {
                opt.setAttribute('data-html', driverOptionHtmlMap[opt.value]);
            }
        });
    }


    function paramFieldHtml(fname, fdef, value, namePrefix) {
        var type = fdef.type || 'text';
        var name = namePrefix + '_' + fname;
        var label = '<label class="form__label">' + esc(fdef.label || fname) + '</label>';

        if (type === 'select') {
            var opts = fdef.options || {};
            var html = '<option value="">—</option>';
            Object.keys(opts).forEach(function (k) {
                var sel = String(value) === String(k) ? ' selected' : '';
                html += '<option value="' + esc(k) + '"' + sel + '>' + esc(opts[k]) + '</option>';
            });
            return '<div class="ce-param" data-param-name="' + esc(fname) + '">' + label
                + '<div class="select-wrapper"><div class="select__field-container" data-controller="select"'
                + ' data-select-placeholder="' + esc(fdef.placeholder || '') + '" data-select-allow-empty="1">'
                + '<select name="' + esc(name) + '" class="select__field" data-select data-param="' + esc(fname) + '"'
                + ' data-allow-empty="true" data-initial-value="' + esc(String(value)) + '">' + html + '</select></div></div></div>';
        }

        if (type === 'number') {
            var min = fdef.min !== undefined ? ' min="' + fdef.min + '"' : '';
            var max = fdef.max !== undefined ? ' max="' + fdef.max + '"' : '';
            return '<div class="ce-param" data-param-name="' + esc(fname) + '">' + label
                + '<div class="input-wrapper"><div class="input__field-container">'
                + '<input type="number" name="' + esc(name) + '" class="input__field" data-param="' + esc(fname) + '"'
                + ' value="' + esc(String(value || '')) + '" placeholder="' + esc(fdef.label || '') + '"' + min + max + '>'
                + '</div></div></div>';
        }

        return '<div class="ce-param" data-param-name="' + esc(fname) + '">' + label
            + '<div class="input-wrapper"><div class="input__field-container">'
            + '<input type="text" name="' + esc(name) + '" class="input__field" data-param="' + esc(fname) + '"'
            + ' value="' + esc(String(value || '')) + '" placeholder="' + esc(fdef.placeholder || fdef.label || '') + '">'
            + '</div></div></div>';
    }

    function renderParams(paramsContainer, driverAlias, values, namePrefix) {
        paramsContainer.innerHTML = '';
        if (!driverAlias || !drivers[driverAlias]) return;

        var fields = drivers[driverAlias].fields || {};
        Object.keys(fields).forEach(function (fname) {
            var val = values && values[fname] !== undefined ? values[fname] : '';
            paramsContainer.insertAdjacentHTML('beforeend', paramFieldHtml(fname, fields[fname], val, namePrefix));
        });

        initNewSelects(paramsContainer);
    }

    function initNewSelects(el) {
        if (!window.Select) return;
        window.Select.init(el);
    }

    function getMaxGroup() {
        var m = -1;
        editor.querySelectorAll('.ce-group').forEach(function (g) {
            var i = parseInt(g.dataset.group, 10);
            if (i > m) m = i;
        });
        return m;
    }

    function reindex() {
        editor.querySelectorAll('.ce-group').forEach(function (grp, gi) {
            grp.dataset.group = gi;
            grp.querySelectorAll('.ce-row').forEach(function (row, ci) {
                row.dataset.condition = ci;
            });
        });
    }

    function rebuildConnectors() {
        editor.querySelectorAll('.ce-connector, .ce-separator').forEach(function (e) { e.remove(); });

        var groups = editor.querySelectorAll('.ce-group');
        groups.forEach(function (grp, gi) {
            if (gi > 0) {
                var sep = document.createElement('div');
                sep.className = 'ce-separator';
                sep.innerHTML = '<span class="ce-badge ce-or">OR</span>';
                grp.parentNode.insertBefore(sep, grp);
            }
            var rows = grp.querySelectorAll('.ce-row');
            rows.forEach(function (row, ci) {
                if (ci > 0) {
                    var con = document.createElement('div');
                    con.className = 'ce-connector';
                    con.innerHTML = '<span class="ce-badge ce-and">AND</span>';
                    row.parentNode.insertBefore(con, row);
                }
            });
        });

        var empty = editor.querySelector('.ce-empty');
        if (empty && groups.length > 0) empty.remove();
    }

    function bindDriverChange(row, driverSelect, namePrefix) {
        var onChange = function () {
            var val = driverSelect.value;
            renderParams(row.querySelector('.ce-field-params'), val, {}, namePrefix);
        };
        driverSelect.addEventListener('change', onChange);

        var checkFs = function () {
            var fs = typeof FluteSelect !== 'undefined' ? FluteSelect.get(driverSelect) : null;
            if (fs) {
                fs.on('change', function () { onChange(); });
            } else {
                setTimeout(checkFs, 50);
            }
        };
        setTimeout(checkFs, 50);
    }

    function makeRow(gi, ci) {
        var namePrefix = 'cond_' + gi + '_' + ci;
        var row = document.createElement('div');
        row.className = 'ce-row';
        row.dataset.condition = ci;
        row.innerHTML = '<div class="ce-fields">'
            + '<div class="ce-field form-field ce-field-driver"><label class="form__label">' + esc(i18n.driverPlaceholder || 'System') + '</label>' + driverSelectHtml(namePrefix + '_driver', '') + '</div>'
            + '<div class="ce-field ce-field-params"></div>'
            + '</div>'
            + '<div class="ce-actions">'
            + '<button type="button" class="btn btn-outline-primary btn-tiny ce-btn-and" data-tooltip="AND">And</button>'
            + '<button type="button" class="btn btn-outline-warning btn-tiny ce-btn-or" data-tooltip="OR">Or</button>'
            + '<button type="button" class="btn btn-outline-error btn-tiny ce-btn-remove">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256"><path d="M208.49,191.51a12,12,0,0,1-17,17L128,145,64.49,208.49a12,12,0,0,1-17-17L111,128,47.51,64.49a12,12,0,0,1,17-17L128,111l63.51-63.52a12,12,0,0,1,17,17L145,128Z"/></svg>'
            + '</button></div>';

        applyDriverOptionHtml(row);

        var driverSelect = row.querySelector('.ce-field-driver select[data-select]');
        if (driverSelect) {
            bindDriverChange(row, driverSelect, namePrefix);
        }

        return row;
    }

    function makeGroup(gi) {
        var grp = document.createElement('div');
        grp.className = 'ce-group';
        grp.dataset.group = gi;
        grp.appendChild(makeRow(gi, 0));
        return grp;
    }

    function addConditionToGroup(grp) {
        var gi = parseInt(grp.dataset.group, 10);
        var ci = grp.querySelectorAll('.ce-row').length;
        var row = makeRow(gi, ci);
        grp.appendChild(row);
        initNewSelects(row);
        rebuildConnectors();
    }

    function addNewGroup() {
        var gi = getMaxGroup() + 1;
        var grp = makeGroup(gi);
        editor.appendChild(grp);
        initNewSelects(grp);
        reindex();
        rebuildConnectors();
    }

    function removeRow(row) {
        var grp = row.closest('.ce-group');
        var rows = grp.querySelectorAll('.ce-row');
        if (rows.length <= 1) {
            var groups = editor.querySelectorAll('.ce-group');
            if (groups.length <= 1) return;
            grp.remove();
        } else {
            row.remove();
        }
        reindex();
        rebuildConnectors();
    }

    editor.addEventListener('click', function (e) {
        var btn;
        if ((btn = e.target.closest('.ce-btn-and'))) {
            addConditionToGroup(btn.closest('.ce-group'));
            return;
        }
        if ((btn = e.target.closest('.ce-btn-or'))) {
            addNewGroup();
            return;
        }
        if ((btn = e.target.closest('.ce-btn-remove'))) {
            removeRow(btn.closest('.ce-row'));
            return;
        }
    });

    var addFirstBtn = document.getElementById('ce-add-first');
    if (addFirstBtn) {
        addFirstBtn.addEventListener('click', function () {
            addNewGroup();
        });
    }

    editor.querySelectorAll('.ce-row').forEach(function (row) {
        applyDriverOptionHtml(row);
    });

    editor.querySelectorAll('.ce-row').forEach(function (row) {
        var driverSelect = row.querySelector('.ce-field-driver select[data-select]');
        if (!driverSelect) return;
        var gi = row.closest('.ce-group').dataset.group;
        var ci = row.dataset.condition;
        var namePrefix = 'cond_' + gi + '_' + ci;

        bindDriverChange(row, driverSelect, namePrefix);
    });
}

if (document.readyState !== 'loading') {
    initRoleSyncConditionsEditor();
} else {
    document.addEventListener('DOMContentLoaded', initRoleSyncConditionsEditor);
}
if (!window._rsCeListenersRegistered) {
    window._rsCeListenersRegistered = true;
    document.addEventListener('htmx:afterSettle', initRoleSyncConditionsEditor);
    document.addEventListener('htmx:afterSwap', initRoleSyncConditionsEditor);
}
