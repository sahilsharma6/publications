<?php
session_start();
/* Send the correct 404 HTTP status header */
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found | Professional Publication Services</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --ink: #1a1208;
            --paper: #faf8f4;
            --cream: #f3ede2;
            --cream-dark: #e6ddd0;
            --accent: #b5390f;
            --accent2: #c9920a;
            --muted: #7a6f62;
            --border: #e0d8cc;
            --r: 6px;
            --r-lg: 14px;
            --t: .22s cubic-bezier(.4, 0, .2, 1);
            --shadow: 0 4px 24px rgba(26, 18, 8, .08);
            --shadow-lg: 0 16px 48px rgba(26, 18, 8, .14);
        }

        html,
        body {
            height: 100%;
            font-family: "Outfit", sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        body {
            background: var(--ink);
            color: #faf7f2;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ════════════════════════════════════
       BACKGROUND
    ════════════════════════════════════ */
        .bg-layer {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

        /* Radial glows */
        .bg-layer::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 70% at 85% 20%, rgba(181, 57, 15, .2), transparent),
                radial-gradient(ellipse 45% 60% at 10% 80%, rgba(201, 146, 10, .12), transparent),
                radial-gradient(ellipse 40% 50% at 50% 50%, rgba(181, 57, 15, .06), transparent);
        }

        /* Subtle grid */
        .bg-layer::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, .025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .025) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        /* ════════════════════════════════════
       TOP NAV STRIP
    ════════════════════════════════════ */
        .err-nav {
            position: relative;
            z-index: 10;
            padding: 20px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, .07);
            background: rgba(26, 18, 8, .5);
            backdrop-filter: blur(12px);
        }

        .err-logo img {
            height: 46px;
            width: auto;
            filter: brightness(1.05);
        }

        .err-logo {
            text-decoration: none;
        }

        .err-nav-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 20px;
            background: rgba(255, 255, 255, .07);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: var(--r);
            color: rgba(250, 247, 242, .75);
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            transition: all var(--t);
            font-family: "Outfit", sans-serif;
        }

        .err-nav-link:hover {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        /* ════════════════════════════════════
       MAIN CONTENT
    ════════════════════════════════════ */
        .err-main {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 28px;
        }

        .err-content {
            text-align: center;
            max-width: 680px;
            width: 100%;
        }

        /* ─── Giant 404 number ─────────────────────── */
        .err-number-wrap {
            position: relative;
            display: inline-block;
            margin-bottom: 8px;
        }

        .err-404 {
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: clamp(120px, 20vw, 220px);
            font-weight: 700;
            line-height: .9;
            letter-spacing: -8px;
            color: transparent;
            -webkit-text-stroke: 1.5px rgba(255, 255, 255, .12);
            position: relative;
            user-select: none;
            animation: fadeIn404 1s cubic-bezier(.4, 0, .2, 1) both;
        }

        /* Glowing fill overlay */
        .err-404-fill {
            position: absolute;
            inset: 0;
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: clamp(120px, 20vw, 220px);
            font-weight: 700;
            line-height: .9;
            letter-spacing: -8px;
            background: linear-gradient(135deg, rgba(181, 57, 15, .55) 0%, rgba(201, 146, 10, .55) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: fadeIn404 1s .1s cubic-bezier(.4, 0, .2, 1) both;
        }

        @keyframes fadeIn404 {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        /* Divider line through 404 */
        .err-divider {
            display: flex;
            align-items: center;
            gap: 14px;
            justify-content: center;
            margin: 4px 0 28px;
            animation: slideUp .7s .3s cubic-bezier(.4, 0, .2, 1) both;
        }

        .err-divider::before,
        .err-divider::after {
            content: '';
            flex: 1;
            max-width: 80px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(201, 146, 10, .5));
        }

        .err-divider::after {
            background: linear-gradient(90deg, rgba(201, 146, 10, .5), transparent);
        }

        .err-divider-label {
            font-family: "DM Mono", monospace;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--accent2);
        }

        /* ─── Heading ──────────────────────────────── */
        .err-h1 {
            font-family: "Cormorant Garamond", serif;
            font-size: clamp(26px, 4vw, 44px);
            font-weight: 700;
            color: #faf7f2;
            line-height: 1.1;
            letter-spacing: -.3px;
            margin-bottom: 14px;
            animation: slideUp .7s .4s cubic-bezier(.4, 0, .2, 1) both;
        }

        .err-h1 em {
            font-style: italic;
            color: rgba(250, 247, 242, .38);
        }

        .err-p {
            font-size: 15px;
            color: rgba(250, 247, 242, .48);
            line-height: 1.72;
            max-width: 480px;
            margin: 0 auto 36px;
            animation: slideUp .7s .5s cubic-bezier(.4, 0, .2, 1) both;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        /* ─── Action buttons ───────────────────────── */
        .err-btns {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 52px;
            animation: slideUp .7s .6s cubic-bezier(.4, 0, .2, 1) both;
        }

        .btn-err {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 26px;
            border-radius: var(--r);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all var(--t);
            font-family: "Outfit", sans-serif;
        }

        .btn-err-solid {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 4px 20px rgba(181, 57, 15, .4);
        }

        .btn-err-solid:hover {
            background: #9b2e08;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(181, 57, 15, .5);
        }

        .btn-err-ghost {
            border: 1.5px solid rgba(255, 255, 255, .15);
            color: rgba(250, 247, 242, .72);
            background: transparent;
        }

        .btn-err-ghost:hover {
            border-color: rgba(255, 255, 255, .38);
            color: #fff;
            background: rgba(255, 255, 255, .06);
        }

        /* ─── Quick links ──────────────────────────── */
        .err-links-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(250, 247, 242, .28);
            margin-bottom: 14px;
            animation: slideUp .7s .7s cubic-bezier(.4, 0, .2, 1) both;
        }

        .err-links {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            animation: slideUp .7s .75s cubic-bezier(.4, 0, .2, 1) both;
        }

        .err-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 99px;
            font-size: 13px;
            font-weight: 500;
            color: rgba(250, 247, 242, .6);
            text-decoration: none;
            transition: all var(--t);
        }

        .err-link i {
            font-size: 12px;
            color: var(--accent2);
        }

        .err-link:hover {
            background: rgba(255, 255, 255, .1);
            border-color: rgba(201, 146, 10, .25);
            color: #faf7f2;
        }

        /* ════════════════════════════════════
       FLOATING PARTICLES
    ════════════════════════════════════ */
        .particles {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            opacity: 0;
            animation: particleDrift var(--dur) var(--delay) ease-in-out infinite;
        }

        @keyframes particleDrift {
            0% {
                opacity: 0;
                transform: translateY(0) scale(.5);
            }

            20% {
                opacity: var(--op);
            }

            80% {
                opacity: var(--op);
            }

            100% {
                opacity: 0;
                transform: translateY(-120px) scale(1.2);
            }
        }

        /* ════════════════════════════════════
       FOOTER STRIP
    ════════════════════════════════════ */
        .err-footer {
            position: relative;
            z-index: 1;
            padding: 18px 28px;
            text-align: center;
            font-size: 12px;
            color: rgba(250, 247, 242, .22);
            border-top: 1px solid rgba(255, 255, 255, .05);
            font-family: "DM Mono", monospace;
        }

        .err-footer span {
            color: rgba(201, 146, 10, .5);
        }

        /* ════════════════════════════════════
       RESPONSIVE
    ════════════════════════════════════ */
        @media(max-width:480px) {
            .err-nav {
                padding: 14px 16px;
            }

            .err-btns {
                flex-direction: column;
                align-items: center;
            }

            .btn-err {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>

    <!-- Background layers -->
    <div class="bg-layer"></div>

    <!-- Floating particles -->
    <div class="particles" id="particles"></div>

    <!-- Nav -->
    <nav class="err-nav">
        <a href="./" class="err-logo">
            <img src="./uploads/logos/logotest.png" alt="Professional Publication Services">
        </a>
        <a href="./" class="err-nav-link">
            <i class="fas fa-home"></i> Back to Home
        </a>
    </nav>

    <!-- Main -->
    <main class="err-main">
        <div class="err-content">

            <!-- 404 number -->
            <div class="err-number-wrap">
                <div class="err-404">404</div>
                <div class="err-404-fill">404</div>
            </div>

            <!-- Divider -->
            <div class="err-divider">
                <span class="err-divider-label">Page Not Found</span>
            </div>

            <!-- Heading & text -->
            <h1 class="err-h1">This page seems to have<br><em>gone missing</em></h1>
            <p class="err-p">
                The page you're looking for doesn't exist, has been moved, or the URL may be incorrect.
                Don't worry — let's get you back on track.
            </p>

            <!-- Action buttons -->
            <div class="err-btns">
                <a href="./" class="btn-err btn-err-solid">
                    <i class="fas fa-home"></i> Go to Homepage
                </a>
                <a href="./books.php" class="btn-err btn-err-ghost">
                    <i class="fas fa-book-open"></i> Browse Books
                </a>
                <a href="./contact.php" class="btn-err btn-err-ghost">
                    <i class="fas fa-envelope"></i> Contact Us
                </a>
            </div>

            <!-- Quick links -->
            <div class="err-links-label">Or explore these pages</div>
            <div class="err-links">
                <a href="./" class="err-link"><i class="fas fa-home"></i> Home</a>
                <a href="./about.php" class="err-link"><i class="fas fa-info-circle"></i> About Us</a>
                <a href="./books.php" class="err-link"><i class="fas fa-book"></i> Books</a>
                <a href="./journal-development.php" class="err-link"><i class="fas fa-journal-whills"></i> Journal
                    Development</a>
                <a href="./contact.php" class="err-link"><i class="fas fa-phone"></i> Contact</a>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="err-footer">
        Error <span>404</span> &nbsp;·&nbsp; Professional Publication Services &nbsp;·&nbsp;
        <a href="./" style="color:rgba(201,146,10,.5); text-decoration:none;">Return Home</a>
    </footer>

    <script>
        /* ── Generate floating particles ─────────────────────────── */
        (function () {
            const container = document.getElementById('particles');
            const colors = [
                'rgba(181,57,15,',
                'rgba(201,146,10,',
                'rgba(250,247,242,',
            ];

            for (let i = 0; i < 28; i++) {
                const p = document.createElement('div');
                p.className = 'particle';

                const size = Math.random() * 4 + 1.5;
                const color = colors[Math.floor(Math.random() * colors.length)];
                const op = (Math.random() * 0.15 + 0.04).toFixed(2);
                const dur = (Math.random() * 12 + 8).toFixed(1) + 's';
                const delay = (Math.random() * 10).toFixed(1) + 's';
                const left = Math.random() * 100;
                const top = Math.random() * 100;

                p.style.cssText = `
          width:${size}px; height:${size}px;
          background:${color}0.6);
          left:${left}%;
          top:${top}%;
          --dur:${dur};
          --delay:${delay};
          --op:${op};
          box-shadow: 0 0 ${size * 3}px ${color}0.3);
        `;
                container.appendChild(p);
            }
        })();
    </script>
</body>

</html>