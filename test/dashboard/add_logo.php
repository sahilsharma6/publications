<?php
session_start();
include '../../db.php';

/* ── Auth Guard ──────────────────────────────────────────────────────────── */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'SuperAdmin'], true)) {
    header("Location: login.php");
    exit();
}

/* ── Config ──────────────────────────────────────────────────────────────── */
$uploadDir = 'uploads/logos/';
$logoFile = $uploadDir . 'logo.png';
$toast = null;

if (!is_dir($uploadDir))
    mkdir($uploadDir, 0755, true);

/* ── Handle UPLOAD ───────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_logo') {
    if (empty($_FILES['logo']['name']) || $_FILES['logo']['error'] !== 0) {
        $toast = ['type' => 'error', 'msg' => 'Please select a logo file to upload.'];
    } else {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        $maxSize = 3 * 1024 * 1024; // 3 MB

        if (!in_array($ext, $allowed)) {
            $toast = ['type' => 'error', 'msg' => 'Only JPG, PNG, WEBP, SVG files are allowed.'];
        } elseif ($_FILES['logo']['size'] > $maxSize) {
            $toast = ['type' => 'error', 'msg' => 'Logo must be under 3 MB.'];
        } else {
            // Save with original extension but fixed name "logo"
            $savePath = $uploadDir . 'logo.' . $ext;
            // Remove old logo files (any extension)
            foreach (glob($uploadDir . 'logo.*') as $old)
                @unlink($old);

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $savePath)) {
                header("Location: add_logo.php?toast=uploaded");
                exit();
            } else {
                $toast = ['type' => 'error', 'msg' => 'Upload failed. Check folder permissions.'];
            }
        }
    }
}

/* ── Handle DELETE ───────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_logo') {
    $deleted = false;
    foreach (glob($uploadDir . 'logo.*') as $f) {
        @unlink($f);
        $deleted = true;
    }
    header("Location: add_logo.php?toast=" . ($deleted ? 'deleted' : 'notfound'));
    exit();
}

/* ── Toast from redirect ─────────────────────────────────────────────────── */
if (!$toast && isset($_GET['toast'])) {
    $map = [
        'uploaded' => ['type' => 'success', 'msg' => 'Logo uploaded successfully.'],
        'deleted' => ['type' => 'success', 'msg' => 'Logo removed successfully.'],
        'notfound' => ['type' => 'error', 'msg' => 'No logo file found to delete.'],
    ];
    $toast = $map[$_GET['toast']] ?? null;
}

/* ── Current logo ────────────────────────────────────────────────────────── */
$currentLogo = null;
foreach (glob($uploadDir . 'logo.*') ?: [] as $f) {
    $currentLogo = $f;
    break;
}
$logoExists = (bool) $currentLogo;

/* ── Logo file info ──────────────────────────────────────────────────────── */
$logoSize = $logoExists ? round(filesize($currentLogo) / 1024, 1) . ' KB' : null;
$logoExt = $logoExists ? strtoupper(pathinfo($currentLogo, PATHINFO_EXTENSION)) : null;
$logoDate = $logoExists ? date('d M Y, H:i', filemtime($currentLogo)) : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logo Management — BookAdmin</title>
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

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, .35)
            }

            50% {
                box-shadow: 0 0 0 8px rgba(59, 130, 246, 0)
            }
        }

        @keyframes breathe {

            0%,
            100% {
                transform: scale(1)
            }

            50% {
                transform: scale(1.03)
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

        /* ── Main grid ──────────────────────────────── */
        .page-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            align-items: start;
            max-width: 900px;
        }

        @media(max-width:760px) {
            .page-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── Card base ──────────────────────────────── */
        .card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-accent-bar {
            height: 4px;
        }

        .accent-upload {
            background: linear-gradient(90deg, #3b82f6, #6366f1, #8b5cf6);
        }

        .accent-current {
            background: linear-gradient(90deg, #10b981, #06b6d4, #3b82f6);
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
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .icon-blue {
            background: #eff6ff;
            color: #3b82f6;
        }

        .icon-green {
            background: #ecfdf5;
            color: #10b981;
        }

        .icon-red {
            background: #fef2f2;
            color: #ef4444;
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

        /* ── Upload zone ─────────────────────────────── */
        .upload-zone {
            border: 2px dashed var(--gray-200);
            border-radius: var(--r-lg);
            padding: 32px 20px;
            text-align: center;
            cursor: pointer;
            transition: all var(--t);
            position: relative;
            background: var(--gray-50);
            margin-bottom: 20px;
        }

        .upload-zone:hover,
        .upload-zone.drag-over {
            border-color: var(--accent);
            background: var(--accent-light);
        }

        .upload-zone.drag-over {
            transform: scale(1.01);
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
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #eff6ff, #e0e7ff);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 28px;
            color: #6366f1;
            transition: all var(--t);
        }

        .upload-zone:hover .upload-zone-icon,
        .upload-zone.drag-over .upload-zone-icon {
            background: linear-gradient(135deg, #dbeafe, #c7d2fe);
            transform: translateY(-2px);
            animation: pulse 1.6s infinite;
        }

        .upload-zone-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--gray-700);
            margin-bottom: 5px;
        }

        .upload-zone-title span {
            color: var(--accent);
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .upload-zone-hint {
            font-size: 12px;
            color: var(--gray-400);
            line-height: 1.5;
        }

        .upload-zone-hint b {
            color: var(--gray-600);
        }

        /* ── Format badges ───────────────────────────── */
        .format-badges {
            display: flex;
            gap: 6px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .fmt-badge {
            padding: 2px 9px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .3px;
            border: 1.5px solid;
        }

        .fmt-png {
            background: #f0fdf4;
            color: #16a34a;
            border-color: #bbf7d0;
        }

        .fmt-jpg {
            background: #eff6ff;
            color: #2563eb;
            border-color: #bfdbfe;
        }

        .fmt-webp {
            background: #faf5ff;
            color: #7c3aed;
            border-color: #ddd6fe;
        }

        .fmt-svg {
            background: #fff7ed;
            color: #c2410c;
            border-color: #fed7aa;
        }

        /* ── New image preview (inside upload zone) ─── */
        .new-preview-wrap {
            display: none;
            margin-top: 16px;
            position: relative;
            width: 100px;
            margin-left: auto;
            margin-right: auto;
        }

        .new-preview-wrap.show {
            display: block;
        }

        .new-preview-img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: var(--r);
            border: 2px solid var(--gray-200);
            background: #fff;
            display: block;
            padding: 6px;
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

        /* ── Selected file info ──────────────────────── */
        .file-info-bar {
            display: none;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: var(--accent-light);
            border: 1.5px solid #bfdbfe;
            border-radius: var(--r);
            margin-bottom: 16px;
            font-size: 13px;
        }

        .file-info-bar.show {
            display: flex;
        }

        .file-info-bar i {
            font-size: 18px;
            color: var(--accent);
            flex-shrink: 0;
        }

        .file-info-name {
            font-weight: 600;
            color: var(--gray-800);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 160px;
        }

        .file-info-size {
            font-size: 11.5px;
            color: var(--gray-400);
            font-family: "DM Mono", monospace;
            margin-left: auto;
            flex-shrink: 0;
        }

        /* ── Submit button ───────────────────────────── */
        .btn-upload {
            width: 100%;
            padding: 13px 24px;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
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
            box-shadow: 0 2px 12px rgba(99, 102, 241, .35);
        }

        .btn-upload:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(99, 102, 241, .45);
            filter: brightness(1.06);
        }

        .btn-upload:active {
            transform: translateY(0);
        }

        .btn-upload.loading {
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

        /* ── Current logo card ───────────────────────── */

        /* Logo showcase area */
        .logo-showcase {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: var(--r-lg);
            padding: 32px 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 180px;
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
            border: 1px solid var(--gray-200);
        }

        /* subtle grid pattern */
        .logo-showcase::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(var(--gray-200) 1px, transparent 1px),
                linear-gradient(90deg, var(--gray-200) 1px, transparent 1px);
            background-size: 28px 28px;
            opacity: .5;
        }

        .logo-img-wrap {
            position: relative;
            z-index: 1;
            background: #fff;
            border-radius: var(--r-lg);
            padding: 16px 24px;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: breathe 4s ease-in-out infinite;
        }

        .logo-img-wrap img {
            max-width: 200px;
            max-height: 120px;
            object-fit: contain;
            display: block;
        }

        /* Dark mode preview toggle */
        .preview-toggle {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 0;
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            border-radius: var(--r);
            overflow: hidden;
        }

        .preview-toggle button {
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 600;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray-500);
            font-family: inherit;
            transition: all var(--t);
        }

        .preview-toggle button.active {
            background: #fff;
            color: var(--gray-800);
            box-shadow: var(--shadow-sm);
        }

        .logo-showcase.dark-bg {
            background: linear-gradient(135deg, #0f172a, #1e293b);
        }

        .logo-showcase.dark-bg::before {
            opacity: .15;
        }

        /* No logo placeholder */
        .no-logo-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            min-height: 180px;
            color: var(--gray-300);
            border: 2px dashed var(--gray-200);
            border-radius: var(--r-lg);
            margin-bottom: 20px;
            padding: 24px;
            text-align: center;
        }

        .no-logo-placeholder i {
            font-size: 48px;
        }

        .no-logo-placeholder p {
            font-size: 13.5px;
            color: var(--gray-400);
            font-weight: 500;
        }

        .no-logo-placeholder span {
            font-size: 12px;
            color: var(--gray-300);
        }

        /* ── Logo meta info ──────────────────────────── */
        .logo-meta {
            display: flex;
            flex-direction: column;
            gap: 1px;
            margin-bottom: 20px;
        }

        .meta-row {
            display: flex;
            align-items: center;
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
            width: 18px;
        }

        .meta-label {
            font-size: 11.5px;
            color: var(--gray-400);
            width: 70px;
            flex-shrink: 0;
        }

        .meta-value {
            font-size: 13px;
            color: var(--gray-700);
            font-weight: 500;
        }

        .meta-value.mono {
            font-family: "DM Mono", monospace;
            font-size: 12px;
        }

        /* Status badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .status-inactive {
            background: var(--gray-100);
            color: var(--gray-500);
            border: 1px solid var(--gray-200);
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .dot-green {
            background: #22c55e;
        }

        .dot-gray {
            background: var(--gray-400);
        }

        /* ── Delete button ───────────────────────────── */
        .btn-delete {
            width: 100%;
            padding: 11px 20px;
            background: none;
            border: 1.5px solid #fecaca;
            color: var(--danger);
            font-size: 13.5px;
            font-weight: 600;
            font-family: inherit;
            border-radius: var(--r);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all var(--t);
        }

        .btn-delete:hover {
            background: var(--danger);
            color: #fff;
            border-color: var(--danger);
            box-shadow: 0 4px 16px rgba(239, 68, 68, .3);
        }

        /* ── Confirm delete overlay ──────────────────── */
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
            transition: all var(--t);
            box-shadow: 0 2px 10px rgba(239, 68, 68, .3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
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

        /* ── Tips card ───────────────────────────────── */
        .tips-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-lg);
            overflow: hidden;
            margin-top: 22px;
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

    <!-- Toast -->
    <?php if ($toast): ?>
        <div class="toast <?= $toast['type'] ?>" id="toast">
            <i class='bx <?= $toast['type'] === 'success' ? 'bx-check-circle' : 'bx-x-circle' ?>'></i>
            <?= htmlspecialchars($toast['msg'], ENT_QUOTES) ?>
            <button class="toast-close" onclick="dismissToast()">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Delete confirm overlay -->
    <div class="overlay" id="deleteOverlay">
        <div class="del-modal">
            <div class="del-modal-icon"><i class='bx bx-trash'></i></div>
            <h3>Remove Logo?</h3>
            <p>This will permanently delete the current logo from the server. You can re-upload one at any time.</p>
            <form method="POST" id="deleteForm" class="del-modal-btns">
                <input type="hidden" name="action" value="delete_logo">
                <button type="button" class="btn-del-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn-del-confirm">
                    <span class="del-spinner" id="delSpinner"></span>
                    <span id="delBtnText">Yes, Remove</span>
                </button>
            </form>
        </div>
    </div>

    <!-- ── Page header ─────────────────────────────────────────────── -->
    <div class="page-header">
        <div class="page-header-left">
            <h1>Logo Management</h1>
            <nav class="breadcrumb">
                <a href="<?= $root_url ?>"><i class='bx bx-home-alt'></i> Dashboard</a>
                <i class='bx bx-chevron-right'></i>
                <span>Logo</span>
            </nav>
        </div>
        <!-- Status pill -->
        <div class="status-badge <?= $logoExists ? 'status-active' : 'status-inactive' ?>">
            <span class="status-dot <?= $logoExists ? 'dot-green' : 'dot-gray' ?>"></span>
            <?= $logoExists ? 'Logo Active' : 'No Logo Set' ?>
        </div>
    </div>

    <!-- ── Page grid ──────────────────────────────────────────────── -->
    <div class="page-grid">

        <!-- ── LEFT: Upload ──────────────────────────── -->
        <div>
            <div class="card">
                <div class="card-accent-bar accent-upload"></div>
                <div class="card-header">
                    <div class="card-header-icon icon-blue"><i class='bx bx-cloud-upload'></i></div>
                    <div class="card-header-text">
                        <h2><?= $logoExists ? 'Replace Logo' : 'Upload Logo' ?></h2>
                        <p><?= $logoExists ? 'Upload a new file to replace the current logo' : 'Upload your brand logo to the system' ?>
                        </p>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="uploadForm" novalidate>
                        <input type="hidden" name="action" value="upload_logo">

                        <!-- File info bar (shows after selection) -->
                        <div class="file-info-bar" id="fileInfoBar">
                            <i class='bx bx-image'></i>
                            <span class="file-info-name" id="fileInfoName"></span>
                            <span class="file-info-size" id="fileInfoSize"></span>
                        </div>

                        <!-- Drop zone -->
                        <div class="upload-zone" id="uploadZone">
                            <input type="file" name="logo" id="logoInput" accept=".jpg,.jpeg,.png,.webp,.svg">
                            <div class="upload-zone-icon"><i class='bx bx-image-add'></i></div>
                            <div class="upload-zone-title">Drop your logo here or <span>browse</span></div>
                            <div class="upload-zone-hint">
                                Recommended: <b>transparent PNG</b> or <b>SVG</b><br>
                                Ideal size: 300×100 px or wider · Max <b>3 MB</b>
                            </div>
                            <div class="format-badges">
                                <span class="fmt-badge fmt-png">PNG</span>
                                <span class="fmt-badge fmt-jpg">JPG</span>
                                <span class="fmt-badge fmt-webp">WEBP</span>
                                <span class="fmt-badge fmt-svg">SVG</span>
                            </div>
                            <!-- New image preview -->
                            <div class="new-preview-wrap" id="previewWrap">
                                <img id="previewImg" class="new-preview-img" src="" alt="">
                                <button type="button" class="preview-remove" id="removePreview"><i
                                        class='bx bx-x'></i></button>
                            </div>
                        </div>

                        <button type="submit" class="btn-upload" id="uploadBtn">
                            <span class="btn-spinner" id="uploadSpinner"></span>
                            <i class='bx bx-upload' id="uploadIcon"></i>
                            <span id="uploadText"><?= $logoExists ? 'Replace Logo' : 'Upload Logo' ?></span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Tips -->
            <div class="tips-card">
                <div class="tips-header"><i class='bx bx-bulb'></i> Logo Tips</div>
                <div class="tips-body">
                    <div class="tip-row">
                        <div class="tip-dot" style="background:#10b981"></div>
                        <div class="tip-text"><strong>Use transparent PNG or SVG</strong> for best results across light
                            and dark backgrounds.</div>
                    </div>
                    <div class="tip-row">
                        <div class="tip-dot" style="background:var(--accent)"></div>
                        <div class="tip-text"><strong>Recommended dimensions:</strong> at least 300px wide. Wider logos
                            (600px+) scale better on retina screens.</div>
                    </div>
                    <div class="tip-row">
                        <div class="tip-dot" style="background:var(--warning)"></div>
                        <div class="tip-text"><strong>Uploading replaces</strong> the current logo immediately. The old
                            file is permanently deleted.</div>
                    </div>
                    <div class="tip-row">
                        <div class="tip-dot" style="background:#8b5cf6"></div>
                        <div class="tip-text"><strong>SVG is ideal</strong> — it scales to any size without quality loss
                            and keeps file sizes tiny.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── RIGHT: Current logo ────────────────────── -->
        <div class="card">
            <div class="card-accent-bar accent-current"></div>
            <div class="card-header">
                <div class="card-header-icon icon-green"><i class='bx bx-image'></i></div>
                <div class="card-header-text">
                    <h2>Current Logo</h2>
                    <p>Preview your active logo on different backgrounds</p>
                </div>
            </div>
            <div class="card-body">

                <?php if ($logoExists): ?>

                    <!-- Showcase with light/dark toggle -->
                    <div class="logo-showcase" id="logoShowcase">
                        <div class="preview-toggle">
                            <button id="btnLight" class="active" onclick="setTheme('light')">☀ Light</button>
                            <button id="btnDark" onclick="setTheme('dark')">☾ Dark</button>
                        </div>
                        <div class="logo-img-wrap" id="logoImgWrap">
                            <img src="<?= htmlspecialchars($currentLogo . '?v=' . filemtime($currentLogo), ENT_QUOTES) ?>"
                                alt="Current Logo" id="logoPreviewImg">
                        </div>
                    </div>

                    <!-- Meta info -->
                    <div class="logo-meta">
                        <div class="meta-row">
                            <i class='bx bx-file'></i>
                            <span class="meta-label">Filename</span>
                            <span class="meta-value mono"><?= htmlspecialchars(basename($currentLogo), ENT_QUOTES) ?></span>
                        </div>
                        <div class="meta-row">
                            <i class='bx bx-image-alt'></i>
                            <span class="meta-label">Format</span>
                            <span class="meta-value">
                                <span class="fmt-badge fmt-<?= strtolower($logoExt) ?>"
                                    style="font-size:11px"><?= $logoExt ?></span>
                            </span>
                        </div>
                        <div class="meta-row">
                            <i class='bx bx-data'></i>
                            <span class="meta-label">File size</span>
                            <span class="meta-value mono"><?= $logoSize ?></span>
                        </div>
                        <div class="meta-row">
                            <i class='bx bx-calendar'></i>
                            <span class="meta-label">Uploaded</span>
                            <span class="meta-value"><?= $logoDate ?></span>
                        </div>
                        <div class="meta-row">
                            <i class='bx bx-link'></i>
                            <span class="meta-label">Path</span>
                            <span class="meta-value mono"
                                style="font-size:11px;color:var(--gray-400)"><?= htmlspecialchars($currentLogo, ENT_QUOTES) ?></span>
                        </div>
                    </div>

                    <!-- Delete -->
                    <button class="btn-delete" onclick="openDeleteModal()">
                        <i class='bx bx-trash'></i> Remove Current Logo
                    </button>

                <?php else: ?>

                    <!-- No logo state -->
                    <div class="no-logo-placeholder">
                        <i class='bx bx-image-alt'></i>
                        <p>No logo uploaded yet</p>
                        <span>Upload a logo using the form on the left to see it previewed here.</span>
                    </div>

                    <div class="logo-meta">
                        <div class="meta-row">
                            <i class='bx bx-info-circle'></i>
                            <span class="meta-label">Status</span>
                            <span class="meta-value">
                                <span class="status-badge status-inactive" style="font-size:11.5px">
                                    <span class="status-dot dot-gray"></span> Not set
                                </span>
                            </span>
                        </div>
                        <div class="meta-row">
                            <i class='bx bx-folder'></i>
                            <span class="meta-label">Save path</span>
                            <span class="meta-value mono"
                                style="font-size:11px;color:var(--gray-400)"><?= htmlspecialchars($uploadDir, ENT_QUOTES) ?>logo.*</span>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </div>

    </div><!-- /.page-grid -->

    </div><!-- /.dash-content -->
    </section><!-- /.home-section -->

    <script>
        (() => {
            /* ── Upload: file selection & preview ─────────── */
            const logoInput = document.getElementById('logoInput');
            const previewWrap = document.getElementById('previewWrap');
            const previewImg = document.getElementById('previewImg');
            const removeBtn = document.getElementById('removePreview');
            const uploadZone = document.getElementById('uploadZone');
            const fileInfoBar = document.getElementById('fileInfoBar');
            const fileInfoName = document.getElementById('fileInfoName');
            const fileInfoSize = document.getElementById('fileInfoSize');

            logoInput?.addEventListener('change', () => handleFile(logoInput.files[0]));

            uploadZone?.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
            uploadZone?.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
            uploadZone?.addEventListener('drop', e => {
                e.preventDefault(); uploadZone.classList.remove('drag-over');
                const file = e.dataTransfer.files[0];
                if (file) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    logoInput.files = dt.files;
                    handleFile(file);
                }
            });

            function handleFile(file) {
                if (!file) return;
                // Show file info bar
                fileInfoName.textContent = file.name;
                fileInfoSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
                fileInfoBar.classList.add('show');

                // Show image preview
                if (file.type.startsWith('image/')) {
                    const r = new FileReader();
                    r.onload = e => { previewImg.src = e.target.result; previewWrap.classList.add('show'); };
                    r.readAsDataURL(file);
                }
            }

            removeBtn?.addEventListener('click', e => {
                e.stopPropagation();
                logoInput.value = '';
                previewWrap.classList.remove('show');
                fileInfoBar.classList.remove('show');
            });

            /* ── Upload submit ───────────────────────────── */
            const uploadForm = document.getElementById('uploadForm');
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadSpinner = document.getElementById('uploadSpinner');
            const uploadIcon = document.getElementById('uploadIcon');
            const uploadText = document.getElementById('uploadText');

            uploadForm?.addEventListener('submit', e => {
                if (!logoInput.files.length) {
                    e.preventDefault();
                    uploadZone.style.borderColor = 'var(--danger)';
                    uploadZone.style.background = 'var(--danger-bg)';
                    uploadZone.style.animation = 'shake .4s var(--t)';
                    setTimeout(() => {
                        uploadZone.style.borderColor = '';
                        uploadZone.style.background = '';
                        uploadZone.style.animation = '';
                    }, 1800);
                    return;
                }
                uploadBtn.classList.add('loading');
                uploadSpinner.style.display = 'block';
                uploadIcon.style.display = 'none';
                uploadText.textContent = 'Uploading…';
            });

            /* ── Delete modal ────────────────────────────── */
            window.openDeleteModal = () => {
                document.getElementById('deleteOverlay').classList.add('open');
                document.body.style.overflow = 'hidden';
            };
            window.closeDeleteModal = () => {
                document.getElementById('deleteOverlay').classList.remove('open');
                document.body.style.overflow = '';
            };

            document.getElementById('deleteForm')?.addEventListener('submit', () => {
                document.getElementById('delSpinner').style.display = 'block';
                document.getElementById('delBtnText').textContent = 'Removing…';
            });

            document.getElementById('deleteOverlay')?.addEventListener('click', e => {
                if (e.target.id === 'deleteOverlay') closeDeleteModal();
            });

            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') closeDeleteModal();
            });

            /* ── Toast ───────────────────────────────────── */
            const toast = document.getElementById('toast');
            if (toast) {
                const t = setTimeout(dismissToast, 4500);
                toast.addEventListener('click', () => { clearTimeout(t); dismissToast(); });
            }
        })();

        /* ── Light / dark preview toggle ──────────────── */
        function setTheme(mode) {
            const showcase = document.getElementById('logoShowcase');
            const wrap = document.getElementById('logoImgWrap');
            const btnLight = document.getElementById('btnLight');
            const btnDark = document.getElementById('btnDark');

            if (mode === 'dark') {
                showcase.classList.add('dark-bg');
                wrap.style.background = 'rgba(255,255,255,.06)';
                wrap.style.boxShadow = '0 8px 32px rgba(0,0,0,.5)';
                btnDark.classList.add('active');
                btnLight.classList.remove('active');
            } else {
                showcase.classList.remove('dark-bg');
                wrap.style.background = '#fff';
                wrap.style.boxShadow = '';
                btnLight.classList.add('active');
                btnDark.classList.remove('active');
            }
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