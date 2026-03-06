<!-- <nav class="navbar navbar-expand-lg navbar-modern">
    <div class="container">
        <a class="navbar-brand fw-bold" href="./"><img src="../uploads/logos/logotest.png" height="100" width="150"
                alt=""></a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="./books.php">Books</a></li>
                <li class="nav-item"><a class="nav-link" href="./contact.php">Contact</a></li>
            </ul>
            <a href="login.php" class="btn btn-primary-custom">Login</a>
        </div>
    </div>
</nav> -->



<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$userName = $_SESSION['username'] ?? 'Account';
?>
<style>
    /* ══════════════════════════════════════════════
   NAVBAR
══════════════════════════════════════════════ */
    :root {
        --ink: #1a1208;
        --paper: #faf8f4;
        --cream: #f3ede2;
        --accent: #b5390f;
        --accent2: #c9920a;
        --muted: #7a6f62;
        --border: #e0d8cc;
        --t: .22s cubic-bezier(.4, 0, .2, 1);
    }

    .site-nav {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: rgba(26, 18, 8, .96);
        backdrop-filter: blur(14px) saturate(1.4);
        -webkit-backdrop-filter: blur(14px) saturate(1.4);
        border-bottom: 1px solid rgba(255, 255, 255, .07);
        transition: all .3s var(--t);
        font-family: "Outfit", sans-serif;
    }

    /* Scrolled state — slightly lighter border */
    .site-nav.scrolled {
        background: rgba(26, 18, 8, .98);
        border-bottom-color: rgba(201, 146, 10, .2);
        box-shadow: 0 4px 24px rgba(0, 0, 0, .3);
    }

    .nav-inner {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 28px;
        height: 68px;
        display: flex;
        align-items: center;
        gap: 0;
    }

    /* ── Logo ─────────────────────────────────── */
    .nav-logo {
        display: flex;
        align-items: center;
        text-decoration: none;
        flex-shrink: 0;
        margin-right: auto;
    }

    .nav-logo img {
        height: 52px;
        width: auto;
        display: block;
        filter: brightness(1.05);
        transition: opacity var(--t);
    }

    .nav-logo:hover img {
        opacity: .85;
    }

    /* ── Desktop links ────────────────────────── */
    .nav-links {
        display: flex;
        align-items: center;
        gap: 4px;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .nav-links li {
        position: relative;
    }

    .nav-links a {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 500;
        letter-spacing: .15px;
        color: rgba(250, 247, 242, .62);
        text-decoration: none;
        border-radius: 6px;
        transition: all var(--t);
        position: relative;
    }

    .nav-links a::after {
        content: '';
        position: absolute;
        bottom: 2px;
        left: 16px;
        right: 16px;
        height: 2px;
        border-radius: 99px;
        background: var(--accent2);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .25s var(--t);
    }

    .nav-links a:hover {
        color: rgba(250, 247, 242, .95);
        background: rgba(255, 255, 255, .06);
    }

    .nav-links a:hover::after {
        transform: scaleX(1);
    }

    .nav-links a.active {
        color: #faf7f2;
        font-weight: 700;
        background: rgba(255, 255, 255, .07);
    }

    .nav-links a.active::after {
        transform: scaleX(1);
    }

    .nav-links a i {
        font-size: 13px;
        opacity: .6;
    }

    /* ── Dropdown ─────────────────────────────── */
    .has-drop>a::before {
        content: '\f107';
        /* chevron-down */
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        font-size: 10px;
        opacity: .55;
        margin-left: 2px;
        display: inline-block;
        transition: transform var(--t);
    }

    .has-drop:hover>a::before {
        transform: rotate(180deg);
    }

    .drop-menu {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        min-width: 200px;
        background: #1e1509;
        border: 1px solid rgba(255, 255, 255, .09);
        border-radius: 10px;
        padding: 6px;
        box-shadow: 0 18px 48px rgba(0, 0, 0, .5);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-8px);
        transition: all .22s var(--t);
        pointer-events: none;
        z-index: 100;
    }

    .has-drop:hover .drop-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
        pointer-events: all;
    }

    .drop-menu a {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 9px 12px;
        border-radius: 6px;
        font-size: 13px;
        color: rgba(250, 247, 242, .65);
    }

    .drop-menu a::after {
        display: none;
    }

    .drop-menu a:hover {
        background: rgba(255, 255, 255, .07);
        color: rgba(250, 247, 242, .95);
    }

    .drop-menu a i {
        font-size: 13px;
        color: var(--accent2);
        width: 16px;
        flex-shrink: 0;
    }

    .drop-divider {
        height: 1px;
        background: rgba(255, 255, 255, .07);
        margin: 4px 6px;
    }

    /* ── Right side ───────────────────────────── */
    .nav-right {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left: 18px;
        flex-shrink: 0;
    }

    /* Search icon button */
    .nav-icon-btn {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: rgba(255, 255, 255, .06);
        border: 1px solid rgba(255, 255, 255, .09);
        color: rgba(250, 247, 242, .55);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        transition: all var(--t);
    }

    .nav-icon-btn:hover {
        background: rgba(255, 255, 255, .12);
        color: #faf7f2;
        border-color: rgba(255, 255, 255, .18);
    }

    /* Login / CTA button */
    .btn-nav-login {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 20px;
        background: var(--accent);
        color: #fff;
        border-radius: 6px;
        border: none;
        font-size: 13.5px;
        font-weight: 600;
        letter-spacing: .15px;
        text-decoration: none;
        font-family: "Outfit", sans-serif;
        transition: all var(--t);
        box-shadow: 0 2px 12px rgba(181, 57, 15, .4);
        white-space: nowrap;
    }

    .btn-nav-login:hover {
        background: #9b2e08;
        color: #fff;
        box-shadow: 0 4px 18px rgba(181, 57, 15, .55);
        transform: translateY(-1px);
    }

    /* ── Divider ──────────────────────────────── */
    .nav-divider {
        width: 1px;
        height: 22px;
        background: rgba(255, 255, 255, .1);
        margin: 0 4px;
    }

    /* ══════════════════════════════════════════════
   MOBILE HAMBURGER
══════════════════════════════════════════════ */
    .nav-burger {
        display: none;
        flex-direction: column;
        justify-content: center;
        gap: 5px;
        width: 40px;
        height: 40px;
        padding: 6px;
        cursor: pointer;
        border: none;
        background: transparent;
        border-radius: 8px;
        margin-left: 12px;
        transition: background var(--t);
    }

    .nav-burger:hover {
        background: rgba(255, 255, 255, .06);
    }

    .nav-burger span {
        display: block;
        height: 2px;
        border-radius: 99px;
        background: rgba(250, 247, 242, .75);
        transition: all .3s var(--t);
        transform-origin: center;
    }

    .nav-burger.open span:nth-child(1) {
        transform: translateY(7px) rotate(45deg);
    }

    .nav-burger.open span:nth-child(2) {
        opacity: 0;
        transform: scaleX(0);
    }

    .nav-burger.open span:nth-child(3) {
        transform: translateY(-7px) rotate(-45deg);
    }

    /* ══════════════════════════════════════════════
   MOBILE DRAWER
══════════════════════════════════════════════ */
    .nav-drawer {
        display: none;
        /* JS toggles show class */
        position: fixed;
        top: 68px;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(26, 18, 8, .97);
        backdrop-filter: blur(16px);
        padding: 24px 24px 40px;
        overflow-y: auto;
        z-index: 999;
        flex-direction: column;
        gap: 4px;
        transform: translateY(-12px);
        opacity: 0;
        transition: opacity .28s var(--t), transform .28s var(--t);
    }

    .nav-drawer.show {
        display: flex;
        transform: translateY(0);
        opacity: 1;
    }

    .drawer-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px 16px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 500;
        color: rgba(250, 247, 242, .65);
        text-decoration: none;
        transition: all var(--t);
        border: 1px solid transparent;
    }

    .drawer-link:hover {
        background: rgba(255, 255, 255, .06);
        border-color: rgba(255, 255, 255, .08);
        color: #faf7f2;
    }

    .drawer-link.active {
        background: rgba(201, 146, 10, .1);
        border-color: rgba(201, 146, 10, .2);
        color: #faf7f2;
        font-weight: 700;
    }

    .drawer-link i {
        font-size: 16px;
        color: var(--accent2);
        width: 20px;
        flex-shrink: 0;
        text-align: center;
    }

    .drawer-link .dlbl {
        flex: 1;
    }

    .drawer-link .dbadge {
        font-size: 10.5px;
        font-weight: 700;
        padding: 2px 8px;
        background: rgba(201, 146, 10, .15);
        color: var(--accent2);
        border-radius: 99px;
        flex-shrink: 0;
    }

    .drawer-divider {
        height: 1px;
        background: rgba(255, 255, 255, .07);
        margin: 8px 0;
    }

    .drawer-bottom {
        margin-top: auto;
        padding-top: 20px;
    }

    .btn-drawer-login {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 14px;
        background: var(--accent);
        color: #fff;
        border-radius: 10px;
        border: none;
        font-family: "Outfit", sans-serif;
        font-size: 15px;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 4px 20px rgba(181, 57, 15, .4);
        transition: all var(--t);
    }

    .btn-drawer-login:hover {
        background: #9b2e08;
        color: #fff;
    }

    /* ══════════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════════ */
    @media (max-width: 900px) {

        .nav-links,
        .nav-right .nav-icon-btn,
        .nav-divider,
        .btn-nav-login {
            display: none;
        }

        .nav-burger {
            display: flex;
        }

        body.nav-open {
            overflow: hidden;
        }
    }

    @media (max-width: 480px) {
        .nav-inner {
            padding: 0 16px;
        }
    }
</style>

<nav class="site-nav" id="siteNav">
    <div class="nav-inner">

        <!-- Logo -->
        <a href="./" class="nav-logo">
            <img src="../uploads/logos/logotest.png" alt="Professional Publication Services">
        </a>

        <!-- Desktop links -->
        <ul class="nav-links" id="navLinks">
            <li>
                <a href="./" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Home
                </a>
            </li>
            <li>
                <a href="./books.php" class="<?= $currentPage === 'books.php' ? 'active' : '' ?>">
                    <i class="fas fa-book-open"></i> Books
                </a>
            </li>
            <!-- <li class="has-drop">
                <a href="#" class="<?= in_array($currentPage, ['services.php']) ? 'active' : '' ?>">
                    <i class="fas fa-layer-group"></i> Services
                </a>
                <div class="drop-menu">
                    <a href="#services"><i class="fas fa-pen-nib"></i> Manuscript Writing</a>
                    <a href="#services"><i class="fas fa-magnifying-glass"></i> Journal Selection</a>
                    <a href="#services"><i class="fas fa-spell-check"></i> Editing Services</a>
                    <div class="drop-divider"></div>
                    <a href="#services"><i class="fas fa-file-signature"></i> Publication Support</a>
                </div>
            </li> -->
            <li>
                <a href="./contact.php" class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i> Contact
                </a>
            </li>
        </ul>

        <!-- Right side -->
        <div class="nav-right">
            <div class="nav-divider"></div>
            <a href="./contact.php" class="nav-icon-btn" title="Contact us">
                <i class="fas fa-phone"></i>
            </a>
            <?php if ($isLoggedIn): ?>
                <a href="<?= $isAdmin ? './dashboard' : 'profile.php' ?>" class="nav-icon-btn"
                    title="<?= htmlspecialchars($userName) ?>">
                    <i class="fas fa-user-circle" style="font-size:20px; color:var(--accent2);"></i>
                </a>
            <?php else: ?>

            <?php endif; ?>
        </div>

        <!-- Hamburger -->
        <button class="nav-burger" id="navBurger" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>

    </div>
</nav>

<!-- Mobile Drawer -->
<div class="nav-drawer" id="navDrawer">

    <a href="./" class="drawer-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span class="dlbl">Home</span>
    </a>

    <a href="./books.php" class="drawer-link <?= $currentPage === 'books.php' ? 'active' : '' ?>">
        <i class="fas fa-book-open"></i>
        <span class="dlbl">Books</span>
        <span class="dbadge">New</span>
    </a>





    <div class="drawer-divider"></div>

    <a href="./contact.php" class="drawer-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>">
        <i class="fas fa-envelope"></i>
        <span class="dlbl">Contact Us</span>
    </a>

    <div class="drawer-bottom">
        <?php if ($isLoggedIn): ?>
            <a href="<?= $isAdmin ? './dashboard' : 'profile.php' ?>" class="drawer-link">
                <i class="fas fa-user-circle"></i>
                <span class="dlbl">
                    <?= htmlspecialchars($userName) ?>
                </span>
            </a>
            <div class="drawer-divider"></div>
            <a href="../logout.php" class="btn-drawer-login" style="background:#333;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        <?php else: ?>
        <?php endif; ?>
    </div>

</div>

<script>
    (function () {
        const nav = document.getElementById('siteNav');
        const burger = document.getElementById('navBurger');
        const drawer = document.getElementById('navDrawer');
        let open = false;

        /* Scroll state */
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 30);
        }, { passive: true });

        /* Burger toggle */
        burger.addEventListener('click', () => {
            open = !open;
            burger.classList.toggle('open', open);
            drawer.classList.toggle('show', open);
            document.body.classList.toggle('nav-open', open);
        });

        /* Close on outside click */
        document.addEventListener('click', e => {
            if (open && !nav.contains(e.target) && !drawer.contains(e.target)) {
                closeDrawer();
            }
        });

        /* Close on ESC */
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });

        /* Expose close fn */
        window.closeDrawer = () => {
            open = false;
            burger.classList.remove('open');
            drawer.classList.remove('show');
            document.body.classList.remove('nav-open');
        };
    })();
</script>