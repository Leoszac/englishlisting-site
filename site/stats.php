<?php
// Aggregate, anonymous daily counts from hit.php (last 60 days). No personal data.
header('Cache-Control: no-store');
header('Content-Type: application/json');
header('X-Robots-Tag: noindex');
$dir = dirname(__DIR__) . '/el-stats';
$out = ['days' => [], 'top_paths_30d' => []]; $paths = [];
for ($i = 59; $i >= 0; $i--) {
  $day = gmdate('Y-m-d', time() - $i * 86400);
  $f = "$dir/$day.json"; if (!is_file($f)) continue;
  $d = json_decode((string)file_get_contents($f), true) ?: [];
  $out['days'][$day] = ['views' => $d['views'] ?? 0, 'entries' => $d['entries'] ?? 0, 'refs' => $d['refs'] ?? []];
  if ($i < 30) foreach (($d['paths'] ?? []) as $p => $n) { $paths[$p] = ($paths[$p] ?? 0) + $n; }
}
arsort($paths); $out['top_paths_30d'] = array_slice($paths, 0, 50, true);
$wl = dirname(__DIR__) . '/el-waitlist/emails.csv';
$out['waitlist_signups'] = is_file($wl) ? count(file($wl, FILE_SKIP_EMPTY_LINES)) : 0;
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
