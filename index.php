<?php
session_start();
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlogSpace | Stories Worth Reading</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Features Section ── */
        .features-section {
            padding: 100px 8%;
            border-top: 1px solid #2a2a2a;
        }

        .features-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: var(--accent);
            margin-bottom: 20px;
        }

        .features-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 5vw, 3rem);
            max-width: 500px;
            line-height: 1.2;
            margin-bottom: 70px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
        }

        .feature-card {
            border-top: 1px solid #333;
            padding-top: 30px;
            transition: 0.3s ease;
        }

        .feature-number {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            color: #2e2e2e;
            line-height: 1;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--text-primary);
        }

        .feature-card p {
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.7;
        }

        /* ── CTA Strip ── */
        .cta-strip {
            margin: 0 8% 100px 8%;
            padding: 60px 80px;
            border: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 40px;
        }

        .cta-strip h2 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            line-height: 1.2;
            margin: 0;
        }

        .cta-strip h2 span { color: var(--accent); }

        .cta-strip p {
            color: var(--text-muted);
            margin: 10px 0 0 0;
            font-size: 0.95rem;
        }

        .cta-right { flex-shrink: 0; }

        /* ── Footer ── */
        .landing-footer {
            border-top: 1px solid #2a2a2a;
            padding: 30px 8%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .landing-footer p {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin: 0;
        }

        @media (max-width: 768px) {
            .features-grid { grid-template-columns: 1fr; }
            .cta-strip { flex-direction: column; padding: 40px 30px; text-align: center; }
        }
    </style>
</head>
<body>

    <!-- ── Navigation ── -->
    <nav class="landing-nav">
        <a href="/BlogSpace/index.php" class="logo">BlogSpace<span>.</span></a>
        <a href="/BlogSpace/auth.php" class="btn-primary" style="padding: 10px 25px;">Sign In</a>
    </nav>

    <!-- ── Hero ── -->
    <header class="hero-section">
        <h1>Stories worth <br>reading <span style="color: var(--accent);">again.</span></h1>
        <p>A refined sanctuary for the modern writer. Clean, focused, and designed for depth.</p>
        <div class="btn-group">
            <a href="/BlogSpace/auth.php" class="btn-primary">Start Writing</a>
            <a href="#features" style="color: white; text-decoration: none; font-weight: 600; border-bottom: 1px solid var(--accent);">
                See what's inside
            </a>
        </div>
    </header>

    <!-- ── Features Section ── -->
    <section class="features-section" id="features">
        <p class="features-label">Why BlogSpace</p>
        <h2>Built for writers who mean it.</h2>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-number">01</div>
                <h3>Write Freely</h3>
                <p>A distraction-free editor that gets out of your way. Just you, your thoughts, and the page.</p>
            </div>
            <div class="feature-card">
                <div class="feature-number">02</div>
                <h3>Build Your Audience</h3>
                <p>Share your stories, gain followers, and grow a readership that actually cares what you write.</p>
            </div>
            <div class="feature-card">
                <div class="feature-number">03</div>
                <h3>Elegant Reading</h3>
                <p>Every post is presented beautifully. No clutter, no noise — just clean, focused storytelling.</p>
            </div>
        </div>
    </section>

    <!-- ── CTA Strip ── -->
    <div class="cta-strip">
        <div class="cta-left">
            <h2>Ready to share <span>your story?</span></h2>
            <p>Join hundreds of writers who chose clarity over chaos.</p>
        </div>
        <div class="cta-right">
            <a href="/BlogSpace/auth.php" class="btn-primary" style="padding: 16px 35px; font-size: 0.9rem;">
                Create Free Account
            </a>
        </div>
    </div>

    <!-- ── Footer ── -->
    <footer class="landing-footer">
        <a href="/BlogSpace/index.php" class="logo" style="font-size: 1.2rem;">BlogSpace<span>.</span></a>
        <p>© 2026 BlogSpace. All rights reserved.</p>
    </footer>

</body>
</html>
