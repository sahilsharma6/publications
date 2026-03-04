<?php
session_start();
include '../../../db.php';

/* ── Auth Guard ──────────────────────────────────────────────────────────── */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'SuperAdmin'])) {
    header("Location: login.php");
    exit();
}

/* ── Handle DELETE ───────────────────────────────────────────────────────── */
$toast = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];

    // Remove pivot rows first (FK safety)
    $dp = $conn->prepare("DELETE FROM book_authors WHERE author_id = ?");
    $dp->bind_param("i", $delete_id);
    $dp->execute();
    $dp->close();

    // Delete author image from disk
    $imgRow = $conn->prepare("SELECT image FROM authors WHERE id = ?");
    $imgRow->bind_param("i", $delete_id);
    $imgRow->execute();
    $imgRow->bind_result($imgFile);
    $imgRow->fetch();
    $imgRow->close();
    if ($imgFile) {
        $imgPath = "../../uploads/authors/" . $imgFile;
        if (file_exists($imgPath))
            @unlink($imgPath);
    }

    $del = $conn->prepare("DELETE FROM authors WHERE id = ?");
    $del->bind_param("i", $delete_id);
    $toast = $del->execute()
        ? ['type' => 'success', 'msg' => 'Author deleted successfully.']
        : ['type' => 'error', 'msg' => 'Failed to delete author.'];
    $del->close();
}

/* ── Params ──────────────────────────────────────────────────────────────── */
$search = trim($_GET['search'] ?? '');
$perPage = (int) ($_GET['per_page'] ?? 10);
$perPage = in_array($perPage, [5, 10, 25, 50], true) ? $perPage : 10;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$sort = in_array($_GET['sort'] ?? '', ['name', 'title', 'id'], true) ? $_GET['sort'] : 'id';
$dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$view = ($_GET['view'] ?? 'table') === 'grid' ? 'grid' : 'table';

$sp = '%' . $search . '%';

/* ── Count ───────────────────────────────────────────────────────────────── */
$cntStmt = $conn->prepare("SELECT COUNT(*) FROM authors WHERE name LIKE ? OR title LIKE ?");
$cntStmt->bind_param("ss", $sp, $sp);
$cntStmt->execute();
$cntStmt->bind_result($totalRows);
$cntStmt->fetch();
$cntStmt->close();

$totalPages = max(1, (int) ceil($totalRows / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;

/* ── Fetch authors ───────────────────────────────────────────────────────── */
$authStmt = $conn->prepare(
    "SELECT id, name, title, description, image
     FROM authors
     WHERE name LIKE ? OR title LIKE ?
     ORDER BY $sort $dir
     LIMIT ? OFFSET ?"
);
$authStmt->bind_param("ssii", $sp, $sp, $perPage, $offset);
$authStmt->execute();
$authRes = $authStmt->get_result();
$authors = $authRes ? $authRes->fetch_all(MYSQLI_ASSOC) : [];
$authStmt->close();

/* ── Fetch books for each author ─────────────────────────────────────────── */
$authorBooks = [];
if (!empty($authors)) {
    $ids = implode(',', array_map(fn($a) => (int) $a['id'], $authors));
    $bookQuery = $conn->query(
        "SELECT ba.author_id, bd.name
         FROM book_authors ba
         JOIN books_data bd ON bd.id = ba.book_id
         WHERE ba.author_id IN ($ids)
         ORDER BY bd.name ASC"
    );
    if ($bookQuery) {
        while ($row = $bookQuery->fetch_assoc()) {
            $authorBooks[$row['author_id']][] = $row['name'];
        }
    }
}

/* ── Stats ───────────────────────────────────────────────────────────────── */
$totalAll = (int) $conn->query("SELECT COUNT(*) FROM authors")->fetch_row()[0];
$withPhoto = (int) $conn->query("SELECT COUNT(*) FROM authors WHERE image IS NOT NULL AND image != ''")->fetch_row()[0];
$withBooks = (int) $conn->query("SELECT COUNT(DISTINCT author_id) FROM book_authors")->fetch_row()[0];

/* ── URL helper ──────────────────────────────────────────────────────────── */
function pageUrl(array $ov = []): string
{
    return 'AllAuthors.php?' . http_build_query(array_merge([
        'page' => $_GET['page'] ?? 1,
        'search' => $_GET['search'] ?? '',
        'sort' => $_GET['sort'] ?? 'id',
        'dir' => $_GET['dir'] ?? 'desc',
        'view' => $_GET['view'] ?? 'table',
        'per_page' => $_GET['per_page'] ?? 10,
    ], $ov));
}
function sortUrl(string $col): string
{
    $cd = $_GET['sort'] ?? 'id';
    $dir = $_GET['dir'] ?? 'desc';
    return pageUrl(['sort' => $col, 'dir' => ($cd === $col && $dir === 'asc') ? 'desc' : 'asc', 'page' => 1]);
}
function sortIcon(string $col): string
{
    $cs = $_GET['sort'] ?? 'id';
    $cd = $_GET['dir'] ?? 'desc';
    $cls = ($cs === $col) ? 'active' : '';
    $ico = ($cs === $col) ? ($cd === 'asc' ? 'bx-sort-up' : 'bx-sort-down') : 'bx-sort-alt-2';
    return "<i class='bx $ico sort-icon $cls'></i>";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Authors — BookAdmin</title>
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
    /* ── Page header ─────────────────────────────────── */
    .page-header       { margin-bottom: 26px; }
    .page-header h1    { font-size: 22px; font-weight: 700; color: var(--gray-900); letter-spacing: -.3px; }

    .breadcrumb {
        display: flex; align-items: center; gap: 6px;
        font-size: 12.5px; color: var(--gray-400); margin-top: 6px;
    }
    .breadcrumb a {
        color: var(--gray-400); text-decoration: none;
        display: inline-flex; align-items: center; gap: 4px;
        transition: color var(--t);
    }
    .breadcrumb a:hover { color: var(--accent); }

    .header-row {
        display: flex; align-items: flex-start;
        justify-content: space-between; flex-wrap: wrap;
        gap: 14px; margin-top: 10px;
    }

    .btn-add {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 20px;
        background: var(--accent); color: #fff;
        font-size: 13px; font-weight: 600; font-family: inherit;
        border: none; border-radius: var(--r); cursor: pointer;
        text-decoration: none; transition: all var(--t);
        box-shadow: 0 2px 10px rgba(59,130,246,.28);
        white-space: nowrap;
    }
    .btn-add:hover {
        background: var(--accent-dark);
        box-shadow: 0 4px 18px rgba(59,130,246,.38);
        transform: translateY(-1px);
    }

    /* ── Summary strip ───────────────────────────────── */
    .summary-strip {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 22px;
    }

    .summary-card {
        background: #fff; border: 1px solid var(--gray-200);
        border-radius: var(--r-lg); padding: 18px 20px;
        display: flex; align-items: center; gap: 14px;
        box-shadow: var(--shadow-sm);
        transition: transform var(--t), box-shadow var(--t);
    }
    .summary-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); }

    .sum-icon {
        width: 44px; height: 44px; border-radius: var(--r);
        display: flex; align-items: center; justify-content: center;
        font-size: 21px; flex-shrink: 0;
    }
    .si-purple { background: #f5f3ff; color: #7c3aed; }
    .si-blue   { background: var(--accent-light); color: var(--accent); }
    .si-green  { background: var(--success-bg); color: var(--success); }

    .sum-val   { font-size: 26px; font-weight: 700; color: var(--gray-900); line-height: 1; }
    .sum-label { font-size: 12px; color: var(--gray-400); margin-top: 3px; }

    /* ── Main card ───────────────────────────────────── */
    .main-card {
        background: #fff; border: 1px solid var(--gray-200);
        border-radius: var(--r-xl); box-shadow: var(--shadow);
        overflow: hidden;
    }

    /* ── Toolbar ─────────────────────────────────────── */
    .toolbar {
        display: flex; align-items: center;
        justify-content: space-between; flex-wrap: wrap;
        gap: 12px; padding: 15px 22px;
        border-bottom: 1px solid var(--gray-100);
    }

    .toolbar-left { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

    .search-box { position: relative; }
    .search-box i {
        position: absolute; left: 11px; top: 50%;
        transform: translateY(-50%);
        font-size: 16px; color: var(--gray-400); pointer-events: none;
        transition: color var(--t);
    }
    .search-box:focus-within i { color: var(--accent); }

    .search-input {
        padding: 9px 14px 9px 34px;
        border: 1.5px solid var(--gray-200); border-radius: var(--r);
        font-size: 13px; font-family: inherit;
        color: var(--gray-800); background: var(--gray-50);
        outline: none; width: 220px; transition: all var(--t);
    }
    .search-input::placeholder { color: var(--gray-300); }
    .search-input:focus {
        border-color: var(--accent); background: #fff;
        box-shadow: 0 0 0 3px rgba(59,130,246,.1); width: 260px;
    }

    .per-page-select {
        padding: 8px 10px;
        border: 1.5px solid var(--gray-200); border-radius: var(--r);
        font-size: 13px; font-family: inherit; color: var(--gray-700);
        background: var(--gray-50); cursor: pointer; outline: none;
        transition: all var(--t);
    }
    .per-page-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59,130,246,.1); }

    .result-label { font-size: 12.5px; color: var(--gray-400); }
    .result-label strong { color: var(--gray-700); }

    .view-toggle {
        display: flex; border: 1.5px solid var(--gray-200);
        border-radius: var(--r); overflow: hidden;
    }
    .view-btn {
        width: 34px; height: 34px;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; color: var(--gray-400);
        text-decoration: none; transition: all var(--t);
        background: var(--gray-50);
    }
    .view-btn:hover { background: var(--gray-100); color: var(--gray-700); }
    .view-btn.active { background: var(--accent); color: #fff; }
    .view-btn + .view-btn { border-left: 1.5px solid var(--gray-200); }

    /* ── TABLE ───────────────────────────────────────── */
    .table-wrap { overflow-x: auto; }

    table { width: 100%; border-collapse: collapse; font-size: 13.5px; }

    thead th {
        padding: 11px 16px; text-align: left;
        font-size: 11px; font-weight: 600;
        text-transform: uppercase; letter-spacing: .7px;
        color: var(--gray-400); background: var(--gray-50);
        border-bottom: 1px solid var(--gray-200);
        white-space: nowrap;
    }

    .th-sort a {
        display: inline-flex; align-items: center; gap: 4px;
        color: inherit; text-decoration: none; transition: color var(--t);
    }
    .th-sort a:hover { color: var(--accent); }
    .sort-icon { font-size: 13px; opacity: .5; }
    .sort-icon.active { opacity: 1; color: var(--accent); }

    tbody tr { border-bottom: 1px solid var(--gray-100); transition: background var(--t); }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: #f8fbff; }

    tbody td { padding: 13px 16px; color: var(--gray-800); vertical-align: middle; }

    /* Author cell */
    .author-cell { display: flex; align-items: center; gap: 12px; }

    .author-avatar {
        width: 40px; height: 40px; border-radius: var(--r);
        object-fit: cover; flex-shrink: 0;
        border: 1.5px solid var(--gray-200);
        background: var(--gray-100);
    }

    .author-initials {
        width: 40px; height: 40px; border-radius: var(--r);
        background: linear-gradient(135deg, var(--accent-light), #e0e7ff);
        color: var(--accent); font-size: 15px; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; border: 1.5px solid rgba(59,130,246,.15);
    }

    .author-info-name { font-size: 14px; font-weight: 600; color: var(--gray-800); }
    .author-info-id   { font-size: 11px; color: var(--gray-300); font-family: "DM Mono", monospace; margin-top: 1px; }

    .title-badge {
        display: inline-block;
        padding: 3px 10px; border-radius: 20px;
        background: var(--gray-100); color: var(--gray-600);
        font-size: 12px; font-weight: 500;
    }

    /* Books chips */
    .books-cell { display: flex; flex-wrap: wrap; gap: 5px; }

    .book-chip {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 9px; border-radius: 20px;
        background: var(--accent-light); color: var(--accent);
        font-size: 11.5px; font-weight: 500;
        border: 1px solid rgba(59,130,246,.18);
        white-space: nowrap; max-width: 150px;
        overflow: hidden; text-overflow: ellipsis;
    }
    .book-chip i { font-size: 12px; flex-shrink: 0; }

    .no-books {
        font-size: 12px; color: var(--gray-300);
        font-style: italic;
    }

    /* Desc preview */
    .desc-preview {
        font-size: 12.5px; color: var(--gray-500);
        max-width: 220px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    /* Row num */
    .td-num {
        font-size: 11.5px; font-weight: 600;
        color: var(--gray-300); font-family: "DM Mono", monospace;
    }

    /* ── Action buttons ──────────────────────────────── */
    .row-actions { display: flex; gap: 6px; align-items: center; justify-content: center; }

    .act-btn {
        width: 32px; height: 32px; border-radius: var(--r-sm);
        border: 1.5px solid transparent;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; cursor: pointer; transition: all var(--t);
        background: transparent; text-decoration: none; flex-shrink: 0;
    }
    .act-edit   { background: var(--accent-light); color: var(--accent); border-color: rgba(59,130,246,.2); }
    .act-edit:hover   { background: var(--accent); color: #fff; border-color: var(--accent); transform: scale(1.08); }
    .act-delete { background: var(--danger-bg); color: var(--danger); border-color: rgba(239,68,68,.15); }
    .act-delete:hover { background: var(--danger); color: #fff; border-color: var(--danger); transform: scale(1.08); }
    .act-view   { background: var(--gray-100); color: var(--gray-500); border-color: var(--gray-200); }
    .act-view:hover { background: var(--gray-200); color: var(--gray-700); }

    /* ── GRID VIEW ───────────────────────────────────── */
    .grid-view {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 16px; padding: 20px;
    }

    .grid-card {
        border: 1.5px solid var(--gray-200); border-radius: var(--r-lg);
        padding: 22px 18px 16px;
        display: flex; flex-direction: column; gap: 0;
        transition: all var(--t); background: #fff;
        position: relative; overflow: hidden;
    }
    .grid-card::before {
        content: ''; position: absolute;
        top: 0; left: 0; right: 0; height: 3px;
        background: linear-gradient(90deg, var(--accent), #818cf8);
        opacity: 0; transition: opacity var(--t);
    }
    .grid-card:hover {
        border-color: var(--accent);
        box-shadow: 0 6px 24px rgba(59,130,246,.12);
        transform: translateY(-3px);
    }
    .grid-card:hover::before { opacity: 1; }

    .grid-avatar-wrap { display: flex; justify-content: center; margin-bottom: 14px; }

    .grid-avatar {
        width: 72px; height: 72px; border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--gray-200);
    }
    .grid-initials {
        width: 72px; height: 72px; border-radius: 50%;
        background: linear-gradient(135deg, var(--accent-light), #e0e7ff);
        color: var(--accent); font-size: 26px; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        border: 3px solid rgba(59,130,246,.15);
    }

    .grid-name  { font-size: 15px; font-weight: 700; color: var(--gray-800); text-align: center; margin-bottom: 4px; }
    .grid-title { font-size: 12px; color: var(--gray-400); text-align: center; margin-bottom: 12px; }

    .grid-books { display: flex; flex-wrap: wrap; gap: 5px; justify-content: center; margin-bottom: 14px; min-height: 28px; }

    .grid-desc {
        font-size: 12px; color: var(--gray-400); line-height: 1.5;
        text-align: center;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden; margin-bottom: 14px;
    }

    .grid-actions { display: flex; gap: 8px; margin-top: auto; }
    .grid-actions .act-btn { flex: 1; border-radius: var(--r-sm); }

    /* ── Empty state ─────────────────────────────────── */
    .empty-state { text-align: center; padding: 56px 24px; }
    .empty-icon {
        width: 72px; height: 72px; border-radius: 50%;
        background: var(--gray-100); color: var(--gray-300);
        display: flex; align-items: center; justify-content: center;
        font-size: 32px; margin: 0 auto 16px;
    }
    .empty-state h3 { font-size: 16px; font-weight: 600; color: var(--gray-700); margin-bottom: 6px; }
    .empty-state p  { font-size: 13px; color: var(--gray-400); margin-bottom: 20px; }
    .btn-empty {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 18px; background: var(--accent); color: #fff;
        border-radius: var(--r); font-size: 13px; font-weight: 600;
        text-decoration: none; transition: all var(--t);
    }
    .btn-empty:hover { background: var(--accent-dark); }

    /* ── Pagination ──────────────────────────────────── */
    .pagination-row {
        display: flex; align-items: center;
        justify-content: space-between; flex-wrap: wrap;
        gap: 12px; padding: 15px 22px;
        border-top: 1px solid var(--gray-100);
    }
    .pag-info { font-size: 12.5px; color: var(--gray-400); }
    .pag-info strong { color: var(--gray-700); font-weight: 600; }

    .pag-list { display: flex; gap: 4px; list-style: none; flex-wrap: wrap; }

    .pag-btn {
        display: flex; align-items: center; justify-content: center;
        min-width: 34px; height: 34px; border-radius: var(--r-sm);
        border: 1.5px solid var(--gray-200); background: #fff;
        color: var(--gray-600); font-size: 13px; font-weight: 500;
        font-family: inherit; text-decoration: none; padding: 0 8px;
        transition: all var(--t); cursor: pointer; white-space: nowrap;
    }
    .pag-btn:hover:not(.is-active):not(.is-disabled) {
        border-color: var(--accent); color: var(--accent); background: var(--accent-light);
    }
    .pag-btn.is-active {
        background: var(--accent); border-color: var(--accent); color: #fff;
        box-shadow: 0 2px 10px rgba(59,130,246,.3); cursor: default; font-weight: 700;
    }
    .pag-btn.is-disabled { opacity: .35; cursor: not-allowed; pointer-events: none; }
    .pag-ellipsis {
        display: flex; align-items: center; justify-content: center;
        width: 34px; height: 34px; color: var(--gray-300); font-size: 13px;
    }

    /* ── Detail popover ──────────────────────────────── */
    .detail-panel {
        display: none; padding: 18px 20px;
        border-top: 1px solid var(--gray-100);
        background: var(--gray-50);
        animation: fadeUp .2s var(--t) both;
    }
    .detail-panel.open { display: block; }
    .detail-panel-inner {
        display: flex; gap: 18px; flex-wrap: wrap; align-items: flex-start;
    }
    .detail-img {
        width: 64px; height: 64px; border-radius: var(--r);
        object-fit: cover; flex-shrink: 0;
        border: 1.5px solid var(--gray-200);
    }
    .detail-initials {
        width: 64px; height: 64px; border-radius: var(--r);
        background: linear-gradient(135deg, var(--accent-light), #e0e7ff);
        color: var(--accent); font-size: 22px; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .detail-body { flex: 1; min-width: 0; }
    .detail-name { font-size: 15px; font-weight: 700; color: var(--gray-800); margin-bottom: 4px; }
    .detail-desc { font-size: 13px; color: var(--gray-500); line-height: 1.55; margin-bottom: 10px; }
    .detail-books { display: flex; flex-wrap: wrap; gap: 5px; }

    /* ── Responsive ──────────────────────────────────── */
    @media (max-width: 720px) {
        .summary-strip { grid-template-columns: 1fr 1fr; }
        .summary-strip .summary-card:last-child { display: none; }
    }
    @media (max-width: 480px) {
        .summary-strip { grid-template-columns: 1fr; }
        .grid-view { grid-template-columns: 1fr; }
    }
    </style>
</head>
<body>

<?php include '../sidebar.php'; ?>

<?php if ($toast): ?>
    <div class="toast <?= $toast['type'] ?>" id="toast">
        <i class='bx <?= $toast['type'] === 'success' ? 'bx-check-circle' : 'bx-x-circle' ?>'></i>
        <?= htmlspecialchars($toast['msg'], ENT_QUOTES) ?>
        <button class="toast-close" onclick="dismissToast()">&times;</button>
    </div>
<?php endif; ?>

<!-- Delete modal -->
<div class="overlay" id="deleteOverlay">
    <div class="modal">
        <div class="modal-icon-wrap"><i class='bx bx-user-x'></i></div>
        <h3>Delete Author?</h3>
        <p>You are about to delete <strong id="delAuthorName"></strong>.<br>
           This will also remove all their book assignments. This cannot be undone.</p>
        <form method="POST" id="deleteForm" class="modal-btns">
            <input type="hidden" name="delete_id" id="deleteIdField">
            <button type="button" class="btn-cancel-modal" onclick="closeModal()">Cancel</button>
            <button type="submit" class="btn-del-confirm" onclick="showSpinner()">
                <span class="spinner" id="delSpinner"></span>
                <span id="delBtnText">Delete</span>
            </button>
        </form>
    </div>
</div>

<!-- ── Page header ─────────────────────────────────────────────── -->
<div class="page-header">
    <nav class="breadcrumb">
        <a href="../"><i class='bx bx-home-alt'></i> Dashboard</a>
        <i class='bx bx-chevron-right'></i>
        <span>Authors</span>
    </nav>
    <div class="header-row">
        <h1>All Authors</h1>
        <a href="AddAuthor.php" class="btn-add">
            <i class='bx bx-user-plus'></i> Add Author
        </a>
    </div>
</div>

<!-- ── Summary strip ──────────────────────────────────────────── -->
<div class="summary-strip">
    <div class="summary-card">
        <div class="sum-icon si-purple"><i class='bx bx-group'></i></div>
        <div>
            <div class="sum-val"><?= number_format($totalAll) ?></div>
            <div class="sum-label">Total Authors</div>
        </div>
    </div>
    <div class="summary-card">
        <div class="sum-icon si-green"><i class='bx bx-book-open'></i></div>
        <div>
            <div class="sum-val"><?= number_format($withBooks) ?></div>
            <div class="sum-label">With Books Assigned</div>
        </div>
    </div>
    <div class="summary-card">
        <div class="sum-icon si-blue"><i class='bx bx-image'></i></div>
        <div>
            <div class="sum-val"><?= number_format($withPhoto) ?></div>
            <div class="sum-label">With Photo</div>
        </div>
    </div>
</div>

<!-- ── Main card ──────────────────────────────────────────────── -->
<div class="main-card">

    <!-- Toolbar -->
    <form method="GET" action="AllAuthors.php" id="filterForm">
        <input type="hidden" name="view"     value="<?= htmlspecialchars($view, ENT_QUOTES) ?>">
        <input type="hidden" name="sort"     value="<?= htmlspecialchars($sort, ENT_QUOTES) ?>">
        <input type="hidden" name="dir"      value="<?= htmlspecialchars($dir, ENT_QUOTES) ?>">
        <input type="hidden" name="page"     value="1">

        <div class="toolbar">
            <div class="toolbar-left">
                <div class="search-box">
                    <i class='bx bx-search'></i>
                    <input type="text" class="search-input" name="search"
                           id="searchInput"
                           value="<?= htmlspecialchars($search, ENT_QUOTES) ?>"
                           placeholder="Search authors…"
                           autocomplete="off">
                </div>
                <select name="per_page" class="per-page-select" onchange="this.form.submit()">
                    <?php foreach ([5, 10, 25, 50] as $n): ?>
                        <option value="<?= $n ?>" <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?> / page</option>
                    <?php endforeach; ?>
                </select>
                <span class="result-label">
                    <strong><?= number_format($totalRows) ?></strong>
                    <?= $totalRows === 1 ? 'author' : 'authors' ?>
                </span>
            </div>
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

    <?php if (empty($authors)): ?>
        <!-- Empty state -->
        <div class="empty-state">
            <div class="empty-icon"><i class='bx bx-user-x'></i></div>
            <h3><?= $search ? 'No Authors Found' : 'No Authors Yet' ?></h3>
            <p><?= $search ? 'Try a different search term.' : 'Add your first author to get started.' ?></p>
            <?php if ($search): ?>
                    <a href="AllAuthors.php" class="btn-empty"><i class='bx bx-x'></i> Clear Search</a>
            <?php else: ?>
                    <a href="AddAuthor.php" class="btn-empty"><i class='bx bx-plus'></i> Add Author</a>
            <?php endif; ?>
        </div>

    <?php elseif ($view === 'grid'): ?>
        <!-- ── GRID VIEW ──────────────────────────────────── -->
        <div class="grid-view">
            <?php foreach ($authors as $i => $a):
                $books = $authorBooks[$a['id']] ?? [];
                $initial = strtoupper(substr($a['name'], 0, 1));
                $imgPath = "../../uploads/authors/" . $a['image'];
                $hasImg = !empty($a['image']) && file_exists($imgPath);
                ?>
                <div class="grid-card" style="animation-delay:<?= $i * .04 ?>s">

                    <div class="grid-avatar-wrap">
                        <?php if ($hasImg): ?>
                                <img src="<?= $imgPath ?>" class="grid-avatar" alt="<?= htmlspecialchars($a['name'], ENT_QUOTES) ?>">
                        <?php else: ?>
                                <div class="grid-initials"><?= $initial ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="grid-name"><?= htmlspecialchars($a['name'], ENT_QUOTES) ?></div>
                    <?php if ($a['title']): ?>
                        <div class="grid-title"><?= htmlspecialchars($a['title'], ENT_QUOTES) ?></div>
                    <?php endif; ?>

                    <div class="grid-books">
                        <?php if (empty($books)): ?>
                                <span class="no-books">No books assigned</span>
                        <?php else: ?>
                                <?php foreach (array_slice($books, 0, 2) as $bk): ?>
                                        <span class="book-chip"><i class='bx bx-book-alt'></i><?= htmlspecialchars($bk, ENT_QUOTES) ?></span>
                                <?php endforeach; ?>
                                <?php if (count($books) > 2): ?>
                                        <span class="book-chip" style="background:var(--gray-100);color:var(--gray-500);border-color:var(--gray-200)">
                                            +<?= count($books) - 2 ?> more
                                        </span>
                                <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($a['description']): ?>
                        <div class="grid-desc"><?= htmlspecialchars($a['description'], ENT_QUOTES) ?></div>
                    <?php endif; ?>

                    <div class="grid-actions">
                        <a href="EditAuthor.php?id=<?= (int) $a['id'] ?>" class="act-btn act-edit" title="Edit">
                            <i class='bx bx-edit-alt'></i>
                        </a>
                        <button class="act-btn act-delete" title="Delete"
                            onclick="openDelete(<?= (int) $a['id'] ?>, <?= json_encode($a['name']) ?>)">
                            <i class='bx bx-trash'></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <!-- ── TABLE VIEW ─────────────────────────────────── -->
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:46px">#</th>
                        <th class="th-sort"><a href="<?= sortUrl('name') ?>">Author <?= sortIcon('name') ?></a></th>
                        <th class="th-sort"><a href="<?= sortUrl('title') ?>">Title <?= sortIcon('title') ?></a></th>
                        <th>Books</th>
                        <th>Biography</th>
                        <th style="width:110px;text-align:center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($authors as $i => $a):
                        $books = $authorBooks[$a['id']] ?? [];
                        $initial = strtoupper(substr($a['name'], 0, 1));
                        $imgPath = "../../uploads/authors/" . $a['image'];
                        $hasImg = !empty($a['image']) && file_exists($imgPath);
                        ?>
                        <tr style="animation: fadeUp .25s <?= $i * .03 ?>s both">
                            <td class="td-num"><?= str_pad($offset + $i + 1, 2, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <div class="author-cell">
                                    <?php if ($hasImg): ?>
                                            <img src="<?= $imgPath ?>" class="author-avatar" alt="">
                                    <?php else: ?>
                                            <div class="author-initials"><?= $initial ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="author-info-name"><?= htmlspecialchars($a['name'], ENT_QUOTES) ?></div>
                                        <div class="author-info-id">#<?= (int) $a['id'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($a['title']): ?>
                                    <span class="title-badge"><?= htmlspecialchars($a['title'], ENT_QUOTES) ?></span>
                                <?php else: ?>
                                    <span style="color:var(--gray-300);font-size:12px">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (empty($books)): ?>
                                        <span class="no-books">None assigned</span>
                                <?php else: ?>
                                    <div class="books-cell">
                                        <?php foreach (array_slice($books, 0, 2) as $bk): ?>
                                                <span class="book-chip">
                                                    <i class='bx bx-book-alt'></i>
                                                    <?= htmlspecialchars($bk, ENT_QUOTES) ?>
                                                </span>
                                        <?php endforeach; ?>
                                        <?php if (count($books) > 2): ?>
                                                <span class="book-chip" style="background:var(--gray-100);color:var(--gray-500);border-color:var(--gray-200)">
                                                    +<?= count($books) - 2 ?> more
                                                </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($a['description']): ?>
                                    <div class="desc-preview" title="<?= htmlspecialchars($a['description'], ENT_QUOTES) ?>">
                                        <?= htmlspecialchars($a['description'], ENT_QUOTES) ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--gray-300);font-size:12px">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <button class="act-btn act-view" title="Quick view"
                                        onclick="toggleDetail('detail-<?= (int) $a['id'] ?>', this)">
                                        <i class='bx bx-expand-alt'></i>
                                    </button>
                                    <a href="EditAuthor.php?id=<?= (int) $a['id'] ?>" class="act-btn act-edit" title="Edit">
                                        <i class='bx bx-edit-alt'></i>
                                    </a>
                                    <button class="act-btn act-delete" title="Delete"
                                        onclick="openDelete(<?= (int) $a['id'] ?>, <?= json_encode($a['name']) ?>)">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <!-- Expandable detail row -->
                        <tr>
                            <td colspan="6" style="padding:0;border:none">
                                <div class="detail-panel" id="detail-<?= (int) $a['id'] ?>">
                                    <div class="detail-panel-inner">
                                        <?php if ($hasImg): ?>
                                                <img src="<?= $imgPath ?>" class="detail-img" alt="">
                                        <?php else: ?>
                                                <div class="detail-initials"><?= $initial ?></div>
                                        <?php endif; ?>
                                        <div class="detail-body">
                                            <div class="detail-name"><?= htmlspecialchars($a['name'], ENT_QUOTES) ?></div>
                                            <?php if ($a['description']): ?>
                                                <div class="detail-desc"><?= nl2br(htmlspecialchars($a['description'], ENT_QUOTES)) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($books)): ?>
                                                <div class="detail-books">
                                                    <?php foreach ($books as $bk): ?>
                                                        <span class="book-chip"><i class='bx bx-book-alt'></i><?= htmlspecialchars($bk, ENT_QUOTES) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($totalRows > 0): ?>
        <div class="pagination-row">
            <div class="pag-info">
                Showing <strong><?= $offset + 1 ?></strong>–<strong><?= min($offset + $perPage, $totalRows) ?></strong>
                of <strong><?= number_format($totalRows) ?></strong>
            </div>

            <?php if ($totalPages > 1):
                $w = 2;
                $pS = max(1, $currentPage - $w);
                $pE = min($totalPages, $currentPage + $w);
                ?>
                <ul class="pag-list">
                    <li><a class="pag-btn <?= $currentPage <= 1 ? 'is-disabled' : '' ?>" href="<?= pageUrl(['page' => 1]) ?>"><i class='bx bx-chevrons-left'></i></a></li>
                    <li><a class="pag-btn <?= $currentPage <= 1 ? 'is-disabled' : '' ?>" href="<?= pageUrl(['page' => $currentPage - 1]) ?>"><i class='bx bx-chevron-left'></i></a></li>

                    <?php if ($pS > 1): ?>
                            <li><a class="pag-btn" href="<?= pageUrl(['page' => 1]) ?>">1</a></li>
                            <?php if ($pS > 2): ?><li><span class="pag-ellipsis">…</span></li><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($p = $pS; $p <= $pE; $p++): ?>
                            <li><a class="pag-btn <?= $p === $currentPage ? 'is-active' : '' ?>" href="<?= pageUrl(['page' => $p]) ?>"><?= $p ?></a></li>
                    <?php endfor; ?>
                    <?php if ($pE < $totalPages): ?>
                            <?php if ($pE < $totalPages - 1): ?><li><span class="pag-ellipsis">…</span></li><?php endif; ?>
                            <li><a class="pag-btn" href="<?= pageUrl(['page' => $totalPages]) ?>"><?= $totalPages ?></a></li>
                    <?php endif; ?>

                    <li><a class="pag-btn <?= $currentPage >= $totalPages ? 'is-disabled' : '' ?>" href="<?= pageUrl(['page' => $currentPage + 1]) ?>"><i class='bx bx-chevron-right'></i></a></li>
                    <li><a class="pag-btn <?= $currentPage >= $totalPages ? 'is-disabled' : '' ?>" href="<?= pageUrl(['page' => $totalPages]) ?>"><i class='bx bx-chevrons-right'></i></a></li>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div><!-- /.main-card -->

</div><!-- /.dash-content -->
</section><!-- /.home-section -->

<script>
// ── Search debounce ──────────────────────────────────────────
const searchInput = document.getElementById('searchInput');
let st;
searchInput?.addEventListener('input', () => {
    clearTimeout(st);
    st = setTimeout(() => document.getElementById('filterForm').submit(), 420);
});

// ── Delete modal ─────────────────────────────────────────────
const overlay     = document.getElementById('deleteOverlay');
const delName     = document.getElementById('delAuthorName');
const deleteIdFld = document.getElementById('deleteIdField');

function openDelete(id, name) {
    deleteIdFld.value   = id;
    delName.textContent = name;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    overlay.classList.remove('open');
    document.body.style.overflow = '';
}
function showSpinner() {
    document.getElementById('delSpinner').style.display = 'block';
    document.getElementById('delBtnText').textContent   = 'Deleting…';
}
overlay?.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// ── Expandable detail row (table view) ───────────────────────
function toggleDetail(id, btn) {
    const panel = document.getElementById(id);
    if (!panel) return;
    const isOpen = panel.classList.toggle('open');
    btn.querySelector('i').className = isOpen ? 'bx bx-collapse-alt' : 'bx bx-expand-alt';
}

// ── Toast dismiss ────────────────────────────────────────────
const toast = document.getElementById('toast');
if (toast) {
    const t = setTimeout(dismissToast, 4500);
    toast.addEventListener('click', () => { clearTimeout(t); dismissToast(); });
}
function dismissToast() {
    const t = document.getElementById('toast');
    if (!t) return;
    t.classList.add('hiding');
    setTimeout(() => t.remove(), 320);
}
</script>
</body>
</html>