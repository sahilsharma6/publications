<?php
session_start();
include '../../../db.php';

/* ── Auth Guard ──────────────────────────────────────────────────────────── */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'SuperAdmin'])) {
    header("Location: login.php");
    exit();
}

/* ── Validate ID ─────────────────────────────────────────────────────────── */
$author_id = (int) ($_GET['id'] ?? $_POST['author_id'] ?? 0);
if ($author_id <= 0) {
    header("Location: AllAuthors.php");
    exit();
}

/* ── Fetch author ────────────────────────────────────────────────────────── */
$stmt = $conn->prepare("SELECT id, name, title, description, image FROM authors WHERE id = ?");
$stmt->bind_param("i", $author_id);
$stmt->execute();
$res = $stmt->get_result();
$author = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$author) {
    header("Location: AllAuthors.php?toast=notfound");
    exit();
}

/* ── Current assigned book ───────────────────────────────────────────────── */
$baStmt = $conn->prepare(
    "SELECT ba.book_id FROM book_authors ba WHERE ba.author_id = ? LIMIT 1"
);
$baStmt->bind_param("i", $author_id);
$baStmt->execute();
$baStmt->bind_result($currentBookId);
$baStmt->fetch();
$baStmt->close();
$currentBookId = (int) ($currentBookId ?? 0);

/* ── Fetch all books ─────────────────────────────────────────────────────── */
$booksRes = $conn->query("SELECT id, name FROM books_data ORDER BY name ASC");
$books = $booksRes ? $booksRes->fetch_all(MYSQLI_ASSOC) : [];

/* ── State ───────────────────────────────────────────────────────────────── */
$errors = [];
$toast = null;

/* ── Handle POST ─────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $newBookId = (int) ($_POST['book_id'] ?? 0);
    $removeImg = isset($_POST['remove_image']);

    /* ── Validation ── */
    if ($name === '') {
        $errors['name'] = 'Author name is required.';
    } elseif (mb_strlen($name) < 2) {
        $errors['name'] = 'Name must be at least 2 characters.';
    } elseif (mb_strlen($name) > 120) {
        $errors['name'] = 'Name must be 120 characters or fewer.';
    }

    if (mb_strlen($title) > 100) {
        $errors['title'] = 'Title must be 100 characters or fewer.';
    }

    /* ── Image handling ── */
    $imageName = $author['image']; // keep existing by default

    if ($removeImg) {
        // Delete old file
        if ($imageName) {
            $old = "../../uploads/authors/" . $imageName;
            if (file_exists($old))
                @unlink($old);
        }
        $imageName = null;
    }

    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../../uploads/authors/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            $errors['image'] = 'Only JPG, PNG, WEBP files are allowed.';
        } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
            $errors['image'] = 'Image must be under 3 MB.';
        } else {
            // Remove old image before replacing
            if ($imageName) {
                $old = "../../uploads/authors/" . $imageName;
                if (file_exists($old))
                    @unlink($old);
            }
            $imageName = uniqid('author_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName)) {
                $errors['image'] = 'Upload failed. Check folder permissions.';
                $imageName = $author['image'];
            }
        }
    }

    /* ── Update ── */
    if (empty($errors)) {
        $upd = $conn->prepare(
            "UPDATE authors SET name=?, title=?, description=?, image=? WHERE id=?"
        );
        $upd->bind_param("ssssi", $name, $title, $description, $imageName, $author_id);

        if ($upd->execute()) {
            $upd->close();

            /* ── Update book assignment ── */
            // Remove existing
            $delPivot = $conn->prepare("DELETE FROM book_authors WHERE author_id = ?");
            $delPivot->bind_param("i", $author_id);
            $delPivot->execute();
            $delPivot->close();

            // Insert new if chosen
            if ($newBookId > 0) {
                $insPivot = $conn->prepare("INSERT IGNORE INTO book_authors (book_id, author_id) VALUES (?, ?)");
                $insPivot->bind_param("ii", $newBookId, $author_id);
                $insPivot->execute();
                $insPivot->close();
            }

            $conn->close();
            header("Location: AllAuthors.php?toast=updated&author=" . urlencode($name));
            exit();

        } else {
            $toast = ['type' => 'error', 'msg' => 'Database error: ' . htmlspecialchars($conn->error, ENT_QUOTES)];
        }
    }

    // Re-hydrate author with posted values on error
    $author['name'] = htmlspecialchars($name, ENT_QUOTES);
    $author['title'] = htmlspecialchars($title, ENT_QUOTES);
    $author['description'] = htmlspecialchars($description, ENT_QUOTES);
    $currentBookId = $newBookId;
}

$conn->close();

$imgPath = "../../uploads/authors/" . ($author['image'] ?? '');
$hasImg = !empty($author['image']) && file_exists($imgPath);
$initial = strtoupper(substr($author['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Author — BookAdmin</title>
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            20% {
                transform: translateX(-6px);
            }

            40% {
                transform: translateX(6px);
            }

            60% {
                transform: translateX(-3px);
            }

            80% {
                transform: translateX(3px);
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ── Page header ─────────────────────────────────── */
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

        @media (max-width: 860px) {
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
            background: linear-gradient(90deg, #f59e0b, #ef4444, #ec4899);
        }

        .card-header {
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header-icon {
            width: 38px;
            height: 38px;
            border-radius: var(--r);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
            background: #fff7ed;
            color: #f59e0b;
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
            margin: 24px 0 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-100);
        }

        .section-title:first-child {
            margin-top: 0;
        }

        /* ── Form row ────────────────────────────────────── */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 540px) {
            .form-row {
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

        .form-label .lbl-hint {
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

        textarea.form-input {
            resize: vertical;
            min-height: 100px;
            padding-top: 12px;
            line-height: 1.55;
        }

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

        /* ── Image section ───────────────────────────────── */
        .img-edit-wrap {
            display: flex;
            gap: 18px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        /* Current photo */
        .current-photo-wrap {
            flex-shrink: 0;
        }

        .current-photo {
            width: 90px;
            height: 90px;
            border-radius: var(--r-lg);
            object-fit: cover;
            display: block;
            border: 2px solid var(--gray-200);
        }

        .current-initials {
            width: 90px;
            height: 90px;
            border-radius: var(--r-lg);
            background: linear-gradient(135deg, var(--accent-light), #e0e7ff);
            color: var(--accent);
            font-size: 32px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(59, 130, 246, .15);
        }

        .current-photo-label {
            font-size: 11px;
            color: var(--gray-400);
            text-align: center;
            margin-top: 5px;
        }

        /* Upload zone */
        .upload-zone {
            flex: 1;
            min-width: 180px;
            border: 2px dashed var(--gray-200);
            border-radius: var(--r-lg);
            padding: 18px 14px;
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
            font-size: 26px;
            color: var(--gray-400);
            margin-bottom: 6px;
            display: block;
        }

        .upload-zone-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 3px;
        }

        .upload-zone-hint {
            font-size: 11.5px;
            color: var(--gray-400);
        }

        .upload-zone-hint span {
            color: var(--accent);
            font-weight: 500;
        }

        /* New preview inside zone */
        .new-preview-wrap {
            display: none;
            margin-top: 10px;
            position: relative;
            width: 64px;
            margin: 10px auto 0;
        }

        .new-preview-wrap.show {
            display: block;
        }

        .new-preview {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: var(--r);
            border: 2px solid var(--gray-200);
        }

        .preview-remove {
            position: absolute;
            top: -7px;
            right: -7px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--danger);
            color: #fff;
            font-size: 12px;
            border: 2px solid #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--t);
        }

        .preview-remove:hover {
            background: #dc2626;
        }

        /* Remove existing checkbox */
        .remove-img-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            font-size: 12.5px;
            color: var(--gray-500);
        }

        .remove-img-row input[type="checkbox"] {
            accent-color: var(--danger);
            width: 14px;
            height: 14px;
        }

        .remove-img-row label {
            cursor: pointer;
            color: var(--danger);
            font-weight: 500;
        }

        /* ── Book select ─────────────────────────────────── */
        select.form-input {
            padding-left: 54px;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24'%3E%3Cpath fill='%239ca3af' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }

        /* ── Divider ─────────────────────────────────────── */
        .form-divider {
            height: 1px;
            background: var(--gray-100);
            margin: 22px -26px;
        }

        /* ── Actions ─────────────────────────────────────── */
        .form-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-save {
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

        .btn-save:hover {
            background: var(--accent-dark);
            box-shadow: 0 4px 20px rgba(59, 130, 246, .4);
            transform: translateY(-1px);
        }

        .btn-save:active {
            transform: translateY(0);
        }

        .btn-save.loading {
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

        /* Author preview card */
        .preview-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            position: sticky;
            top: 84px;
        }

        .preview-card-top {
            background: linear-gradient(135deg, #1e1b4b, #312e81);
            padding: 28px 20px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .preview-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, .3);
        }

        .preview-initials-big {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .1);
            color: #fff;
            font-size: 30px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid rgba(255, 255, 255, .2);
        }

        .preview-name {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            text-align: center;
        }

        .preview-title {
            font-size: 12px;
            color: rgba(255, 255, 255, .6);
            text-align: center;
        }

        .preview-body {
            padding: 16px;
        }

        .preview-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 9px 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .preview-row:last-child {
            border-bottom: none;
        }

        .preview-row i {
            font-size: 16px;
            color: var(--gray-400);
            flex-shrink: 0;
            margin-top: 1px;
        }

        .preview-row-content {
            font-size: 13px;
            color: var(--gray-700);
            line-height: 1.4;
        }

        .preview-row-label {
            font-size: 11px;
            color: var(--gray-400);
            margin-bottom: 2px;
        }

        /* Danger zone */
        .danger-zone {
            background: #fff;
            border: 1px solid #fecaca;
            border-radius: var(--r-lg);
            padding: 16px;
            box-shadow: var(--shadow-sm);
        }

        .danger-zone-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: var(--danger);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .danger-zone-title i {
            font-size: 15px;
        }

        .danger-zone p {
            font-size: 12.5px;
            color: var(--gray-500);
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .btn-danger {
            width: 100%;
            padding: 10px;
            background: var(--danger-bg);
            color: var(--danger);
            border: 1.5px solid rgba(239, 68, 68, .25);
            border-radius: var(--r);
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: all var(--t);
        }

        .btn-danger:hover {
            background: var(--danger);
            color: #fff;
            border-color: var(--danger);
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
            <p>You are about to permanently delete <strong>
                    <?= htmlspecialchars($author['name'], ENT_QUOTES) ?>
                </strong> and all their book assignments.</p>
            <form method="POST" action="AllAuthors.php" class="modal-btns">
                <input type="hidden" name="delete_id" value="<?= $author_id ?>">
                <button type="button" class="btn-cancel-modal" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-del-confirm">
                    <span class="spinner" id="delSpinner"></span>
                    <span id="delTxt">Delete</span>
                </button>
            </form>
        </div>
    </div>

    <!-- ── Page header ─────────────────────────────────────────────── -->
    <div class="page-header">
        <nav class="breadcrumb">
            <a href="../"><i class='bx bx-home-alt'></i> Dashboard</a>
            <i class='bx bx-chevron-right'></i>
            <a href="AllAuthors.php">Authors</a>
            <i class='bx bx-chevron-right'></i>
            <span>Edit</span>
        </nav>
        <h1>Edit Author</h1>
    </div>

    <!-- ── Page grid ──────────────────────────────────────────────── -->
    <div class="page-grid">

        <!-- ── LEFT: Edit form ──────────────────────────── -->
        <div class="card">
            <div class="card-accent-bar"></div>
            <div class="card-header">
                <div class="card-header-icon"><i class='bx bx-edit'></i></div>
                <div class="card-header-text">
                    <h2>Edit Author Details</h2>
                    <p>Update the information below and save</p>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="EditAuthor.php?id=<?= $author_id ?>" id="editForm"
                    enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="author_id" value="<?= $author_id ?>">

                    <!-- ── Basic info ── -->
                    <div class="section-title">Basic Information</div>

                    <div class="form-row">
                        <!-- Name -->
                        <div class="form-group">
                            <label for="author_name" class="form-label">
                                <span>Full Name <span class="req">*</span></span>
                                <span class="lbl-hint">max 120</span>
                            </label>
                            <div class="input-wrap">
                                <div class="input-icon"><i class='bx bx-user'></i></div>
                                <input type="text" id="author_name" name="name"
                                    class="form-input <?= !empty($errors['name']) ? 'has-error' : '' ?>"
                                    value="<?= htmlspecialchars($author['name'], ENT_QUOTES) ?>" maxlength="120"
                                    autocomplete="off" autofocus>
                            </div>
                            <?php if (!empty($errors['name'])): ?>
                                <div class="field-error"><i class='bx bx-error-circle'></i>
                                    <?= htmlspecialchars($errors['name'], ENT_QUOTES) ?>
                                </div>
                            <?php endif; ?>
                            <span class="char-count" id="nameCount">
                                <?= mb_strlen($author['name']) ?> / 120
                            </span>
                        </div>

                        <!-- Title -->
                        <div class="form-group">
                            <label for="author_title" class="form-label">
                                <span>Title / Role</span>
                                <span class="lbl-hint">optional</span>
                            </label>
                            <div class="input-wrap">
                                <div class="input-icon"><i class='bx bx-badge'></i></div>
                                <input type="text" id="author_title" name="title"
                                    class="form-input <?= !empty($errors['title']) ? 'has-error' : '' ?>"
                                    value="<?= htmlspecialchars($author['title'] ?? '', ENT_QUOTES) ?>" maxlength="100"
                                    autocomplete="off">
                            </div>
                            <?php if (!empty($errors['title'])): ?>
                                <div class="field-error"><i class='bx bx-error-circle'></i>
                                    <?= htmlspecialchars($errors['title'], ENT_QUOTES) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Biography -->
                    <div class="form-group">
                        <label for="author_desc" class="form-label">
                            <span>Biography</span>
                            <span class="lbl-hint">optional</span>
                        </label>
                        <textarea id="author_desc" name="description" class="form-input no-icon" rows="4"
                            maxlength="1000"><?= htmlspecialchars($author['description'] ?? '', ENT_QUOTES) ?></textarea>
                        <span class="char-count" id="descCount">
                            <?= mb_strlen($author['description'] ?? '') ?> / 1000
                        </span>
                    </div>

                    <!-- ── Photo ── -->
                    <div class="section-title">Photo</div>

                    <div class="form-group">
                        <div class="img-edit-wrap">

                            <!-- Current photo -->
                            <div class="current-photo-wrap">
                                <?php if ($hasImg): ?>
                                    <img src="<?= $imgPath ?>" class="current-photo" alt="" id="currentPhoto">
                                <?php else: ?>
                                    <div class="current-initials" id="currentPhoto">
                                        <?= $initial ?>
                                    </div>
                                <?php endif; ?>
                                <div class="current-photo-label">Current</div>

                                <?php if ($hasImg): ?>
                                    <div class="remove-img-row">
                                        <input type="checkbox" name="remove_image" id="removeImgCb" value="1">
                                        <label for="removeImgCb">Remove photo</label>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Upload zone -->
                            <div class="upload-zone" id="uploadZone">
                                <input type="file" name="image" id="imageInput" accept=".jpg,.jpeg,.png,.webp">
                                <span class="upload-zone-icon"><i class='bx bx-cloud-upload'></i></span>
                                <div class="upload-zone-label">Replace photo</div>
                                <div class="upload-zone-hint"><span>JPG</span>, <span>PNG</span>, <span>WEBP</span> ·
                                    max 3 MB</div>
                                <div class="new-preview-wrap" id="newPreviewWrap">
                                    <img id="newPreview" class="new-preview" src="" alt="">
                                    <button type="button" class="preview-remove" id="removeNewImg">
                                        <i class='bx bx-x'></i>
                                    </button>
                                </div>
                            </div>

                        </div>
                        <?php if (!empty($errors['image'])): ?>
                            <div class="field-error" style="margin-top:8px">
                                <i class='bx bx-error-circle'></i>
                                <?= htmlspecialchars($errors['image'], ENT_QUOTES) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ── Book ── -->
                    <div class="section-title">Book Assignment</div>

                    <div class="form-group">
                        <label for="book_id" class="form-label">
                            <span>Assigned Book</span>
                            <span class="lbl-hint">one book per author</span>
                        </label>
                        <div class="input-wrap">
                            <div class="input-icon"><i class='bx bx-book-open'></i></div>
                            <select name="book_id" id="book_id" class="form-input">
                                <option value="0">— No book —</option>
                                <?php foreach ($books as $b): ?>
                                    <option value="<?= (int) $b['id'] ?>" <?= $currentBookId === (int) $b['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['name'], ENT_QUOTES) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-divider"></div>

                    <div class="form-actions">
                        <a href="AllAuthors.php" class="btn-cancel">
                            <i class='bx bx-arrow-back'></i> Cancel
                        </a>
                        <button type="submit" class="btn-save" id="saveBtn">
                            <span class="btn-spinner" id="btnSpinner"></span>
                            <i class='bx bx-check' id="btnIcon"></i>
                            <span id="btnText">Save Changes</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <!-- ── RIGHT panel ──────────────────────────────── -->
        <div class="right-panel">

            <!-- Live preview card -->
            <div class="preview-card">
                <div class="preview-card-top">
                    <?php if ($hasImg): ?>
                        <img src="<?= $imgPath ?>" class="preview-avatar" id="previewAvatar" alt="">
                    <?php else: ?>
                        <div class="preview-initials-big" id="previewInitials">
                            <?= $initial ?>
                        </div>
                    <?php endif; ?>
                    <div class="preview-name" id="previewName">
                        <?= htmlspecialchars($author['name'], ENT_QUOTES) ?>
                    </div>
                    <div class="preview-title" id="previewTitle">
                        <?= htmlspecialchars($author['title'] ?? '', ENT_QUOTES) ?>
                    </div>
                </div>
                <div class="preview-body">
                    <div class="preview-row">
                        <i class='bx bx-hash'></i>
                        <div class="preview-row-content">
                            <div class="preview-row-label">Author ID</div>
                            #
                            <?= $author_id ?>
                        </div>
                    </div>
                    <div class="preview-row">
                        <i class='bx bx-book-alt'></i>
                        <div class="preview-row-content">
                            <div class="preview-row-label">Current Book</div>
                            <span id="previewBook">
                                <?php
                                if ($currentBookId > 0) {
                                    foreach ($books as $b) {
                                        if ((int) $b['id'] === $currentBookId) {
                                            echo htmlspecialchars($b['name'], ENT_QUOTES);
                                            break;
                                        }
                                    }
                                } else {
                                    echo '<span style="color:var(--gray-300)">None assigned</span>';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danger zone -->
            <div class="danger-zone">
                <div class="danger-zone-title"><i class='bx bx-error'></i> Danger Zone</div>
                <p>Deleting this author is permanent and removes all their book assignments.</p>
                <button class="btn-danger" onclick="openDeleteModal()">
                    <i class='bx bx-trash'></i> Delete Author
                </button>
            </div>

        </div><!-- /.right-panel -->

    </div><!-- /.page-grid -->

    </div><!-- /.dash-content -->
    </section><!-- /.home-section -->

    <script>
        (() => {
            /* ── Char counters ───────────────────────────── */
            function counter(inputId, countId, max) {
                const el = document.getElementById(inputId);
                const ct = document.getElementById(countId);
                if (!el || !ct) return;
                const upd = () => {
                    const len = el.value.length;
                    ct.textContent = `${len} / ${max}`;
                    ct.className = 'char-count' + (len >= max ? ' over' : len >= max * .8 ? ' warn' : '');
                };
                el.addEventListener('input', upd);
                upd();
            }
            counter('author_name', 'nameCount', 120);
            counter('author_desc', 'descCount', 1000);

            /* ── Live preview update ─────────────────────── */
            const nameInput = document.getElementById('author_name');
            const titleInput = document.getElementById('author_title');
            const prevName = document.getElementById('previewName');
            const prevTitle = document.getElementById('previewTitle');
            const prevInits = document.getElementById('previewInitials');

            nameInput?.addEventListener('input', () => {
                const v = nameInput.value.trim();
                if (prevName) prevName.textContent = v || 'Author Name';
                if (prevInits) prevInits.textContent = v ? v[0].toUpperCase() : '?';
                nameInput.classList.remove('has-error');
            });

            titleInput?.addEventListener('input', () => {
                if (prevTitle) prevTitle.textContent = titleInput.value.trim();
                titleInput.classList.remove('has-error');
            });

            /* Book dropdown → preview ── */
            const bookSel = document.getElementById('book_id');
            const prevBook = document.getElementById('previewBook');
            bookSel?.addEventListener('change', () => {
                const opt = bookSel.options[bookSel.selectedIndex];
                if (prevBook) {
                    prevBook.innerHTML = (bookSel.value === '0')
                        ? '<span style="color:var(--gray-300)">None assigned</span>'
                        : opt.textContent.trim();
                }
            });

            /* ── Image upload & preview ──────────────────── */
            const fileInput = document.getElementById('imageInput');
            const newPreviewW = document.getElementById('newPreviewWrap');
            const newPreview = document.getElementById('newPreview');
            const removeNewB = document.getElementById('removeNewImg');
            const uploadZone = document.getElementById('uploadZone');
            const removeCb = document.getElementById('removeImgCb');

            fileInput?.addEventListener('change', () => showNew(fileInput.files[0]));

            uploadZone?.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
            uploadZone?.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
            uploadZone?.addEventListener('drop', e => {
                e.preventDefault(); uploadZone.classList.remove('drag-over');
                if (e.dataTransfer.files[0]) showNew(e.dataTransfer.files[0]);
            });

            function showNew(file) {
                if (!file?.type.startsWith('image/')) return;
                const r = new FileReader();
                r.onload = e => {
                    newPreview.src = e.target.result;
                    newPreviewW.classList.add('show');
                    // update sidebar preview avatar
                    const pa = document.getElementById('previewAvatar');
                    if (pa) pa.src = e.target.result;
                };
                r.readAsDataURL(file);
            }

            removeNewB?.addEventListener('click', e => {
                e.stopPropagation();
                fileInput.value = '';
                newPreview.src = '';
                newPreviewW.classList.remove('show');
            });

            /* ── Submit loading state ────────────────────── */
            const form = document.getElementById('editForm');
            const saveBtn = document.getElementById('saveBtn');
            const spinner = document.getElementById('btnSpinner');
            const btnIcon = document.getElementById('btnIcon');
            const btnText = document.getElementById('btnText');

            form?.addEventListener('submit', e => {
                if (!nameInput.value.trim() || nameInput.value.trim().length < 2) {
                    e.preventDefault();
                    nameInput.classList.add('has-error');
                    nameInput.focus();
                    return;
                }
                saveBtn.classList.add('loading');
                spinner.style.display = 'block';
                btnIcon.style.display = 'none';
                btnText.textContent = 'Saving…';
            });

            /* ── Toast dismiss ───────────────────────────── */
            const toast = document.getElementById('toast');
            if (toast) {
                const t = setTimeout(dismissToast, 4500);
                toast.addEventListener('click', () => { clearTimeout(t); dismissToast(); });
            }
        })();

        /* ── Delete modal ────────────────────────────── */
        const overlay = document.getElementById('deleteOverlay');
        function openDeleteModal() { overlay.classList.add('open'); document.body.style.overflow = 'hidden'; }
        function closeModal() { overlay.classList.remove('open'); document.body.style.overflow = ''; }
        overlay?.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

        function dismissToast() {
            const t = document.getElementById('toast');
            if (!t) return;
            t.classList.add('hiding');
            setTimeout(() => t.remove(), 320);
        }
    </script>
</body>

</html>