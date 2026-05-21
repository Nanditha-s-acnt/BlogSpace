<?php
// ── Start session and connect to database ──────────────────
session_start();
require_once 'db.php';

// ── Redirect to login if not logged in ────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ── Get post ID from URL ───────────────────────────────────
$post_id = (int)($_GET['id'] ?? 0);

if ($post_id === 0) {
    header("Location: dashboard.php");
    exit();
}

// ── Fetch the post + author name ───────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT posts.*, users.username AS author_name
     FROM posts
     JOIN users ON posts.user_id = users.id
     WHERE posts.id = ?
     AND posts.status = 'published'"
);
mysqli_stmt_bind_param($stmt, "i", $post_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$post   = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// ── Post not found → back to feed ─────────────────────────
if (!$post) {
    header("Location: dashboard.php");
    exit();
}

// ── Handle: Submit Comment ─────────────────────────────────
if (isset($_POST['submit_comment'])) {
    $comment = trim($_POST['comment'] ?? '');

    if ($comment !== '') {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO comments (post_id, user_id, comment_text)
             VALUES (?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iis", $post_id, $user_id, $comment);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("Location: post.php?id=$post_id");
    exit();
}

// ── Fetch comments ─────────────────────────────────────────
$cstmt = mysqli_prepare($conn,
    "SELECT comments.*, users.username
     FROM comments
     JOIN users ON comments.user_id = users.id
     WHERE comments.post_id = ?
     ORDER BY comments.created_at ASC"
);
mysqli_stmt_bind_param($cstmt, "i", $post_id);
mysqli_stmt_execute($cstmt);
$comments_result = mysqli_stmt_get_result($cstmt);
$comments        = [];
while ($row = mysqli_fetch_assoc($comments_result)) {
    $comments[] = $row;
}
mysqli_stmt_close($cstmt);

// ── Theme ──────────────────────────────────────────────────
$allowed_themes = ['default', 'dark', 'light', 'sepia', 'ocean'];
$user_theme     = in_array($_SESSION['theme'] ?? '', $allowed_themes)
                    ? $_SESSION['theme'] : 'default';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlogSpace | <?php echo htmlspecialchars($post['title']); ?></title>

    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="themes/<?php echo htmlspecialchars($user_theme); ?>.css" id="theme-style">
</head>

<body class="dashboard-body">

    <!-- ══════════════ SIDEBAR ══════════════ -->
    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="logo">BlogSpace<span>.</span></div>
            <nav class="side-nav">
                <a href="dashboard.php"><i class='bx bx-home-alt-2'></i> Feed</a>
                <a href="profile.php"><i class='bx bx-user-circle'></i> My Profile</a>
                <a href="write.php"><i class='bx bx-edit-alt'></i> Write Post</a>
                <a href="drafts.php"><i class='bx bx-file'></i> Drafts</a>
                <a href="settings.php"><i class='bx bx-cog'></i> Settings</a>
            </nav>
        </div>
        <div class="sidebar-bottom">
            <a href="logout.php" class="logout-link">Logout</a>
        </div>
    </aside>

    <!-- ══════════════ MAIN CONTENT ══════════════ -->
    <main class="feed-container">
        <article class="full-post">

            <!-- ── Back link ──────────────────────────── -->
            <a href="dashboard.php" class="back-link">
                <i class='bx bx-arrow-back'></i> Back to Feed
            </a>

            <!-- ── Topic tag ──────────────────────────── -->
            <div style="margin-top: 30px;">
                <span style="
                    font-size: 0.68rem;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                    color: var(--accent);
                    font-weight: 600;
                ">
                    <?php echo htmlspecialchars($post['topic'] ?? 'General'); ?>
                </span>
            </div>

            <!-- ── Title ──────────────────────────────── -->
            <h1 class="post-title">
                <?php echo htmlspecialchars($post['title']); ?>
            </h1>

            <!-- ── Meta: author + date ────────────────── -->
            <div style="
                display: flex;
                align-items: center;
                gap: 16px;
                margin-bottom: 40px;
                padding-bottom: 30px;
                border-bottom: 1px solid #2a2a2a;
            ">
                <i class='bx bxs-user-circle' style="font-size:2rem; color:var(--accent);"></i>
                <div>
                    <div style="font-weight:600; font-size:0.9rem;">
                        @<?php echo htmlspecialchars($post['author_name']); ?>
                    </div>
                    <div style="font-size:0.78rem; color:var(--text-muted);">
                        <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                    </div>
                </div>
            </div>

            <!-- ── Post Image ──────────────────────────── -->
<?php if (!empty($post['media_url'])): ?>
    <div style="margin-bottom: 30px;">
        <img src="<?php echo htmlspecialchars($post['media_url']); ?>"
             alt="Post image"
             style="width:100%; max-height:500px; object-fit:cover; border-radius:6px;">
    </div>
<?php endif; ?>

            <!-- ── Post Body ──────────────────────────── -->
            <div class="post-content">
                <?php
                // Preserve line breaks from the textarea
                $paragraphs = explode("\n", $post['content']);
                foreach ($paragraphs as $para) {
                    $para = trim($para);
                    if ($para !== '') {
                        echo '<p>' . htmlspecialchars($para) . '</p>';
                    }
                }
                ?>
            </div>
            <!-- ── Code Block ── -->
<?php if (!empty($post['content_extra']) && ($post['post_format'] ?? $post['post_type'] ?? '') === 'code'): ?>
<div style="margin-top: 30px;">
    <pre style="
        background: #111;
        border: 1px solid #333;
        border-radius: 6px;
        padding: 20px;
        overflow-x: auto;
        color: #0f0;
        font-family: monospace;
        font-size: 0.9rem;
        line-height: 1.6;
    "><code><?php echo htmlspecialchars($post['content_extra']); ?></code></pre>
</div>
<?php endif; ?>

            <!-- ══════ COMMENTS SECTION ══════ -->
            <div style="
                margin-top: 60px;
                padding-top: 40px;
                border-top: 1px solid #2a2a2a;
            ">
                <h3 style="
                    font-family: 'Playfair Display', serif;
                    font-size: 1.6rem;
                    margin-bottom: 30px;
                ">
                    Comments
                    <span style="
                        font-family: 'Plus Jakarta Sans', sans-serif;
                        font-size: 0.8rem;
                        color: var(--text-muted);
                        font-weight: 400;
                        margin-left: 10px;
                    "><?php echo count($comments); ?></span>
                </h3>

                <!-- ── Comment Form ───────────────────── -->
                <div class="comment-box" style="margin-bottom: 40px;">
                    <form method="POST" action="post.php?id=<?php echo $post_id; ?>">
                        <textarea
                            name="comment"
                            placeholder="Share your thoughts…"
                            required
                        ></textarea>
                        <div style="margin-top: 12px; text-align: right;">
                            <button type="submit" name="submit_comment" class="btn-primary">
                                Post Comment
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ── Comment List ───────────────────── -->
                <div class="comment-list">
                    <?php if (empty($comments)): ?>
                        <p style="color:var(--text-muted); font-style:italic;">
                            No comments yet. Be the first to respond.
                        </p>
                    <?php else: ?>
                        <?php foreach ($comments as $c): ?>
                            <div class="comment-item">
                                <div class="comment-header">
                                    <span class="comment-user">
                                        @<?php echo htmlspecialchars($c['username']); ?>
                                    </span>
                                    <span class="comment-time">
                                        <?php echo date('M j, Y · g:i a', strtotime($c['created_at'])); ?>
                                    </span>
                                </div>
                                <p class="comment-text">
                                    <?php echo htmlspecialchars($c['comment_text']); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
            <!-- /.comments -->

        </article>
    </main>

</body>
</html>
