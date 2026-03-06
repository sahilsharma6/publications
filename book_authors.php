<?php
include 'db.php';
session_start();

$book_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$book_id) {
    header("Location: index.php");
    exit();
}

// Fetch book info
$stmt = $conn->prepare("SELECT b.id, b.title, b.authors AS author_str, b.img, c.name AS category_name
                        FROM books_data b JOIN categories c ON c.id = b.category_id
                        WHERE b.id = ? LIMIT 1");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    header("Location: index.php");
    exit();
}

// Fetch all authors for this book
$stmt = $conn->prepare("SELECT a.id, a.name, a.title, a.description, a.image
                        FROM authors a JOIN book_authors ba ON ba.author_id = a.id
                        WHERE ba.book_id = ? ORDER BY a.name ASC");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$authors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalAuthors = count($authors);
$coverPath = '../' . $book['img'];
$hasCover = !empty($book['img']) && file_exists($coverPath);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authors of
        <?= htmlspecialchars($book['title']) ?>
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

        /* ══ Hero ══ */
        .page-hero {
            background: var(--ink);
            padding: 56px 0 0;
            position: relative;
            overflow: hidden;
        }

        .hero-glow {
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(ellipse at 0% 80%, rgba(200, 71, 43, .16) 0%, transparent 55%),
                radial-gradient(ellipse at 100% 10%, rgba(184, 134, 11, .09) 0%, transparent 50%);
        }

        .hero-stripe {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), var(--gold), var(--accent));
        }

        /* Book card inside hero */
        .hero-book-card {
            display: flex;
            align-items: center;
            gap: 28px;
            padding: 32px 0 48px;
            position: relative;
            z-index: 1;
            flex-wrap: wrap;
        }

        /* Tiny book cover */
        .hero-cover-wrap {
            flex-shrink: 0;
            width: 80px;
        }

        .hero-cover {
            width: 80px;
            height: 110px;
            border-radius: var(--r-lg);
            object-fit: cover;
            box-shadow: 0 8px 32px rgba(0, 0, 0, .45);
            border: 2px solid rgba(255, 255, 255, .12);
            display: block;
        }

        .hero-cover-placeholder {
            width: 80px;
            height: 110px;
            border-radius: var(--r-lg);
            background: rgba(255, 255, 255, .06);
            border: 2px solid rgba(255, 255, 255, .12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: rgba(255, 255, 255, .2);
        }

        /* Hero text */
        .hero-text {
            flex: 1;
            min-width: 200px;
        }

        .hero-eyebrow {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .hero-eyebrow::before {
            content: '';
            width: 20px;
            height: 1.5px;
            background: var(--accent);
        }

        .hero-book-title {
            font-family: "Playfair Display", serif;
            font-size: clamp(22px, 3.5vw, 40px);
            font-weight: 800;
            color: #fff;
            line-height: 1.15;
            letter-spacing: -.5px;
            margin-bottom: 8px;
        }

        .hero-book-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .hero-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: rgba(255, 255, 255, .5);
        }

        .hero-meta-item i {
            font-size: 12px;
        }

        .hero-author-count {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 14px;
            border-radius: 99px;
            border: 1px solid rgba(255, 255, 255, .14);
            background: rgba(255, 255, 255, .07);
            font-size: 13px;
            color: rgba(255, 255, 255, .7);
        }

        .hero-author-count strong {
            color: #fff;
        }

        /* Breadcrumb */
        .page-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: rgba(255, 255, 255, .35);
            padding-bottom: 16px;
            position: relative;
            z-index: 1;
        }

        .page-breadcrumb a {
            color: rgba(255, 255, 255, .35);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: color var(--t);
        }

        .page-breadcrumb a:hover {
            color: rgba(255, 255, 255, .75);
        }

        .page-breadcrumb i.sep {
            font-size: 11px;
        }

        /* ══ Authors list ══ */
        .authors-container {
            padding: 56px 0 80px;
        }

        /* Each author row */
        .author-entry {
            display: flex;
            gap: 28px;
            align-items: flex-start;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-xl);
            padding: 28px;
            margin-bottom: 20px;
            transition: all var(--t);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            animation: fadeInUp .45s both;
        }

        .author-entry::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(to bottom, var(--accent), var(--gold));
            opacity: 0;
            transition: opacity var(--t);
        }

        .author-entry:hover {
            box-shadow: var(--shadow-lg);
            border-color: rgba(200, 71, 43, .22);
            transform: translateY(-3px);
            color: inherit;
        }

        .author-entry:hover::before {
            opacity: 1;
        }

        /* Index badge */
        .author-index {
            position: absolute;
            top: 20px;
            right: 22px;
            font-family: "Playfair Display", serif;
            font-style: italic;
            font-size: 40px;
            font-weight: 700;
            line-height: 1;
            color: var(--border);
            transition: color var(--t);
            pointer-events: none;
        }

        .author-entry:hover .author-index {
            color: rgba(200, 71, 43, .12);
        }

        /* Avatar */
        .entry-avatar-wrap {
            flex-shrink: 0;
        }

        .entry-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--border);
            box-shadow: var(--shadow);
            display: block;
            transition: border-color var(--t);
        }

        .entry-initials {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--ink), #3a3632);
            color: rgba(255, 255, 255, .9);
            font-family: "Playfair Display", serif;
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid var(--border);
            box-shadow: var(--shadow);
            transition: border-color var(--t);
        }

        .author-entry:hover .entry-avatar,
        .author-entry:hover .entry-initials {
            border-color: rgba(200, 71, 43, .4);
        }

        /* Body */
        .entry-body {
            flex: 1;
            min-width: 0;
            padding-right: 40px;
        }

        .entry-name {
            font-family: "Playfair Display", serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 4px;
            line-height: 1.2;
            transition: color var(--t);
        }

        .author-entry:hover .entry-name {
            color: var(--accent);
        }

        .entry-role {
            font-size: 13px;
            color: var(--muted);
            font-style: italic;
            margin-bottom: 14px;
        }

        .entry-bio {
            font-size: 14px;
            line-height: 1.7;
            color: #4a4540;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 16px;
        }

        .entry-bio.no-bio {
            color: var(--muted);
            font-style: italic;
            -webkit-line-clamp: 1;
        }

        /* Profile link */
        .entry-profile-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 700;
            color: var(--accent);
            transition: gap var(--t);
        }

        .author-entry:hover .entry-profile-link {
            gap: 10px;
        }

        .entry-profile-link i {
            font-size: 12px;
        }

        /* Empty state */
        .empty-authors {
            text-align: center;
            padding: 72px 24px;
            background: var(--cream);
            border-radius: var(--r-xl);
            border: 1px dashed var(--border);
        }

        .empty-authors-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: var(--cream);
            border: 1.5px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: var(--muted);
            margin: 0 auto 18px;
        }

        .empty-authors h3 {
            font-family: "Playfair Display", serif;
            font-size: 20px;
            color: var(--ink);
            margin-bottom: 8px;
        }

        .empty-authors p {
            font-size: 14px;
            color: var(--muted);
        }

        /* Back to book btn */
        .btn-back-book {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 22px;
            border-radius: var(--r);
            border: 1.5px solid var(--border);
            background: var(--cream);
            font-size: 13.5px;
            font-weight: 600;
            color: var(--ink);
            text-decoration: none;
            transition: all var(--t);
            margin-bottom: 32px;
        }

        .btn-back-book:hover {
            background: var(--ink);
            color: #fff;
            border-color: var(--ink);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }
    </style>
</head>

<body>
    <?php include 'Header.php'; ?>

    <!-- ══ Hero ══ -->
    <section class="page-hero">
        <div class="hero-glow"></div>
        <div class="hero-stripe"></div>
        <div class="container">
            <nav class="page-breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <i class="fas fa-chevron-right sep"></i>
                <a href="book_details.php?id=<?= $book_id ?>">
                    <?= htmlspecialchars($book['title'], ENT_QUOTES) ?>
                </a>
                <i class="fas fa-chevron-right sep"></i>
                <span style="color:rgba(255,255,255,.65)">Authors</span>
            </nav>

            <div class="hero-book-card">
                <div class="hero-cover-wrap">
                    <?php if ($hasCover): ?>
                        <img src="<?= htmlspecialchars($coverPath, ENT_QUOTES) ?>"
                            alt="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>" class="hero-cover">
                    <?php else: ?>
                        <div class="hero-cover-placeholder"><i class="fas fa-book"></i></div>
                    <?php endif; ?>
                </div>
                <div class="hero-text">
                    <div class="hero-eyebrow">Book Authors</div>
                    <h1 class="hero-book-title">
                        <?= htmlspecialchars($book['title'], ENT_QUOTES) ?>
                    </h1>
                    <div class="hero-book-meta">
                        <?php if (!empty($book['category_name'])): ?>
                            <span class="hero-meta-item"><i class="fas fa-tag"></i>
                                <?= htmlspecialchars($book['category_name'], ENT_QUOTES) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($book['author_str'])): ?>
                            <span class="hero-meta-item"><i class="fas fa-pen-nib"></i>
                                <?= htmlspecialchars($book['author_str'], ENT_QUOTES) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="hero-author-count">
                        <i class="fas fa-users" style="font-size:13px;color:rgba(255,255,255,.45)"></i>
                        <span><strong>
                                <?= $totalAuthors ?>
                            </strong>
                            <?= $totalAuthors === 1 ? 'author' : 'authors' ?> linked to this book
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ══ Authors list ══ -->
    <main class="authors-container">
        <div class="container">

            <!-- Back button -->
            <a href="book_details.php?id=<?= $book_id ?>" class="btn-back-book">
                <i class="fas fa-arrow-left"></i> Back to Book
            </a>

            <?php if (empty($authors)): ?>
                <div class="empty-authors">
                    <div class="empty-authors-icon"><i class="fas fa-user-slash"></i></div>
                    <h3>No Authors Yet</h3>
                    <p>No author profiles have been linked to this book yet.</p>
                </div>

            <?php else: ?>
                <?php foreach ($authors as $i => $author):
                    $imgPath = "../uploads/authors/" . $author['image'];
                    $hasImg = !empty($author['image']) && file_exists($imgPath);
                    $initial = strtoupper(substr($author['name'], 0, 1));
                    ?>
                    <a href="author_detail.php?id=<?= (int) $author['id'] ?>" class="author-entry"
                        style="animation-delay:<?= $i * .09 ?>s">

                        <!-- Number badge -->
                        <span class="author-index">
                            <?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?>
                        </span>

                        <!-- Avatar -->
                        <div class="entry-avatar-wrap">
                            <?php if ($hasImg): ?>
                                <img src="<?= htmlspecialchars($imgPath, ENT_QUOTES) ?>"
                                    alt="<?= htmlspecialchars($author['name'], ENT_QUOTES) ?>" class="entry-avatar">
                            <?php else: ?>
                                <div class="entry-initials">
                                    <?= $initial ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Info -->
                        <div class="entry-body">
                            <div class="entry-name">
                                <?= htmlspecialchars($author['name'], ENT_QUOTES) ?>
                            </div>

                            <?php if (!empty($author['title'])): ?>
                                <div class="entry-role">
                                    <?= htmlspecialchars($author['title'], ENT_QUOTES) ?>
                                </div>
                            <?php endif; ?>

                            <div class="entry-bio <?= empty($author['description']) ? 'no-bio' : '' ?>">
                                <?= !empty($author['description'])
                                    ? htmlspecialchars($author['description'], ENT_QUOTES)
                                    : 'No biography available for this author.' ?>
                            </div>

                            <span class="entry-profile-link">
                                View Full Profile <i class="fas fa-arrow-right"></i>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </main>

    <?php include 'Footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>