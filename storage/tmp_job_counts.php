<?php
$pdo=new PDO('mysql:host=127.0.0.1;dbname=colibriplus','root','');
$jobs=$pdo->query('SELECT COUNT(*) c FROM jobs')->fetch(PDO::FETCH_ASSOC)['c'];
$failed=$pdo->query('SELECT COUNT(*) c FROM failed_jobs')->fetch(PDO::FETCH_ASSOC)['c'];
echo "jobs={$jobs}\nfailed_jobs={$failed}\n";
