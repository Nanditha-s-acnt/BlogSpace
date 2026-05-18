<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

$user_id    = (int)$_SESSION['user_id'];
$user_theme = in_array($_SESSION['theme'] ?? '', ['default','dark','light','sepia','ocean'])
                ? $_SESSION['theme'] : 'default';

$query = mysqli_query($conn,
    "SELECT * FROM posts
     WHERE user_id='$user_id'
     AND status='draft'
     ORDER BY updated_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlogSpace | My Drafts</title>

    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="themes/<?= htmlspecialchars($user_theme) ?>.css">

    <style>
        .drafts-header {
            padding: 50px 0 40px;
            border-bottom: 1px solid #2a2a2a;
            margin-bottom: 40px;
        }
        .drafts-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.4rem;
            margin: 0 0 8px;
        }
        .drafts-header p { color: var(--text-muted); margin: 0; }

        .draft-card {
            background: var(--surface);
            border: 1px solid #2a2a2a;
            border-radius: 4px;
            padding: 28px 30px;
            margin-bottom: 20px;
            transition: border-color 0.2s, transform 0.2s;
        }
        .draft-card:hover {
            border-color: var(--accent);
            transform: translateY(-3px);
        }

        .draft-topic {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--accent);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .draft-card h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            margin: 0 0 10px;
            color: var(--text-primary);
        }

        .draft-card p {
            color: var(--text-muted);
            font-size: 0.88rem;
            line-height: 1.6;
            margin: 0 0 18px;
        }

        .draft-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 1px solid #222;
        }

        .draft-date {
            font-size: 0.75rem;
            color: #555;
        }

        .draft-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn-edit {
            padding: 8px 18px;
            background: var(--accent);
            color: #1a1a1a;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.82rem;
            text-decoration: none;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-edit:hover { opacity: 0.85; }

        .btn-delete {
            background: none;
            border: 1px solid #444;
            color: #777;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 0.82rem;
            cursor: pointer;
            text-decoration: none;
            transition: border-color 0.2s, color 0.2s;
        }
        .btn-delete:hover { border-color: #e05252; color: #e05252; }

        .empty-drafts {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
        }
        .empty-drafts i { font-size: 3rem; color: #333; margin-bottom: 16px; display: block; }
        .empty-drafts h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--text-primary);
        }
        .empty-drafts a { color: var(--accent); text-decoration: none; font-weight: 600; }
    </style>
</head>

<body class="dashboard-body">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="logo">BlogSpace<span>.</span></div>
            <nav class="side-nav">
                <a href="dashboard.php"><i class='bx bx-home-alt-2'></i> Feed</a>
                <a href="profile.php"><i class='bx bx-user-circle'></i> My Profile</a>
                <a href="write.php"><i class='bx bx-edit-alt'></i> Write Post</a>
                <a href="drafts.php" class="active"><i class='bx bx-file'></i> Drafts</a>
                <a href="settings.php"><i class='bx bx-cog'></i> Settings</a>
            </nav>
        </div>
        <div class="sidebar-bottom">
            <a href="logout.php" class="logout-link"><i class='bx bx-log-out'></i> Logout</a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="feed-container">

        <div class="drafts-header">
            <h1>My Drafts</h1>
            <p>Pick up where you left off.</p>
        </div>

        <?php if(mysqli_num_rows($query) === 0): ?>

        <div class="empty-drafts">
            <i class='bx bx-file-blank'></i>
            <h3>No drafts yet</h3>
            <p>Start writing and your drafts will appear here. <a href="write.php">Write something →</a></p>
        </div>

        <?php else: ?>

        <?php while($draft = mysqli_fetch_assoc($query)): ?>

        <div class="draft-card">

            <div class="draft-topic"><?= htmlspecialchars($draft['topic'] ?? 'General') ?></div>

            <h2><?= htmlspecialchars($draft['title'] ?: 'Untitled Draft') ?></h2>

            <p><?= htmlspecialchars(substr(strip_tags($draft['content']), 0, 180)) ?>...</p>

            <div class="draft-footer">

                <span class="draft-date">
                    <i class='bx bx-time-five'></i>
                    Last saved <?= date('M j, Y · g:i a', strtotime($draft['updated_at'])) ?>
                </span>

                <div class="draft-actions">
                    <a href="write.php?edit=<?= $draft['id'] ?>" class="btn-edit">
                        <i class='bx bx-edit-alt'></i> Continue Writing
                    </a>
                    <a href="delete_draft.php?id=<?= $draft['id'] ?>" class="btn-delete"
                       onclick="return confirm('Delete this draft permanently?')">
                        <i class='bx bx-trash'></i> Delete
                    </a>
                </div>

            </div>
        </div>

        <?php endwhile; ?>
        <?php endif; ?>

    </main>

</body>
</html>
