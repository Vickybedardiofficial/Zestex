<?php
$pdo=new PDO('mysql:host=127.0.0.1;dbname=colibriplus','root','');
echo 'jobs='.$pdo->query('SELECT COUNT(*) c FROM jobs')->fetch(PDO::FETCH_ASSOC)['c'].PHP_EOL;
