<?php
session_start();
include '../../db.php';

/* ── Auth Guard ──────────────────────────────────────────────────────────── */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'SuperAdmin'], true)) {
    header("Location: login.php");
    exit();
}

$toast = null;

/* ── Handle DELETE ───────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = (int) ($_POST['delete_id'] ?? 0);
    if ($delId > 0) {
        $sel = $conn->prepare("SELECT image_path FROM book_images WHERE id = ?");
        $sel->bind_param("i", $delId);
        $sel->execute();
        $sel->bind_result($imgPath);
        $sel->fetch();
        $sel->close();
        if ($imgPath && file_exists($imgPath))
            @unlink($imgPath);
        $del = $conn->prepare("DELETE FROM book_images WHERE id = ?");
        $del->bind_param("i", $delId);
        $del->execute();
        $del->close();
        header("Location: manage_book_images.php?toast=deleted");
        exit();
    }
}

/* ── Toast from redirect ─────────────────────────────────────────────────── */
if (isset($_GET['toast'])) {
    $map = [
        'deleted' => ['type' => 'success', 'msg' => 'Image deleted successfully.'],
        'updated' => ['type' => 'success', 'msg' => 'Image updated successfully.'],
        'uploaded' => ['type' => 'success', 'msg' => ($_GET['count'] ?? 1) . ' image(s) uploaded successfully.'],
    ];
    $toast = $map[$_GET['toast']] ?? null;
}

/* ── Filters ─────────────────────────────────────────────────────────────── */
$search = trim($_GET['search'] ?? '');
$catFilter = (int) ($_GET['cat'] ?? 0);
$perPage = (int) ($_GET['per_page'] ?? 20);
$perPage = in_array($perPage, [12, 20, 40, 60], true) ? $perPage : 20;
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = "WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $where .= " AND (bd.title LIKE ? OR c.name LIKE ?)";
    $sp = '%' . $search . '%';
    $params[] = $sp;
    $params[] = $sp;
    $types .= "ss";
}
if ($catFilter > 0) {
    $where .= " AND bi.category_id = ?";
    $params[] = $catFilter;
    $types .= "i";
}

/* ── Count ───────────────────────────────────────────────────────────────── */
$countSql = "SELECT COUNT(*) FROM book_images bi JOIN books_data bd ON bi.book_id=bd.id JOIN categories c ON bi.category_id=c.id $where";
$countStmt = $conn->prepare($countSql);
if ($types)
    $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$countStmt->bind_result($totalRows);
$countStmt->fetch();
$countStmt->close();

$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

/* ── Fetch ───────────────────────────────────────────────────────────────── */
$fetchSql = "SELECT bi.id, bi.image_path, bd.title AS book_title, c.name AS cat_name
             FROM book_images bi
             JOIN books_data bd ON bi.book_id = bd.id
             JOIN categories c  ON bi.category_id = c.id
             $where
             ORDER BY bi.id DESC
             LIMIT ? OFFSET ?";

$fTypes = $types . "ii";
$fParams = array_merge($params, [$perPage, $offset]);
$fStmt = $conn->prepare($fetchSql);
$fStmt->bind_param($fTypes, ...$fParams);
$fStmt->execute();
$images = $fStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$fStmt->close();

/* ── Categories for filter ───────────────────────────────────────────────── */
$catRes = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$allCats = $catRes ? $catRes->fetch_all(MYSQLI_ASSOC) : [];

/* ── Stats ───────────────────────────────────────────────────────────────── */
$totalAll = (int) $conn->query("SELECT COUNT(*) FROM book_images")->fetch_row()[0];

function pgUrl(array $ov = []): string
{
    $p = array_merge(['page' => $_GET['page'] ?? 1, 'search' => $_GET['search'] ?? '', 'cat' => $_GET['cat'] ?? 0, 'per_page' => $_GET['per_page'] ?? 20], $ov);
    return 'manage_book_images.php?' . http_build_query($p);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Book Images — BookAdmin</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --accent: #3b82f6;
            --accent-light: #eff6ff;
            --accent-dark: #1d4ed8;
            --success: #22c55e;
            --success-light: #f0fdf4;
            --danger: #ef4444;
            --danger-light: #fef2f2;
            --danger-bg: #fef2f2;
            --warning: #f59e0b;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --r-sm: 8px;
            --r: 12px;
            --r-lg: 16px;
            --r-xl: 20px;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, .06);
            --shadow: 0 4px 16px rgba(0, 0, 0, .07);
            --shadow-lg: 0 10px 40px rgba(0, 0, 0, .12);
            --t: 0.2s cubic-bezier(.4, 0, .2, 1);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "DM Sans", sans-serif;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(14px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateX(60px)
            }

            to {
                opacity: 1;
                transform: translateX(0)
            }
        }

        @keyframes toastOut {
            from {
                opacity: 1;
                transform: translateX(0)
            }

            to {
                opacity: 0;
                transform: translateX(60px)
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(.93)
            }

            to {
                opacity: 1;
                transform: scale(1)
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg)
            }
        }

        .dash-content>* {
            animation: fadeUp .4s var(--t) both;
        }

        .dash-content>*:nth-child(1) {
            animation-delay: .05s
        }

        .dash-content>*:nth-child(2) {
            animation-delay: .12s
        }

        .dash-content>*:nth-child(3) {
            animation-delay: .18s
        }

        /* Toast */
        .toast {
            position: fixed;
            top: 22px;
            right: 22px;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 18px;
            border-radius: var(--r-lg);
            font-size: 13.5px;
            font-weight: 500;
            min-width: 260px;
            max-width: 380px;
            box-shadow: var(--shadow-lg);
            animation: toastIn .35s var(--t) both;
        }

        .toast.success {
            background: var(--success-light);
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .toast.error {
            background: var(--danger-light);
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .toast i {
            font-size: 20px;
            flex-shrink: 0;
        }

        .toast-close {
            margin-left: auto;
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            color: inherit;
            opacity: .6;
            padding: 0;
            transition: opacity var(--t);
        }

        .toast.hiding {
            animation: toastOut .3s var(--t) forwards;
        }

        /* Page header */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 26px;
            flex-wrap: wrap;
        }

        .page-header-left h1 {
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

        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            background: var(--accent);
            border-radius: var(--r);
            font-size: 13.5px;
            font-weight: 600;
            color: #fff;
            text-decoration: none;
            transition: all var(--t);
            box-shadow: 0 2px 10px rgba(59, 130, 246, .25);
        }

        .btn-add:hover {
            background: var(--accent-dark);
            transform: translateY(-1px);
        }

        /* Summary strip */
        .summary-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        @media(max-width:640px) {
            .summary-strip {
                grid-template-columns: 1fr 1fr;
            }
        }

        .summary-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-lg);
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: var(--shadow-sm);
            transition: transform var(--t), box-shadow var(--t);
        }

        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .summary-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--r);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .si-blue {
            background: #eff6ff;
            color: #3b82f6;
        }

        .si-indigo {
            background: #eef2ff;
            color: #6366f1;
        }

        .si-purple {
            background: #f5f3ff;
            color: #8b5cf6;
        }

        .summary-val {
            font-size: 22px;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1.1;
        }

        .summary-label {
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 2px;
        }

        /* Main card */
        .main-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-100);
            flex-wrap: wrap;
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-box i {
            position: absolute;
            left: 12px;
            color: var(--gray-400);
            font-size: 17px;
            pointer-events: none;
        }

        .search-input {
            padding: 8px 14px 8px 38px;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r);
            font-size: 13.5px;
            font-family: inherit;
            background: var(--gray-50);
            color: var(--gray-800);
            outline: none;
            transition: all var(--t);
            width: 220px;
        }

        .search-input:focus {
            background: #fff;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .1);
        }

        .cat-filter {
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
            max-width: 150px;
        }

        .cat-filter:focus {
            border-color: var(--accent);
            background-color: #fff;
        }

        .per-page-sel {
            height: 38px;
            padding: 0 28px 0 10px;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r);
            font-size: 13px;
            font-family: inherit;
            color: var(--gray-600);
            background: var(--gray-50);
            outline: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24'%3E%3Cpath fill='%239ca3af' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 6px center;
            background-color: var(--gray-50);
        }

        .result-label {
            font-size: 13px;
            color: var(--gray-500);
        }

        .result-label strong {
            color: var(--gray-800);
        }

        /* Images grid */
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            padding: 20px;
        }

        /* Image card */
        .img-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-lg);
            overflow: hidden;
            transition: transform var(--t), box-shadow var(--t);
            display: flex;
            flex-direction: column;
        }

        .img-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .img-card-thumb {
            height: 170px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            cursor: pointer;
        }

        .img-card-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .4s var(--t);
        }

        .img-card:hover .img-card-thumb img {
            transform: scale(1.05);
        }

        .img-zoom-overlay {
            position: absolute;
            inset: 0;
            background: rgba(59, 130, 246, 0);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all var(--t);
        }

        .img-card:hover .img-zoom-overlay {
            background: rgba(59, 130, 246, .22);
            opacity: 1;
        }

        .img-zoom-overlay i {
            font-size: 28px;
            color: #fff;
            filter: drop-shadow(0 2px 6px rgba(0, 0, 0, .3));
        }

        .img-id-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background: rgba(15, 23, 42, .65);
            backdrop-filter: blur(6px);
            color: rgba(255, 255, 255, .85);
            font-size: 10.5px;
            font-family: "DM Mono", monospace;
            padding: 3px 7px;
            border-radius: 4px;
        }

        .img-no-image {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            color: var(--gray-300);
        }

        .img-no-image i {
            font-size: 36px;
        }

        .img-card-body {
            padding: 12px 14px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .img-book-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--gray-800);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.35;
        }

        .img-cat-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 99px;
            background: var(--accent-light);
            color: var(--accent-dark);
            font-size: 11px;
            font-weight: 600;
            width: fit-content;
        }

        .img-card-actions {
            display: flex;
            border-top: 1px solid var(--gray-100);
        }

        .img-act-btn {
            flex: 1;
            padding: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            font-size: 12.5px;
            font-weight: 600;
            font-family: inherit;
            border: none;
            background: none;
            cursor: pointer;
            transition: background var(--t), color var(--t);
            text-decoration: none;
        }

        .img-act-btn.edit {
            color: var(--accent);
            border-right: 1px solid var(--gray-100);
        }

        .img-act-btn.delete {
            color: var(--danger);
        }

        .img-act-btn.edit:hover {
            background: var(--accent-light);
        }

        .img-act-btn.delete:hover {
            background: var(--danger-light);
        }

        .img-act-btn i {
            font-size: 15px;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 64px 24px;
            color: var(--gray-400);
        }

        .empty-state-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 32px;
        }

        .empty-state h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--gray-700);
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 13.5px;
            margin-bottom: 20px;
        }

        .btn-empty {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            background: var(--accent);
            color: #fff;
            border-radius: var(--r);
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            transition: all var(--t);
        }

        .btn-empty:hover {
            background: var(--accent-dark);
        }

        /* Filter chips */
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
            gap: 5px;
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

        /* Pagination */
        .pagination-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 20px;
            border-top: 1px solid var(--gray-100);
            flex-wrap: wrap;
        }

        .pag-info {
            font-size: 13px;
            color: var(--gray-500);
        }

        .pag-info strong {
            color: var(--gray-800);
        }

        .pag-list {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .pag-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: var(--r-sm);
            border: 1.5px solid var(--gray-200);
            background: #fff;
            color: var(--gray-600);
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            transition: all var(--t);
        }

        .pag-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--accent-light);
        }

        .pag-btn.is-active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .pag-btn.is-disabled {
            pointer-events: none;
            opacity: .35;
        }

        .pag-ellipsis {
            padding: 0 4px;
            color: var(--gray-400);
            font-size: 13px;
        }

        /* Delete overlay */
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .55);
            z-index: 9990;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(3px);
        }

        .overlay.open {
            display: flex;
        }

        .del-modal {
            background: #fff;
            border-radius: var(--r-xl);
            padding: 32px 28px 26px;
            max-width: 380px;
            width: 92%;
            box-shadow: var(--shadow-lg);
            animation: scaleIn .25s var(--t) both;
        }

        .del-modal-icon {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: var(--danger-light);
            color: var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 18px;
            border: 4px solid #fee2e2;
        }

        .del-modal h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-900);
            text-align: center;
            margin-bottom: 8px;
        }

        .del-modal p {
            font-size: 13.5px;
            color: var(--gray-500);
            text-align: center;
            line-height: 1.6;
            margin-bottom: 26px;
        }

        .del-modal-btns {
            display: flex;
            gap: 10px;
        }

        .btn-del-cancel {
            flex: 1;
            padding: 11px;
            border: 1.5px solid var(--gray-200);
            background: var(--gray-50);
            color: var(--gray-600);
            font-size: 13.5px;
            font-weight: 600;
            font-family: inherit;
            border-radius: var(--r);
            cursor: pointer;
            transition: all var(--t);
        }

        .btn-del-cancel:hover {
            background: var(--gray-100);
        }

        .btn-del-confirm {
            flex: 1;
            padding: 11px;
            border: none;
            background: var(--danger);
            color: #fff;
            font-size: 13.5px;
            font-weight: 600;
            font-family: inherit;
            border-radius: var(--r);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all var(--t);
            box-shadow: 0 2px 10px rgba(239, 68, 68, .3);
        }

        .btn-del-confirm:hover {
            background: #dc2626;
        }

        .del-spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, .4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
            display: none;
        }

        /* Lightbox */
        .lightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .88);
            z-index: 9995;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .lightbox.open {
            display: flex;
        }

        .lightbox img {
            max-width: 90vw;
            max-height: 88vh;
            border-radius: var(--r-lg);
            box-shadow: 0 20px 60px rgba(0, 0, 0, .6);
            animation: scaleIn .2s var(--t) both;
        }

        .lightbox-close {
            position: fixed;
            top: 20px;
            right: 24px;
            background: rgba(255, 255, 255, .12);
            border: none;
            color: #fff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background var(--t);
        }

        .lightbox-close:hover {
            background: rgba(255, 255, 255, .22);
        }
    </style>
</head>

<body>

    <?php include './sidebar.php'; ?>

    <?php if ($toast): ?>
        <div class="toast <?= $toast['type'] ?>" id="toast">
            <i class='bx <?= $toast['type'] === 'success' ? 'bx-check-circle' : 'bx-x-circle' ?>'></i>
            <?= htmlspecialchars($toast['msg'], ENT_QUOTES) ?>
            <button class="toast-close" onclick="dismissToast()">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Delete overlay -->
    <div class="overlay" id="deleteOverlay">
        <div class="del-modal">
            <div class="del-modal-icon"><i class='bx bx-trash'></i></div>
            <h3>Delete Image?</h3>
            <p>This will permanently remove the image file from the server. This action <strong>cannot be
                    undone.</strong></p>
            <form method="POST" id="deleteForm" class="del-modal-btns">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="delete_id" id="deleteIdField">
                <button type="button" class="btn-del-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-del-confirm">
                    <span class="del-spinner" id="delSpinner"></span>
                    <span id="delBtnText">Delete</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Lightbox -->
    <div class="lightbox" id="lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()"><i class='bx bx-x'></i></button>
        <img id="lightboxImg" src="" alt="">
    </div>

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-left">
            <h1>Book Images</h1>
            <nav class="breadcrumb">
                <a href="dashboard.php"><i class='bx bx-home-alt'></i> Dashboard</a>
                <i class='bx bx-chevron-right'></i>
                <span>Book Images</span>
            </nav>
        </div>
        <a href="add_book_images.php" class="btn-add"><i class='bx bx-plus'></i> Upload Images</a>
    </div>

    <!-- Summary -->
    <div class="summary-strip">
        <div class="summary-card">
            <div class="summary-icon si-purple"><i class='bx bx-images'></i></div>
            <div>
                <div class="summary-val"><?= number_format($totalAll) ?></div>
                <div class="summary-label">Total Images</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon si-blue"><i class='bx bx-filter-alt'></i></div>
            <div>
                <div class="summary-val"><?= $search || $catFilter ? number_format($totalRows) : '—' ?></div>
                <div class="summary-label"><?= $search || $catFilter ? 'Matching' : 'No filter' ?></div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon si-indigo"><i class='bx bx-collection'></i></div>
            <div>
                <div class="summary-val"><?= number_format(count($allCats)) ?></div>
                <div class="summary-label">Categories</div>
            </div>
        </div>
    </div>

    <!-- Main card -->
    <div class="main-card">
        <!-- Toolbar -->
        <form method="GET" action="manage_book_images.php" id="filterForm">
            <input type="hidden" name="page" value="1">
            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search-box">
                        <i class='bx bx-search'></i>
                        <input type="text" name="search" class="search-input" id="searchInput"
                            value="<?= htmlspecialchars($search, ENT_QUOTES) ?>" placeholder="Search book or category…"
                            autocomplete="off">
                    </div>
                    <select name="cat" class="cat-filter" onchange="this.form.submit()">
                        <option value="0">All Categories</option>
                        <?php foreach ($allCats as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= $catFilter === (int) $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name'], ENT_QUOTES) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="per_page" class="per-page-sel" onchange="this.form.submit()">
                        <?php foreach ([12, 20, 40, 60] as $n): ?>
                            <option value="<?= $n ?>" <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?>/page</option>
                        <?php endforeach; ?>
                    </select>
                    <span class="result-label"><strong><?= number_format($totalRows) ?></strong>
                        result<?= $totalRows !== 1 ? 's' : '' ?></span>
                </div>
            </div>
        </form>

        <!-- Active chips -->
        <?php if ($search || $catFilter > 0): ?>
            <div class="filter-chips">
                <?php if ($search): ?>
                    <a href="<?= pgUrl(['search' => '', 'page' => 1]) ?>" class="filter-chip"><i
                            class='bx bx-search'></i>"<?= htmlspecialchars($search, ENT_QUOTES) ?>"<i class='bx bx-x'></i></a>
                <?php endif; ?>
                <?php if ($catFilter > 0):
                    $activeCat = '';
                    foreach ($allCats as $c) {
                        if ((int) $c['id'] === $catFilter) {
                            $activeCat = $c['name'];
                            break;
                        }
                    }
                    ?>
                    <a href="<?= pgUrl(['cat' => 0, 'page' => 1]) ?>" class="filter-chip"><i
                            class='bx bx-collection'></i><?= htmlspecialchars($activeCat, ENT_QUOTES) ?><i
                            class='bx bx-x'></i></a>
                <?php endif; ?>
                <a href="manage_book_images.php" class="filter-chip"
                    style="background:var(--gray-100);color:var(--gray-500)"><i class='bx bx-x-circle'></i>Clear all</a>
            </div>
        <?php endif; ?>

        <?php if (empty($images)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class='bx bx-image-alt'></i></div>
                <h3><?= $search || $catFilter ? 'No Results Found' : 'No Images Yet' ?></h3>
                <p><?= $search || $catFilter ? 'Try different search terms or clear your filters.' : 'Start by uploading images for your books.' ?>
                </p>
                <?php if ($search || $catFilter): ?>
                    <a href="manage_book_images.php" class="btn-empty"><i class='bx bx-x'></i> Clear Filters</a>
                <?php else: ?>
                    <a href="add_book_images.php" class="btn-empty"><i class='bx bx-plus'></i> Upload Images</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="images-grid">
                <?php foreach ($images as $i => $img): ?>
                    <div class="img-card" style="animation:fadeUp .3s <?= $i * .04 ?>s both">
                        <div class="img-card-thumb"
                            onclick="openLightbox('<?= htmlspecialchars($img['image_path'], ENT_QUOTES) ?>')">
                            <span class="img-id-badge">#<?= (int) $img['id'] ?></span>
                            <?php if (!empty($img['image_path']) && file_exists($img['image_path'])): ?>
                                <img src="<?= htmlspecialchars($img['image_path'], ENT_QUOTES) ?>"
                                    alt="<?= htmlspecialchars($img['book_title'], ENT_QUOTES) ?>" loading="lazy">
                                <div class="img-zoom-overlay"><i class='bx bx-zoom-in'></i></div>
                            <?php else: ?>
                                <div class="img-no-image"><i class='bx bx-image-alt'></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="img-card-body">
                            <div class="img-book-title"><?= htmlspecialchars($img['book_title'], ENT_QUOTES) ?></div>
                            <div class="img-cat-pill"><i class='bx bx-collection'
                                    style="font-size:11px"></i><?= htmlspecialchars($img['cat_name'], ENT_QUOTES) ?></div>
                        </div>
                        <div class="img-card-actions">
                            <a href="edit_image.php?id=<?= (int) $img['id'] ?>" class="img-act-btn edit"><i
                                    class='bx bx-edit-alt'></i> Edit</a>
                            <button class="img-act-btn delete" onclick="openDelete(<?= (int) $img['id'] ?>)"><i
                                    class='bx bx-trash'></i> Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1 || $totalRows > 0): ?>
            <div class="pagination-row">
                <div class="pag-info">Showing
                    <strong><?= ($page - 1) * $perPage + 1 ?></strong>–<strong><?= min($page * $perPage, $totalRows) ?></strong> of
                    <strong><?= number_format($totalRows) ?></strong></div>
                <?php if ($totalPages > 1): ?>
                    <ul class="pag-list">
                        <li><a class="pag-btn <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= pgUrl(['page' => 1]) ?>"><i
                                    class='bx bx-chevrons-left'></i></a></li>
                        <li><a class="pag-btn <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= pgUrl(['page' => $page - 1]) ?>"><i
                                    class='bx bx-chevron-left'></i></a></li>
                        <?php
                        $ws = max(1, $page - 2);
                        $we = min($totalPages, $page + 2);
                        if ($ws > 1) {
                            echo '<li><a class="pag-btn" href="' . pgUrl(['page' => 1]) . '">1</a></li>';
                            if ($ws > 2)
                                echo '<li><span class="pag-ellipsis">…</span></li>';
                        }
                        for ($p = $ws; $p <= $we; $p++)
                            echo '<li><a class="pag-btn ' . ($p === $page ? 'is-active' : '') . '" href="' . pgUrl(['page' => $p]) . '">' . $p . '</a></li>';
                        if ($we < $totalPages) {
                            if ($we < $totalPages - 1)
                                echo '<li><span class="pag-ellipsis">…</span></li>';
                            echo '<li><a class="pag-btn" href="' . pgUrl(['page' => $totalPages]) . '">' . $totalPages . '</a></li>';
                        }
                        ?>
                        <li><a class="pag-btn <?= $page >= $totalPages ? 'is-disabled' : '' ?>"
                                href="<?= pgUrl(['page' => $page + 1]) ?>"><i class='bx bx-chevron-right'></i></a></li>
                        <li><a class="pag-btn <?= $page >= $totalPages ? 'is-disabled' : '' ?>"
                                href="<?= pgUrl(['page' => $totalPages]) ?>"><i class='bx bx-chevrons-right'></i></a></li>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    </div>
    </section>

    <script>
        // Search debounce
        const si = document.getElementById('searchInput');
        let st;
        si?.addEventListener('input', () => { clearTimeout(st); st = setTimeout(() => document.getElementById('filterForm').submit(), 420); });

        // Delete modal
        function openDelete(id) {
            document.getElementById('deleteIdField').value = id;
            document.getElementById('deleteOverlay').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeModal() {
            document.getElementById('deleteOverlay').classList.remove('open');
            document.body.style.overflow = '';
        }
        document.getElementById('deleteForm')?.addEventListener('submit', () => {
            document.getElementById('delSpinner').style.display = 'block';
            document.getElementById('delBtnText').textContent = 'Deleting…';
        });
        document.getElementById('deleteOverlay')?.addEventListener('click', e => { if (e.target.id === 'deleteOverlay') closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeModal(); closeLightbox(); } });

        // Lightbox
        function openLightbox(src) {
            document.getElementById('lightboxImg').src = src;
            document.getElementById('lightbox').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('open');
            document.body.style.overflow = '';
        }

        // Toast
        const toast = document.getElementById('toast');
        if (toast) { const t = setTimeout(dismissToast, 4500); toast.addEventListener('click', () => { clearTimeout(t); dismissToast(); }); }
        function dismissToast() {
            const t = document.getElementById('toast');
            if (!t) return;
            t.classList.add('hiding');
            setTimeout(() => t.remove(), 320);
        }
    </script>
</body>

</html>