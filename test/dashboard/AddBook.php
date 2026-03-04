<?php
session_start();
include '../../db.php';

/* ── Auth Guard ──────────────────────────────────────────────────────────── */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'SuperAdmin'], true)) {
    header("Location: login.php");
    exit();
}

$errors = [];
$toast = null;
$old = [];

/* ── Handle POST ─────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $title = trim($_POST['title'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $publishers = trim($_POST['publishers'] ?? '');
    $length = trim($_POST['length'] ?? '');
    $subjects = trim($_POST['subjects'] ?? '');
    $contributors = trim($_POST['contributors'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $name = $title; // name mirrors title

    /* ── Validation ── */
    if ($title === '') {
        $errors['title'] = 'Book title is required.';
    } elseif (mb_strlen($title) > 255) {
        $errors['title'] = 'Title must be 255 characters or fewer.';
    }

    if ($isbn === '') {
        $errors['isbn'] = 'ISBN is required.';
    } else {
        // Duplicate ISBN check
        $dup = $conn->prepare("SELECT id FROM books_data WHERE isbn = ? LIMIT 1");
        $dup->bind_param("s", $isbn);
        $dup->execute();
        $dup->store_result();
        if ($dup->num_rows > 0) {
            $errors['isbn'] = "ISBN \"$isbn\" already exists in the system.";
        }
        $dup->close();
    }

    if ($category_id <= 0) {
        $errors['category_id'] = 'Please select a category.';
    }

    if ($price !== '' && !is_numeric($price)) {
        $errors['price'] = 'Price must be a valid number.';
    }

    /* ── Image Upload ── */
    $img = null;
    if (!empty($_FILES['img']['name'])) {
        $uploadDir = 'uploads/books/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', "svg",];

        if (!in_array($ext, $allowed)) {
            $errors['img'] = 'Only JPG, PNG, WEBP, SVG images are allowed.';
        } elseif ($_FILES['img']['size'] > 5 * 1024 * 1024) {
            $errors['img'] = 'Image must be under 5 MB.';
        } else {
            $img = $uploadDir . uniqid('book_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['img']['tmp_name'], $img)) {
                $errors['img'] = 'Upload failed. Check folder permissions.';
                $img = null;
            }
        }
    }

    /* ── Insert ── */
    if (empty($errors)) {
        $priceVal = $price !== '' ? $price : null;
        $lenVal = $length !== '' ? $length : null;

        $stmt = $conn->prepare(
            "INSERT INTO books_data
             (name, title, price, publishers, img, isbn, length, subjects, contributors, description, category_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssssssssssi",
            $name,
            $title,
            $priceVal,
            $publishers,
            $img,
            $isbn,
            $lenVal,
            $subjects,
            $contributors,
            $description,
            $category_id
        );

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: AllBooks.php?toast=added&book=" . urlencode($title));
            exit();
        } else {
            $toast = ['type' => 'error', 'msg' => 'Database error: ' . htmlspecialchars($conn->error, ENT_QUOTES)];
        }
        $stmt->close();
    }
}

/* ── Fetch categories ─────────────────────────────────────────────────────── */
$catRes = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $catRes ? $catRes->fetch_all(MYSQLI_ASSOC) : [];

/* ── Stats ─────────────────────────────────────────────────────────────────── */
$totalBooks = (int) $conn->query("SELECT COUNT(*) FROM books_data")->fetch_row()[0];
$totalCats = (int) $conn->query("SELECT COUNT(*) FROM categories")->fetch_row()[0];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book — BookAdmin</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        /* ── Header ──────────────────────────────────────── */
        .page-header {
            margin-bottom: 26px;
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

        /* ── Grid ────────────────────────────────────────── */
        .page-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 22px;
            align-items: start;
        }

        @media (max-width: 900px) {
            .page-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── Card ────────────────────────────────────────── */
        .card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-accent-bar {
            height: 4px;
            background: linear-gradient(90deg, #10b981, #3b82f6, #8b5cf6);
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
            background: #ecfdf5;
            color: #10b981;
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

        /* ── Section title ───────────────────────────────── */
        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: var(--gray-400);
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 26px 0 16px;
        }

        .section-title:first-child {
            margin-top: 0;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-100);
        }

        /* ── Form grid ───────────────────────────────────── */
        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 640px) {

            .form-row-2,
            .form-row-3 {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 18px;
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

        .form-label .hint {
            font-size: 11.5px;
            font-weight: 400;
            color: var(--gray-400);
        }

        /* ── Input ───────────────────────────────────────── */
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

        .form-input.has-error {
            border-color: var(--danger);
            background: var(--danger-bg);
            animation: shake .4s var(--t);
        }

        .form-input.has-error:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, .12);
        }

        .form-input.no-icon {
            padding-left: 14px;
        }

        /* Price prefix */
        .price-prefix {
            position: absolute;
            left: 44px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-500);
            pointer-events: none;
            border-left: 1px solid var(--gray-200);
            padding: 0 8px 0 10px;
            line-height: 1;
            z-index: 1;
        }

        .form-input.has-prefix {
            padding-left: 82px;
        }

        textarea.form-input {
            resize: vertical;
            min-height: 110px;
            padding-top: 12px;
            line-height: 1.55;
        }

        /* Select */
        select.form-input {
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24'%3E%3Cpath fill='%239ca3af' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }

        /* Field error */
        .field-error {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
            font-size: 12.5px;
            color: var(--danger);
            font-weight: 500;
        }

        .field-error i {
            font-size: 14px;
        }

        /* Char count */
        .char-count {
            display: block;
            text-align: right;
            margin-top: 4px;
            font-size: 11.5px;
            color: var(--gray-400);
            font-family: "DM Mono", monospace;
            transition: color var(--t);
        }

        .char-count.warn {
            color: var(--warning);
        }

        .char-count.over {
            color: var(--danger);
            font-weight: 600;
        }

        /* ── Image upload ────────────────────────────────── */
        .upload-zone {
            border: 2px dashed var(--gray-200);
            border-radius: var(--r-lg);
            padding: 22px;
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

        .upload-icon {
            font-size: 30px;
            color: var(--gray-300);
            display: block;
            margin-bottom: 8px;
        }

        .upload-label {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .upload-hint {
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 3px;
        }

        .upload-hint span {
            color: var(--accent);
            font-weight: 500;
        }

        .preview-wrap {
            display: none;
            margin-top: 14px;
            position: relative;
            width: 100px;
            margin-left: auto;
            margin-right: auto;
        }

        .preview-wrap.show {
            display: block;
        }

        .preview-img {
            width: 100px;
            height: 130px;
            object-fit: cover;
            border-radius: var(--r);
            border: 2px solid var(--gray-200);
            display: block;
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

        /* ── Divider ─────────────────────────────────────── */
        .form-divider {
            height: 1px;
            background: var(--gray-100);
            margin: 22px -26px;
        }

        /* ── Action buttons ──────────────────────────────── */
        .form-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-submit {
            flex: 1;
            min-width: 160px;
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
            position: relative;
            overflow: hidden;
        }

        .btn-submit:hover {
            background: var(--accent-dark);
            box-shadow: 0 4px 20px rgba(59, 130, 246, .4);
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit.loading {
            pointer-events: none;
            opacity: .8;
        }

        .btn-cancel {
            padding: 12px 22px;
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
            white-space: nowrap;
        }

        .btn-cancel:hover {
            background: var(--gray-200);
            border-color: var(--gray-300);
            color: var(--gray-800);
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

        /* ── Right panel ─────────────────────────────────── */
        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        /* Book cover preview card */
        .cover-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            position: sticky;
            top: 84px;
        }

        .cover-preview-area {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            min-height: 180px;
            justify-content: center;
        }

        .cover-placeholder {
            width: 90px;
            height: 120px;
            border-radius: var(--r);
            background: rgba(255, 255, 255, .07);
            border: 2px dashed rgba(255, 255, 255, .2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, .3);
            font-size: 32px;
        }

        .cover-img-preview {
            width: 90px;
            height: 120px;
            object-fit: cover;
            border-radius: var(--r);
            box-shadow: 0 8px 24px rgba(0, 0, 0, .4);
            display: none;
        }

        .cover-img-preview.show {
            display: block;
        }

        .cover-title-preview {
            font-size: 14px;
            font-weight: 700;
            color: #f8fafc;
            text-align: center;
            max-width: 200px;
            line-height: 1.3;
            word-break: break-word;
        }

        .cover-isbn-preview {
            font-size: 11px;
            color: rgba(255, 255, 255, .4);
            font-family: "DM Mono", monospace;
        }

        .cover-card-body {
            padding: 16px;
        }

        .meta-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 9px 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .meta-row:last-child {
            border-bottom: none;
        }

        .meta-row i {
            font-size: 15px;
            color: var(--gray-400);
            flex-shrink: 0;
            margin-top: 2px;
        }

        .meta-content {
            flex: 1;
            min-width: 0;
        }

        .meta-label {
            font-size: 11px;
            color: var(--gray-400);
            margin-bottom: 1px;
        }

        .meta-val {
            font-size: 13px;
            color: var(--gray-700);
            font-weight: 500;
            word-break: break-word;
        }

        .meta-val.empty {
            color: var(--gray-300);
            font-style: italic;
            font-weight: 400;
        }

        /* Stats */
        .stat-mini {
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

        .stat-mini:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .stat-mini-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--r);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
        }

        .si-green {
            background: #ecfdf5;
            color: #10b981;
        }

        .si-indigo {
            background: #eef2ff;
            color: #6366f1;
        }

        .stat-mini-val {
            font-size: 22px;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1;
        }

        .stat-mini-label {
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 2px;
        }

        /* Tips */
        .tips-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
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
            gap: 12px;
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
            background: var(--accent);
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
        <div class="toast error" id="toast">
            <i class='bx bx-x-circle'></i>
            <?= htmlspecialchars($toast['msg'], ENT_QUOTES) ?>
            <button class="toast-close" onclick="dismissToast()">&times;</button>
        </div>
    <?php endif; ?>

    <!-- ── Page header ─────────────────────────────────────────────── -->
    <div class="page-header">
        <nav class="breadcrumb">
            <a href="dashboard.php"><i class='bx bx-home-alt'></i> Dashboard</a>
            <i class='bx bx-chevron-right'></i>
            <a href="AllBooks.php">Books</a>
            <i class='bx bx-chevron-right'></i>
            <span>Add New</span>
        </nav>
        <h1>Add New Book</h1>
    </div>

    <!-- ── Page grid ──────────────────────────────────────────────── -->
    <div class="page-grid">

        <!-- ── LEFT: Form ───────────────────────────────── -->
        <div class="card">
            <div class="card-accent-bar"></div>
            <div class="card-header">
                <div class="card-header-icon"><i class='bx bx-book-add'></i></div>
                <div class="card-header-text">
                    <h2>Book Details</h2>
                    <p>Fill in the information below and submit</p>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="AddBook.php" enctype="multipart/form-data" id="bookForm" novalidate>

                    <!-- ── Core info ── -->
                    <div class="section-title">Core Information</div>

                    <!-- Title -->
                    <div class="form-group">
                        <label for="title" class="form-label">
                            <span>Book Title <span class="req">*</span></span>
                            <span class="hint">max 255</span>
                        </label>
                        <div class="input-wrap">
                            <div class="input-icon"><i class='bx bx-book-alt'></i></div>
                            <input type="text" id="title" name="title"
                                class="form-input <?= !empty($errors['title']) ? 'has-error' : '' ?>"
                                value="<?= htmlspecialchars($old['title'] ?? '', ENT_QUOTES) ?>"
                                placeholder="e.g. Clean Code" maxlength="255" autocomplete="off" autofocus>
                        </div>
                        <?php if (!empty($errors['title'])): ?>
                            <div class="field-error"><i
                                    class='bx bx-error-circle'></i><?= htmlspecialchars($errors['title'], ENT_QUOTES) ?>
                            </div>
                        <?php endif; ?>
                        <span class="char-count" id="titleCount"><?= mb_strlen($old['title'] ?? '') ?> / 255</span>
                    </div>

                    <div class="form-row-2">
                        <!-- ISBN -->
                        <div class="form-group">
                            <label for="isbn" class="form-label">
                                <span>ISBN <span class="req">*</span></span>
                            </label>
                            <div class="input-wrap">
                                <div class="input-icon"><i class='bx bx-barcode'></i></div>
                                <input type="text" id="isbn" name="isbn"
                                    class="form-input <?= !empty($errors['isbn']) ? 'has-error' : '' ?>"
                                    value="<?= htmlspecialchars($old['isbn'] ?? '', ENT_QUOTES) ?>"
                                    placeholder="978-3-16-148410-0" maxlength="20" autocomplete="off">
                            </div>
                            <?php if (!empty($errors['isbn'])): ?>
                                <div class="field-error"><i
                                        class='bx bx-error-circle'></i><?= htmlspecialchars($errors['isbn'], ENT_QUOTES) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Category -->
                        <div class="form-group">
                            <label for="category_id" class="form-label">
                                <span>Category <span class="req">*</span></span>
                            </label>
                            <div class="input-wrap">
                                <div class="input-icon"><i class='bx bx-collection'></i></div>
                                <select id="category_id" name="category_id"
                                    class="form-input <?= !empty($errors['category_id']) ? 'has-error' : '' ?>">
                                    <option value="0">— Select category —</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= (int) $cat['id'] ?>" <?= ((int) ($old['category_id'] ?? 0) === (int) $cat['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (!empty($errors['category_id'])): ?>
                                <div class="field-error"><i
                                        class='bx bx-error-circle'></i><?= htmlspecialchars($errors['category_id'], ENT_QUOTES) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ── Publishing ── -->
                    <div class="section-title">Publishing</div>

                    <div class="form-row-3">
                        <!-- Publisher -->
                        <div class="form-group">
                            <label for="publishers" class="form-label">Publisher</label>
                            <div class="input-wrap">
                                <div class="input-icon"><i class='bx bx-printer'></i></div>
                                <input type="text" id="publishers" name="publishers" class="form-input"
                                    value="<?= htmlspecialchars($old['publishers'] ?? '', ENT_QUOTES) ?>"
                                    placeholder="e.g. O'Reilly" maxlength="150" autocomplete="off">
                            </div>
                        </div>

                        <!-- Price -->
                        <div class="form-group">
                            <label for="price" class="form-label">
                                <span>Price</span>
                                <span class="hint">numeric</span>
                            </label>
                            <div class="input-wrap">
                                <div class="input-icon"><i class='bx bx-dollar'></i></div>
                                <span class="price-prefix">PKR</span>
                                <input type="text" id="price" name="price"
                                    class="form-input has-prefix <?= !empty($errors['price']) ? 'has-error' : '' ?>"
                                    value="<?= htmlspecialchars($old['price'] ?? '', ENT_QUOTES) ?>" placeholder="0.00"
                                    inputmode="decimal" autocomplete="off">
                            </div>
                            <?php if (!empty($errors['price'])): ?>
                                <div class="field-error"><i
                                        class='bx bx-error-circle'></i><?= htmlspecialchars($errors['price'], ENT_QUOTES) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Length / Pages -->
                        <div class="form-group">
                            <label for="length" class="form-label">Pages</label>
                            <div class="input-wrap">
                                <div class="input-icon"><i class='bx bx-layer'></i></div>
                                <input type="text" id="length" name="length" class="form-input"
                                    value="<?= htmlspecialchars($old['length'] ?? '', ENT_QUOTES) ?>"
                                    placeholder="e.g. 464" maxlength="10" inputmode="numeric" autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <!-- ── Details ── -->
                    <div class="section-title">Details</div>

                    <div class="form-row-2">
                        <!-- Subjects -->
                        <div class="form-group">
                            <label for="subjects" class="form-label">Subjects</label>
                            <div class="input-wrap">
                                <div class="input-icon"><i class='bx bx-tag'></i></div>
                                <input type="text" id="subjects" name="subjects" class="form-input"
                                    value="<?= htmlspecialchars($old['subjects'] ?? '', ENT_QUOTES) ?>"
                                    placeholder="e.g. Programming, Software" maxlength="255" autocomplete="off">
                            </div>
                        </div>

                        <!-- Contributors -->
                        <div class="form-group">
                            <label for="contributors" class="form-label">Contributors</label>
                            <div class="input-wrap">
                                <div class="input-icon"><i class='bx bx-user-check'></i></div>
                                <input type="text" id="contributors" name="contributors" class="form-input"
                                    value="<?= htmlspecialchars($old['contributors'] ?? '', ENT_QUOTES) ?>"
                                    placeholder="Chapter authors, editors…" maxlength="255" autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label for="description" class="form-label">
                            <span>Description</span>
                            <span class="hint">optional</span>
                        </label>
                        <textarea id="description" name="description" class="form-input no-icon" rows="4"
                            maxlength="2000"
                            placeholder="Write a short synopsis or overview of the book…"><?= htmlspecialchars($old['description'] ?? '', ENT_QUOTES) ?></textarea>
                        <span class="char-count" id="descCount"><?= mb_strlen($old['description'] ?? '') ?> /
                            2000</span>
                    </div>

                    <!-- ── Cover image ── -->
                    <div class="section-title">Cover Image</div>

                    <div class="form-group">
                        <div class="upload-zone" id="uploadZone">
                            <input type="file" name="img" id="imgInput" accept=".jpg,.jpeg,.png,.webp">
                            <span class="upload-icon"><i class='bx bx-image-add'></i></span>
                            <div class="upload-label">Drop cover image here or <span>browse</span></div>
                            <div class="upload-hint"><span>JPG</span>, <span>PNG</span>, <span>WEBP</span> — max 5 MB
                            </div>
                            <div class="preview-wrap" id="previewWrap">
                                <img id="previewImg" class="preview-img" src="" alt="Cover Preview">
                                <button type="button" class="preview-remove" id="removePreview" title="Remove">
                                    <i class='bx bx-x'></i>
                                </button>
                            </div>
                        </div>
                        <?php if (!empty($errors['img'])): ?>
                            <div class="field-error" style="margin-top:8px">
                                <i class='bx bx-error-circle'></i><?= htmlspecialchars($errors['img'], ENT_QUOTES) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-divider"></div>

                    <div class="form-actions">
                        <a href="AllBooks.php" class="btn-cancel">
                            <i class='bx bx-arrow-back'></i> Cancel
                        </a>
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <span class="btn-spinner" id="btnSpinner"></span>
                            <i class='bx bx-plus' id="btnIcon"></i>
                            <span id="btnText">Add Book</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <!-- ── RIGHT panel ──────────────────────────────── -->
        <div class="right-panel">

            <!-- Live book card preview -->
            <div class="cover-card">
                <div class="cover-preview-area">
                    <div class="cover-placeholder" id="coverPlaceholder">
                        <i class='bx bx-book'></i>
                    </div>
                    <img id="coverPreviewImg" class="cover-img-preview" src="" alt="">
                    <div class="cover-title-preview" id="previewTitle">Book Title</div>
                    <div class="cover-isbn-preview" id="previewIsbn">ISBN —</div>
                </div>
                <div class="cover-card-body">
                    <div class="meta-row">
                        <i class='bx bx-collection'></i>
                        <div class="meta-content">
                            <div class="meta-label">Category</div>
                            <div class="meta-val empty" id="previewCat">Not selected</div>
                        </div>
                    </div>
                    <div class="meta-row">
                        <i class='bx bx-printer'></i>
                        <div class="meta-content">
                            <div class="meta-label">Publisher</div>
                            <div class="meta-val empty" id="previewPub">—</div>
                        </div>
                    </div>
                    <div class="meta-row">
                        <i class='bx bx-dollar'></i>
                        <div class="meta-content">
                            <div class="meta-label">Price</div>
                            <div class="meta-val empty" id="previewPrice">—</div>
                        </div>
                    </div>
                    <div class="meta-row">
                        <i class='bx bx-layer'></i>
                        <div class="meta-content">
                            <div class="meta-label">Pages</div>
                            <div class="meta-val empty" id="previewPages">—</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stat-mini">
                <div class="stat-mini-icon si-green"><i class='bx bx-book-alt'></i></div>
                <div>
                    <div class="stat-mini-val"><?= number_format($totalBooks) ?></div>
                    <div class="stat-mini-label">Books in library</div>
                </div>
            </div>

            <div class="stat-mini">
                <div class="stat-mini-icon si-indigo"><i class='bx bx-collection'></i></div>
                <div>
                    <div class="stat-mini-val"><?= number_format($totalCats) ?></div>
                    <div class="stat-mini-label">Categories available</div>
                </div>
            </div>

            <!-- Tips -->
            <div class="tips-card">
                <div class="tips-header"><i class='bx bx-bulb'></i> Tips</div>
                <div class="tips-body">
                    <div class="tip-row">
                        <div class="tip-dot"></div>
                        <div class="tip-text"><strong>ISBN must be unique.</strong> The system checks for duplicates
                            automatically before saving.</div>
                    </div>
                    <div class="tip-row">
                        <div class="tip-dot" style="background:var(--success)"></div>
                        <div class="tip-text"><strong>Cover image.</strong> Use portrait images (3:4 ratio) for the best
                            display — minimum 300×400 px.</div>
                    </div>
                    <div class="tip-row">
                        <div class="tip-dot" style="background:var(--warning)"></div>
                        <div class="tip-text"><strong>Authors.</strong> Assign authors separately from the Authors
                            section after adding the book.</div>
                    </div>
                </div>
            </div>

        </div><!-- /.right-panel -->

    </div><!-- /.page-grid -->

    </div><!-- /.dash-content -->
    </section><!-- /.home-section -->

    <script>
        (() => {
            /* ── Char counters ──────────────────────────── */
            function counter(id, countId, max) {
                const el = document.getElementById(id);
                const ct = document.getElementById(countId);
                if (!el || !ct) return;
                const upd = () => {
                    const n = el.value.length;
                    ct.textContent = `${n} / ${max}`;
                    ct.className = 'char-count' + (n >= max ? ' over' : n >= max * .8 ? ' warn' : '');
                };
                el.addEventListener('input', upd);
                upd();
            }
            counter('title', 'titleCount', 255);
            counter('description', 'descCount', 2000);

            /* ── Live preview wiring ────────────────────── */
            function wire(inputId, previewId, emptyText, transform) {
                const el = document.getElementById(inputId);
                const pre = document.getElementById(previewId);
                if (!el || !pre) return;
                el.addEventListener('input', () => {
                    const v = transform ? transform(el.value.trim()) : el.value.trim();
                    pre.textContent = v || emptyText;
                    pre.className = 'meta-val' + (v ? '' : ' empty');
                });
            }

            // Title → card header
            const titleInput = document.getElementById('title');
            const previewTitle = document.getElementById('previewTitle');
            titleInput?.addEventListener('input', () => {
                previewTitle.textContent = titleInput.value.trim() || 'Book Title';
                titleInput.classList.remove('has-error');
            });

            // ISBN
            const isbnInput = document.getElementById('isbn');
            const previewIsbn = document.getElementById('previewIsbn');
            isbnInput?.addEventListener('input', () => {
                previewIsbn.textContent = isbnInput.value.trim() ? 'ISBN ' + isbnInput.value.trim() : 'ISBN —';
                isbnInput.classList.remove('has-error');
            });

            // Category
            const catSel = document.getElementById('category_id');
            const prevCat = document.getElementById('previewCat');
            catSel?.addEventListener('change', () => {
                const opt = catSel.options[catSel.selectedIndex];
                const v = catSel.value !== '0' ? opt.textContent.trim() : '';
                prevCat.textContent = v || 'Not selected';
                prevCat.className = 'meta-val' + (v ? '' : ' empty');
                catSel.classList.remove('has-error');
            });

            wire('publishers', 'previewPub', '—');
            wire('price', 'previewPrice', '—', v => v ? 'PKR ' + v : '');
            wire('length', 'previewPages', '—', v => v ? v + ' pages' : '');

            /* ── Image upload + preview ─────────────────── */
            const imgInput = document.getElementById('imgInput');
            const previewWrap = document.getElementById('previewWrap');
            const previewImg = document.getElementById('previewImg');
            const removeBtn = document.getElementById('removePreview');
            const uploadZone = document.getElementById('uploadZone');
            const coverImg = document.getElementById('coverPreviewImg');
            const coverPlaceholder = document.getElementById('coverPlaceholder');

            imgInput?.addEventListener('change', () => showPreview(imgInput.files[0]));

            uploadZone?.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
            uploadZone?.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
            uploadZone?.addEventListener('drop', e => {
                e.preventDefault(); uploadZone.classList.remove('drag-over');
                if (e.dataTransfer.files[0]) showPreview(e.dataTransfer.files[0]);
            });

            function showPreview(file) {
                if (!file?.type.startsWith('image/')) return;
                const r = new FileReader();
                r.onload = e => {
                    previewImg.src = e.target.result;
                    coverImg.src = e.target.result;
                    previewWrap.classList.add('show');
                    coverImg.classList.add('show');
                    coverPlaceholder.style.display = 'none';
                };
                r.readAsDataURL(file);
            }

            removeBtn?.addEventListener('click', e => {
                e.stopPropagation();
                imgInput.value = '';
                previewImg.src = '';
                coverImg.src = '';
                previewWrap.classList.remove('show');
                coverImg.classList.remove('show');
                coverPlaceholder.style.display = '';
            });

            /* ── Submit loading state ───────────────────── */
            const form = document.getElementById('bookForm');
            const submitBtn = document.getElementById('submitBtn');
            const spinner = document.getElementById('btnSpinner');
            const btnIcon = document.getElementById('btnIcon');
            const btnText = document.getElementById('btnText');

            form?.addEventListener('submit', e => {
                let ok = true;
                const titleEl = document.getElementById('title');
                const isbnEl = document.getElementById('isbn');
                const catEl = document.getElementById('category_id');

                if (!titleEl.value.trim()) { titleEl.classList.add('has-error'); ok = false; }
                if (!isbnEl.value.trim()) { isbnEl.classList.add('has-error'); ok = false; }
                if (catEl.value === '0') { catEl.classList.add('has-error'); ok = false; }

                if (!ok) { e.preventDefault(); return; }

                submitBtn.classList.add('loading');
                spinner.style.display = 'block';
                btnIcon.style.display = 'none';
                btnText.textContent = 'Adding…';
            });

            /* ── Toast dismiss ──────────────────────────── */
            const toast = document.getElementById('toast');
            if (toast) {
                const t = setTimeout(dismissToast, 4500);
                toast.addEventListener('click', () => { clearTimeout(t); dismissToast(); });
            }
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