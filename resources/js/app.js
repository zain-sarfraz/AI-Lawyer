import Alpine from 'alpinejs';
import * as Turbo from '@hotwired/turbo';

window.Alpine = Alpine;
window.Turbo = Turbo;

// Soft page navigations (no full browser refresh).
Turbo.session.drive = true;

// Cases page: debounce search while selects submit immediately via onchange.
let caseFilterSearchTimer = null;
window.__caseFilterSearch = (form) => {
    clearTimeout(caseFilterSearchTimer);
    caseFilterSearchTimer = setTimeout(() => form.requestSubmit(), 400);
};

const prefetchedNavigationLinks = new WeakSet();

function preloadSidebarLink(event) {
    const target = event.target;
    const link = target instanceof Element ? target.closest('a[data-nav-prefetch]') : null;
    const url = link ? new URL(link.href) : null;

    if (
        !link
        || prefetchedNavigationLinks.has(link)
        || !url
        || url.origin !== window.location.origin
        || (url.pathname === window.location.pathname && url.search === window.location.search)
        || link.hasAttribute('download')
        || (link.target && link.target !== '_self')
        || link.closest('[data-turbo="false"]')
    ) {
        return;
    }

    if (event.type === 'pointerover' && link.contains(event.relatedTarget)) {
        return;
    }
    prefetchedNavigationLinks.add(link);
    prefetchedNavigationLinks.add(link);
    const prefetchContainer = document.createElement('div');
    const prefetchLink = link.cloneNode();
    prefetchLink.dataset.turboPreload = '';
    prefetchContainer.append(prefetchLink);
    Turbo.session.preloadOnLoadLinksForView(prefetchContainer);
}

document.addEventListener('pointerover', preloadSidebarLink, { passive: true });
document.addEventListener('focusin', preloadSidebarLink);

document.addEventListener('turbo:before-cache', () => {
    // Avoid restoring open Alpine dropdowns / modals from bfcache.
    document.querySelectorAll('[x-cloak]').forEach((el) => {
        el.removeAttribute('style');
    });
});

document.addEventListener('turbo:before-render', () => {
    if (window.__alpineStarted && typeof Alpine.destroyTree === 'function') {
        Alpine.destroyTree(document.body);
    }
});


window.__validateLawyerForm = (form) => {
    const checked = form.querySelectorAll('input[name="jurisdiction_ids[]"]:checked');
    if (!checked.length) {
        alert('Select at least one practice jurisdiction (checkbox), then save.');
        return false;
    }
    const phoneCountry = form.querySelector('[name="phone_country"]')?.value;
    if (!phoneCountry) {
        alert('Select a phone dial country.');
        return false;
    }
    return true;
};

Alpine.data('lawyerPhoneForm', (initialCountry = '', initialLocal = '') => ({
    phoneCountry: (initialCountry || '').toUpperCase(),
    phoneLocal: initialLocal || '',
    dialMap: { PK: '+92', GB: '+44', US: '+1' },
    get dialCode() {
        return this.dialMap[this.phoneCountry] || '';
    },
    get previewE164() {
        const local = String(this.phoneLocal || '').replace(/\D/g, '').replace(/^0+/, '');
        if (!this.dialCode || !local) return '';
        return `${this.dialCode}${local}`;
    },
    onCountryChange() {
        this.sanitizeLocal();
    },
    sanitizeLocal() {
        // Keep digits only; strip leading 0s so PK 0324… never becomes +440324…
        let digits = String(this.phoneLocal || '').replace(/\D/g, '');
        digits = digits.replace(/^0+/, '');
        this.phoneLocal = digits;
    },
}));

Alpine.data('lawyerPracticeForm', (initialCountry = '', initialLocal = '', jurisdictionsByCountry = {}, selectedIds = []) => ({
    phoneCountry: (initialCountry || '').toUpperCase(),
    phoneLocal: initialLocal || '',
    // Filter only — never auto-check jurisdictions for a country.
    practiceCountry: (initialCountry || '').toUpperCase(),
    jurisdictionsByCountry: jurisdictionsByCountry || {},
    selectedJurisdictions: (selectedIds || []).map(Number),
    jurisOpen: false,
    dialMap: { PK: '+92', GB: '+44', US: '+1' },
    get dialCode() {
        return this.dialMap[this.phoneCountry] || '';
    },
    get previewE164() {
        const local = String(this.phoneLocal || '').replace(/\D/g, '').replace(/^0+/, '');
        if (!this.dialCode || !local) return '';
        return `${this.dialCode}${local}`;
    },
    onPhoneCountryChange() {
        this.sanitizeLocal();
        // Convenience: pre-fill practice-country filter once if empty. Does not pick jurisdictions.
        if (this.phoneCountry && !this.practiceCountry) {
            this.practiceCountry = this.phoneCountry;
        }
    },
    onPracticeCountryChange() {
        // Filter-only: open list for this country; do not auto-select or carry wrong labels.
        this.jurisOpen = false;
    },
    visibleJurisdictions() {
        if (!this.practiceCountry) {
            return Object.values(this.jurisdictionsByCountry).flat();
        }
        return this.jurisdictionsByCountry[this.practiceCountry] || [];
    },
    toggleJurisdiction(id) {
        const value = Number(id);
        if (this.selectedJurisdictions.includes(value)) {
            this.selectedJurisdictions = this.selectedJurisdictions.filter((item) => item !== value);
        } else {
            this.selectedJurisdictions = [...this.selectedJurisdictions, value];
        }
    },
    jurisdictionLabel() {
        const visible = this.visibleJurisdictions();
        const visibleIds = new Set(visible.map((option) => Number(option.id)));
        const names = visible
            .filter((option) => this.selectedJurisdictions.includes(Number(option.id)))
            .map((option) => option.name);
        const otherCount = this.selectedJurisdictions.filter((id) => !visibleIds.has(Number(id))).length;

        if (!names.length && !otherCount) {
            return this.practiceCountry
                ? 'Select jurisdictions for this country…'
                : 'Select jurisdictions…';
        }
        if (!names.length && otherCount) {
            return `Pick jurisdictions here (${otherCount} already selected in other countries)`;
        }
        let label = names.length <= 2
            ? names.join(', ')
            : names.slice(0, 2).join(', ') + ' +' + (names.length - 2) + ' more';
        if (otherCount) {
            label += ` (+${otherCount} other)`;
        }
        return label;
    },
    sanitizeLocal() {
        let digits = String(this.phoneLocal || '').replace(/\D/g, '');
        digits = digits.replace(/^0+/, '');
        this.phoneLocal = digits;
    },
}));

Alpine.data('shell', () => ({
    mobileOpen: false,
    collapsed: localStorage.getItem('sidebar') === 'collapsed',
    dark: document.documentElement.classList.contains('dark'),
    palette: false,
    query: '',
    toggleCollapsed() {
        this.collapsed = !this.collapsed;
        localStorage.setItem('sidebar', this.collapsed ? 'collapsed' : 'open');
    },
    toggleDark() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
    },
    openPalette() {
        this.palette = true;
        this.$nextTick(() => this.$refs.headerSearch?.focus());
    },
}));

Alpine.data('liveTableSearch', (initialQuery = '', initialStatus = '') => ({
    query: initialQuery,
    status: initialStatus,
    visible: 0,
    init() {
        this.apply();
    },
    apply() {
        const q = this.query.trim().toLowerCase();
        const status = (this.status || '').toLowerCase();
        let shown = 0;

        this.$refs.tbody?.querySelectorAll('tr[data-search]').forEach((row) => {
            const hay = (row.dataset.search || '').toLowerCase();
            const rowStatus = (row.dataset.status || '').toLowerCase();
            const matchQuery = !q || hay.includes(q);
            const matchStatus = !status || rowStatus === status;
            const show = matchQuery && matchStatus;
            row.hidden = !show;
            if (show) {
                shown += 1;
            }
        });

        this.visible = shown;
        if (this.$refs.empty) {
            this.$refs.empty.hidden = shown > 0;
        }
        if (this.$refs.pager) {
            this.$refs.pager.hidden = q.length > 0 || status.length > 0;
        }
    },
}));

Alpine.store('lawyerUi', {
    details: null,
    deleteTarget: null,
    openDetails(lawyer) {
        this.details = lawyer;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'lawyer-details' }));
    },
    askDelete(target) {
        this.deleteTarget = target;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'delete-lawyer' }));
    },
    confirmDelete() {
        const form = document.getElementById('delete-lawyer-form');
        if (!form || !this.deleteTarget?.url) {
            return;
        }
        form.action = this.deleteTarget.url;
        form.submit();
    },
    openEdit(id) {
        window.dispatchEvent(new CustomEvent('open-modal', { detail: `edit-lawyer-${id}` }));
    },
});

Alpine.store('userUi', {
    details: null,
    deleteTarget: null,
    openDetails(user) {
        this.details = user ? { ...user } : null;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'user-details' }));
    },
    askDelete(target) {
        this.deleteTarget = target;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'delete-user' }));
    },
    confirmDelete() {
        const form = document.getElementById('delete-user-form');
        if (!form || !this.deleteTarget?.url) {
            return;
        }
        form.action = this.deleteTarget.url;
        form.submit();
    },
    openEdit(id) {
        window.dispatchEvent(new CustomEvent('open-modal', { detail: `edit-user-${id}` }));
    },
});

Alpine.store('clientUi', {
    details: null,
    deleteTarget: null,
    openDetails(client) {
        this.details = client;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'client-details' }));
    },
    askDelete(target) {
        this.deleteTarget = target;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'delete-client' }));
    },
    confirmDelete() {
        const form = document.getElementById('delete-client-form');
        if (!form || !this.deleteTarget?.url) {
            return;
        }
        form.action = this.deleteTarget.url;
        form.submit();
    },
    openEdit(id) {
        window.dispatchEvent(new CustomEvent('open-modal', { detail: `edit-client-${id}` }));
    },
});

Alpine.store('appointmentUi', {
    details: null,
    deleteTarget: null,
    openDetails(appointment) {
        this.details = appointment ? { ...appointment } : null;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'appointment-details' }));
    },
    openEdit(appointment) {
        if (!appointment?.id || !appointment?.update_url) {
            return;
        }

        const template = document.getElementById(`edit-appt-template-${appointment.id}`);
        const fields = document.getElementById('edit-appointment-fields');
        if (template && fields) {
            fields.innerHTML = template.innerHTML;
            fields.querySelectorAll('[data-appt-slot-form]').forEach((el) => {
                delete el.dataset.bound;
            });
        }

        window.dispatchEvent(new CustomEvent('fill-edit-appointment', {
            detail: { action: appointment.update_url },
        }));
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'edit-appointment' }));
        window.dispatchEvent(new CustomEvent('bind-appt-slots'));
    },
    askDelete(target) {
        this.deleteTarget = target;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'delete-appointment' }));
    },
    confirmDelete() {
        const form = document.getElementById('delete-appointment-form');
        if (!form || !this.deleteTarget?.url) {
            return;
        }
        form.action = this.deleteTarget.url;
        form.submit();
    },
});

Alpine.store('caseUi', {
    deleteTarget: null,
    details: null,
    notes: null,
    askDelete(target) {
        this.deleteTarget = target;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'delete-case' }));
    },
    confirmDelete() {
        const form = document.getElementById('delete-case-form');
        if (!form || !this.deleteTarget?.url) {
            return;
        }
        form.action = this.deleteTarget.url;
        form.submit();
    },
    openDetails(payload) {
        this.details = payload ? { ...payload, files: [...(payload.files || [])] } : null;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'case-details' }));
    },
    openNotes(payload) {
        this.notes = payload
            ? {
                title: payload.title,
                case_number: payload.case_number,
                description: payload.description || '',
                update_url: payload.update_url,
            }
            : null;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'case-notes' }));
    },
    openFromAppointment(payload) {
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'create-case' }));
        window.setTimeout(() => {
            const title = document.getElementById('create-case-title');
            const client = document.getElementById('create-case-client_id');
            const lawyer = document.getElementById('create-case-lawyer_id');
            const opened = document.getElementById('create-case-opened_on');
            if (title && payload?.case_title) title.value = payload.case_title;
            if (client && payload?.client_id) client.value = String(payload.client_id);
            if (lawyer && payload?.lawyer_id) lawyer.value = String(payload.lawyer_id);
            if (opened && !opened.value) {
                const today = new Date();
                opened.value = today.toISOString().slice(0, 10);
            }
        }, 50);
    },
});

Alpine.store('documentUi', {
    details: null,
    gallery: null,
    deleteTarget: null,
    openDetails(doc) {
        this.details = doc ? { ...doc } : null;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'document-details' }));
    },
    openGallery(payload) {
        this.gallery = payload ? { ...payload, files: [...(payload.files || [])] } : null;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'document-gallery' }));
    },
    askDelete(target) {
        this.deleteTarget = target;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'delete-document' }));
    },
    confirmDelete() {
        const form = document.getElementById('delete-document-form');
        if (!form || !this.deleteTarget?.url) {
            return;
        }
        form.action = this.deleteTarget.url;
        form.submit();
    },
});

Alpine.store('serviceUi', {
    deleteTarget: null,
    askDelete(target) {
        this.deleteTarget = target;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'delete-service' }));
    },
    confirmDelete() {
        const form = document.getElementById('delete-service-form');
        if (!form || !this.deleteTarget?.url) {
            return;
        }
        form.action = this.deleteTarget.url;
        form.submit();
    },
    openEdit(id) {
        window.dispatchEvent(new CustomEvent('open-modal', { detail: `edit-service-${id}` }));
    },
});

Alpine.data('appointmentNotifier', (urls = {}) => ({
    open: false,
    items: [],
    unreadCount: 0,
    latestId: 0,
    soundEnabled: localStorage.getItem('notification_sound_enabled') !== 'false',
    hasNew: false,
    toast: null,
    toastTimer: null,
    pollingInterval: null,
    audioCtx: null,
    feedUrl: urls.feedUrl || '/notifications/appointments/feed',
    markReadUrl: urls.markReadUrl || '/notifications/appointments/mark-read',

    init() {
        this.fetchFeed(true);
        this.pollingInterval = setInterval(() => this.poll(), 6000);

        const unlockAudio = () => {
            this.ensureAudioContext();
            document.removeEventListener('click', unlockAudio);
            document.removeEventListener('keydown', unlockAudio);
            document.removeEventListener('touchstart', unlockAudio);
        };
        document.addEventListener('click', unlockAudio, { once: true });
        document.addEventListener('keydown', unlockAudio, { once: true });
        document.addEventListener('touchstart', unlockAudio, { once: true });

        this.$el.addEventListener('turbo:before-cache', () => {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
            }
        });
    },

    destroy() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
        if (this.toastTimer) {
            clearTimeout(this.toastTimer);
        }
    },

    async fetchFeed(initial = false) {
        try {
            const res = await fetch(this.feedUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) {
                return;
            }
            const data = await res.json();
            if (!data.success) {
                return;
            }

            this.items = data.items || [];
            this.unreadCount = data.unread_count || 0;
            this.latestId = data.latest_id || 0;

            if (!initial && this.unreadCount > 0) {
                this.hasNew = true;
            }
        } catch (err) {
            console.warn('Notification feed check failed:', err);
        }
    },

    async poll() {
        try {
            const url = `${this.feedUrl}?last_id=${this.latestId}`;
            const res = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) {
                return;
            }
            const data = await res.json();
            if (!data.success) {
                return;
            }

            if (data.has_brand_new && data.brand_new_item) {
                this.hasNew = true;
                this.unreadCount = data.unread_count;
                this.latestId = data.latest_id;
                this.items = data.items || [];

                if (this.soundEnabled) {
                    this.playBellSound();
                }
                this.showToast(data.brand_new_item);
            } else if (data.latest_id !== this.latestId) {
                this.latestId = data.latest_id;
                this.items = data.items || [];
                this.unreadCount = data.unread_count;
            }
        } catch (err) {
            // Silently ignore intermittent poll errors
        }
    },

    showToast(item) {
        this.toast = item;
        if (this.toastTimer) {
            clearTimeout(this.toastTimer);
        }
        this.toastTimer = setTimeout(() => {
            this.toast = null;
        }, 8000);
    },

    async openDropdown() {
        this.open = !this.open;
        if (this.open) {
            await this.fetchFeed();
            // Opening the panel counts as "seen".
            if (this.unreadCount > 0 || this.hasNew) {
                await this.markAllRead();
            }
        }
    },

    async markAllRead() {
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(this.markReadUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ latest_id: this.latestId }),
            });

            if (!res.ok) {
                console.warn('Mark read failed with status', res.status);
                return;
            }

            this.unreadCount = 0;
            this.hasNew = false;
            this.items.forEach((i) => {
                i.is_unread = false;
            });
        } catch (e) {
            console.warn('Mark read failed:', e);
        }
    },

    toggleSound() {
        this.soundEnabled = !this.soundEnabled;
        localStorage.setItem('notification_sound_enabled', this.soundEnabled ? 'true' : 'false');
        if (this.soundEnabled) {
            this.playBellSound();
        }
    },

    testSound() {
        this.playBellSound();
    },

    ensureAudioContext() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) {
                return null;
            }
            if (!this.audioCtx || this.audioCtx.state === 'closed') {
                this.audioCtx = new AudioCtx();
            }
            if (this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }

            return this.audioCtx;
        } catch (e) {
            return null;
        }
    },

    playBellSound() {
        try {
            const ctx = this.ensureAudioContext();
            if (!ctx) {
                return;
            }

            const now = ctx.currentTime;
            const master = ctx.createGain();
            master.gain.setValueAtTime(0.55, now);
            master.connect(ctx.destination);

            const ring = (freq, startAt, duration, peak) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freq, now + startAt);
                gain.gain.setValueAtTime(0.0001, now + startAt);
                gain.gain.exponentialRampToValueAtTime(peak, now + startAt + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + startAt + duration);
                osc.connect(gain);
                gain.connect(master);
                osc.start(now + startAt);
                osc.stop(now + startAt + duration + 0.02);
            };

            // Clear two-tone chime that browsers reliably play after user gesture unlock.
            ring(1046.5, 0, 0.28, 0.7);
            ring(1567.98, 0.16, 0.42, 0.75);
        } catch (err) {
            console.warn('Could not play notification chime:', err);
        }
    },
}));

/* ------------------------------------------------------------------
 |  Cases (legal research) — provider-backed search screen.
 |
 |  Registered here rather than inline in a Blade @push so that the
 |  component still exists after Turbo Drive swaps the body: the first
 |  navigation calls Alpine.start(), later ones only Alpine.initTree().
 | ------------------------------------------------------------------ */

Alpine.data('caseResearch', (initial, routes, initialFilters, knownTypes, initialCategoryLabel, categoryMap) => ({
    loading: false,
    data: initial || {},
    filters: initialFilters || {},
    routes,
    knownTypes: knownTypes || [],
    categoryMap: categoryMap || {},
    categoryLabel: initialCategoryLabel || '',
    courts: [],
    courtsLoading: false,
    courtsTried: false,
    countdown: 0,
    countdownTimer: null,
    detail: null,
    detailLoading: false,
    detailError: '',
    today: new Date().toISOString().slice(0, 10),
    request: null,

    boot() {
        this.data = this.normalise(this.data);
        this.startCountdown();
    },

    destroy() {
        if (this.countdownTimer) {
            clearInterval(this.countdownTimer);
            this.countdownTimer = null;
        }
    },

    normalise(payload) {
        return Object.assign({
            results: [],
            total: 0,
            page: 1,
            per_page: 20,
            total_pages: 0,
            has_next: false,
            has_prev: false,
            reason: 'empty',
            message: null,
            stale: false,
            retry_after: null,
            provider: 'CourtListener',
            country: '',
            stats: { total_found: 0, analyzed: 0, outcomes: {}, classifiable: 0, win_pct: null, loss_pct: null, outcome_note: null },
            range: { date_from: '', date_to: '' },
            hidden_by_outcome: 0,
        }, payload || {});
    },

    /* ---------------- Filter interactions ---------------- */

    typeOptions() {
        const facetKeys = Object.keys(this.data.stats?.case_types || {});
        return Array.from(new Set([...this.knownTypes, ...facetKeys])).sort((a, b) => a.localeCompare(b));
    },

    onCategoryChange() {
        this.categoryLabel = this.categoryLabels()[this.filters.category] || '';
        this.filters.case_type = '';
        this.apply(1);
    },

    categoryLabels() {
        return this.categoryMap;
    },

    onRangeChange() {
        if (this.filters.date_range !== 'custom') {
            this.filters.date_from = '';
            this.filters.date_to = '';
        }
        this.apply(1);
    },

    hasFilters() {
        return ['category', 'q', 'case_type', 'court', 'country', 'outcome']
            .some((key) => (this.filters[key] || '') !== '');
    },

    resetFilters() {
        Object.assign(this.filters, {
            category: '', q: '', case_type: '', court: '', country: '', outcome: '',
            date_range: '2y', date_from: '', date_to: '', page: 1,
        });
        this.categoryLabel = '';
        this.apply(1);
    },

    apply(page) {
        this.filters.page = page || 1;
        this.fetchResults();
    },

    goPage(page) {
        if (page < 1 || (this.data.total_pages && page > this.data.total_pages)) {
            return;
        }
        this.apply(page);
    },

    query(params) {
        const search = new URLSearchParams();
        Object.entries(params || this.filters).forEach(([key, value]) => {
            if (value === '' || value === null || value === undefined) {
                return;
            }
            search.set(key, value);
        });
        const built = search.toString();
        return built ? `?${built}` : '';
    },

    pageUrl(page) {
        return `${this.routes.search}${this.query({ ...this.filters, page })}`;
    },

    syncAddress() {
        const url = `${this.routes.search}${this.query(this.filters)}`;
        if (window.history?.replaceState) {
            window.history.replaceState(null, '', url);
        }
    },

    async fetchResults() {
        if (this.request) {
            this.request.abort();
        }
        this.request = new AbortController();
        this.loading = true;

        try {
            const response = await fetch(`${this.routes.api}${this.query(this.filters)}`, {
                signal: this.request.signal,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok && !payload.reason) {
                this.data = this.normalise({ ...payload, reason: 'error', message: `The search request failed (HTTP ${response.status}).` });
                return;
            }

            this.data = this.normalise(payload);
            this.syncAddress();
            this.startCountdown();
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.data = this.normalise({ reason: 'error', message: 'Network error — the legal-data request did not complete.' });
            }
        } finally {
            this.loading = false;
            this.request = null;
        }
    },

    /* ---------------- Court directory ---------------- */

    async loadCourts() {
        if (this.courtsLoading || this.courts.length > 0) {
            return;
        }
        this.courtsLoading = true;
        this.courtsTried = true;

        try {
            const response = await fetch(this.routes.courts, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const payload = await response.json();
            this.courts = (payload.courts || []).filter((court) => court.in_use !== false);
        } catch (error) {
            this.courts = [];
        } finally {
            this.courtsLoading = false;
        }
    },

    /* ---------------- Presentation helpers ---------------- */

    fmt(value) {
        return Number(value || 0).toLocaleString();
    },

    fmtDate(value) {
        if (!value) {
            return 'Date not published';
        }
        const date = new Date(value);
        return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    },

    clip(text, limit = 150) {
        const clean = String(text || '').replace(/<[^>]*>/g, '');
        return clean.length > limit ? `${clean.slice(0, limit)}…` : clean;
    },

    rangeLabel() {
        const map = {
            '1m': 'Last month', '3m': 'Previous months', '6m': 'Last 6 months',
            '1y': 'Last 1 year', '2y': 'Last 2 years', custom: 'Custom range',
        };
        return map[this.filters.date_range] || 'Last 2 years';
    },

    courtMeta(record) {
        const parts = [record.jurisdiction, record.country || 'US'].filter(Boolean);
        return parts.join(' · ');
    },

    outcomeLabel(record) {
        const map = { won: 'Won', lost: 'Lost', pending: 'Pending', unknown: 'Unknown' };
        return map[record.outcome_class] || 'Unknown';
    },

    outcomeBreakdown() {
        const outcomes = this.data.stats?.outcomes || {};
        return `Won ${outcomes.won || 0} · Lost ${outcomes.lost || 0} · Pending ${outcomes.pending || 0} · Unknown ${outcomes.unknown || 0}`;
    },

    winLossDisplay() {
        if (!this.data.stats?.classifiable) {
            return 'Not verifiable';
        }
        return `${this.data.stats.win_pct}% W / ${this.data.stats.loss_pct}% L`;
    },

    notice() {
        const reason = this.data.reason;

        if (reason === 'unsupported_country') {
            return `${this.data.message || 'No data source covers that country yet.'} CourtListener publishes United States records only; no results are shown rather than guessing.`;
        }
        if (reason === 'rate_limited' || this.data.stale) {
            return this.data.message || 'Provider rate limit reached.';
        }
        if (reason === 'unconfigured') {
            return this.data.message || 'The legal-data provider is not connected.';
        }
        if (reason === 'error') {
            return this.data.message || 'The provider request failed. Existing records are unchanged.';
        }
        if (this.data.hidden_by_outcome > 0 && (this.data.results || []).length > 0) {
            return `${this.data.hidden_by_outcome} record(s) on this page were hidden because their outcome is not verifiable as "${this.filters.outcome}".`;
        }
        return '';
    },

    noticeKind() {
        const reason = this.data.reason;
        if (reason === 'unsupported_country' || reason === 'unconfigured' || reason === 'error') {
            return 'danger';
        }
        return reason === 'rate_limited' || this.data.stale || this.data.hidden_by_outcome > 0 ? 'warn' : 'info';
    },

    noticeTitle() {
        return {
            unsupported_country: 'Country not covered.',
            unconfigured: 'Provider not connected.',
            rate_limited: 'Rate limited.',
            error: 'Request failed.',
        }[this.data.reason] || (this.data.stale ? 'Cached results.' : 'Filter note.');
    },

    startCountdown() {
        if (this.countdownTimer) {
            clearInterval(this.countdownTimer);
            this.countdownTimer = null;
        }
        if (this.data.reason !== 'rate_limited' || !this.data.retry_after) {
            this.countdown = 0;
            return;
        }
        this.countdown = Number(this.data.retry_after);
        this.countdownTimer = setInterval(() => {
            this.countdown -= 1;
            if (this.countdown <= 0) {
                clearInterval(this.countdownTimer);
                this.countdownTimer = null;
            }
        }, 1000);
    },

    emptyHeading() {
        return {
            unsupported_country: 'That country has no connected data source',
            unconfigured: 'No provider connected',
            rate_limited: 'Temporarily rate limited',
            error: 'The provider could not be reached',
        }[this.data.reason] || 'No matching records';
    },

    emptyHint() {
        if (this.data.reason === 'empty') {
            return 'The provider returned no published opinions for this combination of category, court and decision date. Broaden the filters to check.';
        }
        return 'Adjust the filters and search again.';
    },

    /* ---------------- Case detail ---------------- */

    openDetail(record) {
        if (!record) {
            return;
        }
        this.detail = record;
        this.detailError = '';
        this.detailLoading = false;
        this.$dispatch('open-modal', 'case-detail');
    },

    async fetchFullRecord() {
        const id = this.detail?.case_id;
        if (!id || !/^\d+$/.test(String(id))) {
            this.detailError = 'This record has no numeric source identifier, so the full entry cannot be requested.';
            return;
        }

        this.detailLoading = true;
        this.detailError = '';

        try {
            const response = await fetch(`${this.routes.case}/${id}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok || payload.ok === false) {
                this.detailError = payload.message || 'The source record could not be retrieved.';
            } else {
                // Merge so nothing the search result already verified is lost when
                // the source leaves a field empty.
                const merged = { ...this.detail };
                Object.entries(payload.case || {}).forEach(([key, value]) => {
                    if (value !== '' && value !== null && value !== undefined) {
                        merged[key] = value;
                    }
                });
                this.detail = merged;
            }
        } catch (error) {
            this.detailError = 'Network error while retrieving the source record.';
        } finally {
            this.detailLoading = false;
        }
    },

    /**
     * Explains exactly why an outcome is (or is not) classifiable. Never claims
     * a win/loss the provider did not state.
     */
    outcomeNote() {
        const record = this.detail;
        if (!record) {
            return '';
        }

        if (record.judgment_text && record.outcome_class !== 'unknown') {
            return `The source states: “${record.judgment_text}”. Calling this Won or Lost also requires knowing which party the firm represented.`;
        }
        if (record.judgment_text) {
            return `The source states: “${record.judgment_text}”. It does not identify which party prevailed, so the outcome stays Unknown.`;
        }
        if (record.outcome_class === 'pending') {
            return 'The source lists this matter as still open, so it is reported as Pending.';
        }

        return 'CourtListener does not publish a party-relative judgment or disposition for this record, so the outcome cannot be verified and is reported as Unknown rather than guessed.';
    },

    detailRows() {
        const record = this.detail;
        if (!record) {
            return [];
        }

        return [
            { label: 'Case name', value: record.case_name || '—' },
            { label: 'Case ID (source)', value: record.case_id || 'Not published' },
            { label: 'Citation', value: record.citation || 'No citation in the source record' },
            { label: 'Docket number', value: record.docket_number || 'Not published' },
            { label: 'Country', value: record.country || 'US' },
            { label: 'Court', value: record.court_name || record.court_id || '—' },
            { label: 'Court ID', value: record.court_id || 'Not published' },
            { label: 'Jurisdiction', value: record.jurisdiction || 'Not stated by the provider' },
            { label: 'Jurisdiction code', value: record.jurisdiction_code ? `${record.jurisdiction_code} (as published by the source)` : 'Not published for this record' },
            { label: 'City / location', value: 'Not published — the court directory carries no city or state field' },
            { label: 'Category', value: record.category || 'Not categorised' },
            { label: 'Case type / nature of suit', value: record.nature_of_suit || 'Not stated by the provider' },
            { label: 'Procedural posture', value: record.posture || 'Not stated' },
            { label: 'Decision date', value: this.fmtDate(record.date_filed) },
            { label: 'Precedential status', value: record.status || 'Not stated' },
            { label: 'Judgment as published', value: record.judgment_text || 'The provider does not publish a disposition for this record' },
            { label: 'Outcome classification', value: this.outcomeLabel(record) },
            { label: 'Provider', value: record.provider || this.data.provider || 'CourtListener' },
        ];
    },
}));

/* ------------------------------------------------------------------
 |  Cases landing page — verified coverage probe.
 |
 |  One category per request, stopped at the first rate limit so the
 |  landing page can never consume the whole provider quota.
 | ------------------------------------------------------------------ */

Alpine.data('caseCoverage', (url, categories) => ({
    states: {},
    counts: {},
    paused: false,

    init() {
        categories.forEach((key) => {
            this.states[key] = 'checking';
        });
        this.probe(categories, 0);
    },

    async probe(categories, index) {
        if (index >= categories.length) {
            return;
        }

        const key = categories[index];

        if (this.paused) {
            this.states[key] = 'paused';
            this.probe(categories, index + 1);
            return;
        }

        try {
            const response = await fetch(`${url}?category=${encodeURIComponent(key)}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const payload = await response.json();

            if (payload.ok && payload.verified) {
                this.counts[key] = payload.count;
                this.states[key] = payload.count > 0 ? 'ready' : 'empty';
            } else {
                this.states[key] = 'paused';
                this.paused = true; // provider throttling or outage: stop probing
            }
        } catch (error) {
            this.states[key] = 'unreachable';
            this.paused = true;
        }

        this.probe(categories, index + 1);
    },

    dotClass(key) {
        return {
            checking: 'is-checking',
            ready: 'is-ready',
            empty: 'is-empty',
            paused: 'is-paused',
            unreachable: 'is-paused',
        }[this.states[key] || 'checking'];
    },

    labelFor(key) {
        const state = this.states[key] || 'checking';

        if (state === 'checking') {
            return 'Checking coverage…';
        }
        if (state === 'ready') {
            const count = Number(this.counts[key] || 0);
            return `${count.toLocaleString()} decisions in the last 2 years`;
        }
        if (state === 'empty') {
            return 'No published decisions for this category';
        }
        return 'Coverage not verified (provider rate limit)';
    },
}));

document.addEventListener('turbo:load', () => {
    if (!window.__alpineStarted) {
        Alpine.start();
        window.__alpineStarted = true;
        return;
    }

    // Re-bind Alpine after Turbo swaps the body (no full page reload).
    if (typeof Alpine.initTree === 'function') {
        Alpine.initTree(document.body);
    }
});

// Keep CSRF token fresh for Turbo form posts after soft navigations.
document.addEventListener('turbo:before-fetch-request', (event) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        event.detail.fetchOptions.headers['X-CSRF-TOKEN'] = token;
    }
});
