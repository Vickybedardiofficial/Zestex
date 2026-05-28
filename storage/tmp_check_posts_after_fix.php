<?php
$pdo=new PDO('mysql:host=127.0.0.1;dbname=colibriplus','root','');
$st=$pdo->query("SELECT id, created_at, LEFT(REPLACE(REPLACE(content,'\n',' '),'\r',' '),320) c FROM posts WHERE is_ai_generated=1 ORDER BY id DESC LIMIT 8");
while($r=$st->fetch(PDO::FETCH_ASSOC)){echo '#'.$r['id'].' ['.$r['created_at'].'] '.$r['c'].PHP_EOL;}
