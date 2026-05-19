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

// ── CSRF Token ─────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

function verify_csrf($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die("Invalid CSRF token.");
    }
}

// ── Handle: Edit Profile ───────────────────────────────────
if (isset($_POST['save_profile'])) {
    verify_csrf($_POST['csrf_token'] ?? '');

    $new_username = trim($_POST['username'] ?? '');
    $new_bio      = trim($_POST['bio'] ?? '');

    if ($new_username !== '') {
        $stmt = mysqli_prepare($conn,
            "UPDATE users SET username = ?, bio = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ssi", $new_username, $new_bio, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $_SESSION['username'] = $new_username;
    }

    header("Location: profile.php");
    exit();
}

// ── Handle: Delete Post ────────────────────────────────────
if (isset($_POST['delete_post'])) {
    verify_csrf($_POST['csrf_token'] ?? '');

    $post_id = (int)($_POST['post_id'] ?? 0);

    $stmt = mysqli_prepare($conn,
        "DELETE FROM posts WHERE id = ? AND user_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: profile.php");
    exit();
}

// ── Handle: Save Post ──────────────────────────────────────
if (isset($_POST['save_post'])) {
    verify_csrf($_POST['csrf_token'] ?? '');

    $post_id = (int)($_POST['post_id'] ?? 0);

    $check = mysqli_prepare($conn,
        "SELECT id FROM saved_posts WHERE user_id = ? AND post_id = ?"
    );
    mysqli_stmt_bind_param($check, "ii", $user_id, $post_id);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) === 0) {
        $ins = mysqli_prepare($conn,
            "INSERT INTO saved_posts (user_id, post_id) VALUES (?, ?)"
        );
        mysqli_stmt_bind_param($ins, "ii", $user_id, $post_id);
        mysqli_stmt_execute($ins);
        mysqli_stmt_close($ins);
    }
    mysqli_stmt_close($check);

    header("Location: profile.php");
    exit();
}

// ── Fetch user info ────────────────────────────────────────
$u = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($u, "i", $user_id);
mysqli_stmt_execute($u);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($u));
mysqli_stmt_close($u);

// ── Fetch ALL posts (published + draft) ───────────────────
$p = mysqli_prepare($conn,
    "SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC"
);
mysqli_stmt_bind_param($p, "i", $user_id);
mysqli_stmt_execute($p);
$result = mysqli_stmt_get_result($p);

$published_posts = [];
$draft_posts     = [];

while ($row = mysqli_fetch_assoc($result)) {
    if (($row['status'] ?? 'draft') === 'published') {
        $published_posts[] = $row;
    } else {
        $draft_posts[] = $row;
    }
}
mysqli_stmt_close($p);

$post_count  = count($published_posts);
$draft_count = count($draft_posts);

// ── Whitelist theme ────────────────────────────────────────
$allowed_themes = ['default', 'dark', 'light', 'sepia', 'ocean'];
$user_theme     = in_array($_SESSION['theme'] ?? '', $allowed_themes)
                    ? $_SESSION['theme'] : 'default';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlogSpace | My Profile</title>

    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="themes/<?php echo htmlspecialchars($user_theme); ?>.css" id="theme-style">

    <style>
        .section-divider {
            border: none;
            border-top: 1px solid #333;
            margin: 50px 0 35px;
        }

        .section-heading {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            margin: 0 0 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .count-badge {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 20px;
            background: rgba(212, 188, 142, 0.15);
            color: var(--accent);
            border: 1px solid rgba(212, 188, 142, 0.3);
        }

        .count-badge.draft {
            background: rgba(160, 160, 160, 0.1);
            color: var(--text-muted);
            border-color: #444;
        }

        .status-pill {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
            padding: 2px 9px;
            border-radius: 20px;
            margin-left: 10px;
            vertical-align: middle;
        }

        .pill-published {
            background: rgba(92, 214, 92, 0.12);
            color: #5cd65c;
        }

        .pill-draft {
            background: rgba(160, 160, 160, 0.12);
            color: var(--text-muted);
        }

        .card-topic {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--accent);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .card-date {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .empty-state-plain {
            color: var(--text-muted);
            font-style: italic;
            padding: 20px 0 10px;
            font-size: 0.95rem;
        }

        /* catchy empty state */
        .empty-posts-cta {
            text-align: center;
            padding: 60px 30px;
            border: 1px dashed #333;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .empty-posts-cta i {
            font-size: 3rem;
            color: var(--accent);
            display: block;
            margin-bottom: 16px;
        }

        .empty-posts-cta h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.7rem;
            margin: 0 0 12px;
            color: var(--text-primary);
        }

        .empty-posts-cta p {
            color: var(--text-muted);
            font-size: 0.92rem;
            margin: 0 auto 28px;
            max-width: 380px;
            line-height: 1.7;
        }

        .empty-posts-cta a {
            background: var(--accent);
            color: #1a1a1a;
            padding: 13px 30px;
            font-weight: 700;
            font-size: 0.82rem;
            text-decoration: none;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            border-radius: 4px;
            display: inline-block;
            transition: opacity 0.2s;
        }

        .empty-posts-cta a:hover { opacity: 0.8; }

        /* ONE edit + ONE delete per card */
        .card-actions .action-btn {
            background: transparent;
            border: 1px solid #333;
            color: var(--text-muted);
            font-size: 0.82rem;
            font-weight: 600;
            padding: 7px 14px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
            text-decoration: none;
        }

        .card-actions .action-btn i { font-size: 1rem; }

        .card-actions .edit-btn:hover {
            color: var(--accent);
            border-color: var(--accent);
        }

        .card-actions .delete-btn:hover {
            color: var(--error);
            border-color: var(--error);
        }
        .profile-stat-card {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.profile-stat-item {
    text-align: center;
    padding: 14px 18px;
    background: rgba(255,255,255,0.03);
    border: 1px solid #2a2a2a;
    border-radius: 8px;
    min-width: 80px;
}
.profile-stat-num {
    display: block;
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    color: var(--accent);
}
.profile-stat-label {
    font-size: 0.68rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
}
.profile-cover {
    width: 100%;
    height: 160px;
    background: linear-gradient(135deg, #1a1a1a 0%, #2a2420 50%, #1a1a1a 100%);
    border-bottom: 1px solid #2a2a2a;
    border-radius: 4px 4px 0 0;
    overflow: hidden;
    margin-bottom: 0;
}
.profile-cover-inner {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(212,188,142,0.06) 0%, rgba(212,188,142,0.02) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}
.profile-cover-inner i { font-size: 4rem; color: rgba(212,188,142,0.08); }
.profile-avatar-wrapper {
    position: relative;
    width: 110px;
    height: 110px;
    cursor: pointer;
    flex-shrink: 0;
}
.profile-avatar-img {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--accent);
    display: block;
}
.profile-avatar-icon-wrap {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    background: rgba(212,188,142,0.08);
    border: 3px solid var(--accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4.5rem;
    color: var(--accent);
}
.profile-avatar-overlay {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: rgba(0,0,0,0.6);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
    color: white;
    font-size: 0.68rem;
    font-weight: 600;
    gap: 4px;
}
.profile-avatar-overlay i { font-size: 1.3rem; }
.profile-avatar-wrapper:hover .profile-avatar-overlay { opacity: 1; }

        
    </style>
</head>

<body class="dashboard-body">

    <!-- ══════════════ SIDEBAR ══════════════ -->
   <aside class="sidebar">
    <div class="sidebar-top">
        <div class="logo">BlogSpace<span>.</span></div>

        <a href="profile.php" class="sidebar-user-card">
            <div class="sidebar-user-avatar">
                <?php if (!empty($user['profile_pic']) && file_exists('uploads/' . $user['profile_pic'])): ?>
                    <img src="uploads/<?= htmlspecialchars($user['profile_pic']) ?>" alt="">
                <?php else: ?>
                    <div class="sidebar-avatar-fallback"><i class='bx bxs-user-circle'></i></div>
                <?php endif; ?>
                <span class="online-dot"></span>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?= htmlspecialchars($user['username']) ?></span>
                <span class="sidebar-user-handle">My Profile</span>
            </div>
        </a>

        <nav class="side-nav">
            <a href="dashboard.php"><i class='bx bx-home-alt-2'></i> Feed</a>
            <a href="profile.php" class="active"><i class='bx bx-user-circle'></i> My Profile</a>
            <a href="write.php"><i class='bx bx-edit-alt'></i> Write Post</a>
            <a href="drafts.php"><i class='bx bx-file'></i> Drafts</a>
            <a href="settings.php"><i class='bx bx-cog'></i> Settings</a>
        </nav>
    </div>

    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-link">
            <i class='bx bx-log-out'></i> Logout
        </a>
    </div>
</aside>


    <!-- ══════════════ MAIN CONTENT ══════════════ -->
    <main class="feed-container">

        <!-- ── Profile Header ───────────────────── -->
<div class="profile-header">

    <div class="profile-cover">
        <div class="profile-cover-inner"><i class='bx bx-landscape'></i></div>
    </div>

    <div class="profile-main" style="padding:30px 0 0; align-items:flex-start;">

<div class="profile-avatar-wrapper"
     title="Change photo">

    <img src="uploads/<?= !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : '' ?>"
         alt="Profile"
         class="profile-avatar-img"
         id="avatarPreview"
         onerror="this.style.display='none'; document.getElementById('avatarIconWrap').style.display='flex';"
         style="<?= empty($user['profile_pic']) ? 'display:none;' : '' ?>">

    <div class="profile-avatar-icon-wrap" id="avatarIconWrap"
         style="<?= !empty($user['profile_pic']) ? 'display:none;' : 'display:flex;' ?>">
        <i class='bx bxs-user-circle'></i>
    </div>

    <div class="profile-avatar-overlay">
        <i class='bx bx-camera'></i>
        Change Photo
    </div>
</div>
<input type="file" id="avatarFileInput" accept="image/*" style="display:none;">

        <div class="profile-info">
            <h1><?= htmlspecialchars($user['username']) ?></h1>
            <p class="handle">
                <?= htmlspecialchars(!empty($user['bio']) ? $user['bio'] : 'No bio yet. Click Edit Profile to add one.') ?>
            </p>

            <div class="profile-stat-card" style="margin-bottom:20px;">
                <div class="profile-stat-item">
                    <span class="profile-stat-num"><?= $post_count ?></span>
                    <span class="profile-stat-label">Published</span>
                </div>
                <div class="profile-stat-item">
                    <span class="profile-stat-num"><?= $draft_count ?></span>
                    <span class="profile-stat-label">Drafts</span>
                </div>
                <div class="profile-stat-item">
                    <span class="profile-stat-num"><?= date('M Y', strtotime($user['created_at'] ?? 'now')) ?></span>
                    <span class="profile-stat-label">Joined</span>
                </div>
            </div>

            <button class="btn-outline" onclick="openModal()">
                <i class='bx bx-edit' style="vertical-align:middle;margin-right:6px;"></i>
                Edit Profile
            </button>
        </div>
    </div>
</div>


        <!-- ── Published Posts ──────────────────── -->
        <h2 class="section-heading">
            <i class='bx bx-news'></i>
            My Posts
            <span class="count-badge"><?php echo $post_count; ?></span>
        </h2>

        <?php if (empty($published_posts)): ?>

            <!-- ── Catchy empty state ── -->
            <div class="empty-posts-cta">
                <i class='bx bx-pencil'></i>
                <h3>Your story hasn't started yet.</h3>
                <p>Every great writer published a first post.<br>Yours is one click away.</p>
                <a href="write.php">Write Your First Post →</a>
            </div>

        <?php else: ?>
            <?php foreach ($published_posts as $post): ?>
                <div class="profile-card">
                    <div class="card-header-flex">
                        <div>
                            <div class="card-topic">
                                <?php echo htmlspecialchars($post['topic'] ?? 'General'); ?>
                                <span class="status-pill pill-published">Published</span>
                            </div>
                            <h2><?php echo htmlspecialchars($post['title']); ?></h2>
                            <div class="card-date">
                                <i class='bx bx-calendar-alt'></i>
                                <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                            </div>
                        </div>

                        <!-- ONE Edit + ONE Delete -->
                        <div class="card-actions">
                            <a href="edit_post.php?id=<?php echo (int)$post['id']; ?>"
                               class="action-btn edit-btn">
                                <i class='bx bx-pencil'></i> Edit
                            </a>
                            <form method="POST" action="profile.php"
                                  onsubmit="return confirm('Delete this post permanently?');"
                                  style="margin:0;">
                                <input type="hidden" name="csrf_token"
                                       value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="post_id"
                                       value="<?php echo (int)$post['id']; ?>">
                                <button type="submit" name="delete_post"
                                        class="action-btn delete-btn">
                                    <i class='bx bx-trash'></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- ── Drafts ───────────────────────────── -->
        <hr class="section-divider">

        <h2 class="section-heading">
            <i class='bx bx-file-blank'></i>
            Drafts
            <span class="count-badge draft"><?php echo $draft_count; ?></span>
        </h2>

        <?php if (empty($draft_posts)): ?>
            <p class="empty-state-plain">No drafts saved yet.</p>
        <?php else: ?>
            <?php foreach ($draft_posts as $post): ?>
                <div class="profile-card">
                    <div class="card-header-flex">
                        <div>
                            <div class="card-topic">
                                <?php echo htmlspecialchars($post['topic'] ?? 'General'); ?>
                                <span class="status-pill pill-draft">Draft</span>
                            </div>
                            <h2><?php echo htmlspecialchars($post['title']); ?></h2>
                            <div class="card-date">
                                <i class='bx bx-time-five'></i>
                                Last saved: <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                            </div>
                        </div>

                        <!-- ONE Edit + ONE Delete -->
                        <div class="card-actions">
                            <a href="edit_post.php?id=<?php echo (int)$post['id']; ?>"
                               class="action-btn edit-btn">
                                <i class='bx bx-pencil'></i> Edit
                            </a>
                            <form method="POST" action="profile.php"
                                  onsubmit="return confirm('Delete this draft permanently?');"
                                  style="margin:0;">
                                <input type="hidden" name="csrf_token"
                                       value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="post_id"
                                       value="<?php echo (int)$post['id']; ?>">
                                <button type="submit" name="delete_post"
                                        class="action-btn delete-btn">
                                    <i class='bx bx-trash'></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </main>

    <!-- ══════════════ EDIT PROFILE MODAL ══════════════ -->
    <div class="modal-overlay" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Profile</h2>
            <button class="close-modal" onclick="closeModal()">&#x2715;</button>
        </div>

        <div class="pic-upload-area">
            <?php if (!empty($user['profile_pic']) && file_exists('uploads/' . $user['profile_pic'])): ?>
                <img src="uploads/<?= htmlspecialchars($user['profile_pic']) ?>"
                     class="pic-preview-thumb" id="modalThumb">
            <?php else: ?>
                <i class='bx bx-camera'></i>
            <?php endif; ?>
            <p><span>Click to upload</span> a profile photo<br>
               <small style="color:#444;">JPG, PNG, WEBP · Max 5 MB</small></p>
            <input type="file" id="modalPicInput" accept="image/*">
        </div>
        <p id="uploadStatus" style="font-size:0.8rem;color:var(--accent);margin:-8px 0 14px;display:none;"></p>

        <form method="POST" action="profile.php" class="edit-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username"
                       value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>
            <div class="input-group">
                
                <label>Bio</label>
                <textarea name="bio" placeholder="Write a short bio…"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" name="save_profile" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>


<script>
/* ── Modal ── */
function openModal()  { document.getElementById('editModal').style.display = 'flex'; }
function closeModal() { document.getElementById('editModal').style.display = 'none'; }
document.getElementById('editModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal();
});



/* ── Click upload area inside modal ── */
const modalInput = document.getElementById('modalPicInput');
if (modalInput) {
    modalInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            uploadProfilePic(this.files[0]);
        }
    });
}

/* ── Upload function ── */
function uploadProfilePic(file) {
    const status = document.getElementById('uploadStatus');
    if (status) {
        status.style.display = 'block';
        status.style.color   = 'var(--accent)';
        status.textContent   = 'Uploading…';
    }

    const fd = new FormData();
    fd.append('profile_pic', file);

    fetch('upload_profile_pic.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        console.log('Upload response:', data);
        if (data.success) {
            const url = data.url + '?t=' + Date.now();

            /* Update avatar on page */
            const preview = document.getElementById('avatarPreview');
            if (preview) { preview.src = url; preview.style.display = 'block'; }

            const iconWrap = document.getElementById('avatarIconWrap');
            if (iconWrap) iconWrap.style.display = 'none';

            /* Update modal thumbnail */
            let thumb = document.getElementById('modalThumb');
            if (!thumb) {
                thumb = document.createElement('img');
                thumb.id = 'modalThumb';
                thumb.className = 'pic-preview-thumb';
                const area = document.querySelector('.pic-upload-area');
                if (area) area.prepend(thumb);
            }
            thumb.src = url;

            /* Update sidebar avatar */
            document.querySelectorAll('.sidebar-user-avatar img').forEach(img => img.src = url);

            if (status) {
                status.textContent = '✓ Photo updated!';
                setTimeout(() => { status.style.display = 'none'; }, 3000);
            }
        } else {
            if (status) {
                status.style.color = '#ff4d4d';
                status.textContent = '✗ ' + (data.message || 'Upload failed');
            }
        }
    })
    .catch(err => {
        console.error('Upload error:', err);
        if (status) {
            status.style.color   = '#ff4d4d';
            status.textContent   = '✗ Upload error — check console';
        }
    });
}
</script>



</body>
</html>
