<?php
include '../db.php';
session_start();

/* ── Filters ── */
$search = trim($_GET['search'] ?? '');
$perPage = 12;
$page = max(1, (int) ($_GET['page'] ?? 1));
$sp = '%' . $search . '%';

/* ── Count ── */
$cStmt = $conn->prepare("SELECT COUNT(*) FROM authors WHERE name LIKE ? OR title LIKE ? OR description LIKE ?");
$cStmt->bind_param("sss", $sp, $sp, $sp);
$cStmt->execute();
$cStmt->bind_result($totalRows);
$cStmt->fetch();
$cStmt->close();

$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

/* ── Fetch ── */
$aStmt = $conn->prepare(
    "SELECT id, name, title, description, image FROM authors
     WHERE name LIKE ? OR title LIKE ? OR description LIKE ?
     ORDER BY name ASC LIMIT ? OFFSET ?"
);
$aStmt->bind_param("sssii", $sp, $sp, $sp, $perPage, $offset);
$aStmt->execute();
$authors = $aStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$aStmt->close();

/* ── Books per author ── */
$authorBooks = [];
if (!empty($authors)) {
    $ids = implode(',', array_map(fn($a) => (int) $a['id'], $authors));
    $bq = $conn->query(
        "SELECT ba.author_id, bd.title FROM book_authors ba
         JOIN books_data bd ON bd.id = ba.book_id
         WHERE ba.author_id IN ($ids) ORDER BY bd.title ASC"
    );
    while ($row = $bq->fetch_assoc()) {
        $authorBooks[$row['author_id']][] = $row['title'];
    }
}

$totalAll = (int) $conn->query("SELECT COUNT(*) FROM authors")->fetch_row()[0];

function pUrl(array $ov = []): string
{
    return 'all_authors.php?' . http_build_query(array_merge([
        'page' => $_GET['page'] ?? 1,
        'search' => $_GET['search'] ?? '',
    ], $ov));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Authors</title>
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

        /* ── Hero ── */
        .authors-hero {
            background: var(--ink);
            padding: 72px 0 56px;
            position: relative;
            overflow: hidden;
        }

        .authors-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20% 50%, rgba(200, 71, 43, .15) 0%, transparent 60%),
                radial-gradient(circle at 80% 20%, rgba(184, 134, 11, .1) 0%, transparent 50%);
        }

        /* decorative large letter */
        .authors-hero::after {
            content: 'A';
            position: absolute;
            right: 5%;
            top: 50%;
            transform: translateY(-50%);
            font-family: "Playfair Display", serif;
            font-size: 260px;
            font-weight: 900;
            font-style: italic;
            color: rgba(255, 255, 255, .03);
            line-height: 1;
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            z-index: 1;
        }

        .hero-eyebrow {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .hero-eyebrow::before {
            content: '';
            width: 28px;
            height: 1.5px;
            background: var(--accent);
        }

        .hero-title {
            font-family: "Playfair Display", serif;
            font-size: clamp(38px, 5vw, 64px);
            font-weight: 900;
            color: #fff;
            line-height: 1.1;
            letter-spacing: -1px;
            margin-bottom: 16px;
        }

        .hero-title em {
            font-style: italic;
            color: rgba(255, 255, 255, .55);
        }

        .hero-sub {
            font-size: 16px;
            color: rgba(255, 255, 255, .55);
            max-width: 460px;
            line-height: 1.6;
            margin-bottom: 36px;
        }

        .hero-count {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 99px;
            border: 1px solid rgba(255, 255, 255, .12);
            background: rgba(255, 255, 255, .06);
            font-size: 13px;
            color: rgba(255, 255, 255, .7);
            font-weight: 500;
        }

        .hero-count strong {
            color: #fff;
        }

        /* ── Search bar ── */
        .search-section {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(15, 14, 13, .06);
        }

        .search-inner {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .search-field-wrap {
            flex: 1;
            min-width: 240px;
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-field-wrap i {
            position: absolute;
            left: 16px;
            font-size: 16px;
            color: var(--muted);
            pointer-events: none;
            transition: color var(--t);
        }

        .search-field {
            width: 100%;
            padding: 12px 16px 12px 46px;
            border: 1.5px solid var(--border);
            border-radius: 99px;
            font-size: 14px;
            font-family: "DM Sans", sans-serif;
            color: var(--ink);
            background: var(--paper);
            outline: none;
            transition: all var(--t);
        }

        .search-field:focus {
            border-color: var(--accent);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(200, 71, 43, .1);
        }

        .search-field:focus+i,
        .search-field-wrap:focus-within i {
            color: var(--accent);
        }

        .result-count {
            font-size: 13px;
            color: var(--muted);
            white-space: nowrap;
        }

        .result-count strong {
            color: var(--ink);
            font-weight: 600;
        }

        /* ── Main content ── */
        .authors-main {
            padding: 48px 0 80px;
        }

        /* ── Author card ── */
        .author-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-xl);
            overflow: hidden;
            transition: all var(--t);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .author-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), var(--gold));
            transform: scaleX(0);
            transition: transform .3s var(--t);
            transform-origin: left;
        }

        .author-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(200, 71, 43, .2);
            color: inherit;
        }

        .author-card:hover::after {
            transform: scaleX(1);
        }

        /* Card image banner */
        .card-banner {
            height: 120px;
            background: linear-gradient(135deg, var(--cream), #e8ddd0);
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }

        .card-banner-pattern {
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle, rgba(200, 71, 43, .08) 1px, transparent 1px);
            background-size: 20px 20px;
        }

        .card-avatar-wrap {
            position: absolute;
            bottom: -28px;
            left: 24px;
        }

        .card-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: var(--shadow);
        }

        .card-initials {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--ink), #3a3632);
            color: #fff;
            font-size: 26px;
            font-family: "Playfair Display", serif;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #fff;
            box-shadow: var(--shadow);
        }

        /* Card body */
        .card-body-inner {
            padding: 40px 24px 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-name {
            font-family: "Playfair Display", serif;
            font-size: 19px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 4px;
            line-height: 1.2;
            transition: color var(--t);
        }

        .author-card:hover .card-name {
            color: var(--accent);
        }

        .card-role {
            font-size: 12px;
            color: var(--muted);
            font-style: italic;
            margin-bottom: 14px;
            min-height: 16px;
        }

        .card-bio {
            font-size: 13px;
            color: #5a544e;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex: 1;
            margin-bottom: 18px;
        }

        .card-bio.no-bio {
            color: var(--muted);
            font-style: italic;
            font-size: 12.5px;
        }

        /* Books chips */
        .card-books {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 18px;
            min-height: 26px;
        }

        .book-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 99px;
            background: var(--cream);
            border: 1px solid var(--border);
            font-size: 11.5px;
            color: var(--muted);
            font-weight: 500;
            white-space: nowrap;
            max-width: 160px;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: all var(--t);
        }

        .author-card:hover .book-chip {
            background: var(--accent-light);
            border-color: rgba(200, 71, 43, .2);
            color: var(--accent);
        }

        .book-chip i {
            font-size: 10px;
            flex-shrink: 0;
        }

        .chip-more {
            background: var(--cream);
            color: var(--muted);
        }

        /* CTA */
        .card-cta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 1px solid var(--border);
            font-size: 13px;
            font-weight: 600;
            color: var(--accent);
            transition: gap var(--t);
        }

        .card-cta i {
            transition: transform var(--t);
        }

        .author-card:hover .card-cta i {
            transform: translateX(4px);
        }

        /* ── Grid ── */
        .authors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            padding: 80px 24px;
            grid-column: 1 / -1;
        }

        .empty-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--cream);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: var(--muted);
            margin: 0 auto 20px;
        }

        .empty-state h3 {
            font-family: "Playfair Display", serif;
            font-size: 22px;
            color: var(--ink);
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 20px;
        }

        .btn-clear {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 22px;
            border-radius: 99px;
            border: 1.5px solid var(--accent);
            color: var(--accent);
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            transition: all var(--t);
        }

        .btn-clear:hover {
            background: var(--accent);
            color: #fff;
        }

        /* ── Pagination ── */
        .pag-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 56px;
            flex-wrap: wrap;
        }

        .pag-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 12px;
            border-radius: var(--r);
            border: 1.5px solid var(--border);
            background: #fff;
            color: var(--muted);
            font-size: 13.5px;
            font-family: "DM Sans", sans-serif;
            font-weight: 500;
            text-decoration: none;
            transition: all var(--t);
        }

        .pag-btn:hover:not(.active):not(.disabled) {
            border-color: var(--accent);
            color: var(--accent);
        }

        .pag-btn.active {
            background: var(--ink);
            border-color: var(--ink);
            color: #fff;
            font-weight: 700;
        }

        .pag-btn.disabled {
            opacity: .3;
            pointer-events: none;
        }

        .pag-ellipsis {
            color: var(--muted);
            font-size: 13px;
            padding: 0 4px;
        }

        /* ── Animation ── */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .author-card {
            animation: fadeInUp .45s both;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <!-- Hero -->
    <section class="authors-hero">
        <div class="container hero-inner">
            <div class="hero-eyebrow">Our Authors</div>
            <h1 class="hero-title">The Minds<br>Behind <em>the Books</em></h1>
            <p class="hero-sub">Discover the writers and scholars whose words fill our collection.</p>
            <div class="hero-count">
                <i class="fas fa-feather-alt" style="font-size:13px;color:rgba(255,255,255,.5)"></i>
                <span><strong><?= number_format($totalAll) ?></strong> authors in our collection</span>
            </div>
        </div>
    </section>

    <!-- Sticky search -->
    <div class="search-section">
        <div class="container">
            <form method="GET" action="all_authors.php" id="searchForm" class="search-inner">
                <div class="search-field-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" id="searchInput" class="search-field"
                        value="<?= htmlspecialchars($search, ENT_QUOTES) ?>"
                        placeholder="Search by name, role, or biography…" autocomplete="off">
                </div>
                <div class="result-count">
                    <strong><?= number_format($totalRows) ?></strong>
                    <?= $totalRows === 1 ? 'author' : 'authors' ?>
                    <?= $search ? 'found' : '' ?>
                </div>
                <?php if ($search): ?>
                    <a href="all_authors.php"
                        style="font-size:13px;color:var(--muted);text-decoration:none;display:flex;align-items:center;gap:5px;transition:color var(--t)"
                        onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--muted)'">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Main -->
    <main class="authors-main">
        <div class="container">
            <div class="authors-grid">
                <?php if (empty($authors)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-user-slash"></i></div>
                        <h3>No Authors Found</h3>
                        <p><?= $search ? 'No authors match "' . htmlspecialchars($search, ENT_QUOTES) . '".' : 'No authors have been added yet.' ?>
                        </p>
                        <?php if ($search): ?>
                            <a href="all_authors.php" class="btn-clear"><i class="fas fa-times"></i> Clear Search</a>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <?php foreach ($authors as $i => $author):
                        $imgPath = "../uploads/authors/" . $author['image'];
                        $hasImg = !empty($author['image']) && file_exists($imgPath);
                        $initial = strtoupper(substr($author['name'], 0, 1));
                        $books = $authorBooks[$author['id']] ?? [];
                        ?>
                        <a href="author_detail.php?id=<?= (int) $author['id'] ?>" class="author-card"
                            style="animation-delay: <?= $i * .06 ?>s">

                            <div class="card-banner">
                                <div class="card-banner-pattern"></div>
                                <div class="card-avatar-wrap">
                                    <?php if ($hasImg): ?>
                                        <img src="<?= htmlspecialchars($imgPath, ENT_QUOTES) ?>"
                                            alt="<?= htmlspecialchars($author['name'], ENT_QUOTES) ?>" class="card-avatar">
                                    <?php else: ?>
                                        <div class="card-initials"><?= $initial ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card-body-inner">
                                <div class="card-name"><?= htmlspecialchars($author['name'], ENT_QUOTES) ?></div>
                                <div class="card-role">
                                    <?= !empty($author['title']) ? htmlspecialchars($author['title'], ENT_QUOTES) : '' ?></div>

                                <?php if (!empty($books)): ?>
                                    <div class="card-books">
                                        <?php foreach (array_slice($books, 0, 2) as $bk): ?>
                                            <span class="book-chip"><i
                                                    class="fas fa-book"></i><?= htmlspecialchars($bk, ENT_QUOTES) ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($books) > 2): ?>
                                            <span class="book-chip chip-more">+<?= count($books) - 2 ?> more</span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="card-books"></div>
                                <?php endif; ?>

                                <div class="card-bio <?= empty($author['description']) ? 'no-bio' : '' ?>">
                                    <?= !empty($author['description']) ? htmlspecialchars($author['description'], ENT_QUOTES) : 'No biography available.' ?>
                                </div>

                                <div class="card-cta">
                                    <span>View Profile</span>
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pag-row">
                    <a class="pag-btn <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= pUrl(['page' => $page - 1]) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php
                    $ws = max(1, $page - 2);
                    $we = min($totalPages, $page + 2);
                    if ($ws > 1) {
                        echo '<a class="pag-btn" href="' . pUrl(['page' => 1]) . '">1</a>';
                        if ($ws > 2)
                            echo '<span class="pag-ellipsis">…</span>';
                    }
                    for ($p = $ws; $p <= $we; $p++)
                        echo '<a class="pag-btn ' . ($p === $page ? 'active' : '') . '" href="' . pUrl(['page' => $p]) . '">' . $p . '</a>';
                    if ($we < $totalPages) {
                        if ($we < $totalPages - 1)
                            echo '<span class="pag-ellipsis">…</span>';
                        echo '<a class="pag-btn" href="' . pUrl(['page' => $totalPages]) . '">' . $totalPages . '</a>';
                    }
                    ?>
                    <a class="pag-btn <?= $page >= $totalPages ? 'disabled' : '' ?>"
                        href="<?= pUrl(['page' => $page + 1]) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search debounce
        const si = document.getElementById('searchInput');
        let st;
        si?.addEventListener('input', () => { clearTimeout(st); st = setTimeout(() => document.getElementById('searchForm').submit(), 420); });
    </script>
</body>

</html>