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

// ── Handle DELETE ─────────────────────────────────────────────────────────────
$toast = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];
    $del = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $del->bind_param("i", $delete_id);
    $toast = $del->execute()
        ? ['type' => 'success', 'msg' => 'Category deleted successfully.']
        : ['type' => 'error', 'msg' => 'Failed to delete category.'];
    $del->close();
}

// ── Pagination + Search ───────────────────────────────────────────────────────
$perPage = (int) ($_GET['per_page'] ?? 10);
$perPage = in_array($perPage, [5, 10, 25, 50, 100, 200], true) ? $perPage : 10;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$sort = in_array($_GET['sort'] ?? '', ['name', 'id'], true) ? $_GET['sort'] : 'id';
$dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$view = ($_GET['view'] ?? 'table') === 'grid' ? 'grid' : 'table';

$searchParam = '%' . $search . '%';

// Total count
$countStmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE name LIKE ?");
$countStmt->bind_param("s", $searchParam);
$countStmt->execute();
$countStmt->bind_result($totalRows);
$countStmt->fetch();
$countStmt->close();

$totalPages = max(1, (int) ceil($totalRows / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;

// Fetch rows — sort/dir are whitelisted above so safe to interpolate
$catStmt = $conn->prepare(
    "SELECT id, name FROM categories
     WHERE name LIKE ?
     ORDER BY $sort $dir
     LIMIT ? OFFSET ?"
);
$catStmt->bind_param("sii", $searchParam, $perPage, $offset);
$catStmt->execute();

// Guard: if query failed (e.g. bad column), show empty gracefully
$catResult = $catStmt->get_result();
$categories = $catResult ? $catResult->fetch_all(MYSQLI_ASSOC) : [];
$catStmt->close();

// Total ALL categories (for summary card)
$totalAllStmt = $conn->query("SELECT COUNT(*) FROM categories");
$totalAll = (int) $totalAllStmt->fetch_row()[0];

// Newest category
$newestStmt = $conn->query("SELECT name FROM categories ORDER BY id DESC LIMIT 1");
$newestCat = $newestStmt ? ($newestStmt->fetch_row()[0] ?? '—') : '—';

// ── Helper: build URL preserving params ──────────────────────────────────────
function pageUrl(array $override = []): string
{
    $params = array_merge([
        'page' => $_GET['page'] ?? 1,
        'search' => $_GET['search'] ?? '',
        'sort' => $_GET['sort'] ?? 'id',
        'dir' => $_GET['dir'] ?? 'desc',
        'view' => $_GET['view'] ?? 'table',
        'per_page' => $_GET['per_page'] ?? 10,
    ], $override);
    return 'AllCategories.php?' . http_build_query($params);
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
    <title>All Categories — BookAdmin</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="AllCategories.css">

</head>

<body>

    <?php include './sidebar.php'; ?>

    <!-- <?php if ($toast): ?>
    <div class="toast <?= $toast['type'] ?>" id="toast">
        <i class='bx <?= $toast['type'] === 'success' ? 'bx-check-circle' : 'bx-x-circle' ?>'></i>
        <?= htmlspecialchars($toast['msg'], ENT_QUOTES) ?>
        <button class="toast-close" onclick="dismissToast()" aria-label="Close">&times;</button>
    </div>
    <?php endif; ?> -->

    <?php include './components/toast.php'; ?>
    <?php include './components/delete-modal.php'; ?>
    <!-- 
    <div class="overlay" id="deleteOverlay">
        <div class="modal">
            <div class="modal-icon-wrap"><i class='bx bx-trash'></i></div>
            <h3>Delete Category?</h3>
            <p id="delModalMsg">Are you sure you want to delete <strong id="delItemName"></strong>? This cannot be
                undone.</p>
            <form method="POST" action="" id="deleteForm" class="modal-btns">
                <input type="hidden" name="delete_id" id="deleteIdField">
                <button type="button" class="btn-cancel-modal" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-del-confirm" id="confirmDelBtn" onclick="showSpinner()">
                    <span class="spinner" id="delSpinner"></span>
                    <span id="delBtnText">Delete</span>
                </button>
            </form>
        </div>
    </div> -->


    <!-- Page header -->
    <div class="page-header">
        <div class="page-header-left">
            <h1>All Categories</h1>
            <nav class="breadcrumb" aria-label="breadcrumb">
                <a href="dashboard.php"><i class='bx bx-home-alt'></i> Dashboard</a>
                <i class='bx bx-chevron-right'></i>
                <span>Categories</span>
            </nav>
        </div>
        <a href="AddCategories.php" class="btn-add">
            <i class='bx bx-plus'></i> Add Category
        </a>
    </div>

    <!-- Summary strip -->
    <div class="summary-strip">
        <div class="summary-card">
            <div class="summary-icon si-blue"><i class='bx bx-collection'></i></div>
            <div class="summary-info">
                <div class="summary-val"><?= number_format($totalAll) ?></div>
                <div class="summary-label">Total Categories</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon si-indigo"><i class='bx bx-filter-alt'></i></div>
            <div class="summary-info">
                <div class="summary-val"><?= $search ? number_format($totalRows) : '—' ?></div>
                <div class="summary-label">
                    <?= $search ? 'Matching "' . htmlspecialchars($search, ENT_QUOTES) . '"' : 'No filter active' ?>
                </div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon si-teal"><i class='bx bx-bookmark-plus'></i></div>
            <div class="summary-info">
                <div class="summary-val" style="font-size:15px;line-height:1.4">
                    <?= htmlspecialchars($newestCat, ENT_QUOTES) ?>
                </div>
                <div class="summary-label">Most Recent</div>
            </div>
        </div>
    </div>

    <!-- Main card -->
    <div class="main-card">

        <!-- Toolbar -->
        <form method="GET" action="AllCategories.php" id="filterForm">
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
                            value="<?= htmlspecialchars($search, ENT_QUOTES) ?>" placeholder="Search categories…"
                            autocomplete="off" id="searchInput">
                    </div>

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

        <?php if (empty($categories)): ?>
            <!-- Empty state -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class='bx bx-folder-open'></i>
                </div>
                <h3><?= $search ? 'No Results Found' : 'No Categories Yet' ?></h3>
                <p>
                    <?= $search
                        ? 'Try a different search term or clear your filter.'
                        : 'Get started by adding your first category.' ?>
                </p>
                <?php if ($search): ?>
                    <a href="AllCategories.php" class="btn-empty"><i class='bx bx-x'></i> Clear Search</a>
                <?php else: ?>
                    <a href="AddCategories.php" class="btn-empty"><i class='bx bx-plus'></i> Add Category</a>
                <?php endif; ?>
            </div>

        <?php elseif ($view === 'grid'): ?>
            <!-- ── GRID VIEW ─────────────────────────────────── -->
            <div class="grid-view">
                <?php foreach ($categories as $i => $cat): ?>
                    <div class="grid-card" style="animation: fadeUp .3s <?= $i * 0.04 ?>s both">
                        <!-- <div class="grid-card-icon"><i class='bx bx-bookmark'></i></div> -->
                        <div>
                            <div class="grid-card-name"><?= htmlspecialchars($cat['name'], ENT_QUOTES) ?></div>
                            <div class="grid-card-meta">
                                <i class='bx bx-hash' style="font-size:12px"></i><?= (int) $cat['id'] ?>
                                <!-- <?php if (!empty($cat['created_at'])): ?>
                        &nbsp;·&nbsp; <?= date('d M Y', strtotime($cat['created_at'])) ?>
                        <?php endif; ?> -->
                            </div>
                        </div>
                        <div class="grid-card-actions">
                            <a href="EditCategory.php?id=<?= (int) $cat['id'] ?>" class="act-btn act-edit" title="Edit">
                                <i class='bx bx-edit-alt'></i>
                            </a>
                            <!-- <button class="act-btn act-delete" title="Delete"
                                onclick='openDelete(<?= (int) $cat["id"] ?>, <?= json_encode($cat["name"], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>

                                <i class='bx bx-trash'></i>a
                            </button> -->
                            <button class="act-btn act-delete" data-id="<?= (int) $cat['id'] ?>"
                                data-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>">
                                <i class='bx bx-trash'></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- ── TABLE VIEW ────────────────────────────────── -->
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th class="th-sort">
                                <a href="<?= sortUrl('name') ?>">
                                    Name
                                    <i
                                        class='bx <?= $sort === 'name' ? ($dir === 'asc' ? 'bx-sort-up' : 'bx-sort-down') : 'bx-sort-alt-2' ?> sort-icon <?= $sort === 'name' ? 'active' : '' ?>'></i>
                                </a>
                            </th>

                            <th style="width:110px; text-align:center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $i => $cat): ?>
                            <tr style="animation: fadeUp .25s <?= $i * 0.03 ?>s both">
                                <td class="td-num"><?= str_pad($offset + $i + 1, 2, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <span class="cat-badge">
                                        <span class="cat-dot"></span>
                                        <span class="cat-name"><?= htmlspecialchars($cat['name'], ENT_QUOTES) ?></span>
                                        <span class="cat-id">#<?= (int) $cat['id'] ?></span>
                                    </span>
                                </td>

                                <td>
                                    <div class="row-actions" style="justify-content:center">
                                        <a href="EditCategory.php?id=<?= (int) $cat['id'] ?>" class="act-btn act-edit"
                                            title="Edit category">
                                            <i class='bx bx-edit-alt'></i>
                                        </a>
                                        <!-- <button class="act-btn act-delete" title="Delete category"
                                            onclick='openDelete(<?= (int) $cat["id"] ?>, <?= json_encode($cat["name"], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>

                                            <i class='bx bx-trash'></i>
                                        </button> -->
                                        <button class="act-btn act-delete" data-id="<?= (int) $cat['id'] ?>"
                                            data-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>">
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
                <!-- Info -->
                <div class="pag-info">
                    Showing
                    <strong><?= $offset + 1 ?></strong>–<strong><?= min($offset + $perPage, $totalRows) ?></strong>
                    of <strong><?= number_format($totalRows) ?></strong>
                </div>

                <?php if ($totalPages > 1): ?>
                    <ul class="pag-list">

                        <!-- First + Prev -->
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
                        // Smart page window with ellipsis
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
                                    href="<?= pageUrl(['page' => $p]) ?>">
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pEnd < $totalPages): ?>
                            <?php if ($pEnd < $totalPages - 1): ?>
                                <li><span class="pag-ellipsis">…</span></li><?php endif; ?>
                            <li><a class="pag-btn" href="<?= pageUrl(['page' => $totalPages]) ?>"><?= $totalPages ?></a></li>
                        <?php endif; ?>

                        <!-- Next + Last -->
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

    </div><!-- /.dash-content  (opened in sidebar.php) -->
    </section><!-- /.home-section (opened in sidebar.php) -->


    <script src="./components/ui.js"></script>
    <script>
        // ── Search debounce ─────────────────────────────────────────────
        // (Sidebar toggle JS is handled inside sidebar.php)
        const searchInput = document.getElementById('searchInput');
        let searchTimer;
        searchInput?.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 420);
        });

        // // ── Delete modal ─────────────────────────────────────────────────
        // const overlay = document.getElementById('deleteOverlay');
        // const delItemName = document.getElementById('delItemName');
        // const deleteIdFld = document.getElementById('deleteIdField');
        // const delSpinner = document.getElementById('delSpinner');
        // const delBtnText = document.getElementById('delBtnText');

        // function openDelete(id, name) {
        //     deleteIdFld.value = id;
        //     delItemName.textContent = name;
        //     overlay.classList.add('open');
        //     document.body.style.overflow = 'hidden';
        // }

        // function closeModal() {
        //     overlay.classList.remove('open');
        //     document.body.style.overflow = '';
        // }

        // function showSpinner() {
        //     delSpinner.style.display = 'block';
        //     delBtnText.textContent = 'Deleting…';
        // }

        // // Click backdrop to close
        // overlay?.addEventListener('click', e => {
        //     if (e.target === overlay) closeModal();
        // });

        // // Escape key to close
        // document.addEventListener('keydown', e => {
        //     if (e.key === 'Escape') closeModal();
        // });

        // // ── Toast auto-dismiss ───────────────────────────────────────────
        // const toast = document.getElementById('toast');
        // if (toast) {
        //     const timer = setTimeout(() => dismissToast(), 4200);
        //     toast.addEventListener('click', () => { clearTimeout(timer); dismissToast(); });
        // }

        // function dismissToast() {
        //     if (!toast) return;
        //     toast.classList.add('hiding');
        //     setTimeout(() => toast.remove(), 320);
        // }
    </script>


</body>

</html>