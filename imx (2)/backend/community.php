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
    $filter=$_GET['filter'] ?? '';
    $category=$_GET['category'] ?? '';
    $author=intval($_GET['author'] ?? 0);
    $authorRole=$_GET['role'] ?? '';
    $sql='SELECT cp.*, IF(cp.role="brand",b.company_name,i.username) AS author, cp.like_count, cp.share_count, cp.save_count, cp.comment_count FROM community_posts cp LEFT JOIN brands b ON cp.role="brand" AND cp.author_id=b.id LEFT JOIN influencers i ON cp.role="influencer" AND cp.author_id=i.id';
    $conds=[];
    $params=[];
    if($category){
        $conds[]='(i.category=? OR b.industry=?)';
        $params[]=$category;
        $params[]=$category;
    }
    if($author){
        $conds[]='cp.author_id=? AND cp.role=?';
        $params[]=$author;
        $params[]=$authorRole;
    }
    if($conds){
        $sql.=' WHERE '.implode(' AND ',$conds);
    }
    $limit=intval($_GET['limit'] ?? 20);
    $offset=intval($_GET['offset'] ?? 0);
    if($filter==='trending'){
        $sql.=' ORDER BY (cp.like_count+cp.comment_count+cp.share_count) DESC LIMIT ? OFFSET ?';
        $params[]=$limit; $params[]=$offset;
    }else{
        $sql.=' ORDER BY cp.created_at DESC LIMIT ? OFFSET ?';
        $params[]=$limit; $params[]=$offset;
    }
    $stmt=$pdo->prepare($sql);
    $stmt->execute($params);
    $posts=$stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($posts as &$p){
        if($p['poll_options']){
            $opts=explode("|",$p['poll_options']);
            $res=[];
            foreach($opts as $idx=>$o){
                $c=$pdo->prepare('SELECT COUNT(*) FROM community_poll_votes WHERE post_id=? AND option_index=?');
                $c->execute([$p['id'],$idx]);
                $res[]=['option'=>$o,'votes'=>$c->fetchColumn()];
            }
            $p['poll_results']=$res;
        }
    }
    respond(true,$posts);
}

if($action==='post' && $_SERVER['REQUEST_METHOD']==='POST'){
    $content=trim($_POST['content'] ?? '');
    if($content==='') respond(false,null,'Content required');
    $imgUrl=null;
    if(!empty($_FILES['images'])){
        $paths=[];
        foreach($_FILES['images']['tmp_name'] as $idx=>$tmp){
            if(!$tmp) continue;
            $p='/uploads/'.basename($_FILES['images']['name'][$idx]);
            if(move_uploaded_file($tmp, __DIR__.'/..'.$p)) $paths[]=$p;
        }
        if($paths) $imgUrl=implode('|',$paths);
    }
    $pollQ=trim($_POST['poll_question'] ?? '');
    $pollOpts=$_POST['poll_options'] ?? '';
    $stmt=$pdo->prepare('INSERT INTO community_posts (author_id,role,content,image_url,poll_question,poll_options) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$_SESSION['user_id'], $_SESSION['role'], $content,$imgUrl,$pollQ,$pollOpts]);
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

if($action==='comment' && $_SERVER['REQUEST_METHOD']==='POST'){
    $post=intval($_POST['post_id'] ?? 0);
    $txt=trim($_POST['comment'] ?? '');
    if(!$post || $txt==='') respond(false,null,'Invalid data');
    $pdo->prepare('INSERT INTO community_comments (post_id,user_id,role,comment) VALUES (?,?,?,?)')
        ->execute([$post,$_SESSION['user_id'],$_SESSION['role'],$txt]);
    $pdo->prepare('UPDATE community_posts SET comment_count=comment_count+1 WHERE id=?')->execute([$post]);
    respond(true,null,'commented');
}

if($action==='list_comments'){
    $post=intval($_GET['post_id'] ?? 0);
    $stmt=$pdo->prepare('SELECT c.*, IF(c.role="brand",b.company_name,i.username) AS author FROM community_comments c LEFT JOIN brands b ON c.role="brand" AND c.user_id=b.id LEFT JOIN influencers i ON c.role="influencer" AND c.user_id=i.id WHERE post_id=? ORDER BY c.created_at');
    $stmt->execute([$post]);
    respond(true,$stmt->fetchAll(PDO::FETCH_ASSOC));
}

if($action==='share' && $_SERVER['REQUEST_METHOD']==='POST'){
    $post=intval($_POST['post_id'] ?? 0);
    if(!$post) respond(false,null,'Invalid post');
    $pdo->prepare('INSERT INTO community_shares (post_id,user_id,role) VALUES (?,?,?)')
        ->execute([$post,$_SESSION['user_id'],$_SESSION['role']]);
    $pdo->prepare('UPDATE community_posts SET share_count=share_count+1 WHERE id=?')->execute([$post]);
    respond(true,null,'shared');
}

if($action==='save' && $_SERVER['REQUEST_METHOD']==='POST'){
    $post=intval($_POST['post_id'] ?? 0);
    if(!$post) respond(false,null,'Invalid post');
    $check=$pdo->prepare('SELECT id FROM community_saves WHERE post_id=? AND user_id=? AND role=?');
    $check->execute([$post,$_SESSION['user_id'],$_SESSION['role']]);
    if($row=$check->fetch()){
        $pdo->prepare('DELETE FROM community_saves WHERE id=?')->execute([$row['id']]);
        $pdo->prepare('UPDATE community_posts SET save_count=GREATEST(save_count-1,0) WHERE id=?')->execute([$post]);
        respond(true,null,'unsaved');
    }else{
        $pdo->prepare('INSERT INTO community_saves (post_id,user_id,role) VALUES (?,?,?)')->execute([$post,$_SESSION['user_id'],$_SESSION['role']]);
        $pdo->prepare('UPDATE community_posts SET save_count=save_count+1 WHERE id=?')->execute([$post]);
        respond(true,null,'saved');
    }
}

if($action==='vote' && $_SERVER['REQUEST_METHOD']==='POST'){
    $post=intval($_POST['post_id'] ?? 0);
    $opt=intval($_POST['option'] ?? -1);
    if(!$post || $opt<0) respond(false,null,'Invalid');
    $check=$pdo->prepare('SELECT id FROM community_poll_votes WHERE post_id=? AND user_id=? AND role=?');
    $check->execute([$post,$_SESSION['user_id'],$_SESSION['role']]);
    if($row=$check->fetch()){
        $pdo->prepare('UPDATE community_poll_votes SET option_index=? WHERE id=?')->execute([$opt,$row['id']]);
    }else{
        $pdo->prepare('INSERT INTO community_poll_votes (post_id,option_index,user_id,role) VALUES (?,?,?,?)')
            ->execute([$post,$opt,$_SESSION['user_id'],$_SESSION['role']]);
    }
    respond(true,null,'voted');
}

respond(false,null,'Invalid request');
?>
