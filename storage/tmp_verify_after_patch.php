<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=colibriplus','root','');
$latest = $pdo->query("SELECT id, created_at, LEFT(REPLACE(REPLACE(content, '\n',' '), '\r',' '), 220) c FROM posts WHERE is_ai_generated=1 ORDER BY id DESC LIMIT 15");
while($r=$latest->fetch(PDO::FETCH_ASSOC)){echo '#'.$r['id'].' ['.$r['created_at'].'] '.$r['c'].PHP_EOL;}

$st = $pdo->query("SELECT COUNT(*) c FROM posts WHERE content LIKE '%Original Post:%' OR content LIKE '%Is post ka context strong%' OR content LIKE '%Re-share kar raha hoon%' OR content LIKE '%Interesting take.%' OR content LIKE '%Mera angle thoda alag hai%'");
echo 'legacy_total='.$st->fetch(PDO::FETCH_ASSOC)['c'].PHP_EOL;
