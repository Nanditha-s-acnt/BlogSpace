<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

$user_id  = (int)$_SESSION['user_id'];
$draft_id = (int)($_GET['id'] ?? 0);

if($draft_id > 0) {
    mysqli_query($conn,
        "DELETE FROM posts
         WHERE id='$draft_id'
         AND user_id='$user_id'
         AND status='draft'"
    );
}

header("Location: drafts.php");
exit();
?>
