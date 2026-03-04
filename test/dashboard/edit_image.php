<?php
session_start();
include '../../db.php';

/* ── Auth Guard ──────────────────────────────────────────────────────────── */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'SuperAdmin'], true)) {
    header("Location: login.php");
    exit();
}

/* ── Load image record ───────────────────────────────────────────────────── */
$imageId = (int) ($_GET['id'] ?? 0);
if ($imageId <= 0) {
    header("Location: manage_book_images.php");
    exit();
}

$sel = $conn->prepare("SELECT bi.*, bd.title AS book_title, c.name AS cat_name
                       FROM book_images bi
                       JOIN books_data bd ON bi.book_id = bd.id
                       JOIN categories c  ON bi.category_id = c.id
                       WHERE bi.id = ? LIMIT 1");
$sel->bind_param("i", $imageId);
$sel->execute();
$image = $sel->get_result()->fetch_assoc();
$sel->close();

if (!$image) {
    header("Location: manage_book_images.php");
    exit();
}

$toast = null;
$errors = [];
$old = $image;

/* ── Fetch categories ────────────────────────────────────────────────────── */
$catRes = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $catRes ? $catRes->fetch_all(MYSQLI_ASSOC) : [];

/* ── Fetch books for current category ───────────────────────────────────── */
$books = [];
$bStmt = $conn->prepare("SELECT id, title FROM books_data WHERE category_id = ? ORDER BY title ASC");
$bStmt->bind_param("i", $image['category_id']);
$bStmt->execute();
$books = $bStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$bStmt->close();

/* ── Handle POST ─────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = (int) ($_POST['book_id'] ?? 0);
    $category_id = (int) ($_POST['category_id'] ?? 0);

    if ($book_id <= 0)
        $errors['book_id'] = 'Please select a book.';
    if ($category_id <= 0)
        $errors['category_id'] = 'Please select a category.';

    $newImgPath = $image['image_path']; // keep existing by default

    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($ext, $allowed)) {
            $errors['image'] = 'Only JPG, PNG, WEBP, GIF files are allowed.';
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors['image'] = 'Image must be under 5 MB.';
        } else {
            $uploadDir = 'uploads/book_images/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);
            // Remove old
            if ($image['image_path'] && file_exists($image['image_path']))
                @unlink($image['image_path']);
            $newImgPath = $uploadDir . uniqid('bimg_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $newImgPath)) {
                $errors['image'] = 'Upload failed. Check folder permissions.';
                $newImgPath = $image['image_path'];
            }
        }
    }

    if (empty($errors)) {
        $upd = $conn->prepare("UPDATE book_images SET book_id=?, category_id=?, image_path=? WHERE id=?");
        $upd->bind_param("iisi", $book_id, $category_id, $newImgPath, $imageId);
        if ($upd->execute()) {
            $upd->close();
            $conn->close();
            header("Location: manage_book_images.php?toast=updated");
            exit();
        } else {
            $toast = ['type' => 'error', 'msg' => 'Database error: ' . htmlspecialchars($conn->error, ENT_QUOTES)];
        }
        $upd->close();
    }
    // Update old for re-render
    $old = array_merge($old, ['book_id' => $book_id, 'category_id' => $category_id, 'image_path' => $newImgPath]);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book Image — BookAdmin</title>
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

        @keyframes breathe {

            0%,
            100% {
                transform: scale(1)
            }

            50% {
                transform: scale(1.02)
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
            background: linear-gradient(90deg, #3b82f6, #6366f1, #8b5cf6);
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
            background: #eff6ff;
            color: var(--accent);
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
        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media(max-width:600px) {
            .form-row-2 {
                grid-template-columns: 1fr;
            }
        }

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

        /* Current image */
        .current-img-block {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 14px;
            background: var(--gray-50);
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r-lg);
            margin-bottom: 14px;
        }

        .current-img-block img {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: var(--r);
            border: 1px solid var(--gray-200);
            flex-shrink: 0;
        }

        .current-img-block .no-img-box {
            width: 72px;
            height: 72px;
            border-radius: var(--r);
            border: 1.5px dashed var(--gray-200);
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--gray-300);
            flex-shrink: 0;
        }

        .current-img-info {
            font-size: 12px;
            color: var(--gray-500);
        }

        .current-img-info strong {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--gray-700);
            margin-bottom: 3px;
        }

        .current-img-info span {
            font-family: "DM Mono", monospace;
            font-size: 11px;
            word-break: break-all;
            color: var(--gray-400);
        }

        /* Upload zone */
        .upload-zone {
            border: 2px dashed var(--gray-200);
            border-radius: var(--r-lg);
            padding: 24px 18px;
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
            font-size: 30px;
            color: var(--gray-300);
            display: block;
            margin-bottom: 8px;
            transition: color var(--t);
        }

        .upload-zone:hover .upload-zone-icon {
            color: var(--accent);
        }

        .upload-zone-title {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .upload-zone-title span {
            color: var(--accent);
        }

        .upload-zone-hint {
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 3px;
        }

        .new-preview-wrap {
            display: none;
            margin-top: 12px;
            position: relative;
            width: 80px;
            margin-left: auto;
            margin-right: auto;
        }

        .new-preview-wrap.show {
            display: block;
        }

        .new-preview-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--r);
            border: 2px solid var(--gray-200);
        }

        .preview-remove {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--danger);
            color: #fff;
            font-size: 13px;
            border: 2px solid #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--t);
        }

        .preview-remove:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        /* Form divider */
        .form-divider {
            height: 1px;
            background: var(--gray-100);
            margin: 22px -26px;
        }

        /* Actions */
        .form-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-submit {
            flex: 1;
            min-width: 140px;
            padding: 12px 24px;
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
            box-shadow: 0 2px 10px rgba(59, 130, 246, .28);
        }

        .btn-submit:hover {
            background: var(--accent-dark);
            box-shadow: 0 4px 20px rgba(59, 130, 246, .4);
            transform: translateY(-1px);
        }

        .btn-submit.loading {
            pointer-events: none;
            opacity: .8;
        }

        .btn-cancel {
            padding: 12px 20px;
            background: var(--gray-100);
            color: var(--gray-600);
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r);
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all var(--t);
        }

        .btn-cancel:hover {
            background: var(--gray-200);
            color: var(--gray-900);
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

        /* Preview card */
        .preview-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
            position: sticky;
            top: 84px;
        }

        .preview-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .preview-card-header i {
            font-size: 18px;
            color: var(--accent);
        }

        .preview-card-header h3 {
            font-size: 14px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .img-showcase {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .img-showcase::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: linear-gradient(var(--gray-200) 1px, transparent 1px), linear-gradient(90deg, var(--gray-200) 1px, transparent 1px);
            background-size: 24px 24px;
            opacity: .5;
        }

        .img-showcase-inner {
            position: relative;
            z-index: 1;
            background: #fff;
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: breathe 4s ease-in-out infinite;
        }

        .img-showcase-inner img {
            display: block;
            max-width: 220px;
            max-height: 220px;
            object-fit: cover;
        }

        .img-showcase-placeholder {
            color: var(--gray-300);
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .img-showcase-placeholder i {
            font-size: 48px;
            display: block;
            margin-bottom: 8px;
        }

        .img-showcase-placeholder p {
            font-size: 13px;
        }

        .preview-meta {
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .meta-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .meta-row:last-child {
            border-bottom: none;
        }

        .meta-row i {
            font-size: 15px;
            color: var(--gray-400);
            width: 18px;
            flex-shrink: 0;
        }

        .meta-label {
            font-size: 11.5px;
            color: var(--gray-400);
            width: 65px;
            flex-shrink: 0;
        }

        .meta-value {
            font-size: 13px;
            color: var(--gray-700);
            font-weight: 500;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Info card */
        .info-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .info-card-header {
            padding: 13px 16px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .info-card-header i {
            color: var(--accent);
            font-size: 16px;
        }

        .info-card-body {
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            color: var(--gray-500);
        }

        .info-row i {
            font-size: 14px;
            color: var(--gray-400);
            flex-shrink: 0;
        }

        .info-row strong {
            color: var(--gray-700);
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
            <h1>Edit Book Image</h1>
            <nav class="breadcrumb">
                <a href="dashboard.php"><i class='bx bx-home-alt'></i> Dashboard</a>
                <i class='bx bx-chevron-right'></i>
                <a href="manage_book_images.php">Book Images</a>
                <i class='bx bx-chevron-right'></i>
                <span>Edit #
                    <?= $imageId ?>
                </span>
            </nav>
        </div>
    </div>

    <!-- Page grid -->
    <div class="page-grid">

        <!-- LEFT: Form -->
        <div class="card">
            <div class="card-accent-bar"></div>
            <div class="card-header">
                <div class="card-header-icon"><i class='bx bx-edit-alt'></i></div>
                <div class="card-header-text">
                    <h2>Edit Image Details</h2>
                    <p>Update the book assignment or replace the image file</p>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="edit_image.php?id=<?= $imageId ?>" enctype="multipart/form-data"
                    id="editForm" novalidate>

                    <div class="section-title">Book Assignment</div>

                    <div class="form-row-2">
                        <!-- Category -->
                        <div class="form-group">
                            <label class="form-label">Category <span class="req">*</span></label>
                            <div class="input-wrap">
                                <div class="input-icon"><i class='bx bx-collection'></i></div>
                                <select name="category_id" id="categorySelect"
                                    class="form-input <?= !empty($errors['category_id']) ? 'has-error' : '' ?>">
                                    <option value="0">— Select —</option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= (int) $c['id'] ?>"
                                            <?= (int) $c['id'] === (int) $old['category_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(ucfirst($c['name']), ENT_QUOTES) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (!empty($errors['category_id'])): ?>
                                <div class="field-error"><i class='bx bx-error-circle'></i>
                                    <?= $errors['category_id'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Book -->
                        <div class="form-group">
                            <label class="form-label">Book <span class="req">*</span></label>
                            <div class="input-wrap">
                                <div class="input-icon"><i class='bx bx-book-alt'></i></div>
                                <select name="book_id" id="bookSelect"
                                    class="form-input <?= !empty($errors['book_id']) ? 'has-error' : '' ?>">
                                    <option value="0">— Select —</option>
                                    <?php foreach ($books as $b): ?>
                                        <option value="<?= (int) $b['id'] ?>"
                                            <?= (int) $b['id'] === (int) $old['book_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($b['title'], ENT_QUOTES) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (!empty($errors['book_id'])): ?>
                                <div class="field-error"><i class='bx bx-error-circle'></i>
                                    <?= $errors['book_id'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="section-title">Image File</div>

                    <!-- Current image -->
                    <div class="current-img-block">
                        <?php if (!empty($old['image_path']) && file_exists($old['image_path'])): ?>
                            <img src="<?= htmlspecialchars($old['image_path'], ENT_QUOTES) ?>" alt="Current">
                        <?php else: ?>
                            <div class="no-img-box"><i class='bx bx-image-alt'></i></div>
                        <?php endif; ?>
                        <div class="current-img-info">
                            <strong>Current Image</strong>
                            <span>
                                <?= htmlspecialchars($old['image_path'] ?? 'No file', ENT_QUOTES) ?>
                            </span>
                            <div style="margin-top:6px;font-size:12px;color:var(--gray-400);">Upload a new file below to
                                replace it, or leave empty to keep it.</div>
                        </div>
                    </div>

                    <!-- Upload zone -->
                    <div class="form-group">
                        <label class="form-label">
                            Replace Image
                            <span style="font-size:11.5px;font-weight:400;color:var(--gray-400)">optional</span>
                        </label>
                        <div class="upload-zone" id="uploadZone">
                            <input type="file" name="image" id="imgInput" accept="image/*">
                            <span class="upload-zone-icon"><i class='bx bx-image-add'></i></span>
                            <div class="upload-zone-title">Drop new image or <span>browse</span></div>
                            <div class="upload-zone-hint">JPG, PNG, WEBP — max 5 MB</div>
                            <div class="new-preview-wrap" id="previewWrap">
                                <img id="previewImg" class="new-preview-img" src="" alt="">
                                <button type="button" class="preview-remove" id="removePreview"><i
                                        class='bx bx-x'></i></button>
                            </div>
                        </div>
                        <?php if (!empty($errors['image'])): ?>
                            <div class="field-error" style="margin-top:8px"><i class='bx bx-error-circle'></i>
                                <?= $errors['image'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-divider"></div>

                    <div class="form-actions">
                        <a href="manage_book_images.php" class="btn-cancel"><i class='bx bx-arrow-back'></i> Cancel</a>
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <span class="btn-spinner" id="btnSpinner"></span>
                            <i class='bx bx-save' id="btnIcon"></i>
                            <span id="btnText">Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- RIGHT: Preview -->
        <div class="right-panel">
            <div class="preview-card">
                <div class="preview-card-header">
                    <i class='bx bx-image'></i>
                    <h3>Image Preview</h3>
                </div>
                <div class="img-showcase">
                    <?php if (!empty($old['image_path']) && file_exists($old['image_path'])): ?>
                        <div class="img-showcase-inner">
                            <img src="<?= htmlspecialchars($old['image_path'] . '?v=' . filemtime($old['image_path']), ENT_QUOTES) ?>"
                                alt="Preview" id="showcaseImg">
                        </div>
                    <?php else: ?>
                        <div class="img-showcase-placeholder">
                            <i class='bx bx-image-alt'></i>
                            <p>No image</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="preview-meta">
                    <div class="meta-row">
                        <i class='bx bx-hash'></i>
                        <span class="meta-label">Image ID</span>
                        <span class="meta-value">#
                            <?= $imageId ?>
                        </span>
                    </div>
                    <div class="meta-row">
                        <i class='bx bx-book-alt'></i>
                        <span class="meta-label">Book</span>
                        <span class="meta-value" id="previewBook">
                            <?= htmlspecialchars($image['book_title'], ENT_QUOTES) ?>
                        </span>
                    </div>
                    <div class="meta-row">
                        <i class='bx bx-collection'></i>
                        <span class="meta-label">Category</span>
                        <span class="meta-value" id="previewCat">
                            <?= htmlspecialchars($image['cat_name'], ENT_QUOTES) ?>
                        </span>
                    </div>
                    <?php if (!empty($old['image_path']) && file_exists($old['image_path'])): ?>
                        <div class="meta-row">
                            <i class='bx bx-data'></i>
                            <span class="meta-label">File size</span>
                            <span class="meta-value" style="font-family:'DM Mono',monospace;font-size:12px">
                                <?= round(filesize($old['image_path']) / 1024, 1) ?> KB
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info card -->
            <div class="info-card">
                <div class="info-card-header"><i class='bx bx-info-circle'></i> Info</div>
                <div class="info-card-body">
                    <div class="info-row"><i class='bx bx-check-circle'></i><span>Leave image field empty to keep the
                            current file.</span></div>
                    <div class="info-row"><i class='bx bx-refresh'></i><span>Changing category will <strong>not</strong>
                            auto-reload the book list — select the book manually.</span></div>
                    <div class="info-row"><i class='bx bx-trash'></i><span>Old image file is deleted automatically when
                            replaced.</span></div>
                </div>
            </div>
        </div>

    </div><!-- /.page-grid -->

    </div>
    </section>

    <script>
        (() => {
            /* ── Category change → reload books via AJAX ── */
            const catSel = document.getElementById('categorySelect');
            const bookSel = document.getElementById('bookSelect');
            const previewCat = document.getElementById('previewCat');

            catSel?.addEventListener('change', () => {
                const catId = catSel.value;
                const selOpt = catSel.options[catSel.selectedIndex];
                if (previewCat) previewCat.textContent = catId !== '0' ? selOpt.textContent.trim() : '—';
                if (!catId || catId === '0') return;

                fetch(`get_book.php?category_id=${catId}`)
                    .then(r => r.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        bookSel.innerHTML = '<option value="0">— Select book —</option>';
                        doc.querySelectorAll('option').forEach(o => { if (o.value) bookSel.appendChild(o.cloneNode(true)); });
                    });
            });

            bookSel?.addEventListener('change', () => {
                const selOpt = bookSel.options[bookSel.selectedIndex];
                const previewBook = document.getElementById('previewBook');
                if (previewBook) previewBook.textContent = bookSel.value !== '0' ? selOpt.textContent.trim() : '—';
            });

            /* ── Image upload preview ─────────────────────── */
            const imgInput = document.getElementById('imgInput');
            const previewWrap = document.getElementById('previewWrap');
            const previewImg = document.getElementById('previewImg');
            const removeBtn = document.getElementById('removePreview');
            const uploadZone = document.getElementById('uploadZone');
            const showcaseImg = document.getElementById('showcaseImg');

            imgInput?.addEventListener('change', () => handleFile(imgInput.files[0]));
            uploadZone?.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
            uploadZone?.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
            uploadZone?.addEventListener('drop', e => {
                e.preventDefault(); uploadZone.classList.remove('drag-over');
                const f = e.dataTransfer.files[0];
                if (f) { const dt = new DataTransfer(); dt.items.add(f); imgInput.files = dt.files; handleFile(f); }
            });

            const originalSrc = showcaseImg?.src || null;

            function handleFile(file) {
                if (!file?.type.startsWith('image/')) return;
                const r = new FileReader();
                r.onload = e => {
                    previewImg.src = e.target.result;
                    previewWrap.classList.add('show');
                    if (showcaseImg) showcaseImg.src = e.target.result;
                };
                r.readAsDataURL(file);
            }

            removeBtn?.addEventListener('click', e => {
                e.stopPropagation();
                imgInput.value = '';
                previewWrap.classList.remove('show');
                if (showcaseImg && originalSrc) showcaseImg.src = originalSrc;
            });

            /* ── Submit ───────────────────────────────────── */
            const form = document.getElementById('editForm');
            const submitBtn = document.getElementById('submitBtn');
            const spinner = document.getElementById('btnSpinner');
            const btnIcon = document.getElementById('btnIcon');
            const btnText = document.getElementById('btnText');

            form?.addEventListener('submit', e => {
                let ok = true;
                if (catSel.value === '0') { catSel.classList.add('has-error'); ok = false; }
                if (bookSel.value === '0') { bookSel.classList.add('has-error'); ok = false; }
                if (!ok) { e.preventDefault(); return; }
                submitBtn.classList.add('loading');
                spinner.style.display = 'block'; btnIcon.style.display = 'none';
                btnText.textContent = 'Saving…';
            });

            catSel?.addEventListener('change', () => catSel.classList.remove('has-error'));
            bookSel?.addEventListener('change', () => bookSel.classList.remove('has-error'));

            /* ── Toast ────────────────────────────────────── */
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