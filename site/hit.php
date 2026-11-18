<?php
// Cookieless, anonymous page-view counter. Stores only daily aggregate counts
// (page path + referrer category) outside the web root. No IPs, no user agents,
// no cookies, no identifiers.
header('Cache-Control: no-store');
header('Content-Type: text/plain');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }
$path = substr(preg_replace('~[^a-zA-Z0-9/_.-]~', '', (string)($_POST['p'] ?? '')), 0, 200);
if ($path === '' || $path[0] !== '/') { http_response_code(400); exit; }
$host = strtolower((string)parse_url((string)($_POST['r'] ?? ''), PHP_URL_HOST));
$buckets = ['google' => 'google', 'bing' => 'bing', 'duckduckgo' => 'duckduckgo', 'yahoo' => 'yahoo',
  'chatgpt' => 'chatgpt', 'openai' => 'chatgpt', 'perplexity' => 'perplexity', 'gemini' => 'gemini',
  'copilot' => 'copilot', 'claude' => 'claude', 'facebook' => 'social', 'instagram' => 'social',
  'reddit' => 'social', 't.co' => 'social', 'linkedin' => 'social', 'youtube' => 'social'];
$ref = 'direct'; $internal = false;
if ($host !== '') {
  if (preg_match('~(^|\.)englishlisting\.com$~', $host)) { $internal = true; $ref = 'internal'; }
  else { $ref = 'other'; foreach ($buckets as $k => $v) { if (strpos($host, $k) !== false) { $ref = $v; break; } } }
}
$dir = dirname(__DIR__) . '/el-stats';
if (!is_dir($dir)) { @mkdir($dir, 0700, true); }
$fh = @fopen($dir . '/' . gmdate('Y-m-d') . '.json', 'c+');
if (!$fh) { http_response_code(500); exit; }
flock($fh, LOCK_EX);
$d = json_decode(stream_get_contents($fh) ?: '{}', true) ?: [];
$d['views'] = ($d['views'] ?? 0) + 1;
if (!$internal) { $d['entries'] = ($d['entries'] ?? 0) + 1; }
$d['refs'][$ref] = ($d['refs'][$ref] ?? 0) + 1;
if (isset($d['paths'][$path]) || count($d['paths'] ?? []) < 3000) { $d['paths'][$path] = ($d['paths'][$path] ?? 0) + 1; }
ftruncate($fh, 0); rewind($fh); fwrite($fh, json_encode($d)); fflush($fh);
flock($fh, LOCK_UN); fclose($fh);
http_response_code(204);
