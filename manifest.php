<?php
/**
 * Dynamic Web App Manifest
 * Serves site-configured name, theme colors, and icons.
 */
header('Content-Type: application/manifest+json; charset=utf-8');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$settings = loadSiteSettings($pdo);

$site_name = $settings['site_name'] ?? 'ASO Online Market';
$short_name = mb_strimwidth($site_name, 0, 15, '');
$primary_color = $settings['primary_color'] ?? '#E8002D';

$manifest = [
    "name" => $site_name,
    "short_name" => $short_name,
    "description" => $settings['site_description'] ?? "Discover great deals with nationwide delivery on " . $site_name,
    "start_url" => "index.php",
    "scope" => ".",
    "display" => "standalone",
    "background_color" => "#ffffff",
    "theme_color" => $primary_color,
    "orientation" => "portrait",
    "icons" => [
        [
            "src" => "assets/images/logo-rounded.png?v=3",
            "sizes" => "192x192",
            "type" => "image/png",
            "purpose" => "any"
        ],
        [
            "src" => "assets/images/logo-rounded.png?v=3",
            "sizes" => "512x512",
            "type" => "image/png",
            "purpose" => "any"
        ],
        [
            "src" => "assets/images/logo-rounded.png?v=3",
            "sizes" => "512x512",
            "type" => "image/png",
            "purpose" => "maskable"
        ]
    ],
    "categories" => ["shopping", "technology"]
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
