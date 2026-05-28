<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=colibriplus','root','');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$patterns = [
  '%Original Post:%',
  '%Is post ka context strong%',
  '%Re-share kar raha hoon%',
  '%Interesting take.%',
  '%Mera angle thoda alag hai%'
];

$ids = [];
foreach ($patterns as $p) {
  $st = $pdo->prepare("SELECT id FROM posts WHERE content LIKE ?");
  $st->execute([$p]);
  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $ids[(int)$r['id']] = true;
  }
}
$ids = array_keys($ids);
if (empty($ids)) {
  echo "matched=0\n";
  exit;
}

$in = implode(',', array_fill(0, count($ids), '?'));
$pdo->beginTransaction();

$unlink = $pdo->prepare("UPDATE posts SET quote_post_id=NULL, is_quoting=0 WHERE quote_post_id IN ($in)");
$unlink->execute($ids);
$unlinked = $unlink->rowCount();

$del = $pdo->prepare("DELETE FROM posts WHERE id IN ($in)");
$del->execute($ids);
$deleted = $del->rowCount();

$pdo->commit();

echo "matched=".count($ids)."\n";
echo "unlinked={$unlinked}\n";
echo "deleted={$deleted}\n";
