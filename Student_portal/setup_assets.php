<?php
// Create directories if they don't exist
$directories = [
    'assets/css',
    'assets/js',
    'assets/fonts',
    'assets/images',
    'assets/webfonts'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// URLs for the required files
$files = [
    // CSS files
    'assets/css/bootstrap.min.css' => 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css',
    'assets/css/all.min.css' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
    
    // JavaScript files
    'assets/js/jquery.min.js' => 'https://code.jquery.com/jquery-3.5.1.min.js',
    'assets/js/popper.min.js' => 'https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js',
    'assets/js/bootstrap.min.js' => 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js',
    
    // Font Awesome files
    'assets/webfonts/fa-solid-900.woff2' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-solid-900.woff2',
    'assets/webfonts/fa-solid-900.woff' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-solid-900.woff',
    'assets/webfonts/fa-solid-900.ttf' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-solid-900.ttf',
    'assets/webfonts/fa-solid-900.eot' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-solid-900.eot',
    'assets/webfonts/fa-regular-400.woff2' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-regular-400.woff2',
    'assets/webfonts/fa-regular-400.woff' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-regular-400.woff',
    'assets/webfonts/fa-regular-400.ttf' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-regular-400.ttf',
    'assets/webfonts/fa-regular-400.eot' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-regular-400.eot',
];

// Download files
foreach ($files as $local => $url) {
    if (!file_exists($local)) {
        echo "Downloading $local...\n";
        $content = file_get_contents($url);
        if ($content !== false) {
            file_put_contents($local, $content);
            echo "Successfully downloaded $local\n";
        } else {
            echo "Failed to download $local\n";
        }
    } else {
        echo "$local already exists\n";
    }
}

// Create default avatar
$default_avatar = 'assets/images/default-avatar.svg';
if (!file_exists($default_avatar)) {
    $svg_content = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="200" height="200" version="1.1" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
 <circle cx="100" cy="100" r="100" fill="#e0e0e0"/>
 <circle cx="100" cy="85" r="35" fill="#9e9e9e"/>
 <path d="m100 130c-40 0-60 30-60 30h120c0 0-20-30-60-30z" fill="#9e9e9e"/>
</svg>';
    file_put_contents($default_avatar, $svg_content);
    echo "Created default avatar\n";
}

echo "Setup completed!\n";
?> 