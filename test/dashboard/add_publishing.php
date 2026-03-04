<?php
session_start();
include '../../db.php';

/* ── Auth Guard ──────────────────────────────────────────────────────────── */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'SuperAdmin'], true)) {
    header("Location: login.php");
    exit();
}

$role = htmlspecialchars($_SESSION['role'] ?? '', ENT_QUOTES);
$username = htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES);

$toast = null;

/* ── Handle ADD ──────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $description = trim($_POST['description'] ?? '');

    if (empty($_FILES['image']['name'])) {
        $toast = ['type' => 'error', 'msg' => 'Please select an image to upload.'];
    } elseif ($_FILES['image']['error'] !== 0) {
        $toast = ['type' => 'error', 'msg' => 'Upload error. Please try again.'];
    } else {
        $uploadDir = 'uploads/publishing/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];

        if (!in_array($ext, $allowed)) {
            $toast = ['type' => 'error', 'msg' => 'Only JPG, PNG, WEBP, GIF, SVG files are allowed.'];
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $toast = ['type' => 'error', 'msg' => 'Image must be under 5 MB.'];
        } else {
            $targetPath = $uploadDir . uniqid('pub_') . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $stmt = $conn->prepare("INSERT INTO publishing_images (image_path, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $targetPath, $description);
                $stmt->execute();
                $stmt->close();
                header("Location: add_publishing.php?toast=added");
                exit();
            } else {
                $toast = ['type' => 'error', 'msg' => 'Upload failed. Check folder permissions.'];
            }
        }
    }
}

/* ── Handle EDIT ─────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $editId = (int) ($_POST['edit_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($editId > 0) {
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
            $uploadDir = 'uploads/publishing/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];

            if (!in_array($ext, $allowed)) {
                $toast = ['type' => 'error', 'msg' => 'Only JPG, PNG, WEBP, GIF, SVG files are allowed.'];
            } else {
                // Delete old image
                $sel = $conn->prepare("SELECT image_path FROM publishing_images WHERE id = ?");
                $sel->bind_param("i", $editId);
                $sel->execute();
                $sel->bind_result($oldPath);
                $sel->fetch();
                $sel->close();
                if ($oldPath && file_exists($oldPath))
                    @unlink($oldPath);

                $targetPath = $uploadDir . uniqid('pub_') . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $stmt = $conn->prepare("UPDATE publishing_images SET image_path = ?, description = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $targetPath, $description, $editId);
                    $stmt->execute();
                    $stmt->close();
                    header("Location: add_publishing.php?toast=updated");
                    exit();
                } else {
                    $toast = ['type' => 'error', 'msg' => 'Upload failed. Check folder permissions.'];
                }
            }
        } else {
            $stmt = $conn->prepare("UPDATE publishing_images SET description = ? WHERE id = ?");
            $stmt->bind_param("si", $description, $editId);
            $stmt->execute();
            $stmt->close();
            header("Location: add_publishing.php?toast=updated");
            exit();
        }
    }
}

/* ── Handle DELETE ───────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $deleteId = (int) ($_POST['delete_id'] ?? 0);
    if ($deleteId > 0) {
        $sel = $conn->prepare("SELECT image_path FROM publishing_images WHERE id = ?");
        $sel->bind_param("i", $deleteId);
        $sel->execute();
        $sel->bind_result($imgPath);
        $sel->fetch();
        $sel->close();
        if ($imgPath && file_exists($imgPath))
            @unlink($imgPath);

        $del = $conn->prepare("DELETE FROM publishing_images WHERE id = ?");
        $del->bind_param("i", $deleteId);
        $del->execute();
        $del->close();
        header("Location: add_publishing.php?toast=deleted");
        exit();
    }
}

/* ── Toast from redirect ─────────────────────────────────────────────────── */
if (!$toast && isset($_GET['toast'])) {
    $toastMap = [
        'added' => ['type' => 'success', 'msg' => 'Image uploaded successfully.'],
        'updated' => ['type' => 'success', 'msg' => 'Image updated successfully.'],
        'deleted' => ['type' => 'success', 'msg' => 'Image deleted successfully.'],
    ];
    $toast = $toastMap[$_GET['toast']] ?? null;
}

/* ── Fetch all images ────────────────────────────────────────────────────── */
$imagesQuery = $conn->query("SELECT * FROM publishing_images ORDER BY id DESC");
$images = $imagesQuery ? $imagesQuery->fetch_all(MYSQLI_ASSOC) : [];
$totalImages = count($images);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publishing Images — BookAdmin</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">

    <style>
        /* ── Shared tokens (mirror AllCategories.css) ── */
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
            --warning-light: #fffbeb;
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

        /* ── Animations ─────────────────────────────── */
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

        .dash-content>*:nth-child(4) {
            animation-delay: .24s
        }

        /* ── Toast ──────────────────────────────────── */
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
            line-height: 1;
            transition: opacity var(--t);
            flex-shrink: 0;
        }

        .toast-close:hover {
            opacity: 1;
        }

        .toast.hiding {
            animation: toastOut .3s var(--t) forwards;
        }

        /* ── Page header ────────────────────────────── */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 26px;
            flex-wrap: wrap;
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

        /* ── Summary strip ──────────────────────────── */
        .summary-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        @media(max-width:700px) {
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

        .si-amber {
            background: #fffbeb;
            color: #f59e0b;
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

        /* ── Page grid ──────────────────────────────── */
        .page-grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 22px;
            align-items: start;
        }

        @media(max-width:960px) {
            .page-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── Upload card ────────────────────────────── */
        .upload-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
            position: sticky;
            top: 84px;
        }

        .card-accent-bar {
            height: 4px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899);
        }

        .card-header {
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--r);
            background: #f5f3ff;
            color: #8b5cf6;
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
            padding: 24px;
        }

        /* ── Form elements ──────────────────────────── */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 7px;
        }

        .form-label .hint {
            font-size: 11.5px;
            font-weight: 400;
            color: var(--gray-400);
            margin-left: 6px;
        }

        .form-input {
            width: 100%;
            padding: 11px 14px;
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

        textarea.form-input {
            resize: vertical;
            min-height: 90px;
            line-height: 1.55;
        }

        /* ── Upload zone ─────────────────────────────── */
        .upload-zone {
            border: 2px dashed var(--gray-200);
            border-radius: var(--r-lg);
            padding: 26px 20px;
            text-align: center;
            cursor: pointer;
            transition: all var(--t);
            position: relative;
            background: var(--gray-50);
        }

        .upload-zone:hover,
        .upload-zone.drag-over {
            border-color: #8b5cf6;
            background: #faf5ff;
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
            font-size: 34px;
            color: var(--gray-300);
            display: block;
            margin-bottom: 10px;
            transition: color var(--t);
        }

        .upload-zone:hover .upload-icon,
        .upload-zone.drag-over .upload-icon {
            color: #8b5cf6;
        }

        .upload-label {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .upload-hint {
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 4px;
        }

        .upload-hint span {
            color: #8b5cf6;
            font-weight: 500;
        }

        /* Image preview inside upload zone */
        .new-preview-wrap {
            display: none;
            margin-top: 14px;
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

        /* ── Submit button ───────────────────────────── */
        .btn-submit {
            width: 100%;
            padding: 12px 24px;
            background: #8b5cf6;
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
            box-shadow: 0 2px 10px rgba(139, 92, 246, .3);
        }

        .btn-submit:hover {
            background: #7c3aed;
            box-shadow: 0 4px 20px rgba(139, 92, 246, .45);
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

        /* ── Main content card ───────────────────────── */
        .main-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .main-card-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .main-card-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .main-card-title i {
            font-size: 20px;
            color: #8b5cf6;
        }

        .main-card-title h2 {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-800);
        }

        /* ── Search toolbar ──────────────────────────── */
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
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, .1);
        }

        /* ── Image grid ──────────────────────────────── */
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 18px;
            padding: 20px;
        }

        /* ── Image card ──────────────────────────────── */
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
            height: 180px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #f5f3ff, #ede9fe);
            cursor: pointer;
        }

        .img-card-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .4s var(--t);
        }

        .img-card:hover .img-card-thumb img {
            transform: scale(1.04);
        }

        /* Index badge */
        .img-index-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(15, 23, 42, .65);
            backdrop-filter: blur(6px);
            color: rgba(255, 255, 255, .85);
            font-size: 10.5px;
            font-family: "DM Mono", monospace;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 500;
        }

        /* Zoom icon overlay */
        .img-zoom-overlay {
            position: absolute;
            inset: 0;
            background: rgba(139, 92, 246, .0);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background var(--t);
            opacity: 0;
        }

        .img-card:hover .img-zoom-overlay {
            background: rgba(139, 92, 246, .25);
            opacity: 1;
        }

        .img-zoom-overlay i {
            font-size: 30px;
            color: #fff;
            filter: drop-shadow(0 2px 6px rgba(0, 0, 0, .3));
        }

        /* No image placeholder */
        .img-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--gray-300);
        }

        .img-placeholder i {
            font-size: 40px;
        }

        .img-placeholder span {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .img-card-body {
            padding: 14px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .img-card-desc {
            font-size: 13px;
            color: var(--gray-600);
            line-height: 1.45;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex: 1;
        }

        .img-card-desc.empty {
            color: var(--gray-300);
            font-style: italic;
        }

        .img-card-date {
            font-size: 11.5px;
            color: var(--gray-400);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .img-card-date i {
            font-size: 13px;
        }

        /* ── Card actions ────────────────────────────── */
        .img-card-actions {
            display: flex;
            border-top: 1px solid var(--gray-100);
        }

        .img-act-btn {
            flex: 1;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
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

        /* ── Empty state ─────────────────────────────── */
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
            max-width: 280px;
            margin-left: auto;
            margin-right: auto;
        }

        /* ── Edit modal ──────────────────────────────── */
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

        .edit-modal {
            background: #fff;
            border-radius: var(--r-xl);
            padding: 0;
            max-width: 480px;
            width: 94%;
            box-shadow: var(--shadow-lg);
            animation: scaleIn .25s var(--t) both;
            overflow: hidden;
        }

        .edit-modal-header {
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .edit-modal-icon {
            width: 38px;
            height: 38px;
            border-radius: var(--r);
            background: #eff6ff;
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
        }

        .edit-modal-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .edit-modal-close {
            margin-left: auto;
            background: none;
            border: none;
            font-size: 20px;
            color: var(--gray-400);
            cursor: pointer;
            transition: color var(--t);
            line-height: 1;
            padding: 2px;
        }

        .edit-modal-close:hover {
            color: var(--gray-800);
        }

        .edit-modal-body {
            padding: 22px 24px;
        }

        .edit-modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--gray-100);
            display: flex;
            gap: 10px;
        }

        /* Current image in edit modal */
        .edit-current-img {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--r-lg);
            margin-bottom: 16px;
        }

        .edit-current-img img {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: var(--r);
            border: 1px solid var(--gray-200);
        }

        .edit-current-img-info {
            font-size: 12px;
            color: var(--gray-500);
        }

        .edit-current-img-info strong {
            color: var(--gray-700);
            display: block;
            font-size: 13px;
            margin-bottom: 2px;
        }

        .btn-modal-cancel {
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

        .btn-modal-cancel:hover {
            background: var(--gray-100);
            border-color: var(--gray-300);
        }

        .btn-modal-save {
            flex: 1;
            padding: 11px;
            border: none;
            background: var(--accent);
            color: #fff;
            font-size: 13.5px;
            font-weight: 600;
            font-family: inherit;
            border-radius: var(--r);
            cursor: pointer;
            transition: all var(--t);
            box-shadow: 0 2px 10px rgba(59, 130, 246, .28);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-modal-save:hover {
            background: var(--accent-dark);
            box-shadow: 0 4px 18px rgba(59, 130, 246, .4);
        }

        /* ── Delete confirm modal ────────────────────── */
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
            color: var(--gray-400);
            text-align: center;
            line-height: 1.5;
            margin-bottom: 26px;
        }

        .del-modal p strong {
            color: var(--gray-700);
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
            transition: all var(--t);
            box-shadow: 0 2px 10px rgba(239, 68, 68, .3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-del-confirm:hover {
            background: #dc2626;
            box-shadow: 0 4px 18px rgba(239, 68, 68, .4);
        }

        .spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, .4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
            display: none;
        }

        /* ── Lightbox ────────────────────────────────── */
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

        .lightbox-caption {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, .6);
            color: rgba(255, 255, 255, .85);
            padding: 8px 20px;
            border-radius: 99px;
            font-size: 13px;
            backdrop-filter: blur(6px);
            text-align: center;
            max-width: 80vw;
        }

        /* ── No-results ──────────────────────────────── */
        .no-results {
            text-align: center;
            padding: 40px 24px;
            color: var(--gray-400);
            font-size: 13.5px;
        }
    </style>
</head>

<body>

    <?php include './sidebar.php'; ?>

    <!-- Toast -->
    <?php if ($toast): ?>
        <div class="toast <?= $toast['type'] ?>" id="toast">
            <i class='bx <?= $toast['type'] === 'success' ? 'bx-check-circle' : 'bx-x-circle' ?>'></i>
            <?= htmlspecialchars($toast['msg'], ENT_QUOTES) ?>
            <button class="toast-close" onclick="dismissToast()" aria-label="Close">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Delete confirm overlay -->
    <div class="overlay" id="deleteOverlay">
        <div class="del-modal">
            <div class="del-modal-icon"><i class='bx bx-trash'></i></div>
            <h3>Delete Image?</h3>
            <p>Are you sure you want to delete this image? This action <strong>cannot be undone</strong>.</p>
            <form method="POST" id="deleteForm" class="del-modal-btns">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="delete_id" id="deleteIdField">
                <button type="button" class="btn-del-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn-del-confirm" id="delConfirmBtn">
                    <span class="spinner" id="delSpinner"></span>
                    <span id="delBtnText">Delete</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Edit modal -->
    <div class="overlay" id="editOverlay">
        <div class="edit-modal">
            <div class="edit-modal-header">
                <div class="edit-modal-icon"><i class='bx bx-edit-alt'></i></div>
                <div class="edit-modal-title">Edit Image</div>
                <button class="edit-modal-close" onclick="closeEditModal()" aria-label="Close">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="edit-modal-body">
                <div class="edit-current-img" id="editCurrentImg">
                    <img id="editCurrentThumb" src="" alt="Current">
                    <div class="edit-current-img-info">
                        <strong>Current Image</strong>
                        Replace by uploading a new file below, or leave empty to keep it.
                    </div>
                </div>
                <form method="POST" action="add_publishing.php" enctype="multipart/form-data" id="editForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="edit_id" id="editIdField">

                    <div class="form-group">
                        <label class="form-label">Replace Image <span
                                style="font-size:11.5px;font-weight:400;color:var(--gray-400)">optional</span></label>
                        <div class="upload-zone" id="editUploadZone" style="padding:16px 14px;">
                            <input type="file" name="image" id="editImgInput" accept=".jpg,.jpeg,.png,.webp,.gif,.svg">
                            <span class="upload-icon" style="font-size:24px;margin-bottom:6px;"><i
                                    class='bx bx-image-add'></i></span>
                            <div class="upload-label" style="font-size:13px;">Drop new image or <span>browse</span>
                            </div>
                            <div class="upload-hint">JPG, PNG, WEBP — max 5 MB</div>
                            <div class="new-preview-wrap" id="editPreviewWrap">
                                <img id="editPreviewImg" class="new-preview-img" src="" alt="">
                                <button type="button" class="preview-remove" id="editRemovePreview"><i
                                        class='bx bx-x'></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Description <span class="hint">optional</span></label>
                        <textarea name="description" id="editDescField" class="form-input" rows="3"
                            placeholder="Add a description…"></textarea>
                    </div>
                </form>
            </div>
            <div class="edit-modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="button" class="btn-modal-save" onclick="submitEdit()">
                    <i class='bx bx-save'></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- Lightbox -->
    <div class="lightbox" id="lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()"><i class='bx bx-x'></i></button>
        <img id="lightboxImg" src="" alt="">
        <div class="lightbox-caption" id="lightboxCaption"></div>
    </div>

    <!-- ── Page header ─────────────────────────────────────────────── -->
    <div class="page-header">
        <div class="page-header-left">
            <h1>Publishing Images</h1>
            <nav class="breadcrumb">
                <a href="dashboard.php"><i class='bx bx-home-alt'></i> Dashboard</a>
                <i class='bx bx-chevron-right'></i>
                <span>Publishing Images</span>
            </nav>
        </div>
    </div>

    <!-- Summary strip -->
    <div class="summary-strip">
        <div class="summary-card">
            <div class="summary-icon si-purple"><i class='bx bx-images'></i></div>
            <div>
                <div class="summary-val"><?= number_format($totalImages) ?></div>
                <div class="summary-label">Total Images</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon si-green"><i class='bx bx-check-circle'></i></div>
            <div>
                <div class="summary-val">
                    <?= number_format(count(array_filter($images, fn($i) => !empty($i['description'])))) ?></div>
                <div class="summary-label">With Description</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon si-amber"><i class='bx bx-image-alt'></i></div>
            <div>
                <div class="summary-val">
                    <?= number_format(count(array_filter($images, fn($i) => empty($i['description'])))) ?></div>
                <div class="summary-label">No Description</div>
            </div>
        </div>
    </div>

    <!-- ── Page grid ──────────────────────────────────────────────── -->
    <div class="page-grid">

        <!-- ── LEFT: Upload card ─────────────────────── -->
        <div class="upload-card">
            <div class="card-accent-bar"></div>
            <div class="card-header">
                <div class="card-header-icon"><i class='bx bx-cloud-upload'></i></div>
                <div class="card-header-text">
                    <h2>Upload New Image</h2>
                    <p>Add a publishing image to the library</p>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="add_publishing.php" enctype="multipart/form-data" id="uploadForm"
                    novalidate>
                    <input type="hidden" name="action" value="add">

                    <div class="form-group">
                        <label class="form-label">Image File <span style="color:var(--danger)">*</span></label>
                        <div class="upload-zone" id="uploadZone">
                            <input type="file" name="image" id="imgInput" accept=".jpg,.jpeg,.png,.webp,.gif,.svg">
                            <span class="upload-icon"><i class='bx bx-image-add'></i></span>
                            <div class="upload-label">Drop image here or <span>browse</span></div>
                            <div class="upload-hint"><span>JPG</span>, <span>PNG</span>, <span>WEBP</span>,
                                <span>GIF</span> — max <span>5 MB</span></div>
                            <div class="new-preview-wrap" id="previewWrap">
                                <img id="previewImg" class="new-preview-img" src="" alt="">
                                <button type="button" class="preview-remove" id="removePreview"><i
                                        class='bx bx-x'></i></button>
                            </div>
                        </div>
                        <div class="field-error" id="imgError"
                            style="display:none;margin-top:6px;font-size:12.5px;color:var(--danger);display:none;align-items:center;gap:5px;">
                            <i class='bx bx-error-circle'></i> <span id="imgErrorMsg"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Description <span
                                class="hint">optional</span></label>
                        <textarea name="description" id="description" class="form-input"
                            placeholder="Add a caption or description for this image…" rows="3"
                            maxlength="500"></textarea>
                        <span
                            style="display:block;text-align:right;margin-top:4px;font-size:11.5px;color:var(--gray-400);font-family:'DM Mono',monospace"
                            id="descCount">0 / 500</span>
                    </div>

                    <button type="submit" class="btn-submit" id="uploadBtn">
                        <span class="btn-spinner" id="uploadSpinner"></span>
                        <i class='bx bx-upload' id="uploadIcon"></i>
                        <span id="uploadText">Upload Image</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- ── RIGHT: Image grid ─────────────────────── -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class='bx bx-images'></i>
                    <h2>Image Library <span
                            style="font-size:13px;font-weight:500;color:var(--gray-400)">(<?= number_format($totalImages) ?>)</span>
                    </h2>
                </div>
                <div class="search-box">
                    <i class='bx bx-search'></i>
                    <input type="text" class="search-input" id="searchInput" placeholder="Search descriptions…"
                        autocomplete="off">
                </div>
            </div>

            <?php if (empty($images)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class='bx bx-image-alt'></i></div>
                    <h3>No Images Yet</h3>
                    <p>Upload your first publishing image using the form on the left.</p>
                </div>
            <?php else: ?>
                <div class="images-grid" id="imagesGrid">
                    <?php foreach ($images as $i => $img): ?>
                        <div class="img-card" style="animation: fadeUp .3s <?= $i * 0.04 ?>s both"
                            data-desc="<?= strtolower(htmlspecialchars($img['description'] ?? '', ENT_QUOTES)) ?>">

                            <div class="img-card-thumb"
                                onclick="openLightbox('<?= htmlspecialchars($img['image_path'], ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($img['description'] ?? ''), ENT_QUOTES) ?>')">
                                <span class="img-index-badge">#<?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
                                <?php if (!empty($img['image_path']) && file_exists($img['image_path'])): ?>
                                    <img src="<?= htmlspecialchars($img['image_path'], ENT_QUOTES) ?>"
                                        alt="<?= htmlspecialchars($img['description'] ?? 'Image', ENT_QUOTES) ?>" loading="lazy">
                                    <div class="img-zoom-overlay"><i class='bx bx-zoom-in'></i></div>
                                <?php else: ?>
                                    <div class="img-placeholder">
                                        <i class='bx bx-image-alt'></i>
                                        <span>Not found</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="img-card-body">
                                <div class="img-card-desc <?= empty($img['description']) ? 'empty' : '' ?>">
                                    <?= empty($img['description']) ? 'No description' : htmlspecialchars($img['description'], ENT_QUOTES) ?>
                                </div>
                                <?php if (!empty($img['uploaded_at'])): ?>
                                    <div class="img-card-date">
                                        <i class='bx bx-calendar'></i>
                                        <?= date('d M Y', strtotime($img['uploaded_at'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="img-card-actions">
                                <button class="img-act-btn edit"
                                    onclick="openEditModal(<?= (int) $img['id'] ?>, '<?= htmlspecialchars($img['image_path'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($img['description'] ?? ''), ENT_QUOTES) ?>')">
                                    <i class='bx bx-edit-alt'></i> Edit
                                </button>
                                <button class="img-act-btn delete" onclick="openDeleteModal(<?= (int) $img['id'] ?>)">
                                    <i class='bx bx-trash'></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="no-results" id="noResults" style="display:none">
                    <i class='bx bx-search' style="font-size:28px;display:block;margin-bottom:10px;"></i>
                    No images match your search.
                </div>
            <?php endif; ?>
        </div>

    </div><!-- /.page-grid -->

    </div><!-- /.dash-content -->
    </section><!-- /.home-section -->

    <script>
        (() => {
            /* ── Upload: image preview ───────────────────── */
            const imgInput = document.getElementById('imgInput');
            const previewWrap = document.getElementById('previewWrap');
            const previewImg = document.getElementById('previewImg');
            const removeBtn = document.getElementById('removePreview');
            const uploadZone = document.getElementById('uploadZone');

            imgInput?.addEventListener('change', () => showPreview(imgInput, previewWrap, previewImg));

            uploadZone?.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
            uploadZone?.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
            uploadZone?.addEventListener('drop', e => {
                e.preventDefault(); uploadZone.classList.remove('drag-over');
                const file = e.dataTransfer.files[0];
                if (file) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    imgInput.files = dt.files;
                    showPreview(imgInput, previewWrap, previewImg);
                }
            });

            removeBtn?.addEventListener('click', e => {
                e.stopPropagation();
                imgInput.value = '';
                previewWrap.classList.remove('show');
            });

            function showPreview(input, wrap, imgEl) {
                const file = input.files[0];
                if (!file?.type.startsWith('image/')) return;
                const r = new FileReader();
                r.onload = ev => { imgEl.src = ev.target.result; wrap.classList.add('show'); };
                r.readAsDataURL(file);
            }

            /* ── Desc char counter ───────────────────────── */
            const descEl = document.getElementById('description');
            const descCount = document.getElementById('descCount');
            descEl?.addEventListener('input', () => { descCount.textContent = descEl.value.length + ' / 500'; });

            /* ── Upload submit ───────────────────────────── */
            const uploadForm = document.getElementById('uploadForm');
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadSpinner = document.getElementById('uploadSpinner');
            const uploadIcon = document.getElementById('uploadIcon');
            const uploadText = document.getElementById('uploadText');

            uploadForm?.addEventListener('submit', e => {
                if (!imgInput.files.length) {
                    e.preventDefault();
                    // flash upload zone
                    uploadZone.style.borderColor = 'var(--danger)';
                    uploadZone.style.background = 'var(--danger-bg)';
                    setTimeout(() => { uploadZone.style.borderColor = ''; uploadZone.style.background = ''; }, 1800);
                    return;
                }
                uploadBtn.classList.add('loading');
                uploadSpinner.style.display = 'block';
                uploadIcon.style.display = 'none';
                uploadText.textContent = 'Uploading…';
            });

            /* ── Search / filter ─────────────────────────── */
            const searchInput = document.getElementById('searchInput');
            const grid = document.getElementById('imagesGrid');
            const noResults = document.getElementById('noResults');

            searchInput?.addEventListener('input', () => {
                const q = searchInput.value.trim().toLowerCase();
                if (!grid) return;
                let visible = 0;
                grid.querySelectorAll('.img-card').forEach(card => {
                    const desc = card.dataset.desc || '';
                    const show = !q || desc.includes(q);
                    card.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
            });

            /* ── Edit modal ──────────────────────────────── */
            window.openEditModal = (id, imgPath, desc) => {
                document.getElementById('editIdField').value = id;
                document.getElementById('editDescField').value = desc;

                const thumb = document.getElementById('editCurrentThumb');
                const wrap = document.getElementById('editCurrentImg');
                if (imgPath) { thumb.src = imgPath; wrap.style.display = 'flex'; }
                else { wrap.style.display = 'none'; }

                // Reset upload zone
                document.getElementById('editImgInput').value = '';
                document.getElementById('editPreviewWrap').classList.remove('show');

                document.getElementById('editOverlay').classList.add('open');
                document.body.style.overflow = 'hidden';
            };

            window.closeEditModal = () => {
                document.getElementById('editOverlay').classList.remove('open');
                document.body.style.overflow = '';
            };

            window.submitEdit = () => { document.getElementById('editForm').submit(); };

            // Edit: image preview
            const editImgInput = document.getElementById('editImgInput');
            const editPreviewWrap = document.getElementById('editPreviewWrap');
            const editPreviewImg = document.getElementById('editPreviewImg');
            const editRemovePreview = document.getElementById('editRemovePreview');
            const editUploadZone = document.getElementById('editUploadZone');

            editImgInput?.addEventListener('change', () => showPreview(editImgInput, editPreviewWrap, editPreviewImg));
            editUploadZone?.addEventListener('dragover', e => { e.preventDefault(); editUploadZone.classList.add('drag-over'); });
            editUploadZone?.addEventListener('dragleave', () => editUploadZone.classList.remove('drag-over'));
            editUploadZone?.addEventListener('drop', e => {
                e.preventDefault(); editUploadZone.classList.remove('drag-over');
                const file = e.dataTransfer.files[0];
                if (file) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    editImgInput.files = dt.files;
                    showPreview(editImgInput, editPreviewWrap, editPreviewImg);
                }
            });
            editRemovePreview?.addEventListener('click', e => {
                e.stopPropagation();
                editImgInput.value = '';
                editPreviewWrap.classList.remove('show');
            });

            /* ── Delete modal ────────────────────────────── */
            window.openDeleteModal = (id) => {
                document.getElementById('deleteIdField').value = id;
                document.getElementById('deleteOverlay').classList.add('open');
                document.body.style.overflow = 'hidden';
            };

            window.closeDeleteModal = () => {
                document.getElementById('deleteOverlay').classList.remove('open');
                document.body.style.overflow = '';
            };

            document.getElementById('deleteForm')?.addEventListener('submit', () => {
                document.getElementById('delSpinner').style.display = 'block';
                document.getElementById('delBtnText').textContent = 'Deleting…';
            });

            /* ── Escape key ──────────────────────────────── */
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') {
                    closeDeleteModal();
                    closeEditModal();
                    closeLightbox();
                }
            });

            /* ── Click outside overlays ──────────────────── */
            document.getElementById('deleteOverlay')?.addEventListener('click', e => {
                if (e.target.id === 'deleteOverlay') closeDeleteModal();
            });
            document.getElementById('editOverlay')?.addEventListener('click', e => {
                if (e.target.id === 'editOverlay') closeEditModal();
            });

            /* ── Toast ───────────────────────────────────── */
            const toast = document.getElementById('toast');
            if (toast) {
                const t = setTimeout(dismissToast, 4500);
                toast.addEventListener('click', () => { clearTimeout(t); dismissToast(); });
            }
        })();

        /* ── Lightbox ─────────────────────────────────── */
        function openLightbox(src, caption) {
            document.getElementById('lightboxImg').src = src;
            document.getElementById('lightboxCaption').textContent = caption || '';
            document.getElementById('lightboxCaption').style.display = caption ? 'block' : 'none';
            document.getElementById('lightbox').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('open');
            document.body.style.overflow = '';
        }

        /* ── Toast dismiss ────────────────────────────── */
        function dismissToast() {
            const t = document.getElementById('toast');
            if (!t) return;
            t.classList.add('hiding');
            setTimeout(() => t.remove(), 320);
        }
    </script>

</body>

</html>