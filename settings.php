<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

$user_id    = $_SESSION['user_id'];
$message    = '';
$error      = '';
$user_theme = $_SESSION['theme'] ?? 'default';

// Update Account
if (isset($_POST['update_account'])) {
    $email        = mysqli_real_escape_string($conn, trim($_POST['email']));
    $new_password = $_POST['new_password'];

    if (!empty($new_password)) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET email='$email', password='$hashed' WHERE id=$user_id");
    } else {
        mysqli_query($conn, "UPDATE users SET email='$email' WHERE id=$user_id");
    }
    $message = "Account updated successfully!";
}

// Delete Account
if (isset($_POST['delete_account'])) {
    mysqli_query($conn, "DELETE FROM posts WHERE user_id=$user_id");
    mysqli_query($conn, "DELETE FROM users WHERE id=$user_id");
    session_destroy();
    header("Location: index.php");
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$user   = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BlogSpace | Settings</title>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="themes/<?= htmlspecialchars($user_theme) ?>.css" id="theme-style">

<style>
/* ── PAGE HEADER ── */
.settings-header {
    padding: 50px 0 35px;
    border-bottom: 1px solid #2a2a2a;
    margin-bottom: 40px;
    display: flex;
    align-items: center;
    gap: 18px;
}
.settings-header-icon {
    width: 52px; height: 52px;
    background: rgba(255,255,255,0.04);
    border: 1px solid #333;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; color: var(--accent);
}
.settings-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 2.2rem;
    margin: 0 0 4px;
}
.settings-header p { color: var(--text-muted); margin: 0; font-size: 0.88rem; }

/* ── CARDS ── */
.settings-card {
    background: var(--surface);
    border: 1px solid #2a2a2a;
    border-radius: 6px;
    padding: 28px 30px;
    margin-bottom: 20px;
    transition: border-color 0.2s;
}
.settings-card:hover { border-color: #3a3a3a; }

.card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    padding-bottom: 18px;
    border-bottom: 1px solid #222;
}
.card-header i {
    font-size: 1.3rem;
    color: var(--accent);
    background: rgba(255,255,255,0.04);
    padding: 8px;
    border-radius: 8px;
    border: 1px solid #333;
}
.card-header h3 {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: var(--text-muted);
    margin: 0;
}

/* ── INPUTS ── */
.input-group { margin-bottom: 22px; }
.input-group label {
    display: block;
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: var(--accent);
    margin-bottom: 10px;
}
.input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.input-wrap i {
    position: absolute;
    left: 0;
    color: #555;
    font-size: 1.1rem;
}
.input-wrap input {
    width: 100%;
    background: transparent;
    border: none;
    border-bottom: 1px solid #333;
    padding: 11px 0 11px 28px;
    color: white;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.95rem;
    outline: none;
    transition: border-color 0.2s;
    box-sizing: border-box;
}
.input-wrap input:focus { border-bottom-color: var(--accent); }

/* ── THEME GRID ── */
.theme-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    margin-top: 4px;
}
.theme-option {
    cursor: pointer;
    border-radius: 10px;
    border: 2px solid transparent;
    overflow: hidden;
    transition: transform 0.2s, border-color 0.2s;
    position: relative;
}
.theme-option:hover { transform: translateY(-3px); }
.theme-option.active { border-color: var(--accent); }

.theme-swatch {
    height: 60px;
    display: flex;
    align-items: flex-end;
    padding: 6px 8px;
}
.theme-name {
    font-size: 0.7rem;
    font-weight: 600;
    text-align: center;
    padding: 6px 4px;
    background: #111;
    color: var(--text-muted);
    letter-spacing: 0.5px;
}
.theme-option.active .theme-name { color: var(--accent); }
.theme-check {
    position: absolute;
    top: 6px; right: 6px;
    background: var(--accent);
    color: #111;
    border-radius: 50%;
    width: 18px; height: 18px;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    font-weight: 800;
}
.theme-option.active .theme-check { display: flex; }

/* ── STATS ROW ── */
.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}
.stat-box {
    background: rgba(255,255,255,0.02);
    border: 1px solid #2a2a2a;
    border-radius: 8px;
    padding: 18px;
    text-align: center;
}
.stat-box .stat-num {
    font-family: 'Playfair Display', serif;
    font-size: 1.8rem;
    color: var(--accent);
    display: block;
}
.stat-box .stat-label {
    font-size: 0.72rem;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* ── MESSAGES ── */
.msg-success {
    background: rgba(212,188,142,0.08);
    border: 1px solid rgba(212,188,142,0.3);
    border-left: 3px solid var(--accent);
    color: var(--accent);
    padding: 14px 18px;
    margin-bottom: 24px;
    font-size: 0.88rem;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ── DANGER ── */
.danger-card { border-color: rgba(255,77,77,0.2) !important; }
.danger-card:hover { border-color: rgba(255,77,77,0.4) !important; }
.danger-card .card-header i { color: #ff4d4d; border-color: rgba(255,77,77,0.2); }
.danger-card .card-header h3 { color: #ff4d4d; }
.danger-info {
    display: flex;
    align-items: center;
    gap: 14px;
    background: rgba(255,77,77,0.04);
    border: 1px solid rgba(255,77,77,0.12);
    border-radius: 6px;
    padding: 14px 16px;
    margin-bottom: 20px;
}
.danger-info i { font-size: 1.4rem; color: #ff4d4d; flex-shrink: 0; }
.danger-info p { margin: 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; }
.btn-danger-outline {
    background: transparent;
    border: 1px solid #ff4d4d;
    color: #ff4d4d;
    padding: 11px 24px;
    cursor: pointer;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 600;
    font-size: 0.85rem;
    border-radius: 6px;
    transition: 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-danger-outline:hover { background: #ff4d4d; color: white; }

.save-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
}
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
            <a href="drafts.php"><i class='bx bx-file'></i> Drafts</a>
            <a href="settings.php" class="active"><i class='bx bx-cog'></i> Settings</a>
        </nav>
    </div>
    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-link"><i class='bx bx-log-out'></i> Logout</a>
    </div>
</aside>

<!-- MAIN -->
<main class="feed-container">

    <header class="settings-header">
        <div class="settings-header-icon"><i class='bx bx-cog'></i></div>
        <div>
            <h1>Settings</h1>
            <p>Manage your account, appearance, and preferences.</p>
        </div>
    </header>

    <?php if ($message): ?>
    <div class="msg-success">
        <i class='bx bx-check-circle'></i>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <?php
    // Quick stats
    $pub_count   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM posts WHERE user_id=$user_id AND status='published'"))[0];
    $draft_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM posts WHERE user_id=$user_id AND status='draft'"))[0];
    $joined      = date('M Y', strtotime($user['created_at'] ?? 'now'));
    ?>

    <!-- STATS -->
    <div class="stats-row">
        <div class="stat-box">
            <span class="stat-num"><?= $pub_count ?></span>
            <span class="stat-label">Published</span>
        </div>
        <div class="stat-box">
            <span class="stat-num"><?= $draft_count ?></span>
            <span class="stat-label">Drafts</span>
        </div>
        <div class="stat-box">
            <span class="stat-num"><?= $joined ?></span>
            <span class="stat-label">Member Since</span>
        </div>
    </div>

    <!-- ACCOUNT SECURITY -->
    <form method="POST">
        <div class="settings-card">
            <div class="card-header">
                <i class='bx bx-shield-quarter'></i>
                <h3>Account Security</h3>
            </div>

            <div class="input-group">
                <label>Email Address</label>
                <div class="input-wrap">
                    <i class='bx bx-envelope'></i>
                    <input type="email" name="email"
                           value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
            </div>

            <div class="input-group">
                <label>New Password</label>
                <div class="input-wrap">
                    <i class='bx bx-lock-alt'></i>
                    <input type="password" name="new_password"
                           placeholder="Leave blank to keep current password">
                </div>
            </div>

            <button type="submit" name="update_account" class="btn-primary save-btn">
                <i class='bx bx-save'></i> Save Changes
            </button>
        </div>
    </form>

    <!-- THEME -->
    <div class="settings-card">
        <div class="card-header">
            <i class='bx bx-palette'></i>
            <h3>Studio Theme</h3>
        </div>

        <div class="theme-grid">

            <div class="theme-option <?= $user_theme === 'default' ? 'active' : '' ?>" data-theme="default">
                <div class="theme-swatch" style="background:linear-gradient(135deg,#1a1a1a,#2a2a2a);"></div>
                <div class="theme-name">Default</div>
                <div class="theme-check">✓</div>
            </div>

            <div class="theme-option <?= $user_theme === 'ocean' ? 'active' : '' ?>" data-theme="ocean">
                <div class="theme-swatch" style="background:linear-gradient(135deg,#0a1628,#1a3a5c);"></div>
                <div class="theme-name">Ocean</div>
                <div class="theme-check">✓</div>
            </div>

            <div class="theme-option <?= $user_theme === 'sunset' ? 'active' : '' ?>" data-theme="sunset">
                <div class="theme-swatch" style="background:linear-gradient(135deg,#1a0a0a,#3a1a1a);"></div>
                <div class="theme-name">Sunset</div>
                <div class="theme-check">✓</div>
            </div>

            <div class="theme-option <?= $user_theme === 'neon' ? 'active' : '' ?>" data-theme="neon">
                <div class="theme-swatch" style="background:linear-gradient(135deg,#0a0a0a,#001a1a);"></div>
                <div class="theme-name">Neon</div>
                <div class="theme-check">✓</div>
            </div>

            <div class="theme-option <?= $user_theme === 'lavender' ? 'active' : '' ?>" data-theme="lavender">
                <div class="theme-swatch" style="background:linear-gradient(135deg,#12101a,#1e1a2e);"></div>
                <div class="theme-name">Lavender</div>
                <div class="theme-check">✓</div>
            </div>

        </div>

        <p style="color:#444; font-size:0.78rem; margin-top:16px; margin-bottom:0;">
            <i class='bx bx-info-circle'></i>
            Click a theme to apply it instantly across BlogSpace.
        </p>
    </div>

    <!-- DANGER ZONE -->
    <form method="POST" onsubmit="return confirm('This will permanently delete your account and all posts. Are you sure?');">
        <div class="settings-card danger-card">
            <div class="card-header">
                <i class='bx bx-error-alt'></i>
                <h3>Danger Zone</h3>
            </div>

            <div class="danger-info">
                <i class='bx bx-trash'></i>
                <p>Deleting your account is <strong>permanent and irreversible</strong>. All your posts, drafts, and data will be removed immediately.</p>
            </div>

            <button type="submit" name="delete_account" class="btn-danger-outline">
                <i class='bx bx-trash'></i> Delete My Account
            </button>
        </div>
    </form>

</main>

<script>
document.querySelectorAll('.theme-option').forEach(btn => {
    btn.addEventListener('click', () => {
        const theme = btn.dataset.theme;

        // Update active state
        document.querySelectorAll('.theme-option').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Apply theme
        document.getElementById('theme-style').href = 'themes/' + theme + '.css';

        // Save to server
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'save_theme.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('theme=' + encodeURIComponent(theme));
    });
});
</script>

</body>
</html>