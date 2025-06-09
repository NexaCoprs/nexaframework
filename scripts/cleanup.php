<?php
/**
 * Script de nettoyage et d'organisation du projet Nexa Framework
 */

echo "=== Nettoyage du projet Nexa Framework ===\n";

// Supprimer les fichiers temporaires
$tempFiles = [
    'storage/test_report_*.json',
    'public/test.php',
    'public/debug_doc.php'
];

foreach ($tempFiles as $pattern) {
    $files = glob($pattern);
    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
            echo "Supprimé: $file\n";
        }
    }
}

// Créer les dossiers manquants
$directories = [
    'storage/app',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'bootstrap/cache',
    'public/assets',
    'public/css',
    'public/js'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Créé: $dir\n";
    }
}

echo "\n=== Nettoyage terminé ===\n";