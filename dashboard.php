<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /BlogSpace/auth.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$query = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$user  = mysqli_fetch_assoc($query);

// ── Whitelist theme ────────────────────────────────────────
$allowed_themes = ['default', 'dark', 'light', 'sepia', 'ocean'];
$user_theme     = in_array($_SESSION['theme'] ?? '', $allowed_themes)
                    ? $_SESSION['theme'] : 'default';

// ── Fetch posts with like count + whether current user liked ──
$posts_result = mysqli_query($conn,
    "SELECT posts.*, users.username,
            COUNT(DISTINCT likes.id) AS like_count,
            MAX(CASE WHEN likes.user_id = $user_id THEN 1 ELSE 0 END) AS user_liked
     FROM posts
     JOIN users ON posts.user_id = users.id
     LEFT JOIN likes ON likes.post_id = posts.id
     WHERE posts.status = 'published'
     GROUP BY posts.id
     ORDER BY posts.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlogSpace | Feed</title>

    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="themes/<?php echo htmlspecialchars($user_theme); ?>.css" id="theme-style">

    <style>
        /* ── HEADER ── */
        .feed-header {
            padding: 50px 0 40px;
            border-bottom: 1px solid #2a2a2a;
            margin-bottom: 50px;
        }
        .feed-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.6rem;
            margin: 0 0 8px;
            color: var(--text-primary);
        }
        .feed-header h2 span { color: var(--accent); }
        .feed-header p { color: var(--text-muted); margin: 0; font-size: 0.9rem; }

        /* ── TOPIC FILTER BAR ── */
        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }
        .filter-pill {
            padding: 6px 16px;
            border-radius: 30px;
            border: 1px solid #2a2a2a;
            background: transparent;
            color: var(--text-muted);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.78rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            letter-spacing: 0.3px;
        }
        .filter-pill:hover { border-color: var(--accent); color: var(--accent); }
        .filter-pill.active { background: var(--accent); color: #1a1a1a; border-color: var(--accent); font-weight: 700; }

        /* ── FEED GRID ── */
        .blog-feed {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }

        /* ── CARD ── */
        .feed-card {
            background: var(--surface);
            border: 1px solid #2a2a2a;
            border-radius: 4px;
            padding: 30px;
            transition: border-color 0.25s, transform 0.25s, box-shadow 0.25s;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .feed-card:hover {
            border-color: var(--accent);
            transform: translateY(-5px);
            box-shadow: 0 16px 40px rgba(0,0,0,0.35);
        }

        .card-topic {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--accent);
            font-weight: 600;
        }

        .feed-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.35rem;
            line-height: 1.3;
            margin: 0;
            color: var(--text-primary);
        }

        .feed-card p {
            color: var(--text-muted);
            font-size: 0.88rem;
            line-height: 1.7;
            margin: 0;
            flex: 1;
        }

        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 1px solid #2a2a2a;
            margin-top: auto;
        }

        .card-byline { font-size: 0.78rem; color: #555; }
        .card-byline span { color: var(--accent); font-weight: 600; }

        .read-btn {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-primary);
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1.5px solid var(--accent);
            padding-bottom: 2px;
            transition: color 0.2s;
        }
        .read-btn:hover { color: var(--accent); }

        /* ── LIKE + COMMENT BAR ── */
        .post-actions {
            display: flex;
            align-items: center;
            gap: 18px;
            padding-top: 14px;
            border-top: 1px solid #2a2a2a;
        }

        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            font-size: 0.85rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding: 0;
            transition: color 0.2s;
        }
        .action-btn:hover { color: var(--text-primary); }
        .action-btn i { font-size: 1.15rem; }

        .like-btn.liked { color: #e05252 !important; }
        .like-btn.liked i::before { content: "\eb84"; } /* bxs-heart */

        /* ── INLINE COMMENT BOX ── */
        .inline-comment-box {
            display: none;
            margin-top: 4px;
            border-top: 1px solid #222;
            padding-top: 14px;
        }

        .comment-list-inline {
            max-height: 200px;
            overflow-y: auto;
            margin-bottom: 10px;
        }

        .comment-item-inline {
            padding: 8px 0;
            border-bottom: 1px solid #1e1e1e;
            font-size: 0.82rem;
        }
        .comment-item-inline:last-child { border-bottom: none; }

        .comment-author {
            color: var(--accent);
            font-weight: 600;
            margin-right: 6px;
        }
        .comment-time-small {
            color: #444;
            font-size: 0.72rem;
        }
        .comment-body {
            color: #bbb;
            margin: 3px 0 0;
        }

        .comment-input-row {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .comment-input {
            flex: 1;
            padding: 9px 13px;
            background: #111;
            border: 1px solid #333;
            color: white;
            border-radius: 8px;
            outline: none;
            font-size: 0.85rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: border-color 0.2s;
        }
        .comment-input:focus { border-color: var(--accent); }

        .comment-post-btn {
            padding: 9px 16px;
            background: var(--accent);
            color: #111;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.82rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: opacity 0.2s;
        }
        .comment-post-btn:hover { opacity: 0.85; }

        /* ── EMPTY STATES ── */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
        }
        .empty-state i { font-size: 3rem; color: #333; margin-bottom: 16px; display: block; }
        .empty-state h3 { font-family: 'Playfair Display', serif; font-size: 1.5rem; margin-bottom: 10px; color: var(--text-primary); }
        .empty-state a { color: var(--accent); text-decoration: none; font-weight: 600; border-bottom: 1px solid var(--accent); }

        #filter-empty {
            display: none;
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
        }
        #filter-empty i { font-size: 3rem; color: #333; margin-bottom: 16px; display: block; }
        #filter-empty h3 { font-family: 'Playfair Display', serif; font-size: 1.5rem; margin-bottom: 10px; color: var(--text-primary); }

        .profile-pic {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-top: 15px;
        }
    </style>
</head>

<body class="dashboard-body">

    <!-- ══════════════ SIDEBAR ══════════════ -->
    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="logo">BlogSpace<span>.</span></div>

            <img src="uploads/<?php echo !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'default.png'; ?>"
                 alt="Profile Pic"
                 class="profile-pic">

            <nav class="side-nav">
                <a href="dashboard.php" class="active"><i class='bx bx-home-alt-2'></i> Feed</a>
                <a href="profile.php"><i class='bx bx-user-circle'></i> My Profile</a>
                <a href="write.php"><i class='bx bx-edit-alt'></i> Write Post</a>
                <a href="drafts.php"><i class='bx bx-file'></i> Drafts</a>
                <a href="settings.php"><i class='bx bx-cog'></i> Settings</a>
            </nav>
        </div>
        
        <div class="sidebar-bottom" style="position:absolute; bottom:30px; left:30px;">
            <a href="logout.php" class="logout-link">
                <i class='bx bx-log-out'></i>Logout
            </a>
        </div>
    </aside>

    <!-- ══════════════ MAIN CONTENT ══════════════ -->
    <main class="feed-container">

        <header class="feed-header">
            <h2>
                Welcome back,
                <span><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Storyteller'; ?></span>
            </h2>
            <p>Here's what the community is sharing today.</p>
        </header>

        <!-- FILTER BAR -->
        <div class="filter-bar">
            <span class="filter-pill active" data-filter="all">All</span>
            <span class="filter-pill" data-filter="Tech">Tech</span>
            <span class="filter-pill" data-filter="Lifestyle">Lifestyle</span>
            <span class="filter-pill" data-filter="Health">Health</span>
            <span class="filter-pill" data-filter="Travel">Travel</span>
            <span class="filter-pill" data-filter="Food">Food</span>
            <span class="filter-pill" data-filter="Finance">Finance</span>
            <span class="filter-pill" data-filter="Culture">Culture</span>
        </div>

        <!-- FEED -->
        <section class="blog-feed" id="feed">

            <?php if (mysqli_num_rows($posts_result) > 0):
                while ($row = mysqli_fetch_assoc($posts_result)):
                    $topic      = htmlspecialchars($row['topic'] ?? 'General');
                    $title      = htmlspecialchars($row['title']);
                    $excerpt    = htmlspecialchars(substr(strip_tags($row['content']), 0, 130));
                    $author     = htmlspecialchars($row['username']);
                    $date       = date('M j, Y', strtotime($row['created_at']));
                    $id         = (int)$row['id'];
                    $like_count = (int)$row['like_count'];
                    $user_liked = (int)$row['user_liked'];
            ?>

            <article class="feed-card" data-topic="<?= $topic ?>">

                <div class="card-topic"><?= $topic ?></div>

                <h3><?= $title ?></h3>

                <p><?= $excerpt ?>…</p>

                <!-- Author + Read -->
                <div class="card-footer">
                    <div class="card-byline">
                        <span>@<?= $author ?></span> · <?= $date ?>
                    </div>
                    <a href="post.php?id=<?= $id ?>" class="read-btn">Read →</a>
                </div>

                <!-- ── Like + Comment actions ── -->
                <div class="post-actions">

                    <!-- Like -->
                    <button
                        class="action-btn like-btn <?= $user_liked ? 'liked' : '' ?>"
                        data-post="<?= $id ?>"
                        style="<?= $user_liked ? 'color:#e05252;' : '' ?>"
                        title="Like"
                    >
                        <i class='bx <?= $user_liked ? 'bxs-heart' : 'bx-heart' ?>'></i>
                        <span class="like-count"><?= $like_count ?></span>
                    </button>

                    <!-- Comment toggle -->
                    <button
                        class="action-btn comment-toggle"
                        data-post="<?= $id ?>"
                        title="Comment"
                    >
                        <i class='bx bx-comment'></i>
                        <span class="comment-label">Comment</span>
                    </button>

                </div>

                <!-- ── Inline comment panel ── -->
                <div class="inline-comment-box" id="comment-box-<?= $id ?>">

                    <!-- Existing comments load here via JS -->
                    <div class="comment-list-inline" id="comments-<?= $id ?>">
                        <p style="color:#444; font-size:0.8rem; font-style:italic;">Loading comments…</p>
                    </div>

                    <!-- New comment input -->
                    <div class="comment-input-row">
                        <input
                            type="text"
                            class="comment-input"
                            placeholder="Write a comment…"
                            data-post="<?= $id ?>"
                        >
                        <button class="comment-post-btn" data-post="<?= $id ?>">Post</button>
                    </div>

                </div>

            </article>

            <?php endwhile; else: ?>

            <div class="empty-state">
                <i class='bx bx-book-open'></i>
                <h3>No stories yet</h3>
                <p>Be the first one. <a href="write.php">Write a post →</a></p>
            </div>

            <?php endif; ?>

            <div id="filter-empty">
                <i class='bx bx-filter-alt'></i>
                <h3>No posts in this category</h3>
                <p>Nothing published under this topic yet.</p>
            </div>

        </section>

    </main>

    <script>
    /* ══════════════════════════════════════════
       FILTER PILLS
    ══════════════════════════════════════════ */
    document.querySelectorAll('.filter-pill').forEach(pill => {
        pill.addEventListener('click', () => {
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');

            const filter = pill.dataset.filter;
            const cards  = document.querySelectorAll('.feed-card');
            let visible  = 0;

            cards.forEach(card => {
                const match = filter === 'all' || card.dataset.topic === filter;
                card.style.display = match ? 'flex' : 'none';
                if (match) visible++;
            });

            document.getElementById('filter-empty').style.display =
                visible === 0 ? 'block' : 'none';
        });
    });

    /* ══════════════════════════════════════════
       LIKES
    ══════════════════════════════════════════ */
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const postId = btn.dataset.post;
            const fd = new FormData();
            fd.append('post_id', postId);

            fetch('like_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const icon  = btn.querySelector('i');
                const count = btn.querySelector('.like-count');

                if (data.liked) {
                    btn.classList.add('liked');
                    btn.style.color = '#e05252';
                    icon.className  = 'bx bxs-heart';
                } else {
                    btn.classList.remove('liked');
                    btn.style.color = 'var(--text-muted)';
                    icon.className  = 'bx bx-heart';
                }
                count.textContent = data.count;
            })
            .catch(console.error);
        });
    });

    /* ══════════════════════════════════════════
       COMMENTS — toggle panel + load comments
    ══════════════════════════════════════════ */
    function loadComments(postId) {
        const fd = new FormData();
        fd.append('post_id', postId);

        fetch('get_comments.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('comments-' + postId);
            if (!data.success || data.comments.length === 0) {
                list.innerHTML = '<p style="color:#444; font-size:0.8rem; font-style:italic;">No comments yet.</p>';
                return;
            }
            list.innerHTML = data.comments.map(c => `
                <div class="comment-item-inline">
                    <span class="comment-author">@${c.username}</span>
                    <span class="comment-time-small">${c.time}</span>
                    <p class="comment-body">${c.comment}</p>
                </div>
            `).join('');
            // Scroll to bottom of comment list
            list.scrollTop = list.scrollHeight;
        })
        .catch(() => {
            document.getElementById('comments-' + postId).innerHTML =
                '<p style="color:#555; font-size:0.8rem;">Could not load comments.</p>';
        });
    }

    document.querySelectorAll('.comment-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const postId = btn.dataset.post;
            const box    = document.getElementById('comment-box-' + postId);
            const isOpen = box.style.display === 'block';

            box.style.display = isOpen ? 'none' : 'block';

            if (!isOpen) {
                loadComments(postId); // fresh load each time panel opens
            }
        });
    });

    /* ══════════════════════════════════════════
       COMMENTS — submit new comment
    ══════════════════════════════════════════ */
    function submitComment(postId) {
        const input = document.querySelector(`.comment-input[data-post="${postId}"]`);
        const text  = input.value.trim();
        if (!text) return;

        const btn = document.querySelector(`.comment-post-btn[data-post="${postId}"]`);
        btn.disabled    = true;
        btn.textContent = '…';

        const fd = new FormData();
        fd.append('post_id', postId);
        fd.append('comment', text);

        fetch('comment_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            btn.disabled    = false;
            btn.textContent = 'Post';

            if (!data.success) return;

            input.value = '';

            // Append new comment instantly without reload
            const list = document.getElementById('comments-' + postId);
            // Remove "no comments" placeholder if present
            const placeholder = list.querySelector('p');
            if (placeholder) placeholder.remove();

            const div = document.createElement('div');
            div.className = 'comment-item-inline';
            div.innerHTML = `
                <span class="comment-author">@${data.username}</span>
                <span class="comment-time-small">${data.time}</span>
                <p class="comment-body">${data.comment}</p>
            `;
            list.appendChild(div);
            list.scrollTop = list.scrollHeight;
        })
        .catch(() => {
            btn.disabled    = false;
            btn.textContent = 'Post';
        });
    }

    document.querySelectorAll('.comment-post-btn').forEach(btn => {
        btn.addEventListener('click', () => submitComment(btn.dataset.post));
    });

    document.querySelectorAll('.comment-input').forEach(input => {
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') submitComment(input.dataset.post);
        });
    });
    </script>

</body>
</html>
