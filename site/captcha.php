<?php
// Gives the contact form a new sum to solve (see lib/captcha.php).
require __DIR__ . '/lib/captcha.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode(captcha_new());
