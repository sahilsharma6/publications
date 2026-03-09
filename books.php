<?php
include 'db.php';
session_start();

/* ── Fetch all categories that have at least 1 book ─────────── */
$catRes = $conn->query("
    SELECT c.id, c.name, COUNT(b.id) AS book_count
    FROM   categories c
    INNER JOIN books_data b ON b.category_id = c.id
    GROUP  BY c.id, c.name
    ORDER  BY c.name ASC
");
$categories = $catRes ? $catRes->fetch_all(MYSQLI_ASSOC) : [];

/* ── Active category ─────────────────────────────────────────── */
$activeCatId = (int) ($_GET['cat'] ?? 0);
if ($activeCatId === 0 && !empty($categories)) {
    $activeCatId = (int) $categories[0]['id'];
}

$activeCatName = 'All';
foreach ($categories as $c) {
    if ((int) $c['id'] === $activeCatId) {
        $activeCatName = $c['name'];
        break;
    }
}

/* ── Fetch ALL books for active category ─────────────────────── */
$books = [];
if ($activeCatId > 0) {
    $bStmt = $conn->prepare("
        SELECT id, title, img, price, authors, isbn, publishers, length
        FROM   books_data
        WHERE  category_id = ?
        ORDER  BY title ASC
    ");
    $bStmt->bind_param("i", $activeCatId);
    $bStmt->execute();
    $br = $bStmt->get_result();
    $books = $br ? $br->fetch_all(MYSQLI_ASSOC) : [];
    $bStmt->close();
}

/* ── Total books count ───────────────────────────────────────── */
$totalBest = (int) $conn->query("SELECT COUNT(*) FROM books_data")->fetch_row()[0];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books —
        <?= htmlspecialchars($activeCatName, ENT_QUOTES) ?>
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Outfit:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
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
        }

        body {
            background: var(--paper);
            color: var(--ink);
            font-family: "Outfit", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* ── PAGE HERO BAND ──────────────────────────────────────── */
        .page-hero {
            background: var(--ink);
            padding: 52px 0 48px;
            position: relative;
            overflow: hidden;
        }

        .page-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 55% 100% at 80% 50%, rgba(181, 57, 15, .2), transparent),
                radial-gradient(ellipse 35% 70% at 5% 80%, rgba(201, 146, 10, .12), transparent);
            pointer-events: none;
        }

        /* Decorative rule lines */
        .page-hero::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--accent), var(--accent2), transparent);
        }

        .hero-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 28px;
            position: relative;
            z-index: 1;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--accent2);
            margin-bottom: 14px;
        }

        .hero-eyebrow::before,
        .hero-eyebrow::after {
            content: '';
            display: block;
            width: 28px;
            height: 1px;
            background: currentColor;
            opacity: .6;
        }

        .hero-title {
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: clamp(36px, 5vw, 60px);
            font-weight: 700;
            line-height: 1.08;
            color: #faf7f2;
            letter-spacing: -.5px;
            margin-bottom: 14px;
        }

        .hero-title em {
            font-style: italic;
            color: rgba(250, 247, 242, .55);
        }

        .hero-sub {
            font-size: 15px;
            color: rgba(250, 247, 242, .5);
            margin-bottom: 0;
        }

        .hero-sub strong {
            color: rgba(250, 247, 242, .85);
            font-weight: 600;
        }

        /* ── LAYOUT ──────────────────────────────────────────────── */
        .page-body {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 28px 80px;
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 32px;
            align-items: start;
        }

        @media (max-width: 900px) {
            .page-body {
                grid-template-columns: 1fr;
            }
        }

        /* ── SIDEBAR ─────────────────────────────────────────────── */
        .cat-sidebar {
            position: sticky;
            top: 20px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            overflow: hidden;
            box-shadow: 0 2px 16px rgba(26, 18, 8, .07);
        }

        .sidebar-head {
            padding: 16px 18px;
            background: var(--ink);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-head-icon {
            width: 32px;
            height: 32px;
            border-radius: var(--r);
            background: rgba(255, 255, 255, .1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent2);
            font-size: 15px;
            flex-shrink: 0;
        }

        .sidebar-head-title {
            font-family: "Cormorant Garamond", serif;
            font-size: 16px;
            font-weight: 600;
            color: #faf7f2;
            line-height: 1;
        }

        .sidebar-head-sub {
            font-size: 11px;
            color: rgba(250, 247, 242, .4);
            margin-top: 1px;
        }

        /* Cat search */
        .sidebar-search {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
            position: relative;
        }

        .sidebar-search i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 13px;
            color: var(--muted);
            pointer-events: none;
        }

        .sidebar-search input {
            width: 100%;
            padding: 7px 10px 7px 30px;
            border: 1px solid var(--border);
            border-radius: var(--r);
            font-size: 12.5px;
            font-family: inherit;
            background: var(--cream);
            color: var(--ink);
            outline: none;
            transition: all var(--t);
        }

        .sidebar-search input:focus {
            border-color: var(--accent);
            background: #fff;
        }

        .cat-list {
            max-height: 480px;
            overflow-y: auto;
        }

        .cat-list::-webkit-scrollbar {
            width: 3px;
        }

        .cat-list::-webkit-scrollbar-thumb {
            background: var(--cream-dark);
            border-radius: 99px;
        }

        .cat-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 18px;
            border-bottom: 1px solid var(--border);
            text-decoration: none;
            color: var(--ink);
            transition: all var(--t);
            cursor: pointer;
            position: relative;
        }

        .cat-item:last-child {
            border-bottom: none;
        }

        .cat-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 0;
            background: var(--accent);
            transition: width var(--t);
        }

        .cat-item:hover {
            background: var(--cream);
            color: var(--ink);
        }

        .cat-item:hover::before {
            width: 3px;
        }

        .cat-item.active {
            background: var(--cream);
        }

        .cat-item.active::before {
            width: 3px;
        }

        .cat-item-left {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .cat-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--border);
            flex-shrink: 0;
            transition: background var(--t);
        }

        .cat-item.active .cat-dot,
        .cat-item:hover .cat-dot {
            background: var(--accent);
        }

        .cat-item-name {
            font-size: 13.5px;
            font-weight: 500;
            color: var(--ink);
            transition: color var(--t);
        }

        .cat-item.active .cat-item-name {
            font-weight: 700;
            color: var(--accent);
        }

        .cat-count {
            font-size: 11px;
            font-weight: 600;
            background: var(--cream-dark);
            color: var(--muted);
            padding: 2px 8px;
            border-radius: 99px;
            font-family: "DM Mono", monospace;
            transition: all var(--t);
        }

        .cat-item.active .cat-count {
            background: var(--accent);
            color: #fff;
        }

        /* ── MAIN CONTENT ────────────────────────────────────────── */
        .books-main {}

        /* Section header */
        .section-bar {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 28px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .section-bar-left {}

        .section-eyebrow {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 5px;
        }

        .section-title {
            font-family: "Cormorant Garamond", serif;
            font-size: 30px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.1;
            letter-spacing: -.3px;
        }

        .section-count {
            font-size: 13px;
            color: var(--muted);
            margin-top: 3px;
        }

        .section-count strong {
            color: var(--ink);
            font-weight: 600;
        }

        /* View toggle */
        .view-toggle {
            display: flex;
            gap: 4px;
        }

        .vtb {
            width: 36px;
            height: 36px;
            border: 1px solid var(--border);
            border-radius: var(--r);
            background: #fff;
            color: var(--muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            cursor: pointer;
            transition: all var(--t);
        }

        .vtb:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .vtb.active {
            background: var(--ink);
            border-color: var(--ink);
            color: #fff;
        }

        /* ── BOOK GRID ───────────────────────────────────────────── */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
        }

        @media (max-width: 600px) {
            .books-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
        }

        .book-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            transition: all var(--t);
            position: relative;
        }

        .book-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 0;
            background: linear-gradient(90deg, var(--accent), var(--accent2));
            transition: height var(--t);
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 40px rgba(26, 18, 8, .13);
            border-color: var(--cream-dark);
            color: inherit;
        }

        .book-card:hover::after {
            height: 3px;
        }

        /* Rank badge */
        .book-rank {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--ink);
            color: #fff;
            font-size: 10.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "DM Mono", monospace;
            box-shadow: 0 2px 8px rgba(26, 18, 8, .3);
            z-index: 1;
        }

        /* Cover */
        .book-cover {
            aspect-ratio: 3 / 4;
            overflow: hidden;
            background: var(--cream);
            position: relative;
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .5s var(--t);
        }

        .book-card:hover .book-cover img {
            transform: scale(1.06);
        }

        .book-cover-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: var(--cream-dark);
            background: linear-gradient(135deg, var(--cream), #ede6d8);
        }

        /* Quick-view overlay */
        .book-overlay {
            position: absolute;
            inset: 0;
            background: rgba(26, 18, 8, .55);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity var(--t);
            backdrop-filter: blur(2px);
        }

        .book-card:hover .book-overlay {
            opacity: 1;
        }

        .book-overlay-btn {
            background: #fff;
            color: var(--ink);
            font-size: 12px;
            font-weight: 700;
            padding: 8px 18px;
            border-radius: var(--r);
            display: flex;
            align-items: center;
            gap: 6px;
            transform: translateY(6px);
            transition: transform var(--t);
            font-family: "Outfit", sans-serif;
            letter-spacing: .3px;
        }

        .book-card:hover .book-overlay-btn {
            transform: translateY(0);
        }

        /* Body */
        .book-body {
            padding: 13px 14px 15px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
        }

        .book-title {
            font-family: "Cormorant Garamond", serif;
            font-size: 15px;
            font-weight: 600;
            color: var(--ink);
            line-height: 1.25;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-author {
            font-size: 11.5px;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .book-price {
            margin-top: auto;
            padding-top: 8px;
            font-family: "Cormorant Garamond", serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--accent);
            letter-spacing: -.3px;
        }

        .book-price span {
            font-size: 12px;
            font-family: "Outfit", sans-serif;
            color: var(--muted);
            font-weight: 400;
            margin-right: 2px;
        }

        /* ── LIST VIEW ───────────────────────────────────────────── */
        .books-list {
            display: none;
            flex-direction: column;
            gap: 10px;
        }

        .books-list.show {
            display: flex;
        }

        .books-grid.hide {
            display: none;
        }

        .book-list-row {
            display: flex;
            align-items: center;
            gap: 16px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            padding: 14px;
            text-decoration: none;
            color: inherit;
            transition: all var(--t);
            position: relative;
            overflow: hidden;
        }

        .book-list-row::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 0;
            background: linear-gradient(180deg, var(--accent), var(--accent2));
            transition: width var(--t);
        }

        .book-list-row:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 20px rgba(26, 18, 8, .1);
            border-color: var(--cream-dark);
            color: inherit;
        }

        .book-list-row:hover::after {
            width: 4px;
        }

        .list-rank {
            font-family: "DM Mono", monospace;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            min-width: 28px;
            flex-shrink: 0;
        }

        .list-thumb {
            width: 56px;
            height: 74px;
            object-fit: cover;
            border-radius: var(--r);
            flex-shrink: 0;
            border: 1px solid var(--border);
            background: var(--cream);
        }

        .list-thumb-placeholder {
            width: 56px;
            height: 74px;
            border-radius: var(--r);
            background: var(--cream);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--cream-dark);
            flex-shrink: 0;
        }

        .list-info {
            flex: 1;
            min-width: 0;
        }

        .list-title {
            font-family: "Cormorant Garamond", serif;
            font-size: 17px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 3px;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .list-author {
            font-size: 12.5px;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .list-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .list-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 8px;
            border-radius: 99px;
            background: var(--cream);
            border: 1px solid var(--border);
            font-size: 11px;
            color: var(--muted);
        }

        .list-pill i {
            font-size: 10px;
        }

        .list-price {
            font-family: "Cormorant Garamond", serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--accent);
            flex-shrink: 0;
            letter-spacing: -.3px;
            text-align: right;
        }

        .list-price small {
            font-size: 12px;
            font-family: "Outfit", sans-serif;
            color: var(--muted);
            font-weight: 400;
            display: block;
            margin-bottom: 1px;
        }

        .list-arrow {
            color: var(--cream-dark);
            font-size: 18px;
            flex-shrink: 0;
            transition: all var(--t);
        }

        .book-list-row:hover .list-arrow {
            color: var(--accent);
            transform: translateX(4px);
        }

        /* ── EMPTY STATE ─────────────────────────────────────────── */
        .empty-state {
            text-align: center;
            padding: 72px 24px;
        }

        .empty-icon {
            font-size: 56px;
            font-family: "Cormorant Garamond", serif;
            color: var(--cream-dark);
            margin-bottom: 18px;
            line-height: 1;
        }

        .empty-state h3 {
            font-family: "Cormorant Garamond", serif;
            font-size: 26px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
            color: var(--muted);
        }

        /* ── SCROLL REVEAL ───────────────────────────────────────── */
        .reveal {
            opacity: 0;
            transform: translateY(22px);
            transition: opacity .5s var(--t), transform .5s var(--t);
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* ── MOBILE CAT DRAWER ───────────────────────────────────── */
        .mobile-cat-bar {
            display: none;
            position: sticky;
            top: 0;
            z-index: 100;
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 10px 16px;
            box-shadow: 0 2px 12px rgba(26, 18, 8, .08);
        }

        @media (max-width: 900px) {
            .mobile-cat-bar {
                display: flex;
                align-items: center;
                gap: 12px;
                overflow-x: auto;
            }

            .page-body {
                gap: 20px;
            }

            .cat-sidebar {
                display: none;
            }
        }

        .mob-cat-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 99px;
            border: 1.5px solid var(--border);
            background: var(--cream);
            color: var(--ink);
            font-size: 12.5px;
            font-weight: 500;
            white-space: nowrap;
            text-decoration: none;
            transition: all var(--t);
            flex-shrink: 0;
        }

        .mob-cat-pill:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .mob-cat-pill.active {
            background: var(--ink);
            border-color: var(--ink);
            color: #fff;
        }

        .mob-cat-pill .mc-count {
            font-size: 10px;
            font-family: "DM Mono", monospace;
            opacity: .7;
        }
    </style>
</head>

<body>

    <?php include 'Header.php'; ?>

    <!-- ── HERO ─────────────────────────────────────────────────── -->
    <div class="page-hero">
        <div class="hero-inner">
            <div class="hero-eyebrow">Our Collection</div>
            <h1 class="hero-title">All <em>Books</em></h1>
            <p class="hero-sub">
                <strong>
                    <?= number_format($totalBest) ?> books
                </strong>
                across
                <?= count($categories) ?>
                <?= count($categories) === 1 ? 'category' : 'categories' ?>
            </p>
        </div>
    </div>

    <!-- ── MOBILE CATEGORY BAR ──────────────────────────────────── -->
    <div class="mobile-cat-bar">
        <?php foreach ($categories as $cat): ?>
            <a href="books.php?cat=<?= (int) $cat['id'] ?>"
                class="mob-cat-pill <?= (int) $cat['id'] === $activeCatId ? 'active' : '' ?>">
                <?= htmlspecialchars(ucfirst($cat['name']), ENT_QUOTES) ?>
                <span class="mc-count">
                    <?= (int) $cat['book_count'] ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- ── MAIN BODY ─────────────────────────────────────────────── -->
    <div class="page-body">

        <!-- SIDEBAR -->
        <aside class="cat-sidebar">
            <div class="sidebar-head">
                <div class="sidebar-head-icon"><i class="fas fa-bookmark"></i></div>
                <div>
                    <div class="sidebar-head-title">Categories</div>
                    <div class="sidebar-head-sub">
                        <?= count($categories) ?> available
                    </div>
                </div>
            </div>
            <div class="sidebar-search">
                <i class="fas fa-search"></i>
                <input type="text" id="catSearch" placeholder="Filter categories…" autocomplete="off">
            </div>
            <div class="cat-list" id="catList">
                <?php foreach ($categories as $cat): ?>
                    <a href="books.php?cat=<?= (int) $cat['id'] ?>"
                        class="cat-item <?= (int) $cat['id'] === $activeCatId ? 'active' : '' ?>"
                        data-name="<?= htmlspecialchars(strtolower($cat['name']), ENT_QUOTES) ?>">
                        <div class="cat-item-left">
                            <span class="cat-dot"></span>
                            <span class="cat-item-name">
                                <?= htmlspecialchars(ucfirst($cat['name']), ENT_QUOTES) ?>
                            </span>
                        </div>
                        <span class="cat-count">
                            <?= (int) $cat['book_count'] ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- BOOKS PANEL -->
        <main class="books-main">

            <!-- Section bar -->
            <div class="section-bar reveal">
                <div class="section-bar-left">
                    <div class="section-eyebrow">All Books</div>
                    <div class="section-title">
                        <?= htmlspecialchars(ucfirst($activeCatName), ENT_QUOTES) ?>
                    </div>
                    <div class="section-count">
                        <strong>
                            <?= count($books) ?>
                        </strong> featured
                        <?= count($books) === 1 ? 'title' : 'titles' ?>
                    </div>
                </div>
                <div class="view-toggle">
                    <button class="vtb active" id="gridBtn" title="Grid view" onclick="setView('grid')">
                        <i class="fas fa-th-large"></i>
                    </button>
                    <button class="vtb" id="listBtn" title="List view" onclick="setView('list')">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>

            <?php if (empty($books)): ?>
                <!-- Empty state -->
                <div class="empty-state reveal">
                    <div class="empty-icon">📚</div>
                    <h3>No Books Yet</h3>
                    <p>No books have been added to this category yet.<br>Check back soon.</p>
                </div>

            <?php else: ?>

                <!-- GRID VIEW -->
                <div class="books-grid" id="booksGrid">
                    <?php foreach ($books as $i => $book):
                        $imgPath = "./test/dashboard/" . ($book['img'] ?? '');
                        $hasImg = !empty($book['img']) && file_exists("./test/dashboard/" . $book['img']);
                        ?>
                        <a href="book_details.php?id=<?= (int) $book['id'] ?>" class="book-card reveal"
                            style="transition-delay: <?= $i * 0.04 ?>s">

                            <div class="book-rank">
                                <?= $i + 1 ?>
                            </div>

                            <div class="book-cover">
                                <?php if ($hasImg): ?>
                                    <img src="<?= htmlspecialchars($imgPath, ENT_QUOTES) ?>"
                                        alt="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="book-cover-placeholder"><i class="fas fa-book"></i></div>
                                <?php endif; ?>
                                <div class="book-overlay">
                                    <div class="book-overlay-btn">
                                        <i class="fas fa-eye"></i> View Details
                                    </div>
                                </div>
                            </div>

                            <div class="book-body">
                                <div class="book-title">
                                    <?= htmlspecialchars($book['title'], ENT_QUOTES) ?>
                                </div>
                                <?php if (!empty($book['authors'])): ?>
                                    <div class="book-author">
                                        <?= htmlspecialchars($book['authors'], ENT_QUOTES) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($book['price'])): ?>
                                    <div class="book-price"><span>₹</span>
                                        <?= htmlspecialchars($book['price'], ENT_QUOTES) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- LIST VIEW -->
                <div class="books-list" id="booksList">
                    <?php foreach ($books as $i => $book):
                        $imgPath = "../" . ($book['img'] ?? '');
                        $hasImg = !empty($book['img']) && file_exists("../" . $book['img']);
                        ?>
                        <a href="book_details.php?id=<?= (int) $book['id'] ?>" class="book-list-row reveal"
                            style="transition-delay: <?= $i * 0.03 ?>s">

                            <span class="list-rank">
                                <?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?>
                            </span>

                            <?php if ($hasImg): ?>
                                <img src="<?= htmlspecialchars($imgPath, ENT_QUOTES) ?>" class="list-thumb" alt="" loading="lazy">
                            <?php else: ?>
                                <div class="list-thumb-placeholder"><i class="fas fa-book"></i></div>
                            <?php endif; ?>

                            <div class="list-info">
                                <div class="list-title">
                                    <?= htmlspecialchars($book['title'], ENT_QUOTES) ?>
                                </div>
                                <?php if (!empty($book['authors'])): ?>
                                    <div class="list-author">
                                        <?= htmlspecialchars($book['authors'], ENT_QUOTES) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="list-meta">
                                    <?php if (!empty($book['publishers'])): ?>
                                        <span class="list-pill"><i class="fas fa-building"></i>
                                            <?= htmlspecialchars($book['publishers'], ENT_QUOTES) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($book['isbn'])): ?>
                                        <span class="list-pill"><i class="fas fa-barcode"></i>
                                            <?= htmlspecialchars($book['isbn'], ENT_QUOTES) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($book['length'])): ?>
                                        <span class="list-pill"><i class="fas fa-file-alt"></i>
                                            <?= htmlspecialchars($book['length'], ENT_QUOTES) ?> pages
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="list-price">
                                <?php if (!empty($book['price'])): ?>
                                    <small>₹</small>
                                    <?= htmlspecialchars($book['price'], ENT_QUOTES) ?>
                                <?php endif; ?>
                            </div>

                            <i class="fas fa-arrow-right list-arrow"></i>
                        </a>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </main>
    </div><!-- /page-body -->

    <?php include 'Footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /* ── View toggle ────────────────────────────────────────────── */
        let currentView = localStorage.getItem('booksView') || 'grid';
        setView(currentView, false);

        function setView(v, save = true) {
            currentView = v;
            const grid = document.getElementById('booksGrid');
            const list = document.getElementById('booksList');
            const gridBtn = document.getElementById('gridBtn');
            const listBtn = document.getElementById('listBtn');

            if (v === 'grid') {
                grid?.classList.remove('hide');
                list?.classList.remove('show');
                gridBtn?.classList.add('active');
                listBtn?.classList.remove('active');
            } else {
                grid?.classList.add('hide');
                list?.classList.add('show');
                listBtn?.classList.add('active');
                gridBtn?.classList.remove('active');
            }
            if (save) localStorage.setItem('booksView', v);
            // Re-trigger reveals for newly shown items
            setTimeout(triggerReveal, 50);
        }

        /* ── Category search ────────────────────────────────────────── */
        document.getElementById('catSearch')?.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('.cat-item').forEach(item => {
                item.style.display = (!q || item.dataset.name.includes(q)) ? '' : 'none';
            });
        });

        /* ── Scroll reveal ──────────────────────────────────────────── */
        function triggerReveal() {
            const obs = new IntersectionObserver(entries => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        e.target.classList.add('visible');
                        obs.unobserve(e.target);
                    }
                });
            }, { threshold: 0.08 });
            document.querySelectorAll('.reveal:not(.visible)').forEach(el => obs.observe(el));
        }
        triggerReveal();
    </script>
</body>

</html>