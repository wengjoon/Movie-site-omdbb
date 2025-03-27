<?php

require __DIR__.'/vendor/autoload.php';

$app = new Illuminate\Foundation\Application(__DIR__);

// Check file storage configuration
echo "Checking filesystem configuration...\n";
var_dump($app->make('config')->get('filesystems'));

echo "\nChecking available disks...\n";
$filesystemManager = new Illuminate\Filesystem\FilesystemManager($app);
try {
    var_dump(array_keys($filesystemManager->getDrivers()));
} catch (Exception $e) {
    echo "Error getting disks: " . $e->getMessage() . "\n";
}

echo "\nChecking service providers...\n";
$providers = $app->make('config')->get('app.providers');
foreach ($providers as $provider) {
    echo "- $provider\n";
}