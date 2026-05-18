<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

$title      = mysqli_real_escape_string($conn, $_POST['title']         ?? '');
$content    = mysqli_real_escape_string($conn, $_POST['content']       ?? '');
$topic      = mysqli_real_escape_string($conn, $_POST['topic']         ?? 'General');
$post_type  = mysqli_real_escape_string($conn, $_POST['post_type']     ?? 'text');
$media_url  = mysqli_real_escape_string($conn, $_POST['media_url']     ?? '');
$code_block = mysqli_real_escape_string($conn, $_POST['content_extra'] ?? '');
$post_id    = $_POST['post_id'] ?? '';

/* Prevent empty saves */
if(trim($title) == '' && trim($content) == '') {
    echo json_encode(['success' => false, 'message' => 'Empty draft']);
    exit();
}

/* Update existing draft */
if(!empty($post_id)) {

    $post_id = (int)$post_id;

    $result = mysqli_query($conn,
        "UPDATE posts SET
            title         = '$title',
            content       = '$content',
            topic         = '$topic',
            post_type     = '$post_type',
            media_url     = '$media_url',
            content_extra = '$code_block',
            status        = 'draft'
         WHERE id='$post_id'
         AND user_id='$user_id'"
    );

    if(!$result) {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        exit();
    }

    echo json_encode(['success' => true, 'post_id' => $post_id, 'time' => date("h:i A")]);

/* Create new draft */
} else {

    $result = mysqli_query($conn,
        "INSERT INTO posts
            (title, content, user_id, topic, post_type, media_url, content_extra, created_at, status)
         VALUES
            ('$title', '$content', '$user_id', '$topic', '$post_type', '$media_url', '$code_block', NOW(), 'draft')"
    );

    if(!$result) {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        exit();
    }

    $post_id = mysqli_insert_id($conn);
    echo json_encode(['success' => true, 'post_id' => $post_id, 'time' => date("h:i A")]);
}
?>