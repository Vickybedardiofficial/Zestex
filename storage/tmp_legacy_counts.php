<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=colibriplus','root','');
$patterns = [
  'original_post' => '%Original Post:%',
  'context_strong' => '%Is post ka context strong%',
  'reshare' => '%Re-share kar raha hoon%',
  'interesting_take' => '%Interesting take.%',
  'mera_angle' => '%Mera angle thoda alag hai%'
];
foreach ($patterns as $k => $p) {
  $st = $pdo->prepare("SELECT COUNT(*) c FROM posts WHERE status='published' AND content LIKE ?");
  $st->execute([$p]);
  $c = $st->fetch(PDO::FETCH_ASSOC)['c'];
  echo $k . '=' . $c . PHP_EOL;
}
