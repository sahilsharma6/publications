<?php
session_start();
include '../../db.php';

// ── Auth Guard ────────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'SuperAdmin'], true)) {
    header("Location: login.php");
    exit();
}

$role = htmlspecialchars($_SESSION['role'] ?? '', ENT_QUOTES);
$username = htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES);

// ── Toast from redirect ───────────────────────────────────────────────────────
$toast = null;
if (isset($_GET['toast']) && $_GET['toast'] === 'added') {
    $bookName = htmlspecialchars($_GET['book'] ?? 'Book', ENT_QUOTES);
    $toast = ['type' => 'success', 'msg' => "\"$bookName\" added successfully."];
}

// ── Handle DELETE ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];

    // Remove image file if exists
    $imgStmt = $conn->prepare("SELECT img FROM books_data WHERE id = ?");
    $imgStmt->bind_param("i", $delete_id);
    $imgStmt->execute();
    $imgStmt->bind_result($imgPath);
    $imgStmt->fetch();
    $imgStmt->close();
    if ($imgPath && file_exists($imgPath))
        @unlink($imgPath);

    $del = $conn->prepare("DELETE FROM books_data WHERE id = ?");
    $del->bind_param("i", $delete_id);
    $toast = $del->execute()
        ? ['type' => 'success', 'msg' => 'Book deleted successfully.']
        : ['type' => 'error', 'msg' => 'Failed to delete book.'];
    $del->close();
}

// ── Pagination + Search + Filters ────────────────────────────────────────────
$perPage = (int) ($_GET['per_page'] ?? 10);
$perPage = in_array($perPage, [5, 10, 25, 50, 100], true) ? $perPage : 10;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$sort = in_array($_GET['sort'] ?? '', ['title', 'id', 'price', 'publishers'], true) ? $_GET['sort'] : 'id';
$dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$view = ($_GET['view'] ?? 'table') === 'grid' ? 'grid' : 'table';
$catFilter = (int) ($_GET['cat'] ?? 0);

$searchParam = '%' . $search . '%';

// ── Build WHERE ───────────────────────────────────────────────────────────────
$where = "WHERE (b.title LIKE ? OR b.isbn LIKE ? OR b.publishers LIKE ?)";
$types = "sss";
$params = [$searchParam, $searchParam, $searchParam];

if ($catFilter > 0) {
    $where .= " AND b.category_id = ?";
    $types .= "i";
    $params[] = $catFilter;
}

// ── Total count ───────────────────────────────────────────────────────────────
$countSql = "SELECT COUNT(*) FROM books_data b $where";
$countStmt = $conn->prepare($countSql);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$countStmt->bind_result($totalRows);
$countStmt->fetch();
$countStmt->close();

$totalPages = max(1, (int) ceil($totalRows / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;

// ── Fetch books ───────────────────────────────────────────────────────────────
$fetchSql = "SELECT b.id, b.title, b.isbn, b.price, b.publishers, b.img, b.length,
                    b.subjects, b.description, c.name AS category_name
             FROM books_data b
             LEFT JOIN categories c ON c.id = b.category_id
             $where
             ORDER BY $sort $dir
             LIMIT ? OFFSET ?";

$fetchTypes = $types . "ii";
$fetchParams = array_merge($params, [$perPage, $offset]);

$bookStmt = $conn->prepare($fetchSql);
$bookStmt->bind_param($fetchTypes, ...$fetchParams);
$bookStmt->execute();
$bookResult = $bookStmt->get_result();
$books = $bookResult ? $bookResult->fetch_all(MYSQLI_ASSOC) : [];
$bookStmt->close();

// ── Summary stats ─────────────────────────────────────────────────────────────
$totalAll = (int) $conn->query("SELECT COUNT(*) FROM books_data")->fetch_row()[0];
$totalCats = (int) $conn->query("SELECT COUNT(DISTINCT category_id) FROM books_data WHERE category_id IS NOT NULL")->fetch_row()[0];

$newestRow = $conn->query("SELECT title FROM books_data ORDER BY id DESC LIMIT 1");
$newestBook = $newestRow ? ($newestRow->fetch_row()[0] ?? '—') : '—';

// ── Categories for filter dropdown ───────────────────────────────────────────
$catRes = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$allCats = $catRes ? $catRes->fetch_all(MYSQLI_ASSOC) : [];

// ── Helper: URL builder ───────────────────────────────────────────────────────
function pageUrl(array $override = []): string
{
    $params = array_merge([
        'page' => $_GET['page'] ?? 1,
        'search' => $_GET['search'] ?? '',
        'sort' => $_GET['sort'] ?? 'id',
        'dir' => $_GET['dir'] ?? 'desc',
        'view' => $_GET['view'] ?? 'table',
        'per_page' => $_GET['per_page'] ?? 10,
        'cat' => $_GET['cat'] ?? 0,
    ], $override);
    return 'AllBooks.php?' . http_build_query($params);
}

function sortUrl(string $col): string
{
    $currentSort = $_GET['sort'] ?? 'id';
    $currentDir = $_GET['dir'] ?? 'desc';
    $newDir = ($currentSort === $col && $currentDir === 'asc') ? 'desc' : 'asc';
    return pageUrl(['sort' => $col, 'dir' => $newDir, 'page' => 1]);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Books — BookAdmin</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="AllCategories.css">
    <style>
        /* ── Books-specific overrides ──────────────────── */

        /* Summary icons extra colors */
        .si-green {
            background: #ecfdf5;
            color: #10b981;
        }

        .si-purple {
            background: #f5f3ff;
            color: #8b5cf6;
        }

        .si-amber {
            background: #fffbeb;
            color: #f59e0b;
        }

        /* ── Book cover thumbnail ─────────────────────── */
        .book-cover {
            width: 36px;
            height: 48px;
            border-radius: 4px;
            object-fit: cover;
            border: 1px solid var(--gray-200);
            flex-shrink: 0;
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .book-cover-placeholder {
            width: 36px;
            height: 48px;
            border-radius: 4px;
            border: 1px solid var(--gray-200);
            background: linear-gradient(135deg, #e0e7ff, #ede9fe);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .book-cover-placeholder i {
            font-size: 18px;
            color: #a5b4fc;
        }

        /* ── Book info cell ──────────────────────────── */
        .book-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .book-cell-info {
            min-width: 0;
        }

        .book-title {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--gray-800);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 300px;
        }

        .book-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 3px;
            flex-wrap: wrap;
        }

        .book-isbn {
            font-size: 11.5px;
            font-family: "DM Mono", monospace;
            color: var(--gray-400);
        }

        .book-publisher {
            font-size: 11.5px;
            color: var(--gray-400);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 140px;
        }

        /* ── Category badge ──────────────────────────── */
        .cat-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 99px;
            font-size: 11.5px;
            font-weight: 600;
            background: var(--accent-light);
            color: var(--accent-dark);
            white-space: nowrap;
            max-width: 140px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cat-pill.uncategorized {
            background: var(--gray-100);
            color: var(--gray-400);
        }

        /* ── Price badge ─────────────────────────────── */
        .price-tag {
            font-size: 13px;
            font-weight: 600;
            color: #059669;
            font-family: "DM Mono", monospace;
            white-space: nowrap;
        }

        .price-tag.no-price {
            color: var(--gray-300);
            font-weight: 400;
            font-family: "DM Sans", sans-serif;
        }

        /* ── Pages badge ─────────────────────────────── */
        .pages-badge {
            font-size: 12.5px;
            color: var(--gray-500);
            white-space: nowrap;
        }

        .pages-badge.empty {
            color: var(--gray-300);
        }

        /* ── Filter bar extras ───────────────────────── */
        .cat-filter-select {
            height: 38px;
            padding: 0 32px 0 12px;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r);
            font-size: 13px;
            font-family: inherit;
            color: var(--gray-700);
            background: var(--gray-50);
            outline: none;
            cursor: pointer;
            transition: all var(--t);
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24'%3E%3Cpath fill='%239ca3af' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-color: var(--gray-50);
            max-width: 160px;
        }

        .cat-filter-select:focus {
            border-color: var(--accent);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .1);
        }

        /* ── Grid view cards ─────────────────────────── */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 16px;
            padding: 20px;
        }

        .book-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-lg);
            overflow: hidden;
            transition: transform var(--t), box-shadow var(--t);
            display: flex;
            flex-direction: column;
        }

        .book-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .book-card-cover {
            height: 170px;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .book-card-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .book-card-cover-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, .25);
        }

        .book-card-cover-placeholder i {
            font-size: 36px;
        }

        .book-card-cover-placeholder span {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .book-card-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(15, 23, 42, .7);
            backdrop-filter: blur(6px);
            color: rgba(255, 255, 255, .8);
            font-size: 10px;
            font-family: "DM Mono", monospace;
            padding: 3px 7px;
            border-radius: 4px;
            font-weight: 500;
        }

        .book-card-body {
            padding: 12px 14px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .book-card-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--gray-800);
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-card-cat {
            font-size: 11px;
            font-weight: 600;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .book-card-cat.uncategorized {
            color: var(--gray-300);
        }

        .book-card-price {
            font-size: 13px;
            font-weight: 700;
            color: #059669;
            font-family: "DM Mono", monospace;
            margin-top: auto;
        }

        .book-card-price.no-price {
            color: var(--gray-300);
            font-weight: 400;
            font-family: inherit;
        }

        .book-card-actions {
            display: flex;
            border-top: 1px solid var(--gray-100);
        }

        .book-card-actions a,
        .book-card-actions button {
            flex: 1;
            padding: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            border: none;
            background: none;
            cursor: pointer;
            transition: background var(--t), color var(--t);
            text-decoration: none;
        }

        .book-card-actions a {
            color: var(--accent);
        }

        .book-card-actions button {
            color: var(--danger);
            border-left: 1px solid var(--gray-100);
        }

        .book-card-actions a:hover {
            background: var(--accent-light);
        }

        .book-card-actions button:hover {
            background: var(--danger-light);
        }

        /* ── Active filter chip ───────────────────────── */
        .filter-chips {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 20px 14px;
            flex-wrap: wrap;
        }

        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 99px;
            background: var(--accent-light);
            color: var(--accent-dark);
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: background var(--t);
        }

        .filter-chip:hover {
            background: #dbeafe;
        }

        .filter-chip i {
            font-size: 13px;
        }

        /* ── Table: description excerpt ───────────────── */
        .desc-excerpt {
            font-size: 12px;
            color: var(--gray-400);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 220px;
        }
    </style>
</head>

<body>

    <?php include './sidebar.php'; ?>

    <?php include './components/toast.php'; ?>
    <?php include './components/delete-modal.php'; ?>

    <!-- Page header -->
    <div class="page-header">
        <div class="page-header-left">
            <h1>All Books</h1>
            <nav class="breadcrumb" aria-label="breadcrumb">
                <a href="<?= $root_url ?>"><i class='bx bx-home-alt'></i> Dashboard</a>
                <i class='bx bx-chevron-right'></i>
                <span>Books</span>
            </nav>
        </div>
        <a href="AddBook.php" class="btn-add">
            <i class='bx bx-plus'></i> Add Book
        </a>
    </div>

    <!-- Summary strip -->
    <div class="summary-strip">
        <div class="summary-card">
            <div class="summary-icon si-blue"><i class='bx bx-book-alt'></i></div>
            <div class="summary-info">
                <div class="summary-val"><?= number_format($totalAll) ?></div>
                <div class="summary-label">Total Books</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon si-purple"><i class='bx bx-collection'></i></div>
            <div class="summary-info">
                <div class="summary-val"><?= number_format($totalCats) ?></div>
                <div class="summary-label">Categories Used</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon si-green"><i class='bx bx-bookmark-plus'></i></div>
            <div class="summary-info">
                <div class="summary-val" style="font-size:15px;line-height:1.4">
                    <?= htmlspecialchars($newestBook, ENT_QUOTES) ?>
                </div>
                <div class="summary-label">Most Recent</div>
            </div>
        </div>
    </div>

    <!-- Main card -->
    <div class="main-card">

        <!-- Toolbar -->
        <form method="GET" action="AllBooks.php" id="filterForm">
            <input type="hidden" name="view" value="<?= htmlspecialchars($view, ENT_QUOTES) ?>">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort, ENT_QUOTES) ?>">
            <input type="hidden" name="dir" value="<?= htmlspecialchars($dir, ENT_QUOTES) ?>">
            <input type="hidden" name="page" value="1">

            <div class="toolbar">
                <div class="toolbar-left">
                    <!-- Search -->
                    <div class="search-box">
                        <i class='bx bx-search'></i>
                        <input type="text" class="search-input" name="search"
                            value="<?= htmlspecialchars($search, ENT_QUOTES) ?>"
                            placeholder="Search title, ISBN, publisher…" autocomplete="off" id="searchInput">
                    </div>

                    <!-- Category filter -->
                    <select name="cat" class="cat-filter-select" onchange="this.form.submit()">
                        <option value="0">All Categories</option>
                        <?php foreach ($allCats as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= $catFilter === (int) $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name'], ENT_QUOTES) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Per page -->
                    <select name="per_page" class="per-page-select" onchange="this.form.submit()">
                        <?php foreach ([5, 10, 25, 50] as $n): ?>
                            <option value="<?= $n ?>" <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?> / page</option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Result count -->
                    <span class="result-label">
                        <strong><?= number_format($totalRows) ?></strong>
                        <?= $totalRows === 1 ? 'result' : 'results' ?>
                    </span>
                </div>

                <!-- View toggle -->
                <div class="view-toggle">
                    <a href="<?= pageUrl(['view' => 'table', 'page' => 1]) ?>"
                        class="view-btn <?= $view === 'table' ? 'active' : '' ?>" title="Table view">
                        <i class='bx bx-list-ul'></i>
                    </a>
                    <a href="<?= pageUrl(['view' => 'grid', 'page' => 1]) ?>"
                        class="view-btn <?= $view === 'grid' ? 'active' : '' ?>" title="Grid view">
                        <i class='bx bx-grid-alt'></i>
                    </a>
                </div>
            </div>
        </form>

        <!-- Active filter chips -->
        <?php if ($search || $catFilter > 0): ?>
            <div class="filter-chips">
                <?php if ($search): ?>
                    <a href="<?= pageUrl(['search' => '', 'page' => 1]) ?>" class="filter-chip">
                        <i class='bx bx-search'></i> "<?= htmlspecialchars($search, ENT_QUOTES) ?>"
                        <i class='bx bx-x'></i>
                    </a>
                <?php endif; ?>
                <?php if ($catFilter > 0):
                    $activeCatName = '';
                    foreach ($allCats as $c) {
                        if ((int) $c['id'] === $catFilter) {
                            $activeCatName = $c['name'];
                            break;
                        }
                    }
                    ?>
                    <a href="<?= pageUrl(['cat' => 0, 'page' => 1]) ?>" class="filter-chip">
                        <i class='bx bx-collection'></i> <?= htmlspecialchars($activeCatName, ENT_QUOTES) ?>
                        <i class='bx bx-x'></i>
                    </a>
                <?php endif; ?>
                <?php if ($search || $catFilter > 0): ?>
                    <a href="AllBooks.php" class="filter-chip" style="background:var(--gray-100);color:var(--gray-500);">
                        <i class='bx bx-x-circle'></i> Clear all
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($books)): ?>
            <!-- Empty state -->
            <div class="empty-state">
                <div class="empty-state-icon"><i class='bx bx-book-open'></i></div>
                <h3><?= ($search || $catFilter) ? 'No Results Found' : 'No Books Yet' ?></h3>
                <p>
                    <?= ($search || $catFilter)
                        ? 'Try different search terms or clear your filters.'
                        : 'Get started by adding your first book to the library.' ?>
                </p>
                <?php if ($search || $catFilter): ?>
                    <a href="AllBooks.php" class="btn-empty"><i class='bx bx-x'></i> Clear Filters</a>
                <?php else: ?>
                    <a href="AddBook.php" class="btn-empty"><i class='bx bx-plus'></i> Add Book</a>
                <?php endif; ?>
            </div>

        <?php elseif ($view === 'grid'): ?>
            <!-- ── GRID VIEW ─────────────────────────────────────── -->
            <div class="books-grid">
                <?php foreach ($books as $i => $book): ?>
                    <div class="book-card" style="animation: fadeUp .3s <?= $i * 0.04 ?>s both">
                        <div class="book-card-cover">
                            <?php if (!empty($book['img']) && file_exists($book['img'])): ?>
                                <img src="<?= htmlspecialchars($book['img'], ENT_QUOTES) ?>"
                                    alt="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>">
                            <?php else: ?>
                                <div class="book-card-cover-placeholder">
                                    <i class='bx bx-book'></i>
                                    <span>No Cover</span>
                                </div>
                            <?php endif; ?>
                            <span class="book-card-badge">#<?= (int) $book['id'] ?></span>
                        </div>
                        <div class="book-card-body">
                            <div class="book-card-title"><?= htmlspecialchars($book['title'], ENT_QUOTES) ?></div>
                            <div class="book-card-cat <?= $book['category_name'] ? '' : 'uncategorized' ?>">
                                <?= $book['category_name'] ? htmlspecialchars($book['category_name'], ENT_QUOTES) : 'Uncategorized' ?>
                            </div>
                            <?php if (!empty($book['publishers'])): ?>
                                <div
                                    style="font-size:11.5px;color:var(--gray-400);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= htmlspecialchars($book['publishers'], ENT_QUOTES) ?>
                                </div>
                            <?php endif; ?>
                            <div class="book-card-price <?= empty($book['price']) ? 'no-price' : '' ?>">
                                <?= !empty($book['price']) ? 'INR ' . number_format((float) $book['price'], 2) : 'No price' ?>
                            </div>
                        </div>
                        <div class="book-card-actions">
                            <a href="EditBook.php?id=<?= (int) $book['id'] ?>" title="Edit">
                                <i class='bx bx-edit-alt'></i>
                            </a>
                            <button class="act-btn act-delete" data-id="<?= (int) $book['id'] ?>"
                                data-name="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>">
                                <i class='bx bx-trash'></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- ── TABLE VIEW ────────────────────────────────────── -->
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width:46px">#</th>
                            <th>
                                <a href="<?= sortUrl('title') ?>">
                                    Book
                                    <i
                                        class='bx <?= $sort === 'title' ? ($dir === 'asc' ? 'bx-sort-up' : 'bx-sort-down') : 'bx-sort-alt-2' ?> sort-icon <?= $sort === 'title' ? 'active' : '' ?>'></i>
                                </a>
                            </th>
                            <th style="width:140px">Category</th>
                            <th style="width:130px">
                                <a href="<?= sortUrl('publishers') ?>">
                                    Publisher
                                    <i
                                        class='bx <?= $sort === 'publishers' ? ($dir === 'asc' ? 'bx-sort-up' : 'bx-sort-down') : 'bx-sort-alt-2' ?> sort-icon <?= $sort === 'publishers' ? 'active' : '' ?>'></i>
                                </a>
                            </th>
                            <th style="width:110px">
                                <a href="<?= sortUrl('price') ?>">
                                    Price
                                    <i
                                        class='bx <?= $sort === 'price' ? ($dir === 'asc' ? 'bx-sort-up' : 'bx-sort-down') : 'bx-sort-alt-2' ?> sort-icon <?= $sort === 'price' ? 'active' : '' ?>'></i>
                                </a>
                            </th>
                            <th style="width:80px;text-align:center">Pages</th>
                            <th style="width:110px;text-align:center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $i => $book): ?>
                            <tr style="animation: fadeUp .25s <?= $i * 0.03 ?>s both">
                                <td class="td-num"><?= str_pad($offset + $i + 1, 2, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div class="book-cell">
                                        <?php if (!empty($book['img']) && file_exists($book['img'])): ?>
                                            <div class="book-cover">
                                                <img src="<?= htmlspecialchars($book['img'], ENT_QUOTES) ?>"
                                                    alt="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>">
                                            </div>
                                        <?php else: ?>
                                            <div class="book-cover-placeholder">
                                                <i class='bx bx-book'></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="book-cell-info">
                                            <div class="book-title"><?= htmlspecialchars($book['title'], ENT_QUOTES) ?></div>
                                            <div class="book-meta">
                                                <span
                                                    class="book-isbn"><?= htmlspecialchars($book['isbn'], ENT_QUOTES) ?></span>
                                                <?php if (!empty($book['description'])): ?>
                                                    <span
                                                        class="desc-excerpt"><?= htmlspecialchars(substr($book['description'], 0, 60), ENT_QUOTES) ?>…</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="cat-pill <?= $book['category_name'] ? '' : 'uncategorized' ?>">
                                        <?= $book['category_name'] ? htmlspecialchars($book['category_name'], ENT_QUOTES) : 'Uncategorized' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="book-publisher">
                                        <?= !empty($book['publishers']) ? htmlspecialchars($book['publishers'], ENT_QUOTES) : '<span style="color:var(--gray-300)">—</span>' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="price-tag <?= empty($book['price']) ? 'no-price' : '' ?>">
                                        <?= !empty($book['price']) ? 'INR ' . number_format((float) $book['price'], 2) : '—' ?>
                                    </span>
                                </td>
                                <td style="text-align:center">
                                    <span class="pages-badge <?= empty($book['length']) ? 'empty' : '' ?>">
                                        <?= !empty($book['length']) ? (int) $book['length'] . ' pp' : '—' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="row-actions" style="justify-content:center">
                                        <a href="EditBook.php?id=<?= (int) $book['id'] ?>" class="act-btn act-edit"
                                            title="Edit book">
                                            <i class='bx bx-edit-alt'></i>
                                        </a>
                                        <button class="act-btn act-delete" data-id="<?= (int) $book['id'] ?>"
                                            data-name="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>">
                                            <i class='bx bx-trash'></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- ── Pagination ──────────────────────────────── -->
        <?php if ($totalPages > 1 || $totalRows > 0): ?>
            <div class="pagination-row">
                <div class="pag-info">
                    Showing
                    <strong><?= $offset + 1 ?></strong>–<strong><?= min($offset + $perPage, $totalRows) ?></strong>
                    of <strong><?= number_format($totalRows) ?></strong>
                </div>

                <?php if ($totalPages > 1): ?>
                    <ul class="pag-list">
                        <li>
                            <a class="pag-btn <?= $currentPage <= 1 ? 'is-disabled' : '' ?>"
                                href="<?= pageUrl(['page' => 1]) ?>" title="First page">
                                <i class='bx bx-chevrons-left'></i>
                            </a>
                        </li>
                        <li>
                            <a class="pag-btn <?= $currentPage <= 1 ? 'is-disabled' : '' ?>"
                                href="<?= pageUrl(['page' => $currentPage - 1]) ?>" title="Previous page">
                                <i class='bx bx-chevron-left'></i>
                            </a>
                        </li>

                        <?php
                        $window = 2;
                        $pStart = max(1, $currentPage - $window);
                        $pEnd = min($totalPages, $currentPage + $window);
                        if ($pStart > 1): ?>
                            <li><a class="pag-btn" href="<?= pageUrl(['page' => 1]) ?>">1</a></li>
                            <?php if ($pStart > 2): ?>
                                <li><span class="pag-ellipsis">…</span></li><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($p = $pStart; $p <= $pEnd; $p++): ?>
                            <li>
                                <a class="pag-btn <?= $p === $currentPage ? 'is-active' : '' ?>"
                                    href="<?= pageUrl(['page' => $p]) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pEnd < $totalPages): ?>
                            <?php if ($pEnd < $totalPages - 1): ?>
                                <li><span class="pag-ellipsis">…</span></li><?php endif; ?>
                            <li><a class="pag-btn" href="<?= pageUrl(['page' => $totalPages]) ?>"><?= $totalPages ?></a></li>
                        <?php endif; ?>

                        <li>
                            <a class="pag-btn <?= $currentPage >= $totalPages ? 'is-disabled' : '' ?>"
                                href="<?= pageUrl(['page' => $currentPage + 1]) ?>" title="Next page">
                                <i class='bx bx-chevron-right'></i>
                            </a>
                        </li>
                        <li>
                            <a class="pag-btn <?= $currentPage >= $totalPages ? 'is-disabled' : '' ?>"
                                href="<?= pageUrl(['page' => $totalPages]) ?>" title="Last page">
                                <i class='bx bx-chevrons-right'></i>
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div><!-- /.main-card -->

    </div><!-- /.dash-content -->
    </section><!-- /.home-section -->

    <script src="./components/ui.js"></script>
    <script>
        // ── Search debounce ──────────────────────────────────────────────────
        const searchInput = document.getElementById('searchInput');
        let searchTimer;
        searchInput?.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 420);
        });
    </script>

</body>

</html>