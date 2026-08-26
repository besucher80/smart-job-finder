<?php

declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;

    return;
}

spl_autoload_register(static function (string $class): void {
    $map = [
        'Agentur\\SmartJobFinder\\Tests\\' => __DIR__ . '/',
        'Agentur\\SmartJobFinder\\' => dirname(__DIR__) . '/Classes/',
    ];

    foreach ($map as $prefix => $base) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }

        return;
    }
});
