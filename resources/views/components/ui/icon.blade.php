@props(['name' => 'grid'])

@php
    $paths = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'broadcast' => '<path d="M4.9 16.1a7 7 0 0 1 0-9.9"/><path d="M7.8 13.2a3 3 0 0 1 0-4.2"/><circle cx="12" cy="11" r="1"/><path d="M16.2 13.2a3 3 0 0 0 0-4.2"/><path d="M19.1 16.1a7 7 0 0 0 0-9.9"/>',
        'phone' => '<path d="M6 4h3l1.5 3-2 1.5a12 12 0 0 0 6 6L16 13l3 1.5v3A2 2 0 0 1 17 19 14 14 0 0 1 5 6a2 2 0 0 1 1-2z"/>',
        'calendar-check' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18M9 16l2 2 4-4"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'user-plus' => '<circle cx="9" cy="8" r="3"/><path d="M3 19a6 6 0 0 1 12 0M19 8v6M16 11h6"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M3 19a6 6 0 0 1 12 0M15 19a4 4 0 0 1 6-1"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/>',
        'journal' => '<path d="M6 4h11a2 2 0 0 1 2 2v14H8a2 2 0 0 0-2 2V6a2 2 0 0 1 2-2z"/><path d="M6 4a2 2 0 0 0-2 2v14"/>',
        'book' => '<path d="M4 5a2 2 0 0 1 2-2h13v18H6a2 2 0 0 0-2 2z"/><path d="M6 3a2 2 0 0 0-2 2"/>',
        'chart' => '<path d="M4 19V5M4 19h16"/><path d="M8 15v-3M12 15V8M16 15v-5"/>',
        'gear' => '<circle cx="12" cy="12" r="3"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.4 7.6-8 9-4.6-1.4-8-4-8-9V6z"/>',
        'search' => '<circle cx="11" cy="11" r="6"/><path d="M20 20l-3.5-3.5"/>',
        'bell' => '<path d="M6 16V10a6 6 0 1 1 12 0v6l1.5 2H4.5z"/><path d="M10 19a2 2 0 0 0 4 0"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4"/>',
        'moon' => '<path d="M20 14.5A8 8 0 1 1 9.5 4 6.5 6.5 0 0 0 20 14.5z"/>',
        'mic' => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/>',
        'headset' => '<path d="M4 13v-2a8 8 0 0 1 16 0v2"/><path d="M4 13v3a2 2 0 0 0 2 2h1v-7H6a2 2 0 0 0-2 2zM18 11h1a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-7z"/>',
        'clipboard' => '<rect x="6" y="4" width="12" height="16" rx="2"/><path d="M9 4.5h6v2H9zM9 10h6M9 14h4"/>',
        'arrow-up-right' => '<path d="M7 17 17 7M9 7h8v8"/>',
        'scales' => '<path d="M12 3v18M7 7h10"/><path d="M7 7l-3 6a3 3 0 1 0 6 0zM17 7l-3 6a3 3 0 1 0 6 0z"/>',
        'chevron-left' => '<path d="M15 6 9 12l6 6"/>',
        'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'pencil' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'trash' => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>',
        'refresh' => '<path d="M21 12a9 9 0 1 1-2.6-6.1"/><path d="M21 3v6h-6"/>',
        'download' => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
        'upload' => '<path d="M12 21V9"/><path d="m7 14 5-5 5 5"/><path d="M5 3h14"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'h-4 w-4 shrink-0', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.75', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
    {!! $paths[$name] ?? $paths['grid'] !!}
</svg>
