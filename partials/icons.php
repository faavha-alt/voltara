<?php
/**
 * partials/icons.php
 * ============================================================
 * Kenapa ini ada: emoji (☀️🔋⚡💰 dst) sebelumnya sering tampil
 * sebagai "??" di sebagian browser/HP — tergantung font emoji sistem
 * dan encoding yang dikirim server. Source code-nya sudah UTF-8 valid,
 * tapi rendering di sisi client tetap tidak bisa dijamin 100%.
 *
 * SVG inline TIDAK punya masalah itu sama sekali: dia digambar
 * langsung oleh browser dari path vector, tidak bergantung pada font
 * emoji apapun. Jadi solusi permanen untuk masalah "??" adalah
 * berhenti pakai emoji, pakai SVG kecil sebagai gantinya.
 *
 * Pemakaian:
 *   <?= icon('sun', 18) ?>
 *   <?= icon('battery', 20, 'color:var(--mint)') ?>
 * ============================================================
 */
function icon(string $name, int $size = 18, string $style = ''): string {
    $strokeAttrs = 'fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"';
    $paths = [
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
        'battery' => '<rect x="3" y="7" width="15" height="10" rx="2"/><path d="M18 10.5h2.5v3H18z" fill="currentColor" stroke="none"/><path d="M7 12h5"/>',
        'bolt' => '<path d="M12.5 2 4 14h6l-1.5 8L20 10h-6z"/>',
        'leaf' => '<path d="M4 20c8 1 15-5 16-16-11 0-16 6-16 16Z"/><path d="M5 19c3-4 7-7 12-9"/>',
        'coin' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 8v8M9.5 10.2c0-1.3 1.1-2.2 2.5-2.2s2.5.8 2.5 2c0 2.4-5 1.4-5 3.8 0 1.2 1.1 2.2 2.5 2.2s2.5-.9 2.5-2.2"/>',
        'grid' => '<path d="M4 21V9l4-4 4 4v12"/><path d="M12 21V9l4-4 4 4v12"/><path d="M2 21h20"/>',
        'home' => '<path d="M4 11.5 12 4l8 7.5"/><path d="M6 10v10h12V10"/>',
        'plug' => '<path d="M9 2v5M15 2v5M7 7h10v4a5 5 0 0 1-5 5 5 5 0 0 1-5-5Z"/><path d="M12 16v6"/>',
        'chart' => '<path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/>',
        'droplet' => '<path d="M12 2s7 8 7 13a7 7 0 0 1-14 0c0-5 7-13 7-13Z"/>',
        'bell' => '<path d="M6 16v-5a6 6 0 0 1 12 0v5l2 3H4Z"/><path d="M10 21a2 2 0 0 0 4 0"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'gauge' => '<path d="M12 20a8 8 0 1 0-8-8"/><path d="M12 12l4-3.5"/><path d="M4 20h16"/>',
        'wifi' => '<path d="M2 8.5a16 16 0 0 1 20 0"/><path d="M5.5 12.3a11 11 0 0 1 13 0"/><path d="M9 16a6 6 0 0 1 6 0"/><circle cx="12" cy="19.5" r="1" fill="currentColor" stroke="none"/>',
        'thermo' => '<path d="M10 14.5V5a2 2 0 1 1 4 0v9.5a4 4 0 1 1-4 0Z"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5"/>',
        'alert-triangle' => '<path d="M12 4 2 20h20Z"/><path d="M12 10v4"/><circle cx="12" cy="17.3" r="0.6" fill="currentColor" stroke="none"/>',
        'recycle' => '<path d="M7 19H4a2 2 0 0 1-1.7-3l1.6-2.7M17 19h3a2 2 0 0 0 1.7-3l-1.6-2.7M12 3l2.7 4.7M9.3 7.7 12 3M14.7 21l-2.7-4.7M9.3 16.3 12 21M4.9 9.3l2.7 4.7M9.3 7.7 4.9 9.3"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'arrow-down' => '<path d="M12 4v16M6 14l6 6 6-6"/>',
        'arrow-up' => '<path d="M12 20V4M6 10l6-6 6 6"/>',
        'moon' => '<path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z"/>',
        'cloud-sun' => '<circle cx="8" cy="9" r="3"/><path d="M8 3v1.2M3.5 6.5l.9.9M2 9h1.2M14 9h.5a3.5 3.5 0 0 1 0 7H8a4 4 0 0 1-.5-8"/>',
        'sparkle' => '<path d="M12 3l1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5Z"/>',
        'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
        'cpu' => '<rect x="6" y="6" width="12" height="12" rx="2"/><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/>',
        'shield' => '<path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6Z"/><path d="M9 12l2 2 4-4"/>',
        'activity' => '<path d="M2 12h4l2-7 4 14 3-9 2 4h5"/>',
        'mountain' => '<path d="M3 20 9 8l3.5 6L15 9l6 11Z"/><circle cx="7.5" cy="6" r="1.4" fill="currentColor" stroke="none"/>',
        'heart' => '<path d="M12 20s-7-4.5-9.5-9C.7 7.4 2 3.8 5.3 3.1 7.6 2.6 10 3.8 12 6c2-2.2 4.4-3.4 6.7-2.9 3.3.7 4.6 4.3 2.8 7.9C19 15.5 12 20 12 20Z"/>',
        'chevron-left' => '<path d="M15 6l-6 6 6 6"/>',
        'cloud' => '<path d="M6.5 18a4 4 0 0 1-.5-7.97A5.5 5.5 0 0 1 16.6 9 4.5 4.5 0 0 1 16 18Z"/>',
        'cloud-rain' => '<path d="M6.5 15a4 4 0 0 1-.5-7.97A5.5 5.5 0 0 1 16.6 6 4.5 4.5 0 0 1 16 15Z"/><path d="M8 19l-1 2M12 19l-1 2M16 19l-1 2"/>',
    ];
    $inner = $paths[$name] ?? $paths['sparkle'];
    $styleAttr = $style !== '' ? " style=\"{$style}\"" : '';
    return "<svg width=\"{$size}\" height=\"{$size}\" viewBox=\"0 0 24 24\"{$styleAttr} class=\"icn\"><g {$strokeAttrs}>{$inner}</g></svg>";
}