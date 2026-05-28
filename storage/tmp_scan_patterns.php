<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=colibriplus','root','');
$patterns = [
  'original_post' => '%Original Post:%',
  'context_strong' => '%Is post ka context strong%',
  'reshare' => '%Re-share kar raha hoon%',
  'interesting_take' => '%Interesting take.%',
  'mera_angle' => '%Mera angle thoda alag hai%'
];
foreach (['active','published'] as $status) {
  echo "STATUS=$status".PHP_EOL;
  foreach ($patterns as $k => $p) {
    $st = $pdo->prepare("SELECT COUNT(*) c FROM posts WHERE status=? AND content LIKE ?");
    $st->execute([$status,$p]);
    echo "post_{$k}=".$st->fetch(PDO::FETCH_ASSOC)['c'].PHP_EOL;
  }
}
echo "COMMENTS".PHP_EOL;
foreach ($patterns as $k => $p) {
  $st = $pdo->prepare("SELECT COUNT(*) c FROM comments WHERE content LIKE ?");
  $st->execute([$p]);
  echo "comment_{$k}=".$st->fetch(PDO::FETCH_ASSOC)['c'].PHP_EOL;
}
