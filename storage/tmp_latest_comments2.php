<?php
$pdo=new PDO('mysql:host=127.0.0.1;dbname=colibriplus','root','');
$r=$pdo->query("SELECT id, created_at, LEFT(content,260) c FROM comments ORDER BY id DESC LIMIT 3");
while($x=$r->fetch(PDO::FETCH_ASSOC)){echo '#'.$x['id'].' ['.$x['created_at'].'] '.$x['c'].PHP_EOL;}
