<?php
/**
 * v2/partials/icons.php
 * Material Symbols Outlined (font Google) — beda pendekatan dari v1
 * yang pakai SVG custom. Ini persis yang dipakai desain Stitch asli:
 * <span class="material-symbols-outlined">nama_ikon</span>
 * Daftar nama ikon: https://fonts.google.com/icons
 */
function icon(string $name, string $extraClass = ''): string {
    $cls = trim('material-symbols-outlined ' . $extraClass);
    return '<span class="' . htmlspecialchars($cls, ENT_QUOTES) . '">' . htmlspecialchars($name, ENT_QUOTES) . '</span>';
}
