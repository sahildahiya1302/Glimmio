<?php
require_once 'db.php';
session_start();

function respond($s,$d=null,$m=''){header('Content-Type: application/json');echo json_encode(['success'=>$s,'data'=>$d,'message'=>$m]);exit;}

if(!isset($_SESSION['user_id'])){
    respond(false,null,'Unauthorized');
}

$pdo=db_connect();
$action=$_GET['action'] ?? 'list';

if($action==='list'){
    $stmt=$pdo->query('SELECT cp.*, IF(cp.role="brand",b.company_name,i.username) AS author, cp.like_count FROM community_posts cp LEFT JOIN brands b ON cp.role="brand" AND cp.author_id=b.id LEFT JOIN influencers i ON cp.role="influencer" AND cp.author_id=i.id ORDER BY cp.created_at DESC LIMIT 50');
    respond(true,$stmt->fetchAll(PDO::FETCH_ASSOC));
}

if($action==='post' && $_SERVER['REQUEST_METHOD']==='POST'){
    $content=trim($_POST['content'] ?? '');
    if($content==='') respond(false,null,'Content required');
    $stmt=$pdo->prepare('INSERT INTO community_posts (author_id,role,content) VALUES (?,?,?)');
    $stmt->execute([$_SESSION['user_id'], $_SESSION['role'], $content]);
    respond(true,null,'Posted');
}

if($action==='like' && $_SERVER['REQUEST_METHOD']==='POST'){
    $post=intval($_POST['post_id'] ?? 0);
    if(!$post) respond(false,null,'Invalid post');
    $uid=$_SESSION['user_id'];
    $role=$_SESSION['role'];
    $check=$pdo->prepare('SELECT id FROM community_likes WHERE post_id=? AND user_id=? AND role=?');
    $check->execute([$post,$uid,$role]);
    if($row=$check->fetch()){
        $pdo->prepare('DELETE FROM community_likes WHERE id=?')->execute([$row['id']]);
        $pdo->prepare('UPDATE community_posts SET like_count=GREATEST(like_count-1,0) WHERE id=?')->execute([$post]);
        respond(true,null,'unliked');
    }else{
        $pdo->prepare('INSERT INTO community_likes (post_id,user_id,role) VALUES (?,?,?)')->execute([$post,$uid,$role]);
        $pdo->prepare('UPDATE community_posts SET like_count=like_count+1 WHERE id=?')->execute([$post]);
        respond(true,null,'liked');
    }
}

respond(false,null,'Invalid request');
?>
