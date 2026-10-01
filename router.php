<?php
// PHP Built-in Server Router for FireZone Store
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

// Serve existing static file (css, js, images, svg, etc.)
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

// Directory index
if (is_dir($file)) {
    $index = rtrim($file, '/') . '/index.php';
    if (file_exists($index)) {
        require $index;
        exit;
    }
}

// Route extension-less URLs (e.g. /dashboard -> /dashboard.php)
if (file_exists($file . '.php')) {
    require $file . '.php';
    exit;
}

// Default root or fallback
if ($uri === '/' || $uri === '/index') {
    require __DIR__ . '/index.php';
    exit;
}

// 404 page
http_response_code(404);
echo "<!DOCTYPE html><html lang='en' class='dark'><head><title>404 Not Found</title><script src='https://cdn.tailwindcss.com'></script></head><body class='bg-[#0a0a0f] text-white flex items-center justify-center min-h-screen'><div class='text-center p-8'><h1 class='text-6xl font-bold text-red-500 mb-4'>404</h1><p class='text-zinc-400 mb-6'>The requested page was not found.</p><a href='/index.php' class='bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-lg'>Back to Store</a></div></body></html>";
exit;
