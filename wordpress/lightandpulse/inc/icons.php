<?php
/**
 * Small inline SVG icon set (stroke icons) used by the theme and the
 * non-Elementor fallback of the starter pages. Elementor pages use the
 * matching Font Awesome icons instead (see LIGHTANDPULSE_FA).
 */
defined('ABSPATH') || exit;

const LIGHTANDPULSE_ICONS = [
    'globe'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M9 15l2 2 4-4"/>',
    'cart'     => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.6 12.4a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.5L21 8H6"/>',
    'pen'      => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
    'search'   => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
    'shield'   => '<path d="M12 3 4 6v6c0 5 3.4 8.3 8 9 4.6-.7 8-4 8-9V6Z"/><path d="m9 12 2 2 4-4"/>',
    'check'    => '<path d="M20 6 9 17l-5-5"/>',
    'chat'     => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/>',
    'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    'pin'      => '<path d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/>',
    'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    'key'      => '<circle cx="8" cy="15" r="4"/><path d="m10.8 12.2 9.2-9.2M17 6l3 3M15 8l2 2"/>',
    'heart'    => '<path d="M20.8 8.6a5 5 0 0 0-8.8-3.2 5 5 0 0 0-8.8 3.2c0 5.4 8.8 11.4 8.8 11.4s8.8-6 8.8-11.4Z"/>',
    'eye'      => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
];

/** Font Awesome 5 (bundled with Elementor) equivalents for Elementor icon boxes. */
const LIGHTANDPULSE_FA = [
    'globe' => 'fas fa-globe', 'calendar' => 'fas fa-calendar-check', 'cart' => 'fas fa-shopping-cart',
    'pen' => 'fas fa-pencil-ruler', 'search' => 'fas fa-search-location', 'shield' => 'fas fa-shield-alt',
    'check' => 'fas fa-check', 'chat' => 'fab fa-whatsapp', 'mail' => 'far fa-envelope', 'pin' => 'fas fa-map-marker-alt',
    'clock' => 'far fa-clock', 'arrow' => 'fas fa-arrow-right', 'key' => 'fas fa-key', 'heart' => 'far fa-heart', 'eye' => 'far fa-eye',
];

function lightandpulse_icon(string $name, string $class = 'lp-icon'): string
{
    $paths = LIGHTANDPULSE_ICONS[$name] ?? LIGHTANDPULSE_ICONS['check'];
    return '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
}

/** The Lightandpulse mark: a pulse line inside a rounded square. */
function lightandpulse_mark(int $size = 34): string
{
    return '<svg class="lp-mark" viewBox="0 0 40 40" width="' . $size . '" height="' . $size . '" aria-hidden="true"><defs><linearGradient id="lpmark" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="var(--lp-accent)"/><stop offset="1" stop-color="var(--lp-accent-2)"/></linearGradient></defs><rect width="40" height="40" rx="11" fill="url(#lpmark)"/><path d="M7 21h7l3-8 5 15 3-9 2 2h6" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}
