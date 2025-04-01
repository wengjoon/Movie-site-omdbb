<?php

// Define the application path
define('LARAVEL_PUBLIC_PATH', __DIR__ . '/public');

// Change the current directory to public path
chdir(LARAVEL_PUBLIC_PATH);

// Require the original index.php from the public directory
require_once LARAVEL_PUBLIC_PATH . '/index.php';