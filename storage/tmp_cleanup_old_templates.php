<?php
$pdo=new PDO('mysql:host=127.0.0.1;dbname=colibriplus','root','');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$postPatterns = [
  '%Original Post:%',
  '%Is post ka context strong%',
  '%Re-share kar raha hoon%',
  '%Interesting take.%',
  '%Mera angle thoda alag hai%'
];
$commentPatterns = [
  '%Fresh angle:%is moving fast right now%',
  '%Strategic angle:%is moving fast right now%',
  '%What metric should we track first to avoid noise%'
];

$postIds=[];
foreach($postPatterns as $p){
  $st=$pdo->prepare('SELECT id FROM posts WHERE content LIKE ?');
  $st->execute([$p]);
  while($r=$st->fetch(PDO::FETCH_ASSOC)){$postIds[(int)$r['id']]=true;}
}
$postIds=array_keys($postIds);
$deletedPosts=0; $unlinked=0;
if(!empty($postIds)){
  $in=implode(',',array_fill(0,count($postIds),'?'));
  $pdo->beginTransaction();
  $u=$pdo->prepare("UPDATE posts SET quote_post_id=NULL, is_quoting=0 WHERE quote_post_id IN ($in)");
  $u->execute($postIds); $unlinked=$u->rowCount();
  $d=$pdo->prepare("DELETE FROM posts WHERE id IN ($in)");
  $d->execute($postIds); $deletedPosts=$d->rowCount();
  $pdo->commit();
}

$deletedComments=0;
foreach($commentPatterns as $p){
  $d=$pdo->prepare('DELETE FROM comments WHERE content LIKE ?');
  $d->execute([$p]);
  $deletedComments += $d->rowCount();
}

echo "deleted_posts={$deletedPosts}\nunlinked={$unlinked}\ndeleted_comments={$deletedComments}\n";
