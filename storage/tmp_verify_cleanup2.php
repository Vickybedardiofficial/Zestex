<?php
$pdo=new PDO('mysql:host=127.0.0.1;dbname=colibriplus','root','');
$st=$pdo->query("SELECT COUNT(*) c FROM comments WHERE content LIKE '%is moving fast right now%' OR content LIKE '%What metric should we track first to avoid noise%'");
echo 'legacy_comment_pattern='.$st->fetch(PDO::FETCH_ASSOC)['c'].PHP_EOL;
$latest=$pdo->query("SELECT id, LEFT(content,220) c FROM comments ORDER BY id DESC LIMIT 5");
while($r=$latest->fetch(PDO::FETCH_ASSOC)){echo '#'.$r['id'].' '.$r['c'].PHP_EOL;}
