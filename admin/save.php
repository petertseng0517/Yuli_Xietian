<?php
require_once __DIR__ . '/config.php';

session_name(SESSION_NAME);
session_start();

// Must be logged in
if (empty($_SESSION['logged_in'])) {
    http_response_code(403);
    exit('Forbidden');
}

// CSRF check
if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf'])) {
    http_response_code(403);
    exit('Invalid token');
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$action = $_POST['action'] ?? '';

// Load current data
$data = ['posts' => []];
if (file_exists(NEWS_FILE)) {
    $raw = file_get_contents(NEWS_FILE);
    $decoded = json_decode($raw, true);
    if ($decoded && isset($decoded['posts'])) {
        $data = $decoded;
    }
}

if ($action === 'add') {
    $title   = trim($_POST['title']   ?? '');
    $date    = trim($_POST['date']    ?? '');
    $link    = trim($_POST['link']    ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $date === '') {
        header('Location: index.php?error=empty');
        exit;
    }

    // Validate date format YYYY-MM-DD
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        header('Location: index.php?error=date');
        exit;
    }

    $data['posts'][] = [
        'id'      => time(),
        'date'    => $date,
        'title'   => $title,
        'content' => $content,
        'link'    => $link,
    ];

} elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $data['posts'] = array_values(
        array_filter($data['posts'], fn($p) => (int)$p['id'] !== $id)
    );

} else {
    header('Location: index.php');
    exit;
}

// Write back
file_put_contents(
    NEWS_FILE,
    json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

header('Location: index.php?ok=1');
exit;
