import Alpine from 'alpinejs';
import * as Turbo from '@hotwired/turbo';

window.Alpine = Alpine;
window.Turbo = Turbo;

// Soft page navigations (no full browser refresh).
Turbo.session.drive = true;

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
