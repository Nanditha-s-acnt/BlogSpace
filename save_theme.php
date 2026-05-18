<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['user_id'])) {
    exit();
}

$user_id = $_SESSION['user_id'];

$theme = mysqli_real_escape_string($conn, $_POST['theme']);

mysqli_query($conn,
"UPDATE users
 SET theme='$theme'
 WHERE id='$user_id'");

$_SESSION['theme'] = $theme;

echo "saved";
?>