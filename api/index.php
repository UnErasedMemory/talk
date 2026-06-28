<?php

if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL'])) {
    $storage = '/tmp/storage';
    $dirs = [
        "$storage/app/public",
        "$storage/framework/cache/data",
        "$storage/framework/sessions",
        "$storage/framework/testing",
        "$storage/framework/views",
        "$storage/logs",
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

require __DIR__ . '/../public/index.php';
