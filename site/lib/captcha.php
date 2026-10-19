<?php
// A small sum the visitor solves before the contact form is sent.
// The answer is never sent to the browser: the page gets a signed token, and
// contact.php checks the visitor's answer against that signature. No outside service is used.

// Filled in at deploy time with a random value, so tokens cannot be forged.
const CAPTCHA_SECRET = '__CAPTCHA_SECRET__';
const CAPTCHA_MIN_AGE = 3;      // seconds; people need at least this long to fill in the form
const CAPTCHA_MAX_AGE = 7200;   // seconds; after this a new sum is needed

function captcha_sign($issued, $nonce, $answer) {
    return hash_hmac('sha256', "$issued.$nonce.$answer", CAPTCHA_SECRET);
}

function captcha_new() {
    $a = random_int(1, 9);
    $b = random_int(1, 9);
    $issued = time();
    $nonce = bin2hex(random_bytes(8));
    return [
        'question' => "$a + $b",
        'token' => "$issued.$nonce." . captcha_sign($issued, $nonce, $a + $b),
    ];
}

// Each token can be used once, so a solved sum cannot be replayed.
function captcha_used_before($nonce) {
    $file = sys_get_temp_dir() . '/leanig-captcha-used';
    $handle = @fopen($file, 'c+');
    if (!$handle) {
        return false;
    }
    flock($handle, LOCK_EX);
    $now = time();
    $used = json_decode(stream_get_contents($handle) ?: '{}', true) ?: [];
    $used = array_filter($used, function ($t) use ($now) {
        return $t > $now - CAPTCHA_MAX_AGE;
    });
    $seen = isset($used[$nonce]);
    $used[$nonce] = $now;
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($used));
    flock($handle, LOCK_UN);
    fclose($handle);
    return $seen;
}

function captcha_check($token, $answer) {
    $parts = explode('.', (string)$token);
    $answer = trim((string)$answer);
    if (count($parts) !== 3 || !ctype_digit($parts[0]) || !ctype_xdigit($parts[1]) || !preg_match('/^\d{1,2}$/', $answer)) {
        return false;
    }
    [$issued, $nonce, $signature] = $parts;
    $age = time() - (int)$issued;
    if ($age < CAPTCHA_MIN_AGE || $age > CAPTCHA_MAX_AGE) {
        return false;
    }
    // A wrong answer uses up the token too, so a sum cannot be guessed by trying every number.
    if (captcha_used_before($nonce)) {
        return false;
    }
    return hash_equals(captcha_sign($issued, $nonce, (int)$answer), $signature);
}
