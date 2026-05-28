<?php
$pdo=new PDO('mysql:host=127.0.0.1;dbname=colibriplus','root','');
$r=$pdo->query("SELECT id, LEFT(content,220) c, created_at FROM comments ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo '#'.$r['id'].' ['.$r['created_at'].'] '.$r['c'].PHP_EOL;
