<?php
session_start();
require_once 'db.php';
$u = mysqli_query($conn, "SELECT * FROM users WHERE id=" . (int)$_SESSION['user_id']);
$user = mysqli_fetch_assoc($u);



if(!isset($_SESSION['user_id'])) {
    header("Location: /BlogSpace/auth.php");
    exit();
}

/* =========================
   PUBLISH LOGIC
========================= */

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['publish'])) {

    $title       = mysqli_real_escape_string($conn, $_POST['title']);
    $content     = mysqli_real_escape_string($conn, $_POST['content']);
    $topic       = mysqli_real_escape_string($conn, $_POST['topic'] ?? 'General');
    $post_type   = mysqli_real_escape_string($conn, $_POST['post_type'] ?? 'text');
    $media_url   = mysqli_real_escape_string($conn, $_POST['media_url'] ?? '');
    $code_block  = mysqli_real_escape_string($conn, $_POST['content_extra'] ?? '');
    $user_id     = $_SESSION['user_id'];

    if(isset($_POST['post_id']) && !empty($_POST['post_id'])) {

        $post_id = (int)$_POST['post_id'];

        $query = "UPDATE posts SET
                    title='$title',
                    content='$content',
                    topic='$topic',
                    post_type='$post_type',
                    media_url='$media_url',
                    content_extra='$code_block',
                    status='published'
                  WHERE id='$post_id'
                  AND user_id='$user_id'";

    } else {

        $query = "INSERT INTO posts
                 (title, content, user_id, topic, post_type, media_url, content_extra, created_at, status)
                 VALUES
                 ('$title', '$content', '$user_id', '$topic', '$post_type', '$media_url', '$code_block', NOW(), 'published')";
    }

    if(mysqli_query($conn, $query)) {
        header("Location: dashboard.php?success=published");
        exit();
    } else {
        $error = mysqli_error($conn);
    }
}

/* =========================
   EDIT MODE
========================= */

$edit_post = [
    'title'         => '',
    'content'       => '',
    'topic'         => 'Tech',
    'post_type'     => 'text',
    'media_url'     => '',
    'content_extra' => '',
    'id'            => ''
];

if(isset($_GET['edit'])) {

    $edit_id = (int)$_GET['edit'];
    $user_id = $_SESSION['user_id'];

    $res = mysqli_query($conn,
        "SELECT * FROM posts
         WHERE id='$edit_id'
         AND user_id='$user_id'"
    );

    if(mysqli_num_rows($res) > 0) {
        $edit_post = mysqli_fetch_assoc($res);
    }
}

/* =========================
   TOPICS
========================= */

$topics = [
    'Tech'      => '#8A9BB8',
    'Lifestyle' => '#B89B8A',
    'Health'    => '#7E9C8C',
    'Travel'    => '#B89F7A',
    'Food'      => '#A87C7C',
    'Finance'   => '#6F8F8D',
    'Culture'   => '#9A88B5',
    'General'   => '#D4BC8E'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BlogSpace | Create Story</title>

<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="style.css">

<style>
.write-header {
    padding: 60px 0 40px;
    border-bottom: 1px solid #333;
    margin-bottom: 50px;
}
.write-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 3rem;
    margin: 0 0 8px;
}
.write-header p { color: var(--text-muted); margin: 0; }

.write-field { margin-bottom: 35px; }

.write-field label {
    display: block;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: var(--accent);
    margin-bottom: 10px;
}

.title-input {
    width: 100%;
    background: transparent;
    border: none;
    border-bottom: 1px solid #444;
    padding: 12px 0;
    color: white;
    font-family: 'Playfair Display', serif;
    font-size: 1.8rem;
    font-weight: 700;
    outline: none;
}
.title-input:focus { border-bottom-color: var(--accent); }

.topics-wrap { display: flex; flex-wrap: wrap; gap: 10px; }

.topic-pill.active {
    background: var(--pill-color, var(--accent)) !important;
    border-color: transparent !important;
    color: #1a1a1a !important;
}
.topic-pill:hover { transform: translateY(-2px); opacity: 0.9; }

.type-selector { display: flex; gap: 15px; margin-top: 15px; }

.type-btn {
    background: #1a1a1a;
    border: 1px solid #333;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
}
.type-btn.active {
    border-color: var(--accent);
    background: rgba(255,255,255,0.06);
}

.media-input,
.code-editor {
    width: 100%;
    padding: 15px;
    background: #111;
    border: 1px solid #333;
    color: white;
    border-radius: 8px;
    outline: none;
}
.code-editor {
    height: 220px;
    color: #0f0;
    font-family: monospace;
}

.meta-row {
    display: flex;
    justify-content: space-between;
    color: #555;
    font-size: 0.78rem;
    margin-top: 8px;
}

.action-row {
    display: flex;
    align-items: center;
    gap: 20px;
    padding-top: 30px;
    border-top: 1px solid #333;
    margin-top: 40px;
}
.action-row a { color: var(--text-muted); text-decoration: none; }
.action-row a:hover { color: white; }

#autosave-status { color: var(--accent); font-size: 0.85rem; }

.error-message {
    color: var(--error);
    padding: 12px 16px;
    background: rgba(255,77,77,.08);
    border-left: 3px solid var(--error);
    margin-bottom: 30px;
}
  

</style>

</head>
<body class="dashboard-body">

<!-- SIDEBAR -->
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
                <span class="sidebar-user-name"><?= htmlspecialchars($user['username'] ?? $_SESSION['username'] ?? 'Writer') ?></span>
                <span class="sidebar-user-handle">Writer · BlogSpace</span>
            </div>
        </a>

        <nav class="side-nav">
            <a href="dashboard.php"><i class='bx bx-home-alt-2'></i> Feed</a>
            <a href="profile.php"><i class='bx bx-user-circle'></i> My Profile</a>
            <a href="write.php" class="active"><i class='bx bx-edit-alt'></i> Write Post</a>
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


<!-- MAIN -->
<main class="feed-container">

<div class="write-header">
    <h1>Create Story</h1>
    <p>Draft your masterpiece and share it with your followers.</p>
</div>

<?php if(isset($error)): ?>
<div class="error-message"><?= $error ?></div>
<?php endif; ?>

<form id="postForm" method="POST" style="max-width:780px;">

    <input type="hidden" name="post_id" id="post_id" value="<?= $edit_post['id'] ?>">

    <!-- TITLE -->
    <div class="write-field">
        <label>Headline</label>
        <input
            type="text"
            name="title"
            class="title-input"
            placeholder="Give your story a title…"
            value="<?= htmlspecialchars($edit_post['title']) ?>"
            required
        >
    </div>

    <!-- TOPICS -->
    <div class="write-field">
        <label>Topic</label>
        <div class="topics-wrap">
            <?php foreach ($topics as $name => $color): ?>
            <span class="topic-pill" data-name="<?= $name ?>" data-color="<?= $color ?>">
                <?= $name ?>
            </span>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="topic" id="selected-topic" value="<?= htmlspecialchars($edit_post['topic']) ?>">
    </div>

    <!-- TYPE -->
    <div class="write-field">
        <label>Post Format</label>
        <div class="type-selector">
            <input type="hidden" name="post_type" id="post-type" value="<?= htmlspecialchars($edit_post['post_type']) ?>">
            <button type="button" class="type-btn" data-type="text"><i class='bx bx-text'></i> Text</button>
            <button type="button" class="type-btn" data-type="image"><i class='bx bx-image'></i> Image</button>
            <button type="button" class="type-btn" data-type="code"><i class='bx bx-code-alt'></i> Code</button>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="write-field">
        <label>Your Story</label>
        <textarea
            name="content"
            id="editor"
            class="main-editor"
            placeholder="Tell your story…"
            required
        ><?= htmlspecialchars($edit_post['content']) ?></textarea>
        <div class="meta-row">
            <span id="wc">0 words</span>
            <span id="rt">~1 min read</span>
        </div>
    </div>

    <!-- MEDIA -->
<div id="media-field" style="display:none; margin-bottom:30px;">
    <label class="write-field" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:2px;color:var(--accent);display:block;margin-bottom:12px;">Media</label>

    <div style="display:flex; gap:12px; margin-bottom:16px;">
        <button type="button" class="type-btn" id="btn-gallery">
            <i class='bx bx-image-add'></i> Gallery
        </button>
        <button type="button" class="type-btn" id="btn-camera">
            <i class='bx bx-camera'></i> Camera
        </button>
    </div>

    <p style="color:#555; font-size:0.8rem; margin:0 0 10px;">— or paste image URL —</p>

    <input
        type="text"
        name="media_url"
        id="media_url_input"
        class="media-input"
        placeholder="Paste image URL..."
        value="<?= htmlspecialchars($edit_post['media_url']) ?>"
    >

    <!-- Hidden file input, reused for both gallery and camera -->
    <input type="file" id="file-picker" accept="image/*" style="display:none;">

    <!-- Preview -->
    <img
        id="img-preview"
        src=""
        alt="Preview"
        style="display:none; margin-top:14px; max-width:100%; border-radius:8px; border:1px solid #333;"
    >
</div>

    <!-- CODE -->
    <div id="code-field" style="display:none;">
        <label>Code Block</label>
        <textarea
            name="content_extra"
            class="code-editor"
            placeholder="Paste your code here..."
        ><?= htmlspecialchars($edit_post['content_extra'] ?? '') ?></textarea>
    </div>

    <!-- ACTIONS -->
    <div class="action-row">
        <button type="submit" name="publish" class="btn-primary">Publish Story</button>
        <span id="autosave-status"></span>
        <a href="dashboard.php">Cancel</a>
    </div>

</form>

</main>

<script>

/* TOPICS */
const topicPills   = document.querySelectorAll('.topic-pill');
const selectedTopic = document.getElementById('selected-topic');

function activateTopic(pill) {
    topicPills.forEach(x => { x.classList.remove('active'); x.style.cssText = ''; });
    pill.classList.add('active');
    pill.style.cssText = `background:${pill.dataset.color};color:#1a1a1a;border-color:transparent;`;
    selectedTopic.value = pill.dataset.name;
}

topicPills.forEach(p => {
    p.addEventListener('click', () => activateTopic(p));
    if(p.dataset.name === selectedTopic.value) activateTopic(p);
});

/* POST TYPE */
const typeButtons = document.querySelectorAll('.type-btn');

function setType(type) {
    document.getElementById('post-type').value = type;
    typeButtons.forEach(btn => btn.classList.remove('active'));
    document.querySelector(`[data-type="${type}"]`).classList.add('active');
    document.getElementById('media-field').style.display = (type === 'image') ? 'block' : 'none';
    document.getElementById('code-field').style.display  = (type === 'code')  ? 'block' : 'none';
}

typeButtons.forEach(btn => btn.addEventListener('click', () => setType(btn.dataset.type)));
setType("<?= htmlspecialchars($edit_post['post_type']) ?>");

/* WORD COUNT */
const ed = document.getElementById('editor');

function updateMeta() {
    const w = ed.value.trim() ? ed.value.trim().split(/\s+/).length : 0;
    document.getElementById('wc').textContent = w + ' words';
    document.getElementById('rt').textContent = '~' + Math.max(1, Math.round(w / 200)) + ' min read';
}
ed.addEventListener('input', updateMeta);
updateMeta();

/* AUTOSAVE */
let autosaveTimer;

function autosave() {
    var title     = document.querySelector('[name="title"]').value;
    var content   = document.getElementById('editor').value;
    var topic     = document.getElementById('selected-topic').value;
    var post_type = document.getElementById('post-type').value;
    var post_id   = document.getElementById('post_id').value;

    if(title.trim() === '' && content.trim() === '') return;

    var params = 'title='         + encodeURIComponent(title)
               + '&content='      + encodeURIComponent(content)
               + '&topic='        + encodeURIComponent(topic)
               + '&post_type='    + encodeURIComponent(post_type)
               + '&content_extra='+ ''
               + '&media_url='    + ''
               + '&post_id='      + encodeURIComponent(post_id)
               + '&autosave=true';

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'autosave_handler.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if(xhr.readyState === 4) {
            try {
                var data = JSON.parse(xhr.responseText);
                if(data.success) {
                    document.getElementById('post_id').value = data.post_id;
                    var time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    document.getElementById('autosave-status').innerText = 'Saved • ' + time;
                } else {
                    document.getElementById('autosave-status').innerText = 'Autosave failed: ' + (data.message || '');
                }
            } catch(e) {
                document.getElementById('autosave-status').innerText = 'Autosave error';
            }
        }
    };
    xhr.send(params);
}
// Fires 3 seconds after you stop typing
ed.addEventListener('input', () => {
    document.getElementById('autosave-status').innerText = 'Saving...';
    clearTimeout(autosaveTimer);
    autosaveTimer = setTimeout(autosave, 3000);
});

// Fallback every 30 seconds
setInterval(autosave, 30000);

/* GALLERY & CAMERA UPLOAD */
const filePicker    = document.getElementById('file-picker');
const mediaUrlInput = document.getElementById('media_url_input');
const imgPreview    = document.getElementById('img-preview');

document.getElementById('btn-gallery').addEventListener('click', () => {
    filePicker.removeAttribute('capture');
    filePicker.click();
});

document.getElementById('btn-camera').addEventListener('click', () => {
    filePicker.setAttribute('capture', 'environment');
    filePicker.click();
});

filePicker.addEventListener('change', () => {
    const file = filePicker.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        imgPreview.src = e.target.result;
        imgPreview.style.display = 'block';
        mediaUrlInput.value = e.target.result;
    };
    reader.readAsDataURL(file);
});

mediaUrlInput.addEventListener('input', () => {
    const val = mediaUrlInput.value.trim();
    if (val.startsWith('http')) {
        imgPreview.src = val;
        imgPreview.style.display = 'block';
        imgPreview.onerror = () => { imgPreview.style.display = 'none'; };
    } else {
        imgPreview.style.display = 'none';
    }
});

(function() {
    const existing = mediaUrlInput.value.trim();
    if (existing) {
        imgPreview.src = existing;
        imgPreview.style.display = 'block';
    }
})();

</script>
<!-- MOBILE BOTTOM NAV -->
<nav class="mobile-nav">
    <a href="dashboard.php"><i class='bx bx-home-alt-2'></i> Feed</a>
    <a href="profile.php"><i class='bx bx-user-circle'></i> Profile</a>
    <a href="write.php"><i class='bx bx-edit-alt'></i> Write</a>
    <a href="drafts.php"><i class='bx bx-file'></i> Drafts</a>
    <a href="settings.php"><i class='bx bx-cog'></i> Settings</a>
    <a href="logout.php" class="logout-mobile"><i class='bx bx-log-out'></i> Logout</a>
</nav>
</body>
</html>
