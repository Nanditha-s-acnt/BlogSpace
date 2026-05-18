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

// ── Get post ID ────────────────────────────────────────────
$post_id = (int)($_GET['id'] ?? 0);
if ($post_id === 0) {
    header("Location: profile.php");
    exit();
}

// ── Fetch post (only owner can edit) ──────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT * FROM posts WHERE id = ? AND user_id = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$post   = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$post) {
    header("Location: profile.php");
    exit();
}

// ── Handle: Save Edits ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die("Invalid CSRF token.");
    }

    $title     = trim($_POST['title']     ?? '');
    $content   = trim($_POST['content']   ?? '');
    $topic     = trim($_POST['topic']     ?? 'General');
    $format    = trim($_POST['format']    ?? 'text');
    $media_url = trim($_POST['media_url'] ?? '');
    $status    = isset($_POST['save_draft']) ? 'draft' : 'published';

    $allowed_topics  = ['Tech','Lifestyle','Health','Travel','Food','Finance','Culture','General'];
    $allowed_formats = ['text','image','code'];
    if (!in_array($topic,  $allowed_topics))  $topic  = 'General';
    if (!in_array($format, $allowed_formats)) $format = 'text';

    if ($title !== '' && $content !== '') {
        $upd = mysqli_prepare($conn,
            "UPDATE posts
             SET title = ?, content = ?, topic = ?, post_format = ?, media_url = ?, status = ?
             WHERE id = ? AND user_id = ?"
        );
        mysqli_stmt_bind_param($upd, "ssssssii",
            $title, $content, $topic, $format, $media_url, $status, $post_id, $user_id
        );
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
    }

    header("Location: profile.php");
    exit();
}

// ── Theme ──────────────────────────────────────────────────
$allowed_themes = ['default','dark','light','sepia','ocean'];
$user_theme     = in_array($_SESSION['theme'] ?? '', $allowed_themes)
                    ? $_SESSION['theme'] : 'default';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlogSpace | Edit Post</title>

    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="themes/<?php echo htmlspecialchars($user_theme); ?>.css" id="theme-style">

    <style>
        .write-container {
            max-width: 780px;
            margin: 0 auto;
            padding: 48px 24px 80px;
        }

        .write-header {
            margin-bottom: 40px;
        }

        .write-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            margin: 0 0 6px;
        }

        .write-header p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin: 0;
        }

        .form-section {
            margin-bottom: 32px;
        }

        .form-label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
            color: var(--accent);
            display: block;
            margin-bottom: 10px;
        }

        .title-input {
            width: 100%;
            background: transparent;
            border: none;
            border-bottom: 1px solid #333;
            color: var(--text-primary);
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            padding: 10px 0;
            outline: none;
            box-sizing: border-box;
        }

        .title-input::placeholder { color: #444; }
        .title-input:focus { border-bottom-color: var(--accent); }

        /* Topic pills */
        .topic-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .topic-pill input[type="radio"] { display: none; }

        .topic-pill label {
            padding: 8px 18px;
            border-radius: 30px;
            border: 1px solid #333;
            font-size: 0.82rem;
            cursor: pointer;
            color: var(--text-muted);
            transition: 0.2s;
            user-select: none;
        }

        .topic-pill input:checked + label {
            background: var(--accent);
            color: #1a1a1a;
            border-color: var(--accent);
            font-weight: 700;
        }

        .topic-pill label:hover { border-color: var(--accent); color: var(--accent); }

        /* Format buttons */
        .format-btns {
            display: flex;
            gap: 10px;
        }

        .fmt-btn input[type="radio"] { display: none; }

        .fmt-btn label {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 9px 20px;
            border: 1px solid #333;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            color: var(--text-muted);
            transition: 0.2s;
        }

        .fmt-btn input:checked + label {
            border-color: var(--accent);
            color: var(--accent);
            background: rgba(212,188,142,0.08);
        }

        /* Story textarea */
        .story-area {
            width: 100%;
            min-height: 280px;
            background: rgba(255,255,255,0.03);
            border: 1px solid #2a2a2a;
            border-radius: 4px;
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1rem;
            line-height: 1.8;
            padding: 20px;
            resize: vertical;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .story-area:focus { border-color: var(--accent); }

        /* Media URL */
        .media-input {
            width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid #2a2a2a;
            border-radius: 4px;
            color: var(--text-primary);
            font-size: 0.9rem;
            padding: 13px 16px;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .media-input:focus { border-color: var(--accent); }

        /* Image preview */
        #img-preview {
            margin-top: 12px;
            max-width: 100%;
            max-height: 260px;
            border-radius: 4px;
            display: none;
            object-fit: cover;
        }

        /* Action buttons */
        .form-actions {
            display: flex;
            gap: 14px;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .btn-publish {
            background: var(--accent);
            color: #1a1a1a;
            border: none;
            padding: 13px 32px;
            font-weight: 700;
            font-size: 0.82rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            border-radius: 4px;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-publish:hover { opacity: 0.85; }

        .btn-draft {
            background: transparent;
            color: var(--text-muted);
            border: 1px solid #333;
            padding: 13px 28px;
            font-weight: 600;
            font-size: 0.82rem;
            letter-spacing: 1px;
            border-radius: 4px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-draft:hover { border-color: var(--accent); color: var(--accent); }

        .btn-cancel {
            background: transparent;
            color: var(--text-muted);
            border: none;
            padding: 13px 20px;
            font-size: 0.85rem;
            cursor: pointer;
            text-decoration: underline;
            transition: color 0.2s;
        }

        .btn-cancel:hover { color: var(--text-primary); }

        /* media section toggle */
        #media-section { display: none; }
        #media-section.visible { display: block; }
    </style>
</head>

<body class="dashboard-body">

    <!-- ══════════════ SIDEBAR ══════════════ -->
    <aside class="sidebar">
        <div>
            <a href="index.php" class="logo">Blog<span>Space</span></a>
            <nav class="side-nav">
                <a href="dashboard.php"><i class='bx bx-home'></i> Home</a>
                <a href="profile.php" class="active"><i class='bx bx-user'></i> Profile</a>
                <a href="write.php"><i class='bx bx-edit-alt'></i> Write</a>
                <a href="drafts.php"><i class='bx bx-bookmark'></i> Saved</a>
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
        <div class="write-container">

            <div class="write-header">
                <h1>Edit Post</h1>
                <p>Make changes to your story below.</p>
            </div>

            <form method="POST" action="edit_post.php?id=<?php echo $post_id; ?>">
                <input type="hidden" name="csrf_token"
                       value="<?php echo htmlspecialchars($csrf_token); ?>">

                <!-- ── Title ── -->
                <div class="form-section">
                    <input
                        type="text"
                        name="title"
                        class="title-input"
                        placeholder="Give your story a title…"
                        value="<?php echo htmlspecialchars($post['title']); ?>"
                        required
                    >
                </div>

                <!-- ── Topic ── -->
                <div class="form-section">
                    <label class="form-label">Topic</label>
                    <div class="topic-pills">
                        <?php
                        $topics = ['Tech','Lifestyle','Health','Travel','Food','Finance','Culture','General'];
                        foreach ($topics as $t):
                            $checked = ($post['topic'] ?? 'General') === $t ? 'checked' : '';
                        ?>
                        <div class="topic-pill">
                            <input type="radio" name="topic" id="t_<?php echo $t; ?>"
                                   value="<?php echo $t; ?>" <?php echo $checked; ?>>
                            <label for="t_<?php echo $t; ?>"><?php echo $t; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ── Post Format ── -->
                <div class="form-section">
                    <label class="form-label">Post Format</label>
                    <div class="format-btns">
                        <?php
                        $formats = [
                            'text'  => ['bx-text',        'Text'],
                            'image' => ['bx-image-alt',   'Image'],
                            'code'  => ['bx-code-alt',    'Code'],
                        ];
                        foreach ($formats as $val => [$icon, $label]):
                            $checked = ($post['post_format'] ?? 'text') === $val ? 'checked' : '';
                        ?>
                        <div class="fmt-btn">
                            <input type="radio" name="format" id="f_<?php echo $val; ?>"
                                   value="<?php echo $val; ?>" <?php echo $checked; ?>
                                   onchange="toggleMedia()">
                            <label for="f_<?php echo $val; ?>">
                                <i class='bx <?php echo $icon; ?>'></i> <?php echo $label; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ── Story ── -->
                <div class="form-section">
                    <label class="form-label">Your Story</label>
                    <textarea
                        name="content"
                        class="story-area"
                        placeholder="Tell your story…"
                        required
                    ><?php echo htmlspecialchars($post['content']); ?></textarea>
                </div>

                <!-- ── Media URL (image only) ── -->
                <div class="form-section" id="media-section">
                    <label class="form-label">Media URL</label>
                    <input
                        type="url"
                        name="media_url"
                        id="media_url"
                        class="media-input"
                        placeholder="Paste image URL…"
                        value="<?php echo htmlspecialchars($post['media_url'] ?? ''); ?>"
                        oninput="previewImage(this.value)"
                    >
                    <img id="img-preview" src="" alt="Image preview">
                </div>

                <!-- ── Actions ── -->
                <div class="form-actions">
                    <button type="submit" name="save_publish" class="btn-publish">
                        <i class='bx bx-send'></i> Save & Publish
                    </button>
                    <button type="submit" name="save_draft" class="btn-draft">
                        Save as Draft
                    </button>
                    <button type="button" class="btn-cancel"
                            onclick="window.location='profile.php'">
                        Cancel
                    </button>
                </div>

            </form>
        </div>
    </main>

    <script>
        function toggleMedia() {
            const fmt = document.querySelector('input[name="format"]:checked')?.value;
            const section = document.getElementById('media-section');
            if (fmt === 'image') {
                section.classList.add('visible');
            } else {
                section.classList.remove('visible');
            }
        }

        function previewImage(url) {
            const img = document.getElementById('img-preview');
            if (url.trim() !== '') {
                img.src = url;
                img.style.display = 'block';
                img.onerror = () => { img.style.display = 'none'; };
            } else {
                img.style.display = 'none';
            }
        }

        // Run on load to show media section if format is image
        toggleMedia();

        // Preview existing image URL on load
        const existingUrl = document.getElementById('media_url')?.value;
        if (existingUrl) previewImage(existingUrl);
    </script>

</body>
</html>
