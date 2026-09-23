<?php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
header('Content-Type: application/json');

if ($path === '/slow') {
    sleep(2);
}

if ($path === '/retry') {
    $counter = $_GET['counter'];
    $attempt = is_file($counter) ? (int) file_get_contents($counter) + 1 : 1;
    file_put_contents($counter, $attempt);
    http_response_code($attempt < 3 ? (int) ($_GET['code'] ?? 503) : 200);
    echo json_encode(['attempt' => $attempt]);

    return;
}

if ($path === '/status') {
    http_response_code((int) $_GET['code']);
}

if ($path === '/redirect') {
    header('Location: /success', true, 302);
}

echo json_encode(['ok' => true, 'accept' => $_SERVER['HTTP_ACCEPT'] ?? null]);
