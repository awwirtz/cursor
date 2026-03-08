<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

function read_payload(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
    return $_POST;
}

function limit_text(string $value, int $limit): string
{
    $trimmed = trim($value);
    if (function_exists('mb_substr')) {
        return mb_substr($trimmed, 0, $limit);
    }
    return substr($trimmed, 0, $limit);
}

function json_fail(int $status, string $error): void
{
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $error]);
    exit;
}

function ratelimit(): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $slot = (int) floor(time() / 60);
    $key = hash('sha256', $ip . ':' . (string) $slot);
    $file = sys_get_temp_dir() . '/aw_contact_' . $key;

    if (is_file($file)) {
        json_fail(429, 'too_many_requests');
    }

    @file_put_contents($file, '1');
}

$payload = read_payload();

$name = limit_text((string) ($payload['name'] ?? ''), 120);
$email = limit_text((string) ($payload['email'] ?? ''), 190);
$message = limit_text((string) ($payload['message'] ?? ''), 5000);
$website = trim((string) ($payload['website'] ?? ''));
$lang = strtolower(limit_text((string) ($payload['lang'] ?? 'fr'), 2));

if ($website !== '') {
    json_fail(400, 'bot_detected');
}

if ($name === '' || $email === '' || $message === '') {
    json_fail(400, 'missing_fields');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_fail(400, 'invalid_email');
}

ratelimit();

$to = 'aurelienwirtz67@gmail.com';
$subject = $lang === 'en' ? 'New project inquiry - website form' : 'Nouvelle demande projet - formulaire site';
$host = $_SERVER['HTTP_HOST'] ?? 'aurelienwirtz.com';
$safeHost = preg_replace('/[^a-zA-Z0-9.\-]/', '', $host);
if (!is_string($safeHost) || $safeHost === '') {
    $safeHost = 'aurelienwirtz.com';
}

$from = 'no-reply@' . $safeHost;
$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: Aurelien Wirtz Site <' . $from . '>',
    'Reply-To: ' . $email
];

$bodyLines = [
    'Name: ' . $name,
    'Email: ' . $email,
    'Language: ' . $lang,
    'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
    '',
    'Message:',
    $message
];
$body = implode("\n", $bodyLines);

$sent = @mail($to, $subject, $body, implode("\r\n", $headers));
if (!$sent) {
    json_fail(500, 'mail_failed');
}

echo json_encode(['ok' => true]);
