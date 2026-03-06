<?php
session_start();
include '../../db.php';

/* ── Auth Guard ──────────────────────────────────────────────────────────── */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'SuperAdmin'], true)) {
    header("Location: login.php");
    exit();
}

$toast = null;
$errors = [];

/* ── Fetch categories ────────────────────────────────────────────────────── */
$catRes = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $catRes ? $catRes->fetch_all(MYSQLI_ASSOC) : [];

/* ── Fetch books for pre-selected category ───────────────────────────────── */
$books = [];
$selCat = (int) ($_GET['category_id'] ?? $_POST['category_id'] ?? 0);
$selBook = (int) ($_POST['book_id'] ?? 0);

if ($selCat > 0) {
    $bStmt = $conn->prepare("SELECT id, title FROM books_data WHERE category_id = ? ORDER BY title ASC");
    $bStmt->bind_param("i", $selCat);
    $bStmt->execute();
    $books = $bStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $bStmt->close();
}

/* ── Handle POST ─────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = (int) ($_POST['book_id'] ?? 0);
    $category_id = (int) ($_POST['category_id'] ?? 0);

    if ($book_id <= 0)
        $errors['book_id'] = 'Please select a book.';
    if ($category_id <= 0)
        $errors['category_id'] = 'Please select a category.';

    $uploadedFiles = $_FILES['images'] ?? [];
    if (empty($uploadedFiles['name'][0])) {
        $errors['images'] = 'Please select at least one image.';
    }

    if (empty($errors)) {
        $uploadDir = 'uploads/book_images/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0755, true);

        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $uploaded = 0;
        $failed = 0;

        foreach ($uploadedFiles['tmp_name'] as $k => $tmp) {
            if ($uploadedFiles['error'][$k] !== 0) {
                $failed++;
                continue;
            }
            $ext = strtolower(pathinfo($uploadedFiles['name'][$k], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed) || $uploadedFiles['size'][$k] > 5 * 1024 * 1024) {
                $failed++;
                continue;
            }

            $path = $uploadDir . uniqid('bimg_') . '.' . $ext;
            if (move_uploaded_file($tmp, $path)) {
                $ins = $conn->prepare("INSERT INTO book_images (book_id, category_id, image_path) VALUES (?,?,?)");
                $ins->bind_param("iis", $book_id, $category_id, $path);
                $ins->execute();
                $ins->close();
                $uploaded++;
            } else {
                $failed++;
            }
        }

        $conn->close();
        $msg = $uploaded . ' image' . ($uploaded !== 1 ? 's' : '') . ' uploaded successfully.' . ($failed > 0 ? " $failed failed." : '');
        header("Location: manage_book_images.php?toast=uploaded&count=$uploaded");
        exit();
    }
}

/* ── Stats ───────────────────────────────────────────────────────────────── */
$totalImgs = (int) $conn->query("SELECT COUNT(*) FROM book_images")->fetch_row()[0];
$totalBooks = (int) $conn->query("SELECT COUNT(*) FROM books_data")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Book Images — BookAdmin</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        /* ─── Shared tokens (not repeated from dashboard.css) ─── */
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

        @keyframes spin {
            to {
                transform: rotate(360deg)
            }
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0)
            }

            20% {
                transform: translateX(-6px)
            }

            40% {
                transform: translateX(6px)
            }

            60% {
                transform: translateX(-4px)
            }

            80% {
                transform: translateX(4px)
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

        .toast-close:hover {
            opacity: 1;
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

        .btn-manage {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            background: #fff;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r);
            font-size: 13.5px;
            font-weight: 600;
            color: var(--gray-700);
            text-decoration: none;
            transition: all var(--t);
        }

        .btn-manage:hover {
            background: var(--gray-50);
            border-color: var(--gray-300);
            color: var(--gray-900);
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

        .si-green {
            background: #ecfdf5;
            color: #10b981;
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

        /* Page grid */
        .page-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 22px;
            align-items: start;
        }

        @media(max-width:900px) {
            .page-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card */
        .card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-accent-bar {
            height: 4px;
            background: linear-gradient(90deg, #f59e0b, #ef4444, #8b5cf6);
        }

        .card-header {
            padding: 20px 26px 16px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--r);
            background: #fff7ed;
            color: #f59e0b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .card-header-text h2 {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-800);
            margin: 0;
        }

        .card-header-text p {
            font-size: 12px;
            color: var(--gray-400);
            margin: 2px 0 0;
        }

        .card-body {
            padding: 26px;
        }

        /* Section title */
        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: var(--gray-400);
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 16px;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-100);
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 7px;
        }

        .form-label .req {
            color: var(--danger);
            margin-left: 2px;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--gray-400);
            pointer-events: none;
            border-right: 1px solid var(--gray-200);
            transition: all var(--t);
            z-index: 1;
        }

        .form-input {
            width: 100%;
            padding: 11px 14px 11px 54px;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r);
            font-size: 14px;
            font-family: inherit;
            background: var(--gray-50);
            color: var(--gray-800);
            outline: none;
            transition: all var(--t);
        }

        .form-input::placeholder {
            color: var(--gray-300);
        }

        .form-input:focus {
            background: #fff;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
        }

        .input-wrap:focus-within .input-icon {
            color: var(--accent);
            border-color: rgba(59, 130, 246, .3);
        }

        select.form-input {
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24'%3E%3Cpath fill='%239ca3af' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }

        .form-input.has-error {
            border-color: var(--danger);
            background: var(--danger-bg);
            animation: shake .4s var(--t);
        }

        .field-error {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 6px;
            font-size: 12.5px;
            color: var(--danger);
            font-weight: 500;
        }

        .field-error i {
            font-size: 14px;
        }

        /* Books loader skeleton */
        .books-loading {
            display: none;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            background: var(--gray-50);
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r);
            font-size: 13px;
            color: var(--gray-400);
        }

        .books-loading.show {
            display: flex;
        }

        .load-spin {
            width: 14px;
            height: 14px;
            border: 2px solid var(--gray-200);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin .6s linear infinite;
            flex-shrink: 0;
        }

        /* Upload zone */
        .upload-zone {
            border: 2px dashed var(--gray-200);
            border-radius: var(--r-lg);
            padding: 28px 20px;
            text-align: center;
            cursor: pointer;
            transition: all var(--t);
            position: relative;
            background: var(--gray-50);
        }

        .upload-zone:hover,
        .upload-zone.drag-over {
            border-color: var(--accent);
            background: var(--accent-light);
        }

        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .upload-zone-icon {
            font-size: 36px;
            color: var(--gray-300);
            display: block;
            margin-bottom: 10px;
            transition: color var(--t);
        }

        .upload-zone:hover .upload-zone-icon,
        .upload-zone.drag-over .upload-zone-icon {
            color: var(--accent);
        }

        .upload-zone-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .upload-zone-title span {
            color: var(--accent);
        }

        .upload-zone-hint {
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 4px;
        }

        /* File list */
        .file-list {
            display: none;
            flex-direction: column;
            gap: 8px;
            margin-top: 14px;
        }

        .file-list.show {
            display: flex;
        }

        .file-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r);
            font-size: 13px;
        }

        .file-thumb {
            width: 36px;
            height: 36px;
            object-fit: cover;
            border-radius: 6px;
            flex-shrink: 0;
        }

        .file-thumb-placeholder {
            width: 36px;
            height: 36px;
            background: var(--gray-100);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--gray-400);
            flex-shrink: 0;
        }

        .file-name {
            flex: 1;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 500;
            color: var(--gray-700);
        }

        .file-size {
            font-size: 11.5px;
            color: var(--gray-400);
            font-family: "DM Mono", monospace;
            flex-shrink: 0;
        }

        .file-remove {
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            font-size: 16px;
            padding: 2px;
            transition: color var(--t);
            flex-shrink: 0;
        }

        .file-remove:hover {
            color: var(--danger);
        }

        .file-count {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            background: var(--accent-light);
            color: var(--accent-dark);
            border-radius: 99px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }

        /* Submit */
        .btn-submit {
            width: 100%;
            padding: 13px 24px;
            background: var(--accent);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            border: none;
            border-radius: var(--r);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all var(--t);
            box-shadow: 0 2px 12px rgba(59, 130, 246, .3);
        }

        .btn-submit:hover {
            background: var(--accent-dark);
            box-shadow: 0 6px 22px rgba(59, 130, 246, .4);
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit.loading {
            pointer-events: none;
            opacity: .8;
        }

        .btn-spinner {
            width: 16px;
            height: 16px;
            border: 2.5px solid rgba(255, 255, 255, .35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .65s linear infinite;
            display: none;
        }

        /* Right panel */
        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        /* Preview panel */
        .preview-panel {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
            position: sticky;
            top: 84px;
        }

        .preview-panel-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .preview-panel-header i {
            font-size: 18px;
            color: var(--accent);
        }

        .preview-panel-header h3 {
            font-size: 14px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .preview-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            padding: 16px;
        }

        .preview-img-wrap {
            border-radius: var(--r);
            overflow: hidden;
            background: var(--gray-100);
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .preview-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-empty {
            padding: 32px 20px;
            text-align: center;
            color: var(--gray-300);
        }

        .preview-empty i {
            font-size: 36px;
            display: block;
            margin-bottom: 8px;
        }

        .preview-empty p {
            font-size: 12.5px;
        }

        /* Tips card */
        .tips-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .tips-header {
            padding: 13px 16px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .tips-header i {
            color: var(--warning);
            font-size: 16px;
        }

        .tips-body {
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 11px;
        }

        .tip-row {
            display: flex;
            gap: 9px;
            align-items: flex-start;
        }

        .tip-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 6px;
        }

        .tip-text {
            font-size: 12.5px;
            color: var(--gray-500);
            line-height: 1.5;
        }

        .tip-text strong {
            color: var(--gray-700);
            font-weight: 600;
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

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-left">
            <h1>Upload Book Images</h1>
            <nav class="breadcrumb">
                <a href="<?= $root_url ?>"><i class='bx bx-home-alt'></i> Dashboard</a>
                <i class='bx bx-chevron-right'></i>
                <a href="manage_book_images.php">Book Images</a>
                <i class='bx bx-chevron-right'></i>
                <span>Upload</span>
            </nav>
        </div>
        <a href="manage_book_images.php" class="btn-manage">
            <i class='bx bx-images'></i> Manage Images
        </a>
    </div>

    <!-- Summary strip -->
    <div class="summary-strip">
        <div class="summary-card">
            <div class="summary-icon si-purple"><i class='bx bx-images'></i></div>
            <div>
                <div class="summary-val"><?= number_format($totalImgs) ?></div>
                <div class="summary-label">Total Images</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon si-blue"><i class='bx bx-book-alt'></i></div>
            <div>
                <div class="summary-val"><?= number_format($totalBooks) ?></div>
                <div class="summary-label">Books</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon si-green"><i class='bx bx-collection'></i></div>
            <div>
                <div class="summary-val"><?= number_format(count($categories)) ?></div>
                <div class="summary-label">Categories</div>
            </div>
        </div>
    </div>

    <!-- Page grid -->
    <div class="page-grid">

        <!-- LEFT: Form -->
        <div class="card">
            <div class="card-accent-bar"></div>
            <div class="card-header">
                <div class="card-header-icon"><i class='bx bx-image-add'></i></div>
                <div class="card-header-text">
                    <h2>Upload Images</h2>
                    <p>Select a category and book, then upload images</p>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="add_book_images.php" enctype="multipart/form-data" id="uploadForm"
                    novalidate>

                    <div class="section-title">Book Selection</div>

                    <!-- Category -->
                    <div class="form-group">
                        <label class="form-label">Category <span class="req">*</span></label>
                        <div class="input-wrap">
                            <div class="input-icon"><i class='bx bx-collection'></i></div>
                            <select name="category_id" id="categorySelect"
                                class="form-input <?= !empty($errors['category_id']) ? 'has-error' : '' ?>">
                                <option value="0">— Select category —</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>" <?= $selCat === (int) $c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(ucfirst($c['name']), ENT_QUOTES) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (!empty($errors['category_id'])): ?>
                            <div class="field-error"><i class='bx bx-error-circle'></i><?= $errors['category_id'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Books loader -->
                    <div class="books-loading" id="booksLoading">
                        <div class="load-spin"></div> Loading books…
                    </div>

                    <!-- Book -->
                    <div class="form-group" id="bookGroup" style="<?= $selCat ? '' : 'display:none' ?>">
                        <label class="form-label">Book <span class="req">*</span></label>
                        <div class="input-wrap">
                            <div class="input-icon"><i class='bx bx-book-alt'></i></div>
                            <select name="book_id" id="bookSelect"
                                class="form-input <?= !empty($errors['book_id']) ? 'has-error' : '' ?>">
                                <option value="0">— Select book —</option>
                                <?php foreach ($books as $b): ?>
                                    <option value="<?= (int) $b['id'] ?>" <?= $selBook === (int) $b['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['title'], ENT_QUOTES) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (!empty($errors['book_id'])): ?>
                            <div class="field-error"><i class='bx bx-error-circle'></i><?= $errors['book_id'] ?></div>
                        <?php endif; ?>
                        <?php if (empty($books) && $selCat > 0): ?>
                            <div
                                style="margin-top:8px;font-size:12.5px;color:var(--warning);display:flex;align-items:center;gap:5px;">
                                <i class='bx bx-info-circle'></i> No books found in this category.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="section-title" style="margin-top:4px;">Images</div>

                    <!-- Upload zone -->
                    <div class="form-group">
                        <label class="form-label">
                            Image Files <span class="req">*</span>
                            <span id="fileCountBadge" style="display:none" class="file-count"><i
                                    class='bx bx-images'></i> <span id="fileCountNum">0</span> selected</span>
                        </label>
                        <div class="upload-zone" id="uploadZone">
                            <input type="file" name="images[]" id="imagesInput" multiple accept="image/*">
                            <span class="upload-zone-icon"><i class='bx bx-cloud-upload'></i></span>
                            <div class="upload-zone-title">Drop images here or <span>browse</span></div>
                            <div class="upload-zone-hint">JPG, PNG, WEBP — up to 5 MB each — multiple allowed</div>
                        </div>
                        <?php if (!empty($errors['images'])): ?>
                            <div class="field-error"><i class='bx bx-error-circle'></i><?= $errors['images'] ?></div>
                        <?php endif; ?>

                        <!-- File list -->
                        <div class="file-list" id="fileList"></div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <span class="btn-spinner" id="btnSpinner"></span>
                        <i class='bx bx-upload' id="btnIcon"></i>
                        <span id="btnText">Upload Images</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- RIGHT: Preview + tips -->
        <div class="right-panel">
            <div class="preview-panel">
                <div class="preview-panel-header">
                    <i class='bx bx-grid-alt'></i>
                    <h3>Image Preview</h3>
                </div>
                <div id="previewGrid" class="preview-grid" style="display:none"></div>
                <div class="preview-empty" id="previewEmpty">
                    <i class='bx bx-image-alt'></i>
                    <p>Selected images will appear here</p>
                </div>
            </div>

            <div class="tips-card">
                <div class="tips-header"><i class='bx bx-bulb'></i> Tips</div>
                <div class="tips-body">
                    <div class="tip-row">
                        <div class="tip-dot" style="background:#10b981"></div>
                        <div class="tip-text"><strong>Multiple upload.</strong> You can select and upload several images
                            at once for the same book.</div>
                    </div>
                    <div class="tip-row">
                        <div class="tip-dot" style="background:var(--accent)"></div>
                        <div class="tip-text"><strong>Best format.</strong> Use square or portrait images for consistent
                            display in the book gallery.</div>
                    </div>
                    <div class="tip-row">
                        <div class="tip-dot" style="background:var(--warning)"></div>
                        <div class="tip-text"><strong>File limit.</strong> Each image must be under 5 MB. JPG, PNG, and
                            WEBP formats are accepted.</div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.page-grid -->

    </div>
    </section>

    <script>
        (() => {
            /* ── Category → books AJAX ──────────────────── */
            const catSel = document.getElementById('categorySelect');
            const bookGroup = document.getElementById('bookGroup');
            const bookSel = document.getElementById('bookSelect');
            const booksLoader = document.getElementById('booksLoading');

            catSel?.addEventListener('change', () => {
                const catId = catSel.value;
                bookGroup.style.display = 'none';
                if (!catId || catId === '0') return;

                booksLoader.classList.add('show');
                fetch(`get_book.php?category_id=${catId}`)
                    .then(r => r.text())
                    .then(html => {
                        booksLoader.classList.remove('show');
                        // parse returned options into select
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const opts = doc.querySelectorAll('option');
                        bookSel.innerHTML = '<option value="0">— Select book —</option>';
                        opts.forEach(o => { if (o.value) bookSel.appendChild(o.cloneNode(true)); });
                        bookGroup.style.display = '';
                    })
                    .catch(() => { booksLoader.classList.remove('show'); bookGroup.style.display = ''; });
            });

            /* ── File selection & preview ───────────────── */
            const imgInput = document.getElementById('imagesInput');
            const fileList = document.getElementById('fileList');
            const previewGrid = document.getElementById('previewGrid');
            const previewEmpty = document.getElementById('previewEmpty');
            const countBadge = document.getElementById('fileCountBadge');
            const countNum = document.getElementById('fileCountNum');
            const uploadZone = document.getElementById('uploadZone');

            let selectedFiles = [];

            imgInput?.addEventListener('change', () => addFiles(imgInput.files));

            uploadZone?.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
            uploadZone?.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
            uploadZone?.addEventListener('drop', e => {
                e.preventDefault(); uploadZone.classList.remove('drag-over');
                addFiles(e.dataTransfer.files);
            });

            function addFiles(fileObjs) {
                Array.from(fileObjs).forEach(f => {
                    if (f.type.startsWith('image/') && f.size <= 5 * 1024 * 1024) selectedFiles.push(f);
                });
                renderFiles();
            }

            function renderFiles() {
                // Sync to real input
                const dt = new DataTransfer();
                selectedFiles.forEach(f => dt.items.add(f));
                imgInput.files = dt.files;

                countNum.textContent = selectedFiles.length;
                countBadge.style.display = selectedFiles.length ? 'inline-flex' : 'none';

                // File list
                fileList.innerHTML = '';
                if (selectedFiles.length) fileList.classList.add('show'); else fileList.classList.remove('show');

                // Preview grid
                previewGrid.innerHTML = '';
                previewEmpty.style.display = selectedFiles.length ? 'none' : '';
                previewGrid.style.display = selectedFiles.length ? 'grid' : 'none';

                selectedFiles.forEach((f, i) => {
                    const url = URL.createObjectURL(f);

                    // List item
                    const li = document.createElement('div');
                    li.className = 'file-item';
                    const thumb = document.createElement('img');
                    thumb.className = 'file-thumb'; thumb.src = url;
                    const name = document.createElement('span');
                    name.className = 'file-name'; name.textContent = f.name;
                    const size = document.createElement('span');
                    size.className = 'file-size'; size.textContent = (f.size / 1024).toFixed(0) + ' KB';
                    const rm = document.createElement('button');
                    rm.type = 'button'; rm.className = 'file-remove'; rm.innerHTML = "<i class='bx bx-x'></i>";
                    rm.onclick = () => { selectedFiles.splice(i, 1); renderFiles(); };
                    li.append(thumb, name, size, rm);
                    fileList.appendChild(li);

                    // Preview grid cell
                    const cell = document.createElement('div');
                    cell.className = 'preview-img-wrap';
                    const img = document.createElement('img'); img.src = url;
                    cell.appendChild(img);
                    previewGrid.appendChild(cell);
                });
            }

            /* ── Submit ──────────────────────────────────── */
            const form = document.getElementById('uploadForm');
            const submitBtn = document.getElementById('submitBtn');
            const spinner = document.getElementById('btnSpinner');
            const btnIcon = document.getElementById('btnIcon');
            const btnText = document.getElementById('btnText');

            form?.addEventListener('submit', e => {
                let ok = true;
                if (catSel.value === '0') { catSel.classList.add('has-error'); ok = false; }
                if (bookSel.value === '0') { bookSel.classList.add('has-error'); ok = false; }
                if (!selectedFiles.length) {
                    uploadZone.style.borderColor = 'var(--danger)'; uploadZone.style.background = 'var(--danger-bg)';
                    setTimeout(() => { uploadZone.style.borderColor = ''; uploadZone.style.background = ''; }, 1800);
                    ok = false;
                }
                if (!ok) { e.preventDefault(); return; }
                submitBtn.classList.add('loading');
                spinner.style.display = 'block'; btnIcon.style.display = 'none';
                btnText.textContent = 'Uploading…';
            });

            catSel?.addEventListener('change', () => catSel.classList.remove('has-error'));
            bookSel?.addEventListener('change', () => bookSel.classList.remove('has-error'));

            /* ── Toast ───────────────────────────────────── */
            const toast = document.getElementById('toast');
            if (toast) { const t = setTimeout(dismissToast, 4500); toast.addEventListener('click', () => { clearTimeout(t); dismissToast(); }); }
        })();

        function dismissToast() {
            const t = document.getElementById('toast');
            if (!t) return;
            t.classList.add('hiding');
            setTimeout(() => t.remove(), 320);
        }
    </script>
</body>

</html>