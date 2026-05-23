/* ===========================================================================
 * Inganzo Ngari Mgmt — shared client helpers.
 * Available globally as window.app.
 * ======================================================================== */

(function () {
    'use strict';

    const APP = window.APP || {};

    // ---------- AJAX ----------
    async function api(path, opts = {}) {
        const url = (APP.base || '') + '/api/' + path.replace(/^\//, '');
        const init = {
            method: opts.method || 'GET',
            headers: Object.assign({
                'Accept': 'application/json',
                'X-CSRF-Token': APP.csrf || ''
            }, opts.headers || {}),
            credentials: 'same-origin'
        };
        if (opts.json) {
            init.headers['Content-Type'] = 'application/json';
            init.body = JSON.stringify(opts.json);
        } else if (opts.form) {
            init.body = opts.form; // FormData
        } else if (opts.body) {
            init.body = opts.body;
        }
        const res = await fetch(url, init);
        const ct  = res.headers.get('content-type') || '';
        const data = ct.includes('application/json') ? await res.json() : await res.text();
        if (!res.ok) {
            const msg = (data && data.error) ? data.error : `HTTP ${res.status}`;
            throw new Error(msg);
        }
        return data;
    }

    // ---------- Toast ----------
    function toast(message, type = 'info') {
        const area = document.getElementById('toast-area');
        if (!area) { console.log('[' + type + ']', message); return; }
        const div = document.createElement('div');
        div.className = 'toast-app ' + type;
        const icon = type === 'success' ? 'check-circle'
                   : type === 'error'   ? 'circle-exclamation'
                   : 'circle-info';
        div.innerHTML = `<i class="fa-solid fa-${icon}"></i><span>${escapeHtml(message)}</span>`;
        area.appendChild(div);
        setTimeout(() => { div.style.opacity = 0; setTimeout(() => div.remove(), 250); }, 3500);
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'
        })[c]);
    }

    // ---------- Money formatting ----------
    function formatRwf(n) {
        if (n === null || n === undefined || n === '') return '—';
        const v = Number(n);
        if (!isFinite(v)) return '—';
        return new Intl.NumberFormat('en-US').format(Math.round(v));
    }

    // ---------- Rwandan National ID parser ----------
    // Standard format: 1YYYY[7|8]NNNNNNNNNS  (16 digits)
    //   1     – constant leading
    //   YYYY  – birth year (positions 2-5)
    //   6th   – '7' = female, '8' = male
    //   rest  – sequence + check
    function parseNationalId(nid) {
        if (!nid) return null;
        const digits = String(nid).replace(/\D/g, '');
        if (digits.length < 6) return null;
        const yearStr = digits.substring(1, 5);
        const year = parseInt(yearStr, 10);
        if (!year || year < 1900 || year > 2100) return null;

        const genderDigit = digits.charAt(5);
        let gender = null;
        if (genderDigit === '7') gender = 'F';
        else if (genderDigit === '8') gender = 'M';

        return {
            digits: digits,
            year:   year,
            yearStr: yearStr,
            genderDigit: genderDigit,
            gender: gender,
            isComplete: digits.length === 16
        };
    }

    // Wire a National ID input to auto-fill gender + birth-year on the same form.
    // Pass the IDs of the input, gender select, and DOB input.
    function bindNationalIdAutofill(nidId, genderId, dobId, opts = {}) {
        const nidEl    = document.getElementById(nidId);
        const genderEl = document.getElementById(genderId);
        const dobEl    = document.getElementById(dobId);
        if (!nidEl) return;
        const allowGenderOverride = !!opts.allowGenderOverride;

        const apply = () => {
            const p = parseNationalId(nidEl.value);
            if (!p) {
                if (genderEl && !allowGenderOverride) genderEl.removeAttribute('readonly');
                return;
            }
            if (p.gender && genderEl) {
                genderEl.value = p.gender;
                if (!allowGenderOverride) {
                    genderEl.setAttribute('readonly', 'readonly');
                    if (genderEl.tagName === 'SELECT') {
                        // <select> can't be 'readonly', so disable other options visually but keep value submitted
                        Array.from(genderEl.options).forEach(o => { if (o.value !== p.gender) o.disabled = true; });
                    }
                }
            }
            if (dobEl && p.year) {
                const cur = dobEl.value || '';
                const parts = cur.split('-');
                const month = parts[1] || '01';
                const day   = parts[2] || '01';
                dobEl.value = `${p.year}-${month}-${day}`;
            }
        };
        nidEl.addEventListener('input', apply);
        nidEl.addEventListener('blur',  apply);
        apply();
    }

    // ---------- Confirm modal helper ----------
    function confirmAction(message, opts = {}) {
        return new Promise((resolve) => {
            // Minimal Bootstrap modal at runtime.
            const id = 'confirm-' + Math.random().toString(36).slice(2, 8);
            const html = `
              <div class="modal fade" id="${id}" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">${escapeHtml(opts.title || 'Are you sure?')}</h5>
                      <button class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body" style="font-size:14px">${escapeHtml(message)}</div>
                    <div class="modal-footer">
                      <button class="btn btn-ghost" data-bs-dismiss="modal">${escapeHtml(opts.cancelText || 'Cancel')}</button>
                      <button class="btn ${opts.danger ? 'btn-danger' : 'btn-gold'}" id="${id}-ok">${escapeHtml(opts.okText || 'Confirm')}</button>
                    </div>
                  </div>
                </div>
              </div>`;
            document.body.insertAdjacentHTML('beforeend', html);
            const el = document.getElementById(id);
            const modal = new bootstrap.Modal(el);
            el.querySelector('#' + id + '-ok').addEventListener('click', () => {
                modal.hide();
                resolve(true);
            });
            el.addEventListener('hidden.bs.modal', () => { el.remove(); resolve(false); });
            modal.show();
        });
    }

    // ---------- Insurance status chip helper ----------
    function insuranceChip(status) {
        const map = {
            active:   ['Active',   'chip-active'],
            expiring: ['Expiring', 'chip-warn'],
            expired:  ['Expired',  'chip-danger'],
            none:     ['No cover', 'chip-muted'],
            unknown:  ['Unknown',  'chip-muted']
        };
        const [label, cls] = map[status] || ['—', 'chip-muted'];
        return `<span class="chip ${cls}"><span class="dot"></span>${label}</span>`;
    }

    // ---------- Status chip for members ----------
    function memberStatusChip(status) {
        const map = {
            active:    ['Active',    'chip-active'],
            retired:   ['Retired',   'chip-retired'],
            suspended: ['Suspended', 'chip-danger']
        };
        const [label, cls] = map[status] || ['—', 'chip-muted'];
        return `<span class="chip ${cls}"><span class="dot"></span>${label}</span>`;
    }

    // Expose
    window.app = {
        api, toast, formatRwf, parseNationalId, bindNationalIdAutofill,
        confirmAction, insuranceChip, memberStatusChip, escapeHtml
    };
})();
