<?php
$pdo=new PDO('mysql:host=127.0.0.1;dbname=colibriplus','root','');
$st=$pdo->query("SELECT id, created_at, content FROM posts WHERE is_ai_generated=1 ORDER BY id DESC LIMIT 3");
while($r=$st->fetch(PDO::FETCH_ASSOC)){
  echo '#'.$r['id'].' ['.$r['created_at'].']'.PHP_EOL;
  echo str_replace(["\r"],[''],$r['content']).PHP_EOL.'---'.PHP_EOL;
}
