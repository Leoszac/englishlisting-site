<?php
// Prosody Coach waitlist. Stores email + UTC date + source page in a CSV
// OUTSIDE the web root (~/el-waitlist/). Never shared or sold.
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Location: /prosody-coach/', true, 303); exit; }
// Honeypot: bots fill the hidden "website" field.
if (trim((string)($_POST['website'] ?? '')) !== '') { header('Location: /prosody-coach/joined/', true, 303); exit; }
$email = strtolower(trim((string)($_POST['email'] ?? '')));
if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) { header('Location: /prosody-coach/?error=email', true, 303); exit; }
$src = (string)parse_url((string)($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH);
$src = substr(preg_replace('~[^a-zA-Z0-9/_.-]~', '', $src), 0, 200);
$dir = dirname(__DIR__, 2) . '/el-waitlist';
if (!is_dir($dir)) { @mkdir($dir, 0700, true); }
$fh = @fopen($dir . '/emails.csv', 'c+');
if (!$fh) { http_response_code(500); echo 'Sorry, something went wrong. Please try again later.'; exit; }
flock($fh, LOCK_EX);
$exists = false; $n = 0;
while (($row = fgetcsv($fh)) !== false) { $n++; if (($row[0] ?? '') === $email) { $exists = true; break; } }
if (!$exists && $n < 100000) { fseek($fh, 0, SEEK_END); fputcsv($fh, [$email, gmdate('Y-m-d H:i'), $src]); fflush($fh); }
flock($fh, LOCK_UN); fclose($fh);
header('Location: /prosody-coach/joined/', true, 303);
