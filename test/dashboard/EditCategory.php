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

// ── Validate ID ───────────────────────────────────────────────────────────────
$category_id = (int) ($_GET['id'] ?? $_POST['category_id'] ?? 0);

if ($category_id <= 0) {
    header("Location: AllCategories.php");
    exit();
}

// ── Fetch category ────────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT id, name FROM categories WHERE id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: AllCategories.php?toast=notfound");
    exit();
}

$category = $result->fetch_assoc();
$stmt->close();

// ── Handle POST (update) ──────────────────────────────────────────────────────
$errors = [];
$toast = null;
$formVal = htmlspecialchars($category['name'], ENT_QUOTES); // pre-fill

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['category_name'] ?? '');

    // Validation
    if ($newName === '') {
        $errors['name'] = 'Category name is required.';
    } elseif (mb_strlen($newName) > 100) {
        $errors['name'] = 'Name must be 100 characters or fewer.';
    } else {
        // Duplicate check (excluding self)
        $dup = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ? LIMIT 1");
        $dup->bind_param("si", $newName, $category_id);
        $dup->execute();
        $dup->store_result();
        if ($dup->num_rows > 0) {
            $errors['name'] = "A category named \"$newName\" already exists.";
        }
        $dup->close();
    }

    if (empty($errors)) {
        $upd = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $upd->bind_param("si", $newName, $category_id);
        if ($upd->execute()) {
            // Redirect with success toast param
            header("Location: AllCategories.php?toast=updated&cat=" . urlencode($newName));
            exit();
        } else {
            $toast = ['type' => 'error', 'msg' => 'Database error: ' . htmlspecialchars($upd->error, ENT_QUOTES)];
        }
        $upd->close();
    } else {
        $formVal = htmlspecialchars($newName, ENT_QUOTES);
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category — BookAdmin</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --accent: #3b82f6;
            --accent-light: #eff6ff;
            --accent-dark: #1d4ed8;
            --success: #22c55e;
            --success-bg: #f0fdf4;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
            --warning: #f59e0b;
            --warning-bg: #fffbeb;
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

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateX(60px) scale(.96);
            }

            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes toastOut {
            to {
                opacity: 0;
                transform: translateX(60px);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, .4);
            }

            50% {
                box-shadow: 0 0 0 6px rgba(59, 130, 246, 0);
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
                transform: translateX(-4px);
            }

            80% {
                transform: translateX(4px);
            }
        }

        /* ── Layout wrapper ──────────────────────────────── */
        .edit-wrap {
            max-width: 640px;
            animation: fadeUp .4s var(--t) both;
        }

        /* ── Breadcrumb + header ─────────────────────────── */
        .page-header {
            margin-bottom: 28px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            color: var(--gray-400);
            margin-bottom: 10px;
            flex-wrap: wrap;
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

        .page-title-row {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .page-title-icon {
            width: 46px;
            height: 46px;
            border-radius: var(--r-lg);
            background: var(--accent-light);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .page-title-text h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--gray-900);
            letter-spacing: -.4px;
            line-height: 1.2;
        }

        .page-title-text p {
            font-size: 13px;
            color: var(--gray-400);
            margin-top: 2px;
        }

        .cat-id-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--gray-100);
            color: var(--gray-500);
            font-size: 11.5px;
            font-family: "DM Mono", monospace;
            padding: 3px 10px;
            border-radius: 20px;
            border: 1px solid var(--gray-200);
            margin-top: 4px;
        }

        /* ── Card ────────────────────────────────────────── */
        .edit-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .card-top-bar {
            height: 4px;
            background: linear-gradient(90deg, var(--accent) 0%, #818cf8 60%, #a78bfa 100%);
        }

        .card-body {
            padding: 32px;
        }

        /* ── Current value banner ────────────────────────── */
        .current-banner {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--r);
            padding: 14px 16px;
            margin-bottom: 28px;
        }

        .current-banner-icon {
            width: 36px;
            height: 36px;
            border-radius: var(--r-sm);
            background: var(--accent-light);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
        }

        .current-banner-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: var(--gray-400);
            margin-bottom: 2px;
        }

        .current-banner-val {
            font-size: 14.5px;
            font-weight: 600;
            color: var(--gray-800);
        }

        /* ── Form ────────────────────────────────────────── */
        .form-group {
            margin-bottom: 22px;
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
            margin-left: 3px;
        }

        .form-label .label-hint {
            font-size: 11.5px;
            font-weight: 400;
            color: var(--gray-400);
        }

        /* Input wrapper */
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
            border-radius: var(--r) 0 0 var(--r);
        }

        .form-input {
            width: 100%;
            padding: 11px 44px 11px 54px;
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

        .form-input:focus~.input-prefix,
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

        /* Clear button */
        .input-clear {
            position: absolute;
            right: 12px;
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
        }

        .form-input:not(:placeholder-shown)~.input-clear {
            opacity: 1;
            pointer-events: auto;
        }

        .input-clear:hover {
            background: var(--gray-300);
            color: var(--gray-700);
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
        .char-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 5px;
        }

        .char-count {
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

        /* ── Divider ─────────────────────────────────────── */
        .divider {
            height: 1px;
            background: var(--gray-100);
            margin: 24px -32px;
        }

        /* ── Action buttons ──────────────────────────────── */
        .form-actions {
            display: flex;
            gap: 12px;
            align-items: center;
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
            box-shadow: 0 2px 10px rgba(59, 130, 246, .3);
            position: relative;
            overflow: hidden;
        }

        .btn-save::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, .15);
            opacity: 0;
            transition: opacity var(--t);
        }

        .btn-save:hover {
            background: var(--accent-dark);
            box-shadow: 0 4px 20px rgba(59, 130, 246, .4);
            transform: translateY(-1px);
        }

        .btn-save:hover::after {
            opacity: 1;
        }

        .btn-save:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(59, 130, 246, .25);
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

        /* Spinner */
        .btn-spinner {
            width: 16px;
            height: 16px;
            border: 2.5px solid rgba(255, 255, 255, .35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
            display: none;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ── Tip card ────────────────────────────────────── */
        .tip-card {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: var(--r-lg);
            padding: 14px 16px;
            display: flex;
            gap: 11px;
            align-items: flex-start;
        }

        .tip-card i {
            font-size: 18px;
            color: #0284c7;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .tip-card p {
            font-size: 13px;
            color: #0c4a6e;
            line-height: 1.55;
        }

        .tip-card strong {
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
            font-size: 16px;
            cursor: pointer;
            color: inherit;
            opacity: .6;
            line-height: 1;
            padding: 0;
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
                padding: 22px 18px;
            }

            .divider {
                margin: 20px -18px;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .btn-save,
            .btn-cancel {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>

    <?php include './sidebar.php'; ?>

    <?php if ($toast): ?>
        <div class="toast <?= $toast['type'] ?>" id="toast">
            <i class='bx bx-x-circle'></i>
            <?= htmlspecialchars($toast['msg'], ENT_QUOTES) ?>
            <button class="toast-close" onclick="dismissToast()">&times;</button>
        </div>
    <?php endif; ?>

    <div class="edit-wrap">

        <!-- ── Page header ────────────────────────────── -->
        <div class="page-header">
            <nav class="breadcrumb" aria-label="breadcrumb">
                <a href="dashboard.php"><i class='bx bx-home-alt'></i> Dashboard</a>
                <i class='bx bx-chevron-right'></i>
                <a href="AllCategories.php">Categories</a>
                <i class='bx bx-chevron-right'></i>
                <span>Edit</span>
            </nav>
            <div class="page-title-row">
                <div class="page-title-icon"><i class='bx bx-edit'></i></div>
                <div class="page-title-text">
                    <h1>Edit Category</h1>
                    <p>Update the name for this category.</p>
                    <span class="cat-id-chip">
                        <i class='bx bx-hash' style="font-size:12px"></i>ID
                        <?= $category_id ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- ── Edit card ──────────────────────────────── -->
        <div class="edit-card">
            <div class="card-top-bar"></div>
            <div class="card-body">

                <!-- Current value banner -->
                <div class="current-banner">
                    <div class="current-banner-icon"><i class='bx bx-bookmark'></i></div>
                    <div>
                        <div class="current-banner-label">Current name</div>
                        <div class="current-banner-val">
                            <?= htmlspecialchars($category['name'], ENT_QUOTES) ?>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <form method="POST" action="EditCategory.php?id=<?= $category_id ?>" id="editForm" novalidate>
                    <input type="hidden" name="category_id" value="<?= $category_id ?>">

                    <div class="form-group">
                        <label for="category_name" class="form-label">
                            <span>New Name <span class="required">*</span></span>
                            <span class="label-hint">max 100 chars</span>
                        </label>

                        <div class="input-wrap">
                            <div class="input-prefix">
                                <i class='bx bx-collection'></i>
                            </div>
                            <input type="text" id="category_name" name="category_name"
                                class="form-input <?= !empty($errors['name']) ? 'has-error' : '' ?>"
                                value="<?= $formVal ?>" placeholder="e.g. Science Fiction" maxlength="100"
                                autocomplete="off" required>
                            <button type="button" class="input-clear" id="clearBtn" tabindex="-1" aria-label="Clear">
                                <i class='bx bx-x'></i>
                            </button>
                        </div>

                        <?php if (!empty($errors['name'])): ?>
                            <div class="field-error">
                                <i class='bx bx-error-circle'></i>
                                <?= htmlspecialchars($errors['name'], ENT_QUOTES) ?>
                            </div>
                        <?php endif; ?>

                        <div class="char-row">
                            <span class="char-count" id="charCount">
                                <?= mb_strlen($formVal) ?> / 100
                            </span>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div class="form-actions">
                        <a href="AllCategories.php" class="btn-cancel">
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

        <!-- ── Tip ────────────────────────────────────── -->
        <div class="tip-card">
            <i class='bx bx-info-circle'></i>
            <p>
                <strong>Heads up:</strong> Renaming a category updates it everywhere it is used across
                the system. Make sure the new name is clear and consistent with your other categories.
            </p>
        </div>

    </div><!-- /.edit-wrap -->

    </div><!-- /.dash-content -->
    </section><!-- /.home-section -->

    <script>
        (() => {
            const input = document.getElementById('category_name');
            const charCount = document.getElementById('charCount');
            const clearBtn = document.getElementById('clearBtn');
            const saveBtn = document.getElementById('saveBtn');
            const spinner = document.getElementById('btnSpinner');
            const btnIcon = document.getElementById('btnIcon');
            const btnText = document.getElementById('btnText');

            // ── Char counter ──────────────────────────────────────
            function updateCharCount() {
                const len = input.value.length;
                charCount.textContent = `${len} / 100`;
                charCount.className = 'char-count' + (len >= 100 ? ' over' : len >= 80 ? ' warn' : '');
            }

            input.addEventListener('input', updateCharCount);
            updateCharCount();

            // ── Clear button ──────────────────────────────────────
            clearBtn.addEventListener('click', () => {
                input.value = '';
                input.focus();
                updateCharCount();
            });

            // ── Submit — show spinner + disable ───────────────────
            document.getElementById('editForm').addEventListener('submit', function (e) {
                const val = input.value.trim();

                // Client-side guard
                if (!val) {
                    e.preventDefault();
                    input.classList.add('has-error');
                    input.focus();
                    return;
                }

                // Show loading state
                saveBtn.classList.add('loading');
                spinner.style.display = 'block';
                btnIcon.style.display = 'none';
                btnText.textContent = 'Saving…';
            });

            // Remove error class on type
            input.addEventListener('input', () => input.classList.remove('has-error'));

            // ── Auto-focus + select all text on load ──────────────
            input.focus();
            input.select();

            // ── Toast dismiss ─────────────────────────────────────
            const toast = document.getElementById('toast');
            if (toast) {
                const t = setTimeout(dismissToast, 4500);
                toast.addEventListener('click', () => { clearTimeout(t); dismissToast(); });
            }
        })();

        function dismissToast() {
            const toast = document.getElementById('toast');
            if (!toast) return;
            toast.classList.add('hiding');
            setTimeout(() => toast.remove(), 320);
        }
    </script>
</body>

</html>