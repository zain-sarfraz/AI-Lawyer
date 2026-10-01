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
        this.query = '';
        this.$nextTick(() => this.$refs.paletteQuery?.focus());
    },
}));

Alpine.start();
