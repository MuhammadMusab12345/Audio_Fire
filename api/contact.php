<?php
/* ==========================================================================
   CONTACT API (api/contact.php) - used by contact.js
   --------------------------------------------------------------------------
   POST {name, email, subject, message, ts, af_company}

   Server side validation + honeypot + time throttle, prepared statement,
   messages land in the contact_messages table (database.sql).
   ========================================================================== */

require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    af_json(['ok' => false, 'error' => 'Unknown action.'], 404);
}
af_require_csrf();

$in = json_decode(file_get_contents('php://input') ?: '[]', true);
if (!is_array($in)) {
    $in = $_POST;
}

/* honeypot + minimum time + per-session throttle */
$guard = af_form_guard($in['ts'] ?? 0, $in[AF_FORM_HONEYPOT] ?? '', 'contact');
if ($guard) {
    af_json(['ok' => false, 'error' => $guard], 429);
}

/* field validation (mirrors the client checks) */
$name    = trim(strip_tags((string) ($in['name'] ?? '')));
$email   = trim((string) ($in['email'] ?? ''));
$subject = trim(strip_tags((string) ($in['subject'] ?? '')));
$message = trim(strip_tags((string) ($in['message'] ?? '')));

$errors = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 80)       $errors[] = 'Please enter your name (2 - 80 characters).';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 160) $errors[] = 'Please enter a valid email address.';
if (mb_strlen($subject) > 160)                            $errors[] = 'The subject is too long (max 160).';
if (mb_strlen($message) < 5 || mb_strlen($message) > 1000) $errors[] = 'Please write 5 - 1000 characters of message.';
if ($errors) {
    af_json(['ok' => false, 'error' => implode(' ', $errors)], 400);
}

if (!$GLOBALS['AF_DB']) {
    af_json(['ok' => false, 'error' => 'The contact inbox is offline right now - please try again later.'], 503);
}

af_run(
    'INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)',
    [mb_substr($name, 0, 80),
     mb_substr($email, 0, 160),
     $subject !== '' ? mb_substr($subject, 0, 160) : null,
     mb_substr($message, 0, 1000)]
);

af_json(['ok' => true, 'message' => 'Thank you! Your message has been sent.']);
