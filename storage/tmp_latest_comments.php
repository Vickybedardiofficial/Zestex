<?php
$pdo=new PDO('mysql:host=127.0.0.1;dbname=colibriplus','root','');
$st=$pdo->query("SELECT c.id,c.created_at,LEFT(REPLACE(REPLACE(c.content,'\n',' '),'\r',' '),240) content,p.id post_id FROM comments c JOIN posts p ON p.id=c.post_id ORDER BY c.id DESC LIMIT 12");
while($r=$st->fetch(PDO::FETCH_ASSOC)){echo '#'.$r['id'].' p'.$r['post_id'].' ['.$r['created_at'].'] '.$r['content'].PHP_EOL;}
