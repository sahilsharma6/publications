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

    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="EditCategory.css">

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