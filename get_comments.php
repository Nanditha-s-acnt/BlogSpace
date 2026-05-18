<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$post_id = (int)($_POST['post_id'] ?? 0);

if ($post_id === 0) {
    echo json_encode(['success' => false]);
    exit();
}

$stmt = mysqli_prepare($conn,
    "SELECT comments.comment_text, comments.created_at, users.username
     FROM comments
     JOIN users ON comments.user_id = users.id
     WHERE comments.post_id = ?
     ORDER BY comments.created_at ASC"
);
mysqli_stmt_bind_param($stmt, "i", $post_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$comments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $comments[] = [
        'username' => htmlspecialchars($row['username']),
        'comment'  => htmlspecialchars($row['comment_text']),
        'time'     => date('M j, Y · g:i a', strtotime($row['created_at']))
    ];
}

echo json_encode(['success' => true, 'comments' => $comments]);
?>