<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = (int)($_POST['post_id'] ?? 0);

if ($post_id === 0) {
    echo json_encode(['success' => false]);
    exit();
}

// Check if already liked
$check = mysqli_prepare($conn, "SELECT id FROM likes WHERE post_id=? AND user_id=?");
mysqli_stmt_bind_param($check, "ii", $post_id, $user_id);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);

if (mysqli_stmt_num_rows($check) > 0) {
    // Unlike
    $del = mysqli_prepare($conn, "DELETE FROM likes WHERE post_id=? AND user_id=?");
    mysqli_stmt_bind_param($del, "ii", $post_id, $user_id);
    mysqli_stmt_execute($del);
    $liked = false;
} else {
    // Like
    $ins = mysqli_prepare($conn, "INSERT INTO likes (post_id, user_id) VALUES (?,?)");
    mysqli_stmt_bind_param($ins, "ii", $post_id, $user_id);
    mysqli_stmt_execute($ins);
    $liked = true;
}

// Get new count
$cnt = mysqli_prepare($conn, "SELECT COUNT(*) FROM likes WHERE post_id=?");
mysqli_stmt_bind_param($cnt, "i", $post_id);
mysqli_stmt_execute($cnt);
mysqli_stmt_bind_result($cnt, $count);
mysqli_stmt_fetch($cnt);

echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);
?>