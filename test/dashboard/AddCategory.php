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

// ── State ─────────────────────────────────────────────────────────────────────
$toast = null;
$errors = [];
$formVal = '';

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name'] ?? '');

    // ── Validation ────────────────────────────────────────────────────────────
    if ($category_name === '') {
        $errors['name'] = 'Category name is required.';
    } elseif (mb_strlen($category_name) < 2) {
        $errors['name'] = 'Name must be at least 2 characters.';
    } elseif (mb_strlen($category_name) > 100) {
        $errors['name'] = 'Name must be 100 characters or fewer.';
    } else {
        // Duplicate check
        $dup = $conn->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
        $dup->bind_param("s", $category_name);
        $dup->execute();
        $dup->store_result();
        if ($dup->num_rows > 0) {
            $errors['name'] = "\"$category_name\" already exists. Try a different name.";
        }
        $dup->close();
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $category_name);
        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            $stmt->close();
            $conn->close();
            header("Location: AllCategories.php?toast=added&cat=" . urlencode($category_name));
            exit();
        } else {
            $toast = ['type' => 'error', 'msg' => 'Database error: ' . htmlspecialchars($stmt->error, ENT_QUOTES)];
            $formVal = htmlspecialchars($category_name, ENT_QUOTES);
            $stmt->close();
        }
    } else {
        $formVal = htmlspecialchars($category_name, ENT_QUOTES);
    }
}

// ── Recent categories (last 5) ────────────────────────────────────────────────
$recentResult = $conn->query("SELECT id, name FROM categories ORDER BY id DESC LIMIT 5");
$recent = $recentResult ? $recentResult->fetch_all(MYSQLI_ASSOC) : [];

// ── Total count ───────────────────────────────────────────────────────────────
$totalResult = $conn->query("SELECT COUNT(*) FROM categories");
$totalCats = $totalResult ? (int) $totalResult->fetch_row()[0] : 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category — BookAdmin</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        /* ── Variables ───────────────────────────────────── */
        :root {
            --accent: #3b82f6;
            --accent-light: #eff6ff;
            --accent-dark: #1d4ed8;
            --success: #22c55e;
            --success-bg: #f0fdf4;
            --danger: #ef4444;
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
            --shadow: 0 4px 20px rgba(0, 0, 0, .08);
            --shadow-lg: 0 12px 40px rgba(0, 0, 0, .12);
            --t: .2s cubic-bezier(.4, 0, .2, 1);
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

        /* ── Animations ──────────────────────────────────── */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

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

        @keyframes successPop {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            60% {
                transform: scale(1.15);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateX(60px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes toastOut {
            to {
                opacity: 0;
                transform: translateX(60px);
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        /* ── Page layout ─────────────────────────────────── */
        .page-grid {
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: 24px;
            align-items: start;
            animation: fadeUp .4s var(--t) both;
        }

        @media (max-width: 900px) {
            .page-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── Page header ─────────────────────────────────── */
        .page-header {
            margin-bottom: 28px;
            animation: fadeUp .35s var(--t) both;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            color: var(--gray-400);
            margin-bottom: 10px;
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

        .breadcrumb i {
            font-size: 13px;
        }

        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .header-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--r-lg);
            background: var(--accent-light);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .header-text h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--gray-900);
            letter-spacing: -.4px;
        }

        .header-text p {
            font-size: 13px;
            color: var(--gray-400);
            margin-top: 2px;
        }

        .total-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 13px;
            color: var(--gray-600);
            font-weight: 500;
        }

        .total-chip strong {
            color: var(--gray-800);
        }

        /* ── Cards ───────────────────────────────────────── */
        .card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-accent-bar {
            height: 4px;
            background: linear-gradient(90deg, var(--accent), #818cf8, #a78bfa);
        }

        .card-header {
            padding: 22px 26px 16px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header-icon {
            width: 38px;
            height: 38px;
            border-radius: var(--r);
            background: var(--accent-light);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
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

        /* ── Form ────────────────────────────────────────── */
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

        .form-label .required {
            color: var(--danger);
            margin-left: 2px;
        }

        .form-label .hint {
            font-size: 11.5px;
            font-weight: 400;
            color: var(--gray-400);
        }

        /* Input */
        .input-wrap {
            position: relative;
        }

        .input-prefix {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-400);
            font-size: 18px;
            pointer-events: none;
            border-right: 1px solid var(--gray-200);
            transition: all var(--t);
            z-index: 1;
        }

        .form-input {
            width: 100%;
            padding: 11px 42px 11px 54px;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r);
            font-size: 14.5px;
            font-family: inherit;
            color: var(--gray-800);
            background: var(--gray-50);
            outline: none;
            transition: all var(--t);
        }

        .form-input::placeholder {
            color: var(--gray-300);
        }

        .form-input:focus {
            border-color: var(--accent);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
        }

        .input-wrap:focus-within .input-prefix {
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

        /* Clear btn */
        .input-clear {
            position: absolute;
            right: 11px;
            top: 50%;
            transform: translateY(-50%);
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--gray-200);
            border: none;
            color: var(--gray-500);
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            pointer-events: none;
            transition: all var(--t);
            padding: 0;
        }

        .form-input:not(:placeholder-shown)~.input-clear {
            opacity: 1;
            pointer-events: auto;
        }

        .input-clear:hover {
            background: var(--gray-300);
            color: var(--gray-800);
        }

        /* Error message */
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

        /* Char counter */
        .input-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 5px;
            min-height: 18px;
        }

        .char-count {
            font-size: 11.5px;
            color: var(--gray-400);
            font-family: "DM Mono", monospace;
            margin-left: auto;
            transition: color var(--t);
        }

        .char-count.warn {
            color: var(--warning);
        }

        .char-count.over {
            color: var(--danger);
            font-weight: 600;
        }

        /* ── Divider ─────────────────────────────────────── */
        .divider {
            height: 1px;
            background: var(--gray-100);
            margin: 20px -26px;
        }

        /* ── Suggestions ─────────────────────────────────── */
        .suggestions-label {
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .7px;
            color: var(--gray-400);
            margin-bottom: 10px;
        }

        .suggestion-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-bottom: 20px;
        }

        .pill {
            padding: 5px 12px;
            border: 1.5px solid var(--gray-200);
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--gray-600);
            background: var(--gray-50);
            cursor: pointer;
            transition: all var(--t);
            user-select: none;
        }

        .pill:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--accent-light);
        }

        /* ── Submit button ───────────────────────────────── */
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: var(--accent);
            color: #fff;
            font-size: 14.5px;
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
            box-shadow: 0 2px 10px rgba(59, 130, 246, .3);
            position: relative;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, .12);
            opacity: 0;
            transition: opacity var(--t);
        }

        .btn-submit:hover {
            background: var(--accent-dark);
            box-shadow: 0 4px 20px rgba(59, 130, 246, .4);
            transform: translateY(-1px);
        }

        .btn-submit:hover::after {
            opacity: 1;
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

        .btn-view-all {
            width: 100%;
            padding: 11px;
            background: transparent;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r);
            color: var(--gray-600);
            font-size: 13.5px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: all var(--t);
        }

        .btn-view-all:hover {
            background: var(--gray-50);
            border-color: var(--gray-300);
            color: var(--gray-800);
        }

        /* ── Right panel: Recent + Tips ──────────────────── */
        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Recent categories list */
        .recent-list {
            display: flex;
            flex-direction: column;
        }

        .recent-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-100);
            transition: background var(--t);
        }

        .recent-item:last-child {
            border-bottom: none;
        }

        .recent-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .15);
            flex-shrink: 0;
        }

        .recent-name {
            font-size: 13.5px;
            font-weight: 500;
            color: var(--gray-700);
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .recent-id {
            font-size: 11px;
            color: var(--gray-300);
            font-family: "DM Mono", monospace;
            flex-shrink: 0;
        }

        .recent-actions {
            display: flex;
            gap: 5px;
            flex-shrink: 0;
        }

        .mini-btn {
            width: 26px;
            height: 26px;
            border-radius: var(--r-sm);
            border: 1.5px solid transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            transition: all var(--t);
            background: transparent;
        }

        .mini-edit {
            background: var(--accent-light);
            color: var(--accent);
            border-color: rgba(59, 130, 246, .15);
        }

        .mini-edit:hover {
            background: var(--accent);
            color: #fff;
        }

        /* Empty recent */
        .recent-empty {
            text-align: center;
            padding: 28px 16px;
            color: var(--gray-400);
            font-size: 13px;
        }

        .recent-empty i {
            font-size: 32px;
            color: var(--gray-200);
            display: block;
            margin-bottom: 8px;
        }

        /* ── Tips card ───────────────────────────────────── */
        .tips-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .tip-item {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .tip-icon {
            width: 28px;
            height: 28px;
            border-radius: var(--r-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .tip-icon.blue {
            background: #eff6ff;
            color: #3b82f6;
        }

        .tip-icon.green {
            background: #f0fdf4;
            color: #22c55e;
        }

        .tip-icon.orange {
            background: #fff7ed;
            color: #f59e0b;
        }

        .tip-text {
            font-size: 12.5px;
            color: var(--gray-500);
            line-height: 1.55;
            padding-top: 4px;
        }

        .tip-text strong {
            color: var(--gray-700);
            font-weight: 600;
        }

        /* ── Toast ───────────────────────────────────────── */
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
            font-family: "DM Sans", sans-serif;
            min-width: 260px;
            max-width: 380px;
            box-shadow: var(--shadow-lg);
            animation: toastIn .35s var(--t) both;
            cursor: pointer;
        }

        .toast.error {
            background: var(--danger-bg);
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
            font-size: 17px;
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

        /* ── Responsive ──────────────────────────────────── */
        @media (max-width: 560px) {
            .card-body {
                padding: 20px 18px;
            }

            .divider {
                margin: 16px -18px;
            }
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
            <a href="AllCategories.php">Categories</a>
            <i class='bx bx-chevron-right'></i>
            <span>Add New</span>
        </nav>
        <div class="header-row">
            <div class="header-left">
                <div class="header-icon"><i class='bx bx-folder-plus'></i></div>
                <div class="header-text">
                    <h1>Add Category</h1>
                    <p>Create a new category for your books.</p>
                </div>
            </div>
            <div class="total-chip">
                <i class='bx bx-collection' style="font-size:15px;color:var(--accent)"></i>
                <strong><?= number_format($totalCats) ?></strong> categories total
            </div>
        </div>
    </div>

    <!-- ── Two-column grid ────────────────────────────────────────── -->
    <div class="page-grid">

        <!-- ── LEFT: Add form ───────────────────────────── -->
        <div class="card">
            <div class="card-accent-bar"></div>
            <div class="card-header">
                <div class="card-header-icon"><i class='bx bx-plus-circle'></i></div>
                <div class="card-header-text">
                    <h2>New Category</h2>
                    <p>Fill in the details below and submit</p>
                </div>
            </div>
            <div class="card-body">

                <!-- Quick-fill suggestions -->
                <!-- <div class="suggestions-label">Quick suggestions</div>
                <div class="suggestion-pills">
                    <?php
                    $suggestions = [
                        'Fiction',
                        'Non-Fiction',
                        'Science',
                        'History',
                        'Biography',
                        'Technology',
                        'Arts',
                        'Health',
                        'Business',
                        'Travel'
                    ];
                    foreach ($suggestions as $s):
                        ?>
                        <button type="button" class="pill" onclick="fillSuggestion(<?= json_encode($s) ?>)">
                            <?= htmlspecialchars($s, ENT_QUOTES) ?>
                        </button>
                    <?php endforeach; ?>
                </div> -->


                <!-- Form -->
                <form method="POST" action="AddCategory.php" id="addForm" novalidate>

                    <div class="form-group">
                        <label for="category_name" class="form-label">
                            <span>Category Name <span class="required">*</span></span>
                            <span class="hint">max 100 chars</span>
                        </label>
                        <div class="input-wrap">
                            <div class="input-prefix">
                                <i class='bx bx-bookmark'></i>
                            </div>
                            <input type="text" id="category_name" name="category_name"
                                class="form-input <?= !empty($errors['name']) ? 'has-error' : '' ?>"
                                value="<?= $formVal ?>" placeholder="e.g. Science Fiction" maxlength="100"
                                autocomplete="off" autofocus required>
                            <button type="button" class="input-clear" id="clearBtn" tabindex="-1" aria-label="Clear">
                                <i class='bx bx-x'></i>
                            </button>
                        </div>

                        <div class="input-footer">
                            <?php if (!empty($errors['name'])): ?>
                                <div class="field-error">
                                    <i class='bx bx-error-circle'></i>
                                    <?= htmlspecialchars($errors['name'], ENT_QUOTES) ?>
                                </div>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <span class="char-count" id="charCount">
                                <?= mb_strlen($formVal) ?> / 100
                            </span>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <span class="btn-spinner" id="btnSpinner"></span>
                        <i class='bx bx-plus' id="btnIcon"></i>
                        <span id="btnText">Add Category</span>
                    </button>

                    <a href="AllCategories.php" class="btn-view-all">
                        <i class='bx bx-list-ul'></i> View All Categories
                    </a>

                </form>
            </div>
        </div>

        <!-- ── RIGHT: Recent + Tips ──────────────────────── -->
        <div class="right-panel">

            <!-- Recent categories -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-icon" style="background:#f0fdf4;color:#22c55e">
                        <i class='bx bx-time-five'></i>
                    </div>
                    <div class="card-header-text">
                        <h2>Recently Added</h2>
                        <p>Last 5 categories in the system</p>
                    </div>
                </div>
                <div class="card-body" style="padding-top:8px;padding-bottom:8px;">
                    <?php if (empty($recent)): ?>
                        <div class="recent-empty">
                            <i class='bx bx-folder-open'></i>
                            No categories yet
                        </div>
                    <?php else: ?>
                        <div class="recent-list">
                            <?php foreach ($recent as $cat): ?>
                                <div class="recent-item">
                                    <span class="recent-dot"></span>
                                    <span class="recent-name" title="<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>">
                                        <?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>
                                    </span>
                                    <span class="recent-id">#<?= (int) $cat['id'] ?></span>
                                    <div class="recent-actions">
                                        <a href="EditCategory.php?id=<?= (int) $cat['id'] ?>" class="mini-btn mini-edit"
                                            title="Edit">
                                            <i class='bx bx-edit-alt'></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tips -->
            <!-- <div class="card">
                <div class="card-header">
                    <div class="card-header-icon" style="background:#fff7ed;color:#f59e0b">
                        <i class='bx bx-bulb'></i>
                    </div>
                    <div class="card-header-text">
                        <h2>Tips</h2>
                        <p>Best practices for categories</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="tips-list">
                        <div class="tip-item">
                            <div class="tip-icon blue"><i class='bx bx-text'></i></div>
                            <div class="tip-text">
                                <strong>Be concise.</strong> Short, clear names like "Science Fiction" work
                                better than "Books About Science and Fiction Topics."
                            </div>
                        </div>
                        <div class="tip-item">
                            <div class="tip-icon green"><i class='bx bx-check-shield'></i></div>
                            <div class="tip-text">
                                <strong>No duplicates.</strong> The system automatically checks for
                                existing categories so you don't create clashes.
                            </div>
                        </div>
                        <div class="tip-item">
                            <div class="tip-icon orange"><i class='bx bx-link'></i></div>
                            <div class="tip-text">
                                <strong>Think ahead.</strong> Categories are linked to books — renaming
                                later will affect all books under that category.
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->

        </div><!-- /.right-panel -->

    </div><!-- /.page-grid -->

    </div><!-- /.dash-content -->
    </section><!-- /.home-section -->

    <script>
        (() => {
            const input = document.getElementById('category_name');
            const charCount = document.getElementById('charCount');
            const clearBtn = document.getElementById('clearBtn');
            const submitBtn = document.getElementById('submitBtn');
            const spinner = document.getElementById('btnSpinner');
            const btnIcon = document.getElementById('btnIcon');
            const btnText = document.getElementById('btnText');

            // ── Char counter ──────────────────────────────────────
            function updateChar() {
                const len = input.value.length;
                charCount.textContent = `${len} / 100`;
                charCount.className = 'char-count' + (len >= 100 ? ' over' : len >= 80 ? ' warn' : '');
            }

            input.addEventListener('input', () => {
                updateChar();
                input.classList.remove('has-error');
            });

            updateChar();

            // ── Clear button ──────────────────────────────────────
            clearBtn.addEventListener('click', () => {
                input.value = '';
                input.focus();
                updateChar();
            });

            // ── Suggestion pill fill ──────────────────────────────
            window.fillSuggestion = function (name) {
                input.value = name;
                input.focus();
                updateChar();
                input.classList.remove('has-error');
                // Pulse effect on input
                input.style.transition = 'box-shadow .15s';
                input.style.boxShadow = '0 0 0 4px rgba(59,130,246,.2)';
                setTimeout(() => { input.style.boxShadow = ''; }, 350);
            };

            // ── Submit: validate + loading state ─────────────────
            document.getElementById('addForm').addEventListener('submit', function (e) {
                const val = input.value.trim();
                if (!val || val.length < 2) {
                    e.preventDefault();
                    input.classList.add('has-error');
                    input.focus();
                    return;
                }
                // Show loading
                submitBtn.classList.add('loading');
                spinner.style.display = 'block';
                btnIcon.style.display = 'none';
                btnText.textContent = 'Adding…';

            });

            // ── Enter key submits ─────────────────────────────────
            input.addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('addForm').dispatchEvent(new Event('submit', { cancelable: true }));
                    if (!e.defaultPrevented) document.getElementById('addForm').submit();
                }
            });

            // ── Toast dismiss ─────────────────────────────────────
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