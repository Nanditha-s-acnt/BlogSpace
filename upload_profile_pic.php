
<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];

if (!isset($_FILES['profile_pic']) || $_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit();
}

$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$file_type     = mime_content_type($_FILES['profile_pic']['tmp_name']);

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit();
}

if ($_FILES['profile_pic']['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max 5 MB']);
    exit();
}

// Create uploads folder if it doesn't exist
if (!is_dir('uploads')) {
    mkdir('uploads', 0755, true);
}

// Delete old profile pic
$res = mysqli_query($conn, "SELECT profile_pic FROM users WHERE id=$user_id");
$row = mysqli_fetch_assoc($res);
if (!empty($row['profile_pic']) && $row['profile_pic'] !== 'default.png') {
    $old = 'uploads/' . $row['profile_pic'];
    if (file_exists($old)) unlink($old);
}

// Save new file
$ext      = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
$filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
$dest     = 'uploads/' . $filename;

if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'Upload failed — check folder permissions']);
    exit();
}

// Save filename to database
$stmt = mysqli_prepare($conn, "UPDATE users SET profile_pic = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'si', $filename, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo json_encode([
    'success'  => true,
    'filename' => $filename,
    'url'      => 'uploads/' . $filename
]);
