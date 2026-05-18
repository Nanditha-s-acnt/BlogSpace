const express = require('express');
const mysql = require('mysql2');
const path = require('path');
const app = express();

app.use(express.static(__dirname));
app.use(express.json());

// XAMPP Connection
const db = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '', 
    database: 'blogSpace' // Make sure you created this in phpMyAdmin!
});

db.connect(err => {
    if (err) return console.error("❌ XAMPP Error: " + err.message);
    console.log("✅ XAMPP MySQL Connected");
    
    db.query(`CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255),
        content TEXT,
        topic VARCHAR(100)
    )`);
});

// Routes
app.get('/', (req, res) => res.sendFile(path.join(__dirname, 'index.html')));
app.get('/dashboard', (req, res) => res.sendFile(path.join(__dirname, 'dashboard.html')));
app.get('/write', (req, res) => res.sendFile(path.join(__dirname, 'write.html')));

app.post('/api/posts', (req, res) => {
    const { title, content, topic } = req.body;
    db.query("INSERT INTO posts (title, content, topic) VALUES (?, ?, ?)", 
    [title, content, topic], (err) => {
        if (err) return res.status(500).send(err);
        res.status(200).send({ message: "Saved!" });
    });
});

app.listen(5000, () => console.log("🚀 Server at http://localhost:5000"));