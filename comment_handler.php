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
$comment = trim($_POST['comment'] ?? '');

if ($post_id === 0 || $comment === '') {
    echo json_encode(['success' => false]);
    exit();
}

$stmt = mysqli_prepare($conn,
    "INSERT INTO comments (post_id, user_id, comment_text) VALUES (?,?,?)"
);
mysqli_stmt_bind_param($stmt, "iis", $post_id, $user_id, $comment);
mysqli_stmt_execute($stmt);

// Get username for response
$uname_stmt = mysqli_prepare($conn, "SELECT username FROM users WHERE id=?");
mysqli_stmt_bind_param($uname_stmt, "i", $user_id);
mysqli_stmt_execute($uname_stmt);
mysqli_stmt_bind_result($uname_stmt, $username);
mysqli_stmt_fetch($uname_stmt);

echo json_encode([
    'success'  => true,
    'username' => htmlspecialchars($username),
    'comment'  => htmlspecialchars($comment),
    'time'     => date('M j, Y · g:i a')
]);
?>