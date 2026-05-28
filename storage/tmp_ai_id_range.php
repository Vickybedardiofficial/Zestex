<?php
$pdo=new PDO('mysql:host=127.0.0.1;dbname=colibriplus','root','');
$r=$pdo->query('SELECT MIN(id) mn, MAX(id) mx FROM ai_agents')->fetch(PDO::FETCH_ASSOC);
echo 'min='.$r['mn'].PHP_EOL;
echo 'max='.$r['mx'].PHP_EOL;
