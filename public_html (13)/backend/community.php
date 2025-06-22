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
    $stmt=$pdo->query('SELECT cp.*, IF(cp.role="brand",b.company_name,i.username) AS author FROM community_posts cp LEFT JOIN brands b ON cp.role="brand" AND cp.author_id=b.id LEFT JOIN influencers i ON cp.role="influencer" AND cp.author_id=i.id ORDER BY cp.created_at DESC LIMIT 50');
    respond(true,$stmt->fetchAll(PDO::FETCH_ASSOC));
}

if($action==='post' && $_SERVER['REQUEST_METHOD']==='POST'){
    $content=trim($_POST['content'] ?? '');
    if($content==='') respond(false,null,'Content required');
    $stmt=$pdo->prepare('INSERT INTO community_posts (author_id,role,content) VALUES (?,?,?)');
    $stmt->execute([$_SESSION['user_id'], $_SESSION['role'], $content]);
    respond(true,null,'Posted');
}

respond(false,null,'Invalid request');
?>
