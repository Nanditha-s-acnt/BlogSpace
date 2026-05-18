<?php
class Post {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create($title, $content, $user_id) {
        $sql = "INSERT INTO posts (title, content, user_id, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $title, $content, $user_id);
        return $stmt->execute();
    }
    
    public function getAll() {
        $sql = "SELECT posts.*, users.username FROM posts 
                JOIN users ON posts.user_id = users.id 
                ORDER BY created_at DESC";
        return $this->conn->query($sql);
    }
    
    public function getById($id) {
        $sql = "SELECT posts.*, users.username FROM posts 
                JOIN users ON posts.user_id = users.id 
                WHERE posts.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>