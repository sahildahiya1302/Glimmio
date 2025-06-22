<?php
require_once 'db.php';
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['user_id'])){echo json_encode(['success'=>false,'message'=>'Unauthorized']);exit;}
$pdo=db_connect();
$action=$_GET['action'] ?? 'list';
$uid=$_SESSION['user_id'];
$role=$_SESSION['role'];
if($action==='send' && $_SERVER['REQUEST_METHOD']==='POST'){
    $rid=intval($_POST['receiver_id']??0);
    $rrole=$_POST['receiver_role']??'';
    $msg=trim($_POST['message']??'');
    if(!$rid || ($rrole!=='brand' && $rrole!=='influencer') || $msg===''){
        echo json_encode(['success'=>false,'message'=>'Invalid data']);exit;
    }
    $stmt=$pdo->prepare('INSERT INTO direct_messages (sender_id,sender_role,receiver_id,receiver_role,message) VALUES (?,?,?,?,?)');
    $stmt->execute([$uid,$role,$rid,$rrole,$msg]);
    echo json_encode(['success'=>true]);exit;
}
if($action==='list'){
    $other=intval($_GET['other_id']??0);
    $orole=$_GET['other_role']??'';
    if(!$other||($orole!=='brand'&&$orole!=='influencer')){echo json_encode(['success'=>false,'message'=>'Invalid']);exit;}
    $stmt=$pdo->prepare('SELECT * FROM direct_messages WHERE (sender_id=? AND sender_role=? AND receiver_id=? AND receiver_role=?) OR (sender_id=? AND sender_role=? AND receiver_id=? AND receiver_role=?) ORDER BY created_at');
    $stmt->execute([$uid,$role,$other,$orole,$other,$orole,$uid,$role]);
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);exit;
}
echo json_encode(['success'=>false,'message'=>'Invalid action']);
