<?php
include 'db.php';
session_start();

$author_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$author_id) {
    header("Location: all_authors.php");
    exit();
}

// Fetch author
$stmt = $conn->prepare("SELECT * FROM authors WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $author_id);
$stmt->execute();
$author = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$author) {
    header("Location: ./");
    exit();
}

// Fetch books by this author
$stmt = $conn->prepare(
    "SELECT bd.id, bd.title AS title, bd.img, bd.price, bd.publishers, bd.authors AS author_str, c.name AS category
     FROM book_authors ba
     JOIN books_data bd ON bd.id = ba.book_id
     JOIN categories c ON c.id = bd.category_id
     WHERE ba.author_id = ?
     ORDER BY bd.title ASC"
);
$stmt->bind_param("i", $author_id);
$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Image path
$imgPath = "../uploads/authors/" . $author['image'];
$hasImg = !empty($author['image']) && file_exists($imgPath);
$initial = strtoupper(substr($author['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($author['name']) ?> — Author Profile
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,900;1,400;1,600&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --ink: #0f0e0d;
            --paper: #faf8f5;
            --cream: #f2ede6;
            --accent: #c8472b;
            --accent-light: #fdf1ee;
            --gold: #b8860b;
            --muted: #7a7165;
            --border: #e6e0d6;
            --r: 4px;
            --r-lg: 12px;
            --r-xl: 20px;
            --shadow: 0 4px 24px rgba(15, 14, 13, .08);
            --shadow-lg: 0 16px 56px rgba(15, 14, 13, .14);
            --t: 0.22s cubic-bezier(.4, 0, .2, 1);
        }

        body {
            background: var(--paper);
            color: var(--ink);
            font-family: "DM Sans", sans-serif;
        }

        /* ── Hero backdrop ── */
        .author-hero {
            background: var(--ink);
            padding: 0;
            position: relative;
            overflow: hidden;
            min-height: 340px;
            display: flex;
            align-items: flex-end;
        }

        .hero-bg-text {
            position: absolute;
            right: 0;
            bottom: -20px;
            font-family: "Playfair Display", serif;
            font-size: 280px;
            font-weight: 900;
            font-style: italic;
            line-height: 1;
            pointer-events: none;
            color: rgba(255, 255, 255, .025);
            overflow: hidden;
            max-width: 60%;
            white-space: nowrap;
        }

        .hero-glow {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 0% 100%, rgba(200, 71, 43, .18) 0%, transparent 55%),
                radial-gradient(ellipse at 100% 0%, rgba(184, 134, 11, .1) 0%, transparent 50%);
        }

        .hero-stripe {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent), var(--gold), var(--accent));
        }

        /* ── Profile block (overlapping) ── */
        .profile-block {
            position: relative;
            z-index: 2;
            width: 100%;
            padding: 56px 0 0;
        }

        .profile-inner {
            display: flex;
            align-items: flex-end;
            gap: 32px;
            flex-wrap: wrap;
            padding-bottom: 0;
        }

        /* Avatar */
        .profile-avatar-wrap {
            flex-shrink: 0;
            margin-bottom: -48px;
        }

        .profile-avatar {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--ink);
            box-shadow: 0 8px 40px rgba(0, 0, 0, .4);
            background: var(--ink);
            display: block;
        }

        .profile-initials {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2a2624, #1a1816);
            color: rgba(255, 255, 255, .9);
            font-family: "Playfair Display", serif;
            font-size: 52px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 5px solid var(--ink);
            box-shadow: 0 8px 40px rgba(0, 0, 0, .4);
        }

        .profile-title-group {
            flex: 1;
            min-width: 200px;
            padding-bottom: 20px;
        }

        .profile-eyebrow {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .profile-eyebrow::before {
            content: '';
            width: 20px;
            height: 1.5px;
            background: var(--accent);
        }

        .profile-name {
            font-family: "Playfair Display", serif;
            font-size: clamp(28px, 4vw, 48px);
            font-weight: 900;
            color: #fff;
            line-height: 1.1;
            letter-spacing: -1px;
            margin-bottom: 10px;
        }

        .profile-role {
            font-size: 14px;
            color: rgba(255, 255, 255, .5);
            font-style: italic;
        }

        .profile-book-count {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 14px;
            border-radius: 99px;
            border: 1px solid rgba(255, 255, 255, .12);
            background: rgba(255, 255, 255, .06);
            font-size: 12.5px;
            color: rgba(255, 255, 255, .65);
            margin-top: 14px;
        }

        .profile-book-count strong {
            color: #fff;
        }

        /* ── Content area ── */
        .author-content {
            padding: 72px 0 80px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 40px;
            align-items: start;
        }

        @media(max-width:900px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── Biography ── */
        .bio-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-xl);
            overflow: hidden;
            margin-bottom: 32px;
        }

        .bio-card-header {
            padding: 20px 28px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .bio-header-icon {
            width: 38px;
            height: 38px;
            border-radius: var(--r-lg);
            background: var(--accent-light);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
        }

        .bio-header-text h2 {
            font-size: 15px;
            font-weight: 700;
            color: var(--ink);
            margin: 0;
        }

        .bio-header-text p {
            font-size: 12px;
            color: var(--muted);
            margin: 2px 0 0;
        }

        .bio-card-body {
            padding: 28px;
        }

        .bio-text {
            font-size: 15px;
            line-height: 1.8;
            color: #3d3830;
        }

        .bio-text p {
            margin-bottom: 16px;
        }

        .bio-text p:last-child {
            margin-bottom: 0;
        }

        .no-bio {
            font-size: 14px;
            color: var(--muted);
            font-style: italic;
            text-align: center;
            padding: 24px 0;
        }

        /* ── Books section ── */
        .books-section-title {
            font-family: "Playfair Display", serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .books-section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .books-count {
            font-size: 12px;
            font-weight: 400;
            color: var(--muted);
            font-family: "DM Sans", sans-serif;
        }

        .books-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .book-row {
            display: flex;
            gap: 18px;
            align-items: flex-start;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            padding: 16px;
            text-decoration: none;
            color: inherit;
            transition: all var(--t);
            position: relative;
            overflow: hidden;
        }

        .book-row::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--accent);
            transform: scaleY(0);
            transition: transform .25s var(--t);
            transform-origin: bottom;
        }

        .book-row:hover {
            box-shadow: var(--shadow);
            border-color: rgba(200, 71, 43, .2);
            color: inherit;
        }

        .book-row:hover::before {
            transform: scaleY(1);
        }

        .book-thumb {
            width: 64px;
            flex-shrink: 0;
        }

        .book-thumb img {
            width: 64px;
            height: 88px;
            object-fit: cover;
            border-radius: var(--r);
            box-shadow: var(--shadow);
            display: block;
        }

        .book-thumb-placeholder {
            width: 64px;
            height: 88px;
            border-radius: var(--r);
            background: var(--cream);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: var(--muted);
        }

        .book-row-info {
            flex: 1;
            min-width: 0;
        }

        .book-row-title {
            font-family: "Playfair Display", serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 5px;
            line-height: 1.3;
            transition: color var(--t);
        }

        .book-row:hover .book-row-title {
            color: var(--accent);
        }

        .book-row-meta {
            font-size: 12px;
            color: var(--muted);
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 10px;
        }

        .book-row-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .book-row-price {
            font-size: 16px;
            font-weight: 700;
            color: var(--accent);
            font-family: "Playfair Display", serif;
        }

        .book-cat-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 9px;
            border-radius: 99px;
            background: var(--cream);
            border: 1px solid var(--border);
            font-size: 11px;
            color: var(--muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .no-books-msg {
            text-align: center;
            padding: 40px 24px;
            background: var(--cream);
            border-radius: var(--r-lg);
            border: 1px dashed var(--border);
        }

        .no-books-msg i {
            font-size: 32px;
            color: var(--border);
            display: block;
            margin-bottom: 10px;
        }

        .no-books-msg p {
            font-size: 14px;
            color: var(--muted);
            margin: 0;
        }

        /* ── Right sidebar ── */
        .author-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
            position: sticky;
            top: 90px;
        }

        .sidebar-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-xl);
            overflow: hidden;
        }

        .sidebar-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
        }

        .sidebar-card-header i {
            color: var(--accent);
            font-size: 16px;
        }

        .sidebar-card-body {
            padding: 18px 20px;
        }

        /* Quick stats */
        .stat-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }

        .stat-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .stat-label {
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-label i {
            font-size: 14px;
        }

        .stat-value {
            font-weight: 700;
            color: var(--ink);
        }

        /* Back / all-authors */
        .btn-back {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: var(--r-lg);
            border: 1.5px solid var(--border);
            background: var(--cream);
            font-size: 13.5px;
            font-weight: 600;
            color: var(--ink);
            text-decoration: none;
            transition: all var(--t);
        }

        .btn-back:hover {
            background: var(--ink);
            color: #fff;
            border-color: var(--ink);
        }

        .btn-all-authors {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: var(--r-lg);
            border: 1.5px solid var(--accent);
            background: var(--accent);
            font-size: 13.5px;
            font-weight: 600;
            color: #fff;
            text-decoration: none;
            transition: all var(--t);
        }

        .btn-all-authors:hover {
            background: #aa3a22;
            border-color: #aa3a22;
            color: #fff;
        }

        /* ── Breadcrumb ── */
        .page-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: rgba(255, 255, 255, .4);
            padding: 20px 0 0;
            margin-bottom: 0;
        }

        .page-breadcrumb a {
            color: rgba(255, 255, 255, .4);
            text-decoration: none;
            transition: color var(--t);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .page-breadcrumb a:hover {
            color: rgba(255, 255, 255, .8);
        }

        .page-breadcrumb i.sep {
            font-size: 12px;
        }

        /* ── Animation ── */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(24px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .animate-in {
            animation: fadeInUp .5s both;
        }

        .delay-1 {
            animation-delay: .1s;
        }

        .delay-2 {
            animation-delay: .2s;
        }

        .delay-3 {
            animation-delay: .3s;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <!-- Hero -->
    <section class="author-hero">
        <div class="hero-glow"></div>
        <div class="hero-bg-text">
            <?= htmlspecialchars($author['name']) ?>
        </div>
        <div class="hero-stripe"></div>
        <div class="container w-100">
            <nav class="page-breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <i class="fas fa-chevron-right sep"></i>
                <a href="all_authors.php">Authors</a>
                <i class="fas fa-chevron-right sep"></i>
                <span style="color:rgba(255,255,255,.7)">
                    <?= htmlspecialchars($author['name']) ?>
                </span>
            </nav>
            <div class="profile-block">
                <div class="profile-inner">
                    <div class="profile-avatar-wrap animate-in">
                        <?php if ($hasImg): ?>
                            <img src="<?= htmlspecialchars($imgPath, ENT_QUOTES) ?>"
                                alt="<?= htmlspecialchars($author['name'], ENT_QUOTES) ?>" class="profile-avatar">
                        <?php else: ?>
                            <div class="profile-initials">
                                <?= $initial ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-title-group animate-in delay-1">
                        <div class="profile-eyebrow">Author Profile</div>
                        <h1 class="profile-name">
                            <?= htmlspecialchars($author['name'], ENT_QUOTES) ?>
                        </h1>
                        <?php if (!empty($author['title'])): ?>
                            <div class="profile-role">
                                <?= htmlspecialchars($author['title'], ENT_QUOTES) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($books)): ?>
                            <div class="profile-book-count">
                                <i class="fas fa-book-open" style="font-size:12px"></i>
                                <span><strong>
                                        <?= count($books) ?>
                                    </strong>
                                    <?= count($books) === 1 ? 'book' : 'books' ?> in collection
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Content -->
    <main class="author-content">
        <div class="container">
            <div class="content-grid">

                <!-- LEFT: Bio + Books -->
                <div>
                    <!-- Biography -->
                    <div class="bio-card animate-in">
                        <div class="bio-card-header">
                            <div class="bio-header-icon"><i class="fas fa-feather-alt"></i></div>
                            <div class="bio-header-text">
                                <h2>Biography</h2>
                                <p>About this author</p>
                            </div>
                        </div>
                        <div class="bio-card-body">
                            <?php if (!empty($author['description'])): ?>
                                <div class="bio-text">
                                    <?php foreach (explode("\n\n", $author['description']) as $para): ?>
                                        <p>
                                            <?= nl2br(htmlspecialchars($para, ENT_QUOTES)) ?>
                                        </p>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-bio"><i class="fas fa-pen"
                                        style="display:block;font-size:24px;color:var(--border);margin-bottom:10px"></i>No
                                    biography has been written for this author yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Books -->
                    <div class="animate-in delay-1">
                        <div class="books-section-title">
                            Books <span class="books-count">(
                                <?= count($books) ?>)
                            </span>
                        </div>

                        <?php if (empty($books)): ?>
                            <div class="no-books-msg">
                                <i class="fas fa-book-open"></i>
                                <p>No books have been linked to this author yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="books-list">
                                <?php foreach ($books as $i => $book):
                                    $coverPath = "../" . $book['img'];
                                    $hasCover = !empty($book['img']) && file_exists($coverPath);
                                    ?>
                                    <a href="book_details.php?id=<?= (int) $book['id'] ?>" class="book-row"
                                        style="animation: fadeInUp .4s <?= $i * .08 ?>s both">
                                        <div class="book-thumb">
                                            <?php if ($hasCover): ?>
                                                <img src="<?= htmlspecialchars($coverPath, ENT_QUOTES) ?>"
                                                    alt="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>">
                                            <?php else: ?>
                                                <div class="book-thumb-placeholder"><i class="fas fa-book"></i></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="book-row-info">
                                            <div class="book-row-title">
                                                <?= htmlspecialchars($book['title'], ENT_QUOTES) ?>
                                            </div>
                                            <div class="book-row-meta">
                                                <?php if (!empty($book['publishers'])): ?>
                                                    <span><i class="fas fa-building"></i>
                                                        <?= htmlspecialchars($book['publishers'], ENT_QUOTES) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="book-cat-tag"><i class="fas fa-tag"></i>
                                                    <?= htmlspecialchars($book['category'], ENT_QUOTES) ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($book['price'])): ?>
                                                <div class="book-row-price">₹
                                                    <?= htmlspecialchars($book['price'], ENT_QUOTES) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <i class="fas fa-chevron-right"
                                            style="color:var(--border);font-size:13px;flex-shrink:0;margin-top:4px;transition:color var(--t)"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RIGHT: Sidebar -->
                <aside class="author-sidebar animate-in delay-2">

                    <!-- Quick stats card -->
                    <div class="sidebar-card">
                        <div class="sidebar-card-header">
                            <i class="fas fa-chart-simple"></i> Quick Info
                        </div>
                        <div class="sidebar-card-body">
                            <div class="stat-row">
                                <span class="stat-label"><i class="fas fa-book"></i> Books</span>
                                <span class="stat-value">
                                    <?= count($books) ?>
                                </span>
                            </div>
                            <?php if (!empty($author['title'])): ?>
                                <div class="stat-row">
                                    <span class="stat-label"><i class="fas fa-id-badge"></i> Role</span>
                                    <span class="stat-value" style="font-size:12.5px;text-align:right;max-width:140px">
                                        <?= htmlspecialchars($author['title'], ENT_QUOTES) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="stat-row">
                                <span class="stat-label"><i class="fas fa-align-left"></i> Biography</span>
                                <span class="stat-value">
                                    <?= !empty($author['description']) ? 'Yes' : 'None' ?>
                                </span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label"><i class="fas fa-image"></i> Photo</span>
                                <span class="stat-value">
                                    <?= $hasImg ? 'Yes' : 'None' ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Related categories -->
                    <?php if (!empty($books)):
                        $cats = array_unique(array_column($books, 'category'));
                        ?>
                        <div class="sidebar-card">
                            <div class="sidebar-card-header">
                                <i class="fas fa-tags"></i> Categories
                            </div>
                            <div class="sidebar-card-body" style="display:flex;flex-wrap:wrap;gap:8px;padding:16px 20px;">
                                <?php foreach ($cats as $cat): ?>
                                    <span
                                        style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;border-radius:99px;background:var(--cream);border:1px solid var(--border);font-size:12px;color:var(--muted);font-weight:600">
                                        <i class="fas fa-tag" style="font-size:10px"></i>
                                        <?= htmlspecialchars($cat, ENT_QUOTES) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Navigation -->
                    <div class="sidebar-card">
                        <div class="sidebar-card-header">
                            <i class="fas fa-compass"></i> Navigation
                        </div>
                        <div class="sidebar-card-body"
                            style="display:flex;flex-direction:column;gap:10px;padding:16px 20px;">
                            <a href="javascript:history.back()" class="btn-back">
                                <i class="fas fa-arrow-left"></i> Go Back
                            </a>
                            <!-- <a href="all_authors.php" class="btn-all-authors">
                                <i class="fas fa-users"></i> All Authors
                            </a> -->
                        </div>
                    </div>

                </aside>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>