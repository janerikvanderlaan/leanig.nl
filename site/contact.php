<?php
// Receives the contact form on leanig.nl and emails it to LEANIG.
// Answers JSON to the page's script, or redirects back when JavaScript is off.

// Filled in at deploy time from the CONTACT_EMAIL secret, so the address is not public in this repository.
const RECIPIENT = '__CONTACT_EMAIL__';
const SENDER = 'website@leanig.nl';

function clean($value, $max) {
    $value = trim((string)($value ?? ''));
    $value = str_replace(["\r", "\0"], '', $value);
    return mb_substr($value, 0, $max);
}

function finish($ok, $status = 200) {
    $wantsJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    if ($wantsJson) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['ok' => $ok]);
    } else {
        header('Location: /' . ($ok ? '?sent=1' : '') . '#contact', true, 303);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    finish(false, 405);
}

// Bots fill in the hidden "website" field; people never see it.
if (clean($_POST['website'] ?? '', 200) !== '') {
    finish(true);
}

$first   = clean($_POST['first'] ?? '', 100);
$last    = clean($_POST['last'] ?? '', 100);
$email   = clean($_POST['email'] ?? '', 200);
$subject = clean($_POST['subject'] ?? '', 200);
$message = trim(mb_substr((string)($_POST['message'] ?? ''), 0, 5000));

if ($first === '' || $last === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    finish(false, 422);
}

$name = str_replace("\n", ' ', "$first $last");
$mailSubject = 'leanig.nl contact: ' . ($subject !== '' ? str_replace("\n", ' ', $subject) : $name);
$body = "Naam: $name\nE-mail: $email\nOnderwerp: $subject\n\n$message\n";
$headers = [
    'From: LEANIG website <' . SENDER . '>',
    'Reply-To: ' . $email,
    'Content-Type: text/plain; charset=UTF-8',
];

$sent = mail(
    RECIPIENT,
    '=?UTF-8?B?' . base64_encode($mailSubject) . '?=',
    $body,
    implode("\r\n", $headers)
);

finish($sent, $sent ? 200 : 500);
