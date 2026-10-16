import Alpine from 'alpinejs';

window.Alpine = Alpine;

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
    deleteTarget: null,
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

Alpine.start();
