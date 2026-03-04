<?php
session_start();
include '../../db.php';

/* ── Auth Guard ──────────────────────────────────────────────────────────── */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'SuperAdmin'], true)) {
    header("Location: login.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES);
$role = htmlspecialchars($_SESSION['role'] ?? '', ENT_QUOTES);

$errors = [];
$toast = null;

/* ── Fetch user info ─────────────────────────────────────────────────────── */
$userStmt = $conn->prepare("SELECT username, email, role, created_at FROM users WHERE id = ? LIMIT 1");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userInfo = $userResult ? $userResult->fetch_assoc() : [];
$userStmt->close();

/* ── Handle POST ─────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    /* ── Validation ── */
    if ($currentPassword === '') {
        $errors['current_password'] = 'Current password is required.';
    }

    if ($newPassword === '') {
        $errors['new_password'] = 'New password is required.';
    } elseif (strlen($newPassword) < 8) {
        $errors['new_password'] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $newPassword)) {
        $errors['new_password'] = 'Include at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $newPassword)) {
        $errors['new_password'] = 'Include at least one number.';
    }

    if ($confirmPassword === '') {
        $errors['confirm_password'] = 'Please confirm your new password.';
    } elseif ($newPassword !== $confirmPassword && empty($errors['new_password'])) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    /* ── Check current password ── */
    if (empty($errors)) {
        $pwStmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $pwStmt->bind_param("i", $userId);
        $pwStmt->execute();
        $pwStmt->bind_result($hashedPw);
        $pwStmt->fetch();
        $pwStmt->close();

        if (!password_verify($currentPassword, $hashedPw)) {
            $errors['current_password'] = 'Current password is incorrect.';
        }
    }

    /* ── Update ── */
    if (empty($errors)) {
        $newHashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $upStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upStmt->bind_param("si", $newHashed, $userId);

        if ($upStmt->execute()) {
            $upStmt->close();
            $conn->close();
            header("Location: change_password.php?toast=success");
            exit();
        } else {
            $toast = ['type' => 'error', 'msg' => 'Database error: ' . htmlspecialchars($conn->error, ENT_QUOTES)];
        }
        $upStmt->close();
    }
}

/* ── Toast from redirect ─────────────────────────────────────────────────── */
if (!$toast && isset($_GET['toast']) && $_GET['toast'] === 'success') {
    $toast = ['type' => 'success', 'msg' => 'Password changed successfully.'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password — BookAdmin</title>
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

        @keyframes checkPop {
            0% {
                transform: scale(0);
                opacity: 0
            }

            60% {
                transform: scale(1.2)
            }

            100% {
                transform: scale(1);
                opacity: 1
            }
        }

        @keyframes progressFill {
            from {
                width: 0
            }

            to {
                width: var(--w)
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

        /* ── Layout grid ────────────────────────────── */
        .page-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 22px;
            align-items: start;
            max-width: 860px;
        }

        @media(max-width:780px) {
            .page-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── Card ────────────────────────────────────── */
        .card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-accent-bar {
            height: 4px;
            background: linear-gradient(90deg, #6366f1, #3b82f6, #06b6d4);
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
            background: #eef2ff;
            color: #6366f1;
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

        /* ── Section title ───────────────────────────── */
        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: var(--gray-400);
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 18px;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-100);
        }

        /* ── Form group ──────────────────────────────── */
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

        .form-label .hint {
            font-size: 11.5px;
            font-weight: 400;
            color: var(--gray-400);
        }

        /* ── Input ───────────────────────────────────── */
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
            padding: 11px 44px 11px 54px;
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
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
        }

        .input-wrap:focus-within .input-icon {
            color: #6366f1;
            border-color: rgba(99, 102, 241, .3);
        }

        .form-input.has-error {
            border-color: var(--danger);
            background: var(--danger-bg);
            animation: shake .4s var(--t);
        }

        .form-input.has-error:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, .12);
            border-color: var(--danger);
        }

        .form-input.is-valid {
            border-color: var(--success);
            background: var(--success-light);
        }

        .form-input.is-valid:focus {
            box-shadow: 0 0 0 3px rgba(34, 197, 94, .12);
        }

        /* Show/hide password toggle */
        .toggle-pw {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--gray-400);
            cursor: pointer;
            border: none;
            background: none;
            transition: color var(--t);
            z-index: 1;
        }

        .toggle-pw:hover {
            color: var(--gray-700);
        }

        /* Field error */
        .field-error {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 6px;
            font-size: 12.5px;
            color: var(--danger);
            font-weight: 500;
            animation: fadeUp .2s var(--t);
        }

        .field-error i {
            font-size: 14px;
            flex-shrink: 0;
        }

        /* Field success */
        .field-success {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 6px;
            font-size: 12.5px;
            color: #16a34a;
            font-weight: 500;
        }

        .field-success i {
            font-size: 14px;
        }

        /* ── Password strength bar ───────────────────── */
        .strength-wrap {
            margin-top: 10px;
        }

        .strength-bar-track {
            height: 5px;
            background: var(--gray-100);
            border-radius: 99px;
            overflow: hidden;
            margin-bottom: 6px;
        }

        .strength-bar-fill {
            height: 100%;
            border-radius: 99px;
            transition: width .4s var(--t), background .3s;
        }

        .strength-label {
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .strength-label span {
            color: var(--gray-400);
        }

        /* strength colors */
        .str-0 {
            width: 0%;
            background: transparent;
        }

        .str-1 {
            width: 25%;
            background: #ef4444;
        }

        .str-2 {
            width: 50%;
            background: #f59e0b;
        }

        .str-3 {
            width: 75%;
            background: #3b82f6;
        }

        .str-4 {
            width: 100%;
            background: #22c55e;
        }

        /* ── Requirements checklist ──────────────────── */
        .req-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 12px;
        }

        .req-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            color: var(--gray-400);
            transition: color var(--t);
        }

        .req-item.met {
            color: #16a34a;
        }

        .req-check {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 1.5px solid var(--gray-300);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            flex-shrink: 0;
            transition: all var(--t);
        }

        .req-item.met .req-check {
            background: #22c55e;
            border-color: #22c55e;
            color: #fff;
            animation: checkPop .3s var(--t);
        }

        /* ── Submit button ───────────────────────────── */
        .btn-submit {
            width: 100%;
            padding: 13px 24px;
            background: linear-gradient(135deg, #6366f1, #3b82f6);
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
            margin-top: 6px;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(99, 102, 241, .45);
            filter: brightness(1.06);
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

        /* ── Right panel ─────────────────────────────── */
        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        /* User profile card */
        .profile-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .profile-banner {
            height: 64px;
            background: linear-gradient(135deg, #6366f1, #3b82f6, #06b6d4);
        }

        .profile-body {
            padding: 0 20px 20px;
        }

        .profile-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: 3px solid #fff;
            margin-top: -30px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            box-shadow: var(--shadow);
        }

        .profile-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-800);
        }

        .profile-role {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 10px;
            border-radius: 99px;
            font-size: 11.5px;
            font-weight: 600;
            background: #eef2ff;
            color: #4f46e5;
            margin-top: 4px;
        }

        .profile-divider {
            height: 1px;
            background: var(--gray-100);
            margin: 14px 0;
        }

        .profile-meta-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 0;
            font-size: 13px;
            color: var(--gray-600);
        }

        .profile-meta-row i {
            font-size: 15px;
            color: var(--gray-400);
            flex-shrink: 0;
            width: 18px;
        }

        /* Security tips card */
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

        /* ── Form divider ────────────────────────────── */
        .form-divider {
            height: 1px;
            background: var(--gray-100);
            margin: 22px -26px;
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

    <!-- ── Page header ─────────────────────────────────────────────── -->
    <div class="page-header">
        <div class="page-header-left">
            <h1>Change Password</h1>
            <nav class="breadcrumb">
                <a href="dashboard.php"><i class='bx bx-home-alt'></i> Dashboard</a>
                <i class='bx bx-chevron-right'></i>
                <span>Change Password</span>
            </nav>
        </div>
    </div>

    <!-- ── Page grid ──────────────────────────────────────────────── -->
    <div class="page-grid">

        <!-- ── LEFT: Form ────────────────────────────── -->
        <div class="card">
            <div class="card-accent-bar"></div>
            <div class="card-header">
                <div class="card-header-icon"><i class='bx bx-lock-alt'></i></div>
                <div class="card-header-text">
                    <h2>Update Password</h2>
                    <p>Choose a strong password to secure your account</p>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="change_password.php" id="pwForm" novalidate>

                    <div class="section-title">Current Credentials</div>

                    <!-- Current password -->
                    <div class="form-group">
                        <label for="current_password" class="form-label">
                            Current Password <span class="req">*</span>
                        </label>
                        <div class="input-wrap">
                            <div class="input-icon"><i class='bx bx-lock'></i></div>
                            <input type="password" id="current_password" name="current_password"
                                class="form-input <?= !empty($errors['current_password']) ? 'has-error' : '' ?>"
                                placeholder="Enter your current password" autocomplete="current-password">
                            <button type="button" class="toggle-pw" onclick="togglePw('current_password', this)"
                                aria-label="Show/hide">
                                <i class='bx bx-show'></i>
                            </button>
                        </div>
                        <?php if (!empty($errors['current_password'])): ?>
                            <div class="field-error"><i
                                    class='bx bx-error-circle'></i><?= htmlspecialchars($errors['current_password'], ENT_QUOTES) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-divider"></div>
                    <div class="section-title">New Password</div>

                    <!-- New password -->
                    <div class="form-group">
                        <label for="new_password" class="form-label">
                            New Password <span class="req">*</span>
                            <span class="hint" id="strengthLabel">—</span>
                        </label>
                        <div class="input-wrap">
                            <div class="input-icon"><i class='bx bx-key'></i></div>
                            <input type="password" id="new_password" name="new_password"
                                class="form-input <?= !empty($errors['new_password']) ? 'has-error' : '' ?>"
                                placeholder="Minimum 8 characters" autocomplete="new-password">
                            <button type="button" class="toggle-pw" onclick="togglePw('new_password', this)"
                                aria-label="Show/hide">
                                <i class='bx bx-show'></i>
                            </button>
                        </div>
                        <?php if (!empty($errors['new_password'])): ?>
                            <div class="field-error"><i
                                    class='bx bx-error-circle'></i><?= htmlspecialchars($errors['new_password'], ENT_QUOTES) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Strength bar -->
                        <div class="strength-wrap" id="strengthWrap" style="display:none">
                            <div class="strength-bar-track">
                                <div class="strength-bar-fill str-0" id="strengthBar"></div>
                            </div>
                            <!-- Requirements checklist -->
                            <div class="req-list">
                                <div class="req-item" id="req-len">
                                    <div class="req-check"><i class='bx bx-check'></i></div>
                                    At least 8 characters
                                </div>
                                <div class="req-item" id="req-upper">
                                    <div class="req-check"><i class='bx bx-check'></i></div>
                                    One uppercase letter (A–Z)
                                </div>
                                <div class="req-item" id="req-num">
                                    <div class="req-check"><i class='bx bx-check'></i></div>
                                    One number (0–9)
                                </div>
                                <div class="req-item" id="req-special">
                                    <div class="req-check"><i class='bx bx-check'></i></div>
                                    One special character (!@#$…)
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm password -->
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">
                            Confirm New Password <span class="req">*</span>
                        </label>
                        <div class="input-wrap">
                            <div class="input-icon"><i class='bx bx-shield-check'></i></div>
                            <input type="password" id="confirm_password" name="confirm_password"
                                class="form-input <?= !empty($errors['confirm_password']) ? 'has-error' : '' ?>"
                                placeholder="Re-enter your new password" autocomplete="new-password">
                            <button type="button" class="toggle-pw" onclick="togglePw('confirm_password', this)"
                                aria-label="Show/hide">
                                <i class='bx bx-show'></i>
                            </button>
                        </div>
                        <?php if (!empty($errors['confirm_password'])): ?>
                            <div class="field-error" id="matchError"><i
                                    class='bx bx-error-circle'></i><?= htmlspecialchars($errors['confirm_password'], ENT_QUOTES) ?>
                            </div>
                        <?php else: ?>
                            <div class="field-error" id="matchError" style="display:none"><i class='bx bx-error-circle'></i>
                                Passwords do not match.</div>
                            <div class="field-success" id="matchSuccess" style="display:none"><i
                                    class='bx bx-check-circle'></i> Passwords match.</div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <span class="btn-spinner" id="btnSpinner"></span>
                        <i class='bx bx-lock-alt' id="btnIcon"></i>
                        <span id="btnText">Update Password</span>
                    </button>

                </form>
            </div>
        </div>

        <!-- ── RIGHT panel ────────────────────────────── -->
        <div class="right-panel">

            <!-- Profile card -->
            <div class="profile-card">
                <div class="profile-banner"></div>
                <div class="profile-body">
                    <div class="profile-avatar">
                        <?= strtoupper(substr($userInfo['username'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="profile-name"><?= htmlspecialchars($userInfo['username'] ?? '—', ENT_QUOTES) ?></div>
                    <div class="profile-role">
                        <i class='bx bx-shield' style="font-size:12px"></i>
                        <?= htmlspecialchars($userInfo['role'] ?? $role, ENT_QUOTES) ?>
                    </div>
                    <div class="profile-divider"></div>
                    <?php if (!empty($userInfo['email'])): ?>
                        <div class="profile-meta-row">
                            <i class='bx bx-envelope'></i>
                            <span><?= htmlspecialchars($userInfo['email'], ENT_QUOTES) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($userInfo['created_at'])): ?>
                        <div class="profile-meta-row">
                            <i class='bx bx-calendar'></i>
                            <span>Joined <?= date('d M Y', strtotime($userInfo['created_at'])) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="profile-meta-row">
                        <i class='bx bx-time'></i>
                        <span>Changing on <?= date('d M Y') ?></span>
                    </div>
                </div>
            </div>

            <!-- Security tips -->
            <div class="tips-card">
                <div class="tips-header"><i class='bx bx-shield-alt-2'></i> Security Tips</div>
                <div class="tips-body">
                    <div class="tip-row">
                        <div class="tip-dot" style="background:#22c55e"></div>
                        <div class="tip-text"><strong>Use a passphrase</strong> — a random sequence of 3–4 words is both
                            memorable and very strong.</div>
                    </div>
                    <div class="tip-row">
                        <div class="tip-dot" style="background:var(--accent)"></div>
                        <div class="tip-text"><strong>Never reuse passwords</strong> across different websites or
                            applications.</div>
                    </div>
                    <div class="tip-row">
                        <div class="tip-dot" style="background:var(--warning)"></div>
                        <div class="tip-text"><strong>Use a password manager</strong> like Bitwarden or 1Password to
                            generate and store strong passwords.</div>
                    </div>
                    <div class="tip-row">
                        <div class="tip-dot" style="background:#8b5cf6"></div>
                        <div class="tip-text"><strong>Change regularly</strong> — update your password every 3–6 months
                            as a best practice.</div>
                    </div>
                </div>
            </div>

        </div><!-- /.right-panel -->
    </div><!-- /.page-grid -->

    </div><!-- /.dash-content -->
    </section><!-- /.home-section -->

    <script>
        (() => {
            /* ── Show / hide password ────────────────────── */
            window.togglePw = (id, btn) => {
                const input = document.getElementById(id);
                const icon = btn.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'bx bx-hide';
                } else {
                    input.type = 'password';
                    icon.className = 'bx bx-show';
                }
            };

            /* ── Password strength ───────────────────────── */
            const newPw = document.getElementById('new_password');
            const strengthBar = document.getElementById('strengthBar');
            const strengthWrap = document.getElementById('strengthWrap');
            const strengthLbl = document.getElementById('strengthLabel');

            const reqs = {
                len: { el: document.getElementById('req-len'), test: v => v.length >= 8 },
                upper: { el: document.getElementById('req-upper'), test: v => /[A-Z]/.test(v) },
                num: { el: document.getElementById('req-num'), test: v => /[0-9]/.test(v) },
                special: { el: document.getElementById('req-special'), test: v => /[^a-zA-Z0-9]/.test(v) },
            };

            const strengthLevels = [
                { cls: 'str-0', label: '—', color: 'var(--gray-400)' },
                { cls: 'str-1', label: 'Weak', color: '#ef4444' },
                { cls: 'str-2', label: 'Fair', color: '#f59e0b' },
                { cls: 'str-3', label: 'Good', color: '#3b82f6' },
                { cls: 'str-4', label: 'Strong ✓', color: '#22c55e' },
            ];

            newPw?.addEventListener('input', () => {
                const v = newPw.value;
                const show = v.length > 0;
                strengthWrap.style.display = show ? 'block' : 'none';
                newPw.classList.remove('has-error');

                let score = 0;
                for (const key in reqs) {
                    const met = reqs[key].test(v);
                    reqs[key].el.classList.toggle('met', met);
                    if (met) score++;
                }

                const lvl = strengthLevels[score];
                strengthBar.className = 'strength-bar-fill ' + lvl.cls;
                strengthLbl.textContent = lvl.label;
                strengthLbl.style.color = lvl.color;

                checkMatch();
            });

            /* ── Match check ─────────────────────────────── */
            const confirmPw = document.getElementById('confirm_password');
            const matchError = document.getElementById('matchError');
            const matchSuccess = document.getElementById('matchSuccess');

            function checkMatch() {
                if (!confirmPw.value) {
                    matchError.style.display = 'none';
                    matchSuccess.style.display = 'none';
                    confirmPw.classList.remove('has-error', 'is-valid');
                    return;
                }
                const match = newPw.value === confirmPw.value;
                matchError.style.display = match ? 'none' : 'flex';
                matchSuccess.style.display = match ? 'flex' : 'none';
                confirmPw.classList.toggle('has-error', !match);
                confirmPw.classList.toggle('is-valid', match);
            }

            confirmPw?.addEventListener('input', checkMatch);

            // Clear error on type
            document.getElementById('current_password')?.addEventListener('input', function () {
                this.classList.remove('has-error');
            });

            /* ── Submit loading ──────────────────────────── */
            const form = document.getElementById('pwForm');
            const submitBtn = document.getElementById('submitBtn');
            const spinner = document.getElementById('btnSpinner');
            const btnIcon = document.getElementById('btnIcon');
            const btnText = document.getElementById('btnText');

            form?.addEventListener('submit', e => {
                let ok = true;

                const cur = document.getElementById('current_password');
                const nw = document.getElementById('new_password');
                const cf = document.getElementById('confirm_password');

                if (!cur.value.trim()) { cur.classList.add('has-error'); ok = false; }
                if (!nw.value.trim()) { nw.classList.add('has-error'); ok = false; }
                if (!cf.value.trim()) { cf.classList.add('has-error'); ok = false; }
                if (nw.value !== cf.value && nw.value && cf.value) { cf.classList.add('has-error'); ok = false; }

                if (!ok) { e.preventDefault(); return; }

                submitBtn.classList.add('loading');
                spinner.style.display = 'block';
                btnIcon.style.display = 'none';
                btnText.textContent = 'Updating…';
            });

            /* ── Toast ───────────────────────────────────── */
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