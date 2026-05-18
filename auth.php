<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $mode = $_POST['mode'] ?? 'login';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // ── LOGIN ──────────────────────────────────────────────
    if ($mode === 'login') {

        if (empty($email) || empty($password)) {

            $error = "Please fill in all fields.";

        } else {

            $query = "SELECT * FROM users WHERE email = '$email'";
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) > 0) {

                $user = mysqli_fetch_assoc($result);

                if (password_verify($password, $user['password'])) {

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];

                    header("Location: /BlogSpace/dashboard.php");
                    exit();

                } else {

                    $error = "Incorrect password. Please try again.";
                }

            } else {

                $error = "No account found with that email. Please register first.";
            }
        }
    }

    // ── REGISTER ───────────────────────────────────────────
    else if ($mode === 'register') {

        $username = trim($_POST['username'] ?? '');
        $confirm  = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($email) || empty($password) || empty($confirm)) {

            $error = "Please fill in all fields.";

        } elseif ($password !== $confirm) {

            $error = "Passwords do not match.";

        } else {

            $check = "SELECT id FROM users WHERE email = '$email'";
            $res   = mysqli_query($conn, $check);

            if (mysqli_num_rows($res) > 0) {

                $error = "An account with that email already exists. Please sign in.";

            } else {

                $hashed = password_hash($password, PASSWORD_DEFAULT);

                $insert = "INSERT INTO users (username, email, password)
                           VALUES ('$username', '$email', '$hashed')";

                if (mysqli_query($conn, $insert)) {

                    $_SESSION['user_id']  = mysqli_insert_id($conn);
                    $_SESSION['username'] = $username;

                    header("Location: /BlogSpace/dashboard.php");
                    exit();

                } else {

                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }

    // ── FORGOT PASSWORD ────────────────────────────────────
    else if ($mode === 'forgot') {

        $newPassword = $_POST['new_password'] ?? '';
        $confirmNew  = $_POST['confirm_new_password'] ?? '';

        if (empty($email) || empty($newPassword) || empty($confirmNew)) {

            $error = "Please fill in all fields.";

        } elseif ($newPassword !== $confirmNew) {

            $error = "Passwords do not match.";

        } else {

            $check = "SELECT * FROM users WHERE email='$email'";
            $res = mysqli_query($conn, $check);

            if (mysqli_num_rows($res) > 0) {

                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

                $update = "UPDATE users
                           SET password='$hashed'
                           WHERE email='$email'";

                if (mysqli_query($conn, $update)) {

                    $success = "Password updated successfully.";

                } else {

                    $error = "Failed to update password.";
                }

            } else {

                $error = "No account found with this email.";
            }
        }
    }
}

$activeMode = $_POST['mode'] ?? 'login';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlogSpace | Access</title>

    <link rel="stylesheet" href="style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        .input-group { position: relative; }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 42px;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 1.2rem;
        }

        .pw-wrap input { padding-right: 45px; }

        .php-error {
            color: #ff4d4d;
            text-align: center;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .php-success {
            color: #00cc66;
            text-align: center;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .error-text {
            color: #ff4d4d;
            font-size: 0.7rem;
            display: none;
            margin-top: 5px;
        }

        .invalid { border-color: #ff4d4d !important; }

        .auth-panel { display: none; }

        .auth-panel.active { display: block; }
    </style>
</head>

<body>

<div class="auth-wrapper">
    <div class="auth-container">

        <?php if ($error): ?>
            <div class="php-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="php-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>


        <!-- ══════════════ SIGN IN PANEL ══════════════ -->

        <div class="auth-panel <?php echo ($activeMode === 'login') ? 'active' : ''; ?>" id="panel-login">

            <h2>Sign In</h2>

            <p style="color: var(--text-muted); margin-bottom: 40px;">
                Welcome back! Enter your credentials to continue.
            </p>

            <form action="" method="POST" autocomplete="on">

                <input type="hidden" name="mode" value="login">

                <div class="input-group">
                    <label>Email Address</label>

                    <input type="email"
                           name="email"
                           placeholder="you@gmail.com"
                           autocomplete="email"
                           required>
                </div>

                <div class="input-group pw-wrap">

                    <label>Password</label>

                    <input type="password"
                           name="password"
                           id="pw-login"
                           placeholder="••••••••"
                           required>

                    <i class='bx bx-hide toggle-password'
                       onclick="toggleVis('pw-login', this)"></i>
                </div>

                <button type="submit"
                        class="btn-primary"
                        style="width: 100%; margin-top: 20px;">

                    Sign In
                </button>

                <!-- FORGOT PASSWORD BUTTON -->

                <p style="text-align:right; margin-top:12px;">

                    <span onclick="switchPanel('forgot')"
                          style="color:var(--accent); cursor:pointer; font-size:0.85rem;">

                        Forgot Password?
                    </span>
                </p>

            </form>

            <p style="text-align:center; margin-top:30px; font-size:0.8rem; color:var(--text-muted);">

                Don't have an account?

                <span onclick="switchPanel('register')"
                      style="color:var(--accent); cursor:pointer; font-weight:600;">

                    Create one for free
                </span>
            </p>

        </div>


        <!-- ══════════════ REGISTER PANEL ══════════════ -->

        <div class="auth-panel <?php echo ($activeMode === 'register') ? 'active' : ''; ?>" id="panel-register">

            <h2>Create Account</h2>

            <p style="color: var(--text-muted); margin-bottom: 40px;">
                Join BlogSpace — it's completely free.
            </p>

            <form action="" method="POST" autocomplete="on">

                <input type="hidden" name="mode" value="register">

                <div class="input-group">
                    <label>Username</label>

                    <input type="text"
                           name="username"
                           placeholder="e.g. john_doe"
                           autocomplete="username"
                           required>
                </div>

                <div class="input-group">
                    <label>Email Address</label>

                    <input type="email"
                           name="email"
                           placeholder="you@gmail.com"
                           autocomplete="email"
                           required>
                </div>

                <div class="input-group pw-wrap">

                    <label>Password</label>

                    <input type="password"
                           name="password"
                           id="pw-reg"
                           placeholder="••••••••"
                           oninput="validatePw()"
                           required>

                    <i class='bx bx-hide toggle-password'
                       onclick="toggleVis('pw-reg', this)"></i>

                    <div id="pw-err" class="error-text">
                        8+ chars, at least 1 number & 1 symbol.
                    </div>
                </div>

                <div class="input-group pw-wrap">

                    <label>Confirm Password</label>

                    <input type="password"
                           name="confirm_password"
                           id="pw-confirm"
                           placeholder="••••••••"
                           oninput="validateConfirm()"
                           required>

                    <i class='bx bx-hide toggle-password'
                       onclick="toggleVis('pw-confirm', this)"></i>

                    <div id="confirm-err" class="error-text">
                        Passwords do not match.
                    </div>
                </div>

                <button type="submit"
                        class="btn-primary"
                        style="width: 100%; margin-top: 20px;">

                    Create Account
                </button>
            </form>

            <p style="text-align:center; margin-top:30px; font-size:0.8rem; color:var(--text-muted);">

                Already have an account?

                <span onclick="switchPanel('login')"
                      style="color:var(--accent); cursor:pointer; font-weight:600;">

                    Sign in here
                </span>
            </p>

        </div>


        <!-- ══════════════ FORGOT PASSWORD PANEL ══════════════ -->

        <div class="auth-panel <?php echo ($activeMode === 'forgot') ? 'active' : ''; ?>" id="panel-forgot">

            <h2>Reset Password</h2>

            <p style="color: var(--text-muted); margin-bottom: 40px;">
                Enter your email and create a new password.
            </p>

            <form action="" method="POST" autocomplete="on">

                <input type="hidden" name="mode" value="forgot">

                <div class="input-group">

                    <label>Email Address</label>

                    <input type="email"
                           name="email"
                           placeholder="you@gmail.com"
                           autocomplete="email"
                           required>
                </div>

                <div class="input-group pw-wrap">

                    <label>New Password</label>

                    <input type="password"
                           name="new_password"
                           id="pw-new"
                           placeholder="••••••••"
                           required>

                    <i class='bx bx-hide toggle-password'
                       onclick="toggleVis('pw-new', this)"></i>
                </div>

                <div class="input-group pw-wrap">

                    <label>Confirm Password</label>

                    <input type="password"
                           name="confirm_new_password"
                           id="pw-new-confirm"
                           placeholder="••••••••"
                           required>

                    <i class='bx bx-hide toggle-password'
                       onclick="toggleVis('pw-new-confirm', this)"></i>
                </div>

                <button type="submit"
                        class="btn-primary"
                        style="width: 100%; margin-top: 20px;">

                    Reset Password
                </button>
            </form>

            <p style="text-align:center; margin-top:30px; font-size:0.8rem; color:var(--text-muted);">

                Remember your password?

                <span onclick="switchPanel('login')"
                      style="color:var(--accent); cursor:pointer; font-weight:600;">

                    Sign In
                </span>
            </p>

        </div>

        <a href="index.php" class="back-home">
            ← Return to Homepage
        </a>

    </div>
</div>

<script>

function switchPanel(mode) {

    document.querySelectorAll('.auth-panel')
    .forEach(p => p.classList.remove('active'));

    document.getElementById('panel-' + mode)
    .classList.add('active');
}

function toggleVis(inputId, icon) {

    const input = document.getElementById(inputId);

    if (input.type === 'password') {

        input.type = 'text';
        icon.classList.replace('bx-hide', 'bx-show');

    } else {

        input.type = 'password';
        icon.classList.replace('bx-show', 'bx-hide');
    }
}

function validatePw() {

    const pw  = document.getElementById('pw-reg');
    const err = document.getElementById('pw-err');

    const regex =
    /^(?=.*[0-9])(?=.*[!@#$%^&*])[a-zA-Z0-9!@#$%^&*]{8,}$/;

    if (pw.value.length > 0 && !regex.test(pw.value)) {

        pw.classList.add('invalid');
        err.style.display = 'block';

    } else {

        pw.classList.remove('invalid');
        err.style.display = 'none';
    }
}

function validateConfirm() {

    const pw = document.getElementById('pw-reg');
    const confirm = document.getElementById('pw-confirm');
    const err = document.getElementById('confirm-err');

    if (confirm.value.length > 0 &&
        confirm.value !== pw.value) {

        confirm.classList.add('invalid');
        err.style.display = 'block';

    } else {

        confirm.classList.remove('invalid');
        err.style.display = 'none';
    }
}

</script>

</body>
</html>