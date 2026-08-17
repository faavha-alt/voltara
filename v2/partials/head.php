<?php
/**
 * v2/partials/head.php
 * ============================================================
 * Desain "Luminous Energy Interface" — glassmorphism, dari DESIGN.md
 * + code.html yang diberikan user (Google Stitch export ke-2).
 * Beda arah dari desain v2 sebelumnya (flat MD3 + topbar horizontal):
 * ini pakai sidebar kiri tetap, kartu kaca (glass-card), dan palet
 * warna baru (Solar Blue + Energetic Yellow).
 *
 * Data tetap dari sumber yang sama: /proxy.php dan /lib/metrics.php di
 * folder utama (root), dipanggil dengan path root-relative.
 */
if (!isset($page_title)) { $page_title = 'Voltara EMS v2'; }
require_once __DIR__ . '/icons.php';
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
?><!DOCTYPE html>
<html class="light" lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block" rel="stylesheet">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.0/dist/apexcharts.min.js"></script>
<script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "surface": "#f9f9ff",
                    "surface-dim": "#d8dae2",
                    "surface-bright": "#f9f9ff",
                    "surface-container-lowest": "#ffffff",
                    "surface-container-low": "#f2f3fc",
                    "surface-container": "#ecedf6",
                    "surface-container-high": "#e6e8f0",
                    "surface-container-highest": "#e1e2eb",
                    "on-surface": "#191c22",
                    "on-surface-variant": "#434654",
                    "inverse-surface": "#2d3037",
                    "inverse-on-surface": "#eff0f9",
                    "outline": "#747685",
                    "outline-variant": "#c3c6d6",
                    "surface-tint": "#2455cc",
                    "primary": "#1c50c7",
                    "on-primary": "#ffffff",
                    "primary-container": "#3e6ae1",
                    "on-primary-container": "#f9f7ff",
                    "inverse-primary": "#b4c4ff",
                    "secondary": "#7f5600",
                    "on-secondary": "#ffffff",
                    "secondary-container": "#feb31f",
                    "on-secondary-container": "#6b4800",
                    "tertiary": "#0058b1",
                    "on-tertiary": "#ffffff",
                    "tertiary-container": "#1d70d6",
                    "on-tertiary-container": "#f7f8ff",
                    "error": "#ba1a1a",
                    "on-error": "#ffffff",
                    "error-container": "#ffdad6",
                    "on-error-container": "#93000a",
                    "primary-fixed": "#dbe1ff",
                    "primary-fixed-dim": "#b4c4ff",
                    "on-primary-fixed": "#00174c",
                    "on-primary-fixed-variant": "#003dab",
                    "secondary-fixed": "#ffddaf",
                    "secondary-fixed-dim": "#ffba41",
                    "on-secondary-fixed": "#281800",
                    "on-secondary-fixed-variant": "#614000",
                    "tertiary-fixed": "#d7e3ff",
                    "tertiary-fixed-dim": "#aac7ff",
                    "on-tertiary-fixed": "#001b3e",
                    "on-tertiary-fixed-variant": "#00458e",
                    "background": "#f9f9ff",
                    "on-background": "#191c22",
                    "surface-variant": "#e1e2eb",
                    "surface-background": "#F7F8FA",
                    "status-success": "#2DBC4F",
                    "status-warning": "#FBB01B",
                    "status-error": "#E02424",
                    "glass-fill": "rgba(255, 255, 255, 0.7)",
                    "glass-border": "rgba(255, 255, 255, 0.4)"
                },
                borderRadius: { DEFAULT: "0.25rem", lg: "0.5rem", xl: "0.75rem", "2xl": "1.5rem", full: "9999px" },
                spacing: { "sidebar-width": "280px", unit: "8px", "container-padding-desktop": "40px", "container-padding-mobile": "20px", "card-gap": "24px" },
                fontFamily: {
                    "label-sm": ["Inter"], "title-md": ["Inter"], "label-md": ["Inter"], "headline-lg-mobile": ["Inter"],
                    "body-lg": ["Inter"], "headline-lg": ["Inter"], "display-md": ["Inter"], "display-lg": ["Inter"], "body-md": ["Inter"]
                },
                fontSize: {
                    "label-sm": ["12px", {lineHeight:"16px", letterSpacing:"0.05em", fontWeight:"600"}],
                    "title-md": ["20px", {lineHeight:"28px", fontWeight:"600"}],
                    "label-md": ["14px", {lineHeight:"20px", letterSpacing:"0.01em", fontWeight:"500"}],
                    "headline-lg-mobile": ["24px", {lineHeight:"32px", fontWeight:"600"}],
                    "body-lg": ["18px", {lineHeight:"28px", fontWeight:"400"}],
                    "headline-lg": ["28px", {lineHeight:"36px", letterSpacing:"-0.01em", fontWeight:"600"}],
                    "display-md": ["36px", {lineHeight:"44px", letterSpacing:"-0.02em", fontWeight:"700"}],
                    "display-lg": ["48px", {lineHeight:"56px", letterSpacing:"-0.02em", fontWeight:"700"}],
                    "body-md": ["16px", {lineHeight:"24px", fontWeight:"400"}]
                }
            }
        }
    }
</script>
<style>
    body { font-family: 'Inter', sans-serif; background-color: #F7F8FA; }
    .glass-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.4);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        border-radius: 24px;
    }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    .flow-dot { offset-path: var(--path); animation: flow-dist 3s infinite linear; }
    @keyframes flow-dist { 0% {offset-distance:0%; opacity:0;} 10% {opacity:1;} 90% {opacity:1;} 100% {offset-distance:100%; opacity:0;} }
    .gauge-svg circle { transition: stroke-dashoffset 1s ease-out; }
</style>
</head>
<body class="text-on-surface">