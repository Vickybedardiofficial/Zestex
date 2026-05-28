<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=colibriplus','root','');
$q1=$pdo->query("SELECT COUNT(*) c FROM comments WHERE created_at >= NOW() - INTERVAL 1 HOUR");
$q2=$pdo->query("SELECT COUNT(*) c FROM comments c JOIN posts p ON p.id=c.post_id WHERE c.created_at >= NOW() - INTERVAL 1 HOUR AND p.is_ai_generated=1");
$q3=$pdo->query("SELECT c.id, c.created_at, LEFT(REPLACE(REPLACE(c.content,'\n',' '),'\r',' '),180) content FROM comments c ORDER BY c.id DESC LIMIT 8");
echo 'comments_last_hour='.$q1->fetch(PDO::FETCH_ASSOC)['c'].PHP_EOL;
echo 'comments_on_ai_last_hour='.$q2->fetch(PDO::FETCH_ASSOC)['c'].PHP_EOL;
while($r=$q3->fetch(PDO::FETCH_ASSOC)){echo '#'.$r['id'].' ['.$r['created_at'].'] '.$r['content'].PHP_EOL;}
