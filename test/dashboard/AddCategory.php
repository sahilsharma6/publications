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
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="AddCategory.css">
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
            <a href="<?= $root_url ?>"><i class='bx bx-home-alt'></i> Dashboard</a>
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