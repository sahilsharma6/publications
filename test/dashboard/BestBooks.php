<?php


session_start();
include '../../db.php';

/* ── Auth ─────────────────────────────────────────────────────── */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'SuperAdmin'], true)) {
    header('Location: login.php');
    exit();
}

/* ══════════════════════════════════════════════════════════════
   AJAX ENDPOINT  ?ajax=1
══════════════════════════════════════════════════════════════ */
if (!empty($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    /* ── toggle ── */
    if ($action === 'toggle') {
        $book_id = (int) ($_POST['book_id'] ?? 0);
        $category_id = (int) ($_POST['category_id'] ?? 0);

        if ($book_id <= 0 || $category_id <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'Invalid IDs']);
            exit();
        }

        // Check if already best
        $chk = $conn->prepare("SELECT id FROM best_books WHERE book_id = ?");
        $chk->bind_param("i", $book_id);
        $chk->execute();
        $chk->store_result();
        $exists = $chk->num_rows > 0;
        $chk->close();

        if ($exists) {
            // Remove
            $d = $conn->prepare("DELETE FROM best_books WHERE book_id = ?");
            $d->bind_param("i", $book_id);
            $ok = $d->execute();
            $d->close();
            echo json_encode(['ok' => $ok, 'state' => 'removed']);
        } else {
            // Get next sort order for this category
            $so = $conn->prepare("SELECT COALESCE(MAX(sort_order),0)+1 FROM best_books WHERE category_id = ?");
            $so->bind_param("i", $category_id);
            $so->execute();
            $so->bind_result($nextOrder);
            $so->fetch();
            $so->close();

            $ins = $conn->prepare("INSERT INTO best_books (book_id, category_id, sort_order) VALUES (?, ?, ?)");
            $ins->bind_param("iii", $book_id, $category_id, $nextOrder);
            $ok = $ins->execute();
            $ins->close();
            echo json_encode(['ok' => $ok, 'state' => 'added']);
        }
        $conn->close();
        exit();
    }

    /* ── reorder ── */
    if ($action === 'reorder') {
        $order = json_decode($_POST['order'] ?? '[]', true);
        if (!is_array($order)) {
            echo json_encode(['ok' => false]);
            exit();
        }
        $ok = true;
        foreach ($order as $i => $book_id) {
            $bid = (int) $book_id;
            $pos = (int) $i;
            $u = $conn->prepare("UPDATE best_books SET sort_order = ? WHERE book_id = ?");
            $u->bind_param("ii", $pos, $bid);
            if (!$u->execute())
                $ok = false;
            $u->close();
        }
        echo json_encode(['ok' => $ok]);
        $conn->close();
        exit();
    }

    /* ── remove_all ── */
    if ($action === 'remove_all') {
        $category_id = (int) ($_POST['category_id'] ?? 0);
        if ($category_id > 0) {
            $d = $conn->prepare("DELETE FROM best_books WHERE category_id = ?");
            $d->bind_param("i", $category_id);
            $ok = $d->execute();
            $d->close();
            echo json_encode(['ok' => $ok]);
        } else {
            echo json_encode(['ok' => false]);
        }
        $conn->close();
        exit();
    }

    echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
    $conn->close();
    exit();
}

/* ══════════════════════════════════════════════════════════════
   NORMAL PAGE LOAD
══════════════════════════════════════════════════════════════ */

// Fetch all categories with book count + best count
$catRes = $conn->query("
    SELECT c.id, c.name,
           COUNT(DISTINCT b.id)          AS total_books,
           COUNT(DISTINCT bb.book_id)    AS best_count
    FROM   categories c
    LEFT JOIN books_data b  ON b.category_id = c.id
    LEFT JOIN best_books bb ON bb.category_id = c.id
    GROUP BY c.id, c.name
    ORDER BY c.name ASC
");
$categories = $catRes ? $catRes->fetch_all(MYSQLI_ASSOC) : [];

// Active category
$activeCatId = (int) ($_GET['cat'] ?? ($categories[0]['id'] ?? 0));

// Books in active category with their best status + sort_order
$books = [];
if ($activeCatId > 0) {
    $bStmt = $conn->prepare("
        SELECT b.id, b.title, b.authors, b.isbn, b.img, b.price,
               bb.id        AS best_id,
               bb.sort_order AS best_order
        FROM   books_data b
        LEFT JOIN best_books bb ON bb.book_id = b.id AND bb.category_id = ?
        WHERE  b.category_id = ?
        ORDER BY ISNULL(bb.sort_order), bb.sort_order ASC, b.title ASC
    ");
    $bStmt->bind_param("ii", $activeCatId, $activeCatId);
    $bStmt->execute();
    $br = $bStmt->get_result();
    $books = $br ? $br->fetch_all(MYSQLI_ASSOC) : [];
    $bStmt->close();
}

// Stats: total best, total books
$totalBest = (int) $conn->query("SELECT COUNT(*) FROM best_books")->fetch_row()[0];
$totalBooks = (int) $conn->query("SELECT COUNT(*) FROM books_data")->fetch_row()[0];
$totalCats = count($categories);

$activeCat = null;
foreach ($categories as $c) {
    if ((int) $c['id'] === $activeCatId) {
        $activeCat = $c;
        break;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Books — BookAdmin</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        /* ── PAGE ──────────────────────────────────────────────── */
        .page-header {
            margin-bottom: 22px;
        }

        .page-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--gray-900);
            letter-spacing: -.3px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            color: var(--gray-400);
            margin-top: 6px;
        }

        .breadcrumb a {
            color: var(--gray-400);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: color var(--t);
        }

        .breadcrumb a:hover {
            color: var(--accent);
        }

        /* ── SQL HINT BANNER ───────────────────────────────────── */
        .sql-banner {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: var(--r);
            padding: 12px 16px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .sql-banner i {
            color: #d97706;
            font-size: 17px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .sql-banner code {
            display: block;
            margin-top: 6px;
            font-size: 11.5px;
            background: rgba(0, 0, 0, .06);
            padding: 6px 10px;
            border-radius: 4px;
            font-family: "DM Mono", monospace;
            color: #78350f;
            white-space: pre;
            overflow-x: auto;
        }

        .sql-banner-dismiss {
            margin-left: auto;
            font-size: 15px;
            color: #d97706;
            cursor: pointer;
            flex-shrink: 0;
            background: none;
            border: none;
            padding: 0;
            transition: color var(--t);
        }

        .sql-banner-dismiss:hover {
            color: #92400e;
        }

        /* ── SUMMARY STRIP ─────────────────────────────────────── */
        .summary-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 22px;
        }

        .sum-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-lg);
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-sm);
            transition: transform var(--t), box-shadow var(--t);
        }

        .sum-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .sum-icon {
            width: 42px;
            height: 42px;
            border-radius: var(--r);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .si-gold {
            background: #fffbeb;
            color: #d97706;
        }

        .si-blue {
            background: var(--accent-light);
            color: var(--accent);
        }

        .si-purple {
            background: #f5f3ff;
            color: #7c3aed;
        }

        .sum-val {
            font-size: 26px;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1;
        }

        .sum-label {
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 2px;
        }

        /* ── MAIN LAYOUT ───────────────────────────────────────── */
        .main-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 18px;
            align-items: start;
        }

        @media (max-width: 860px) {
            .main-layout {
                grid-template-columns: 1fr;
            }
        }

        /* ── CATEGORY SIDEBAR ──────────────────────────────────── */
        .cat-sidebar {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
            position: sticky;
            top: 80px;
        }

        .cat-sidebar-header {
            padding: 14px 18px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .cat-sidebar-header h3 {
            font-size: 13px;
            font-weight: 700;
            color: var(--gray-700);
        }

        .cat-total-chip {
            font-size: 11px;
            font-weight: 600;
            background: var(--gray-100);
            color: var(--gray-500);
            padding: 2px 8px;
            border-radius: 99px;
        }

        .cat-search-wrap {
            padding: 10px 12px;
            border-bottom: 1px solid var(--gray-100);
        }

        .cat-search {
            width: 100%;
            padding: 7px 10px 7px 32px;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r);
            font-size: 12.5px;
            font-family: inherit;
            color: var(--gray-700);
            background: var(--gray-50);
            outline: none;
            transition: all var(--t);
        }

        .cat-search:focus {
            border-color: var(--accent);
            background: #fff;
        }

        .cat-search-wrap {
            position: relative;
        }

        .cat-search-wrap i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            color: var(--gray-400);
            pointer-events: none;
        }

        .cat-list {
            max-height: 520px;
            overflow-y: auto;
        }

        .cat-list::-webkit-scrollbar {
            width: 4px;
        }

        .cat-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .cat-list::-webkit-scrollbar-thumb {
            background: var(--gray-200);
            border-radius: 99px;
        }

        .cat-item {
            display: flex;
            align-items: center;
            padding: 11px 16px;
            cursor: pointer;
            border-bottom: 1px solid var(--gray-100);
            text-decoration: none;
            transition: background var(--t);
            position: relative;
        }

        .cat-item:last-child {
            border-bottom: none;
        }

        .cat-item:hover {
            background: var(--gray-50);
        }

        .cat-item.active {
            background: var(--accent-light);
            border-left: 3px solid var(--accent);
        }

        .cat-item.active .cat-item-name {
            color: var(--accent);
            font-weight: 700;
        }

        .cat-item-icon {
            width: 30px;
            height: 30px;
            border-radius: var(--r-sm);
            background: var(--gray-100);
            color: var(--gray-500);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            margin-right: 10px;
            transition: all var(--t);
        }

        .cat-item.active .cat-item-icon {
            background: rgba(59, 130, 246, .15);
            color: var(--accent);
        }

        .cat-item-body {
            flex: 1;
            min-width: 0;
        }

        .cat-item-name {
            font-size: 13px;
            color: var(--gray-700);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cat-item-sub {
            font-size: 11px;
            color: var(--gray-400);
            margin-top: 1px;
        }

        .cat-badges {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 3px;
            flex-shrink: 0;
            margin-left: 6px;
        }

        .badge-total {
            font-size: 10.5px;
            font-weight: 600;
            padding: 1px 7px;
            background: var(--gray-100);
            color: var(--gray-500);
            border-radius: 99px;
            font-family: "DM Mono", monospace;
        }

        .badge-best {
            font-size: 10.5px;
            font-weight: 600;
            padding: 1px 7px;
            background: #fffbeb;
            color: #d97706;
            border-radius: 99px;
            font-family: "DM Mono", monospace;
            display: none;
        }

        .badge-best.has-items {
            display: block;
        }

        /* ── BOOKS PANEL ───────────────────────────────────────── */
        .books-panel {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .panel-topbar {
            padding: 14px 20px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .panel-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .panel-sub {
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 1px;
        }

        .panel-topbar-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Search in panel */
        .book-search-wrap {
            position: relative;
        }

        .book-search-wrap i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 15px;
            color: var(--gray-400);
            pointer-events: none;
        }

        .book-search {
            padding: 8px 12px 8px 32px;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r);
            font-size: 13px;
            font-family: inherit;
            color: var(--gray-700);
            background: var(--gray-50);
            outline: none;
            width: 200px;
            transition: all var(--t);
        }

        .book-search:focus {
            border-color: var(--accent);
            background: #fff;
            width: 240px;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .1);
        }

        /* Filter tabs */
        .filter-tabs {
            display: flex;
            gap: 4px;
        }

        .ftab {
            padding: 6px 12px;
            border-radius: var(--r-sm);
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            border: 1.5px solid var(--gray-200);
            background: var(--gray-50);
            color: var(--gray-500);
            transition: all var(--t);
            font-family: inherit;
        }

        .ftab:hover {
            border-color: var(--gray-300);
            color: var(--gray-700);
        }

        .ftab.active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        /* Clear all button */
        .btn-clear-all {
            padding: 7px 14px;
            border-radius: var(--r-sm);
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            border: 1.5px solid rgba(239, 68, 68, .3);
            background: var(--danger-bg);
            color: var(--danger);
            font-family: inherit;
            transition: all var(--t);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-clear-all:hover {
            background: var(--danger);
            color: #fff;
            border-color: var(--danger);
        }

        /* Counter bar */
        .counter-bar {
            padding: 10px 20px;
            background: linear-gradient(135deg, #fffbeb, #fef9ee);
            border-bottom: 1px solid #fde68a;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
        }

        .counter-bar-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .counter-bar-left i {
            color: #d97706;
        }

        .counter-bar b {
            color: #92400e;
        }

        .counter-bar.empty {
            display: none;
        }

        .progress-wrap {
            flex: 1;
            max-width: 200px;
            height: 5px;
            background: #fde68a;
            border-radius: 99px;
            overflow: hidden;
            margin-left: 12px;
        }

        .progress-bar {
            height: 100%;
            background: #d97706;
            border-radius: 99px;
            transition: width .4s var(--t);
        }

        /* ── BOOKS GRID ────────────────────────────────────────── */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 14px;
            padding: 20px;
        }

        .book-card {
            border: 2px solid var(--gray-200);
            border-radius: var(--r-lg);
            overflow: hidden;
            cursor: pointer;
            transition: all .22s var(--t);
            position: relative;
            background: #fff;
            user-select: none;
        }

        .book-card:hover {
            border-color: var(--accent);
            box-shadow: 0 6px 22px rgba(59, 130, 246, .14);
            transform: translateY(-3px);
        }

        .book-card.is-best {
            border-color: #d97706;
            background: #fffbf2;
            box-shadow: 0 4px 18px rgba(217, 119, 6, .15);
        }

        .book-card.is-best:hover {
            border-color: #b45309;
            box-shadow: 0 8px 28px rgba(217, 119, 6, .25);
        }

        .book-card.loading {
            opacity: .6;
            pointer-events: none;
        }

        .book-card.hidden-filter {
            display: none;
        }

        /* Cover */
        .bc-cover {
            aspect-ratio: 3/4;
            overflow: hidden;
            background: var(--gray-100);
            position: relative;
        }

        .bc-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .4s var(--t);
        }

        .book-card:hover .bc-cover img {
            transform: scale(1.05);
        }

        .bc-cover-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: var(--gray-300);
            background: linear-gradient(135deg, var(--gray-100), var(--gray-50));
        }

        /* Star badge */
        .bc-star {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .92);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            color: var(--gray-300);
            box-shadow: 0 2px 8px rgba(0, 0, 0, .12);
            transition: all .2s var(--t);
            border: 1.5px solid rgba(255, 255, 255, .8);
        }

        .book-card.is-best .bc-star {
            color: #d97706;
            background: #fffbeb;
            border-color: #fde68a;
        }

        .book-card:hover .bc-star {
            transform: scale(1.1);
        }

        /* Rank badge (for best books) */
        .bc-rank {
            position: absolute;
            top: 8px;
            left: 8px;
            min-width: 26px;
            height: 22px;
            border-radius: 99px;
            background: #d97706;
            color: #fff;
            font-size: 10.5px;
            font-weight: 700;
            padding: 0 7px;
            display: none;
            align-items: center;
            justify-content: center;
            font-family: "DM Mono", monospace;
            box-shadow: 0 2px 8px rgba(217, 119, 6, .4);
        }

        .book-card.is-best .bc-rank {
            display: flex;
        }

        /* Card body */
        .bc-body {
            padding: 10px 12px 12px;
        }

        .bc-title {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--gray-800);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.35;
            margin-bottom: 5px;
        }

        .book-card.is-best .bc-title {
            color: #92400e;
        }

        .bc-isbn {
            font-size: 10.5px;
            color: var(--gray-400);
            font-family: "DM Mono", monospace;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Hover overlay */
        .bc-overlay {
            position: absolute;
            inset: 0;
            background: rgba(59, 130, 246, .06);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity var(--t);
            pointer-events: none;
        }

        .book-card:hover .bc-overlay {
            opacity: 1;
        }

        .bc-overlay-txt {
            background: var(--accent);
            color: #fff;
            font-size: 11.5px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: var(--r-sm);
            pointer-events: none;
            box-shadow: 0 4px 12px rgba(59, 130, 246, .4);
        }

        .book-card.is-best .bc-overlay {
            background: rgba(217, 119, 6, .06);
        }

        .book-card.is-best .bc-overlay-txt {
            background: var(--danger);
            box-shadow: none;
        }

        /* Loading spinner on card */
        .bc-spinner {
            position: absolute;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .6);
            backdrop-filter: blur(2px);
        }

        .book-card.loading .bc-spinner {
            display: flex;
        }

        .spinner-ring {
            width: 24px;
            height: 24px;
            border: 3px solid var(--gray-200);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin .65s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ── EMPTY STATE ───────────────────────────────────────── */
        .empty-state {
            text-align: center;
            padding: 52px 24px;
        }

        .empty-icon {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            background: var(--gray-100);
            color: var(--gray-300);
            font-size: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
        }

        .empty-state h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 6px;
        }

        .empty-state p {
            font-size: 13px;
            color: var(--gray-400);
        }

        /* ── TOAST ─────────────────────────────────────────────── */
        /* (uses dashboard.css .toast) */

        /* ── BEST BOOKS REORDER SECTION ────────────────────────── */
        .reorder-panel {
            border-top: 1px solid var(--gray-100);
            padding: 16px 20px;
        }

        .reorder-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .reorder-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .reorder-title i {
            color: #d97706;
        }

        .reorder-hint {
            font-size: 12px;
            color: var(--gray-400);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .reorder-hint i {
            font-size: 13px;
        }

        .reorder-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .reorder-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--r);
            cursor: grab;
            transition: all var(--t);
        }

        .reorder-item:active {
            cursor: grabbing;
        }

        .reorder-item.drag-over {
            background: var(--accent-light);
            border-color: var(--accent);
        }

        .reorder-item.dragging {
            opacity: .4;
        }

        .ri-num {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #d97706;
            color: #fff;
            font-size: 10.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-family: "DM Mono", monospace;
        }

        .ri-thumb {
            width: 30px;
            height: 40px;
            object-fit: cover;
            border-radius: 3px;
            flex-shrink: 0;
            border: 1px solid var(--gray-200);
            background: var(--gray-100);
        }

        .ri-title {
            flex: 1;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--gray-800);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ri-handle {
            color: var(--gray-300);
            font-size: 16px;
            cursor: grab;
        }

        .ri-handle:active {
            cursor: grabbing;
        }

        .btn-save-order {
            margin-top: 12px;
            width: 100%;
            padding: 9px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: var(--r);
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all var(--t);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }

        .btn-save-order:hover {
            background: var(--accent-dark);
        }

        /* ── RESPONSIVE ────────────────────────────────────────── */
        @media (max-width: 600px) {
            .books-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .summary-strip {
                grid-template-columns: 1fr 1fr;
            }

            .summary-strip .sum-card:last-child {
                display: none;
            }
        }
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <!-- Toast container -->
    <div id="toast" class="toast success" style="display:none">
        <i class='bx bx-check-circle' id="toastIcon"></i>
        <span id="toastMsg">Saved</span>
    </div>

    <!-- ── Page header ──────────────────────────────────────────── -->
    <div class="page-header">
        <nav class="breadcrumb">
            <a href="<?= $root_url ?>"><i class='bx bx-home-alt'></i> Dashboard</a>
            <i class='bx bx-chevron-right'></i>
            <span>Best Books</span>
        </nav>
        <h1>Best Books Manager</h1>
    </div>


    <!-- ── Summary strip ────────────────────────────────────────── -->
    <div class="summary-strip">
        <div class="sum-card">
            <div class="sum-icon si-gold"><i class='bx bx-star'></i></div>
            <div>
                <div class="sum-val" id="totalBestStat">
                    <?= number_format($totalBest) ?>
                </div>
                <div class="sum-label">Total Best Books</div>
            </div>
        </div>
        <div class="sum-card">
            <div class="sum-icon si-blue"><i class='bx bx-book-alt'></i></div>
            <div>
                <div class="sum-val">
                    <?= number_format($totalBooks) ?>
                </div>
                <div class="sum-label">Books in Library</div>
            </div>
        </div>
        <div class="sum-card">
            <div class="sum-icon si-purple"><i class='bx bx-collection'></i></div>
            <div>
                <div class="sum-val">
                    <?= number_format($totalCats) ?>
                </div>
                <div class="sum-label">Categories</div>
            </div>
        </div>
    </div>

    <!-- ── Main layout ──────────────────────────────────────────── -->
    <div class="main-layout">

        <!-- ── Category sidebar ─────────────────────────────── -->
        <div class="cat-sidebar">
            <div class="cat-sidebar-header">
                <h3>Categories</h3>
                <span class="cat-total-chip">
                    <?= count($categories) ?>
                </span>
            </div>
            <div class="cat-search-wrap">
                <i class='bx bx-search'></i>
                <input type="text" class="cat-search" id="catSearch" placeholder="Find category…" autocomplete="off">
            </div>
            <div class="cat-list" id="catList">
                <?php foreach ($categories as $cat): ?>
                    <a href="BestBooks.php?cat=<?= (int) $cat['id'] ?>"
                        class="cat-item <?= (int) $cat['id'] === $activeCatId ? 'active' : '' ?>"
                        data-name="<?= htmlspecialchars(strtolower($cat['name']), ENT_QUOTES) ?>"
                        data-id="<?= (int) $cat['id'] ?>">
                        <div class="cat-item-icon"><i class='bx bx-collection'></i></div>
                        <div class="cat-item-body">
                            <div class="cat-item-name">
                                <?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>
                            </div>
                            <div class="cat-item-sub">
                                <?= (int) $cat['total_books'] ?> books
                            </div>
                        </div>
                        <div class="cat-badges">
                            <span class="badge-total">
                                <?= (int) $cat['total_books'] ?>
                            </span>
                            <span class="badge-best <?= (int) $cat['best_count'] > 0 ? 'has-items' : '' ?>"
                                id="badge-<?= (int) $cat['id'] ?>">
                                ★
                                <?= (int) $cat['best_count'] ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ── Books panel ──────────────────────────────────── -->
        <div class="books-panel">

            <!-- Top bar -->
            <div class="panel-topbar">
                <div>
                    <div class="panel-title">
                        <?= $activeCat ? htmlspecialchars($activeCat['name'], ENT_QUOTES) : 'Select a Category' ?>
                    </div>
                    <div class="panel-sub" id="panelSub">
                        <?php if ($activeCat): ?>
                            <?= count($books) ?> books —
                            <span id="bestCountLabel">
                                <?= (int) ($activeCat['best_count'] ?? 0) ?>
                            </span>
                            marked as best
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($books)): ?>
                    <div class="panel-topbar-right">
                        <div class="filter-tabs">
                            <button class="ftab active" data-filter="all" onclick="setFilter('all', this)">All</button>
                            <button class="ftab" data-filter="best" onclick="setFilter('best', this)">★ Best</button>
                            <button class="ftab" data-filter="not-best" onclick="setFilter('not-best', this)">Not
                                Best</button>
                        </div>
                        <div class="book-search-wrap">
                            <i class='bx bx-search'></i>
                            <input type="text" class="book-search" id="bookSearch" placeholder="Search books…"
                                autocomplete="off">
                        </div>
                        <button class="btn-clear-all" onclick="clearAllBest()"
                            title="Remove all best books in this category">
                            <i class='bx bx-x-circle'></i> Clear All
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Counter bar -->
            <div class="counter-bar <?= ((int) ($activeCat['best_count'] ?? 0)) === 0 ? 'empty' : '' ?>"
                id="counterBar">
                <div class="counter-bar-left">
                    <i class='bx bx-star'></i>
                    <span><b id="counterNum">
                            <?= (int) ($activeCat['best_count'] ?? 0) ?>
                        </b> books marked as best in this category</span>
                    <div class="progress-wrap">
                        <div class="progress-bar" id="progressBar"
                            style="width: <?= !empty($books) ? round(((int) ($activeCat['best_count'] ?? 0) / count($books)) * 100) : 0 ?>%">
                        </div>
                    </div>
                </div>
                <span style="font-size:12px;color:var(--gray-400)">
                    <?= count($books) ?> total
                </span>
            </div>

            <?php if (empty($books)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class='bx bx-book-open'></i></div>
                    <h3>
                        <?= $activeCat ? 'No Books in This Category' : 'Select a Category' ?>
                    </h3>
                    <p>
                        <?= $activeCat ? 'Add books to this category first.' : 'Pick a category from the left panel.' ?>
                    </p>
                </div>
            <?php else: ?>

                <!-- Books grid -->
                <div class="books-grid" id="booksGrid">
                    <?php
                    $bestCount = 0;
                    foreach ($books as $b) {
                        if (!empty($b['best_id']))
                            $bestCount++;
                    }
                    $rank = 1;
                    foreach ($books as $b):
                        $isBest = !empty($b['best_id']);
                        $imgPath = "../../uploads/" . ($b['img'] ?? '');
                        $hasImg = !empty($b['img']) && file_exists($imgPath);
                        ?>
                        <div class="book-card <?= $isBest ? 'is-best' : '' ?>" id="card-<?= (int) $b['id'] ?>"
                            data-book-id="<?= (int) $b['id'] ?>" data-cat-id="<?= $activeCatId ?>"
                            data-title="<?= htmlspecialchars(strtolower($b['title']), ENT_QUOTES) ?>"
                            data-best="<?= $isBest ? '1' : '0' ?>" onclick="toggleBest(this)"
                            title="<?= htmlspecialchars($b['title'], ENT_QUOTES) ?>">

                            <div class="bc-cover">
                                <?php if ($hasImg): ?>
                                    <img src="<?= htmlspecialchars($imgPath, ENT_QUOTES) ?>"
                                        alt="<?= htmlspecialchars($b['title'], ENT_QUOTES) ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="bc-cover-placeholder"><i class='bx bx-book'></i></div>
                                <?php endif; ?>

                                <div class="bc-rank">
                                    <?= $isBest ? $rank++ : '' ?>
                                </div>
                                <div class="bc-star"><i class='bx <?= $isBest ? 'bxs-star' : 'bx-star' ?>'></i></div>
                            </div>

                            <div class="bc-body">
                                <div class="bc-title">
                                    <?= htmlspecialchars($b['title'], ENT_QUOTES) ?>
                                </div>
                                <?php if (!empty($b['isbn'])): ?>
                                    <div class="bc-isbn">
                                        <?= htmlspecialchars($b['isbn'], ENT_QUOTES) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="bc-overlay">
                                <div class="bc-overlay-txt" id="overlay-<?= (int) $b['id'] ?>">
                                    <?= $isBest ? '✕ Remove' : '★ Add to Best' ?>
                                </div>
                            </div>

                            <div class="bc-spinner">
                                <div class="spinner-ring"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Reorder panel (only shows if there are best books) -->
                <div class="reorder-panel" id="reorderPanel" style="<?= $bestCount === 0 ? 'display:none' : '' ?>">
                    <div class="reorder-header">
                        <div class="reorder-title">
                            <i class='bx bxs-star'></i> Drag to Reorder Best Books
                        </div>
                        <div class="reorder-hint">
                            <i class='bx bx-sort'></i> Drag rows to set display order
                        </div>
                    </div>
                    <div class="reorder-list" id="reorderList">
                        <?php
                        $bestBooks = array_filter($books, fn($b) => !empty($b['best_id']));
                        $rank = 1;
                        foreach ($bestBooks as $b):
                            $imgPath = "../../uploads/" . ($b['img'] ?? '');
                            $hasImg = !empty($b['img']) && file_exists($imgPath);
                            ?>
                            <div class="reorder-item" draggable="true" data-book-id="<?= (int) $b['id'] ?>"
                                id="ri-<?= (int) $b['id'] ?>">
                                <span class="ri-num" id="ri-num-<?= (int) $b['id'] ?>">
                                    <?= $rank++ ?>
                                </span>
                                <?php if ($hasImg): ?>
                                    <img src="<?= htmlspecialchars($imgPath, ENT_QUOTES) ?>" class="ri-thumb" alt="" loading="lazy">
                                <?php else: ?>
                                    <div class="ri-thumb"
                                        style="display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--gray-300)">
                                        <i class='bx bx-book'></i>
                                    </div>
                                <?php endif; ?>
                                <span class="ri-title">
                                    <?= htmlspecialchars($b['title'], ENT_QUOTES) ?>
                                </span>
                                <i class='bx bx-menu ri-handle'></i>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn-save-order" onclick="saveOrder()">
                        <i class='bx bx-save'></i> Save Order
                    </button>
                </div>

            <?php endif; ?>

        </div><!-- /.books-panel -->

    </div><!-- /.main-layout -->

    </div><!-- /.dash-content -->
    </section><!-- /.home-section -->

    <script>
        const ACTIVE_CAT_ID = <?= $activeCatId ?>;
        const TOTAL_BOOKS = <?= count($books) ?>;
        let currentBest = <?= count(array_filter($books, fn($b) => !empty($b['best_id']))) ?>;

        /* ──────────────────────────────────────────────────────────────
           TOGGLE BEST
        ────────────────────────────────────────────────────────────── */
        function toggleBest(card) {
            const bookId = parseInt(card.dataset.bookId);
            const catId = parseInt(card.dataset.catId);

            card.classList.add('loading');

            fetch('BestBooks.php?ajax=1', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=toggle&book_id=${bookId}&category_id=${catId}`
            })
                .then(r => r.json())
                .then(data => {
                    card.classList.remove('loading');
                    if (!data.ok) { showToast('Error saving. Please try again.', 'error'); return; }

                    const adding = data.state === 'added';
                    card.dataset.best = adding ? '1' : '0';
                    card.classList.toggle('is-best', adding);

                    // Update star icon
                    card.querySelector('.bc-star i').className = adding ? 'bx bxs-star' : 'bx bx-star';

                    // Update overlay text
                    document.getElementById(`overlay-${bookId}`).textContent = adding ? '✕ Remove' : '★ Add to Best';

                    // Update counter
                    currentBest += adding ? 1 : -1;
                    updateCounter();

                    // Update category sidebar badge
                    updateCatBadge(catId, currentBest);

                    // Update global stat
                    const gStat = document.getElementById('totalBestStat');
                    gStat.textContent = parseInt(gStat.textContent.replace(/,/g, '')) + (adding ? 1 : -1);

                    // Rebuild reorder list
                    rebuildReorderList();

                    // Re-rank cards
                    rerankCards();

                    showToast(adding ? '★ Added to Best Books' : 'Removed from Best Books', adding ? 'success' : 'error');
                })
                .catch(() => {
                    card.classList.remove('loading');
                    showToast('Network error. Please try again.', 'error');
                });
        }

        /* ──────────────────────────────────────────────────────────────
           CLEAR ALL
        ────────────────────────────────────────────────────────────── */
        function clearAllBest() {
            if (currentBest === 0) { showToast('No best books to remove.', 'error'); return; }
            if (!confirm(`Remove all ${currentBest} best books from this category?`)) return;

            fetch('BestBooks.php?ajax=1', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=remove_all&category_id=${ACTIVE_CAT_ID}`
            })
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) { showToast('Error. Please try again.', 'error'); return; }

                    // Update global stat
                    const gStat = document.getElementById('totalBestStat');
                    gStat.textContent = Math.max(0, parseInt(gStat.textContent.replace(/,/g, '')) - currentBest);

                    currentBest = 0;
                    document.querySelectorAll('.book-card.is-best').forEach(card => {
                        card.classList.remove('is-best');
                        card.dataset.best = '0';
                        card.querySelector('.bc-star i').className = 'bx bx-star';
                        card.querySelector('.bc-rank').textContent = '';
                        const bid = card.dataset.bookId;
                        document.getElementById(`overlay-${bid}`).textContent = '★ Add to Best';
                    });

                    updateCounter();
                    updateCatBadge(ACTIVE_CAT_ID, 0);
                    rebuildReorderList();
                    showToast('Cleared all best books for this category.', 'success');
                });
        }

        /* ──────────────────────────────────────────────────────────────
           COUNTER + PROGRESS
        ────────────────────────────────────────────────────────────── */
        function updateCounter() {
            const bar = document.getElementById('counterBar');
            const num = document.getElementById('counterNum');
            const prog = document.getElementById('progressBar');
            const lbl = document.getElementById('bestCountLabel');

            if (num) num.textContent = currentBest;
            if (lbl) lbl.textContent = currentBest;
            if (prog) prog.style.width = TOTAL_BOOKS > 0 ? (currentBest / TOTAL_BOOKS * 100) + '%' : '0%';
            if (bar) bar.classList.toggle('empty', currentBest === 0);
        }

        function updateCatBadge(catId, count) {
            const badge = document.getElementById(`badge-${catId}`);
            if (!badge) return;
            badge.textContent = `★ ${count}`;
            badge.classList.toggle('has-items', count > 0);
        }

        /* ──────────────────────────────────────────────────────────────
           RE-RANK VISIBLE BEST CARDS
        ────────────────────────────────────────────────────────────── */
        function rerankCards() {
            let rank = 1;
            document.querySelectorAll('.book-card.is-best').forEach(card => {
                card.querySelector('.bc-rank').textContent = rank++;
            });
            document.querySelectorAll('.book-card:not(.is-best)').forEach(card => {
                card.querySelector('.bc-rank').textContent = '';
            });
        }

        /* ──────────────────────────────────────────────────────────────
           REBUILD REORDER LIST
        ────────────────────────────────────────────────────────────── */
        function rebuildReorderList() {
            const panel = document.getElementById('reorderPanel');
            const list = document.getElementById('reorderList');
            if (!panel || !list) return;

            const bestCards = [...document.querySelectorAll('.book-card.is-best')];
            panel.style.display = bestCards.length === 0 ? 'none' : '';

            list.innerHTML = '';
            bestCards.forEach((card, i) => {
                const bid = card.dataset.bookId;
                const title = card.querySelector('.bc-title')?.textContent.trim() || '';
                const imgEl = card.querySelector('.bc-cover img');
                const imgSrc = imgEl ? imgEl.src : '';

                const div = document.createElement('div');
                div.className = 'reorder-item';
                div.draggable = true;
                div.dataset.bookId = bid;
                div.id = `ri-${bid}`;
                div.innerHTML = `
            <span class="ri-num">${i + 1}</span>
            ${imgSrc
                        ? `<img src="${imgSrc}" class="ri-thumb" alt="" loading="lazy">`
                        : `<div class="ri-thumb" style="display:flex;align-items:center;justify-content:font-size:12px;color:var(--gray-300)"><i class='bx bx-book'></i></div>`
                    }
            <span class="ri-title">${title}</span>
            <i class='bx bx-menu ri-handle'></i>
        `;
                list.appendChild(div);
            });

            initDragReorder();
        }

        /* ──────────────────────────────────────────────────────────────
           DRAG-AND-DROP REORDER
        ────────────────────────────────────────────────────────────── */
        let dragSrc = null;

        function initDragReorder() {
            document.querySelectorAll('.reorder-item').forEach(item => {
                item.addEventListener('dragstart', e => {
                    dragSrc = item;
                    item.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                });
                item.addEventListener('dragend', () => {
                    item.classList.remove('dragging');
                    document.querySelectorAll('.reorder-item').forEach(i => i.classList.remove('drag-over'));
                    renumberReorderItems();
                });
                item.addEventListener('dragover', e => {
                    e.preventDefault();
                    if (dragSrc && dragSrc !== item) {
                        document.querySelectorAll('.reorder-item').forEach(i => i.classList.remove('drag-over'));
                        item.classList.add('drag-over');
                    }
                });
                item.addEventListener('drop', e => {
                    e.preventDefault();
                    if (dragSrc && dragSrc !== item) {
                        const list = item.parentNode;
                        const items = [...list.querySelectorAll('.reorder-item')];
                        const srcIdx = items.indexOf(dragSrc);
                        const destIdx = items.indexOf(item);
                        if (srcIdx < destIdx) {
                            list.insertBefore(dragSrc, item.nextSibling);
                        } else {
                            list.insertBefore(dragSrc, item);
                        }
                    }
                });
            });
        }

        function renumberReorderItems() {
            document.querySelectorAll('.reorder-item').forEach((item, i) => {
                const numEl = item.querySelector('.ri-num');
                if (numEl) numEl.textContent = i + 1;
            });
            rerankCards();
        }

        initDragReorder();

        /* ──────────────────────────────────────────────────────────────
           SAVE ORDER
        ────────────────────────────────────────────────────────────── */
        function saveOrder() {
            const items = [...document.querySelectorAll('#reorderList .reorder-item')];
            const order = items.map(i => i.dataset.bookId);

            fetch('BestBooks.php?ajax=1', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=reorder&order=${encodeURIComponent(JSON.stringify(order))}`
            })
                .then(r => r.json())
                .then(data => {
                    showToast(data.ok ? 'Order saved!' : 'Failed to save order.', data.ok ? 'success' : 'error');
                });
        }

        /* ──────────────────────────────────────────────────────────────
           FILTER
        ────────────────────────────────────────────────────────────── */
        let currentFilter = 'all';
        let currentSearch = '';

        function setFilter(filter, btn) {
            currentFilter = filter;
            document.querySelectorAll('.ftab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            applyFilters();
        }

        document.getElementById('bookSearch')?.addEventListener('input', function () {
            currentSearch = this.value.trim().toLowerCase();
            applyFilters();
        });

        function applyFilters() {
            document.querySelectorAll('.book-card').forEach(card => {
                const title = card.dataset.title || '';
                const isBest = card.dataset.best === '1';
                const matchSearch = !currentSearch || title.includes(currentSearch);
                const matchFilter = currentFilter === 'all'
                    || (currentFilter === 'best' && isBest)
                    || (currentFilter === 'not-best' && !isBest);
                card.classList.toggle('hidden-filter', !(matchSearch && matchFilter));
            });
        }

        /* ──────────────────────────────────────────────────────────────
           CATEGORY SEARCH
        ────────────────────────────────────────────────────────────── */
        document.getElementById('catSearch')?.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('.cat-item').forEach(item => {
                item.style.display = !q || item.dataset.name.includes(q) ? '' : 'none';
            });
        });

        /* ──────────────────────────────────────────────────────────────
           TOAST
        ────────────────────────────────────────────────────────────── */
        let toastTimer;
        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            const ico = document.getElementById('toastIcon');
            const txt = document.getElementById('toastMsg');
            if (!t) return;

            t.className = `toast ${type}`;
            ico.className = `bx ${type === 'success' ? 'bx-check-circle' : 'bx-x-circle'}`;
            txt.textContent = msg;
            t.style.display = 'flex';

            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => {
                t.classList.add('hiding');
                setTimeout(() => { t.style.display = 'none'; t.classList.remove('hiding'); }, 320);
            }, 3500);
        }
    </script>
</body>

</html>