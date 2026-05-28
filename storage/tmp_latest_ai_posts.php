<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=colibriplus','root','');
$st = $pdo->query("SELECT id, LEFT(REPLACE(REPLACE(content, '\n', ' '), '\r',' '), 180) AS c FROM posts WHERE is_ai_generated=1 AND status='active' ORDER BY id DESC LIMIT 12");
while($r=$st->fetch(PDO::FETCH_ASSOC)){echo '#'.$r['id'].' '.$r['c'].PHP_EOL;}

echo "--- dup top ---".PHP_EOL;
$dup = $pdo->query("SELECT LEFT(content,120) snippet, COUNT(*) c FROM posts WHERE is_ai_generated=1 AND status='active' GROUP BY content ORDER BY c DESC LIMIT 5");
while($d=$dup->fetch(PDO::FETCH_ASSOC)){echo $d['c'].'x | '.str_replace(["\n","\r"],' ',$d['snippet']).PHP_EOL;}
