<?php
session_start();
include '../../../db.php';

/* ── Auth Guard ──────────────────────────────────────────────────────────── */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'SuperAdmin'])) {
    header("Location: login.php");
    exit();
}

$errors = [];
$toast = null;
$oldPost = [];

/* ── Handle POST ─────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $oldPost = $_POST;

    $name = trim($_POST['name'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $book_id = (int) ($_POST['book_id'] ?? 0);

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

    /* ── Image Upload ── */
    $imageName = null;

    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../../uploads/authors/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            $errors['image'] = 'Only JPG, PNG, WEBP files are allowed.';
        } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
            $errors['image'] = 'Image must be under 3 MB.';
        } else {
            $imageName = uniqid('author_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName)) {
                $errors['image'] = 'Failed to upload image. Check folder permissions.';
                $imageName = null;
            }
        }
    }

    /* ── Insert ── */
    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO authors (name, title, description, image) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("ssss", $name, $title, $description, $imageName);

        if ($stmt->execute()) {
            $author_id = $conn->insert_id;
            $stmt->close();

            /* ── Assign single book ── */
            if ($book_id > 0) {
                $pivot = $conn->prepare(
                    "INSERT IGNORE INTO book_authors (book_id, author_id) VALUES (?, ?)"
                );
                $pivot->bind_param("ii", $book_id, $author_id);
                $pivot->execute();
                $pivot->close();
            }

            $conn->close();
            header("Location: AllAuthors.php?toast=added&author=" . urlencode($name));
            exit();

        } else {
            $toast = ['type' => 'error', 'msg' => 'Database error: ' . htmlspecialchars($conn->error, ENT_QUOTES)];
        }
    }
}

/* ── Fetch Books for dropdown ─────────────────────────────────────────────── */
$booksRes = $conn->query("SELECT id, name FROM books_data ORDER BY name ASC");
$books = $booksRes ? $booksRes->fetch_all(MYSQLI_ASSOC) : [];

/* ── Stats ─────────────────────────────────────────────────────────────────── */
$totalAuthors = (int) $conn->query("SELECT COUNT(*) FROM authors")->fetch_row()[0];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Author — BookAdmin</title>
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="AddAuthor.css">

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

    <!-- ── Page header ─────────────────────────────────────────────── -->
    <div class="page-header">
        <h1>Add Author</h1>
        <nav class="breadcrumb" aria-label="breadcrumb">
            <a href="../"><i class='bx bx-home-alt'></i> Dashboard</a>
            <i class='bx bx-chevron-right'></i>
            <a href="AllAuthors.php">Authors</a>
            <i class='bx bx-chevron-right'></i>
            <span>Add New</span>
        </nav>
    </div>

    <!-- ── Grid ───────────────────────────────────────────────────── -->
    <div class="page-grid">

        <!-- ── LEFT: Main form ──────────────────────────── -->
        <div class="card">
            <div class="card-accent-bar"></div>
            <div class="card-header">
                <div class="card-header-icon"><i class='bx bx-user-plus'></i></div>
                <div class="card-header-text">
                    <h2>Author Details</h2>
                    <p>Fill in the author's information below</p>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="authorForm" novalidate>

                    <!-- ── Basic Info ── -->
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
                                    value="<?= htmlspecialchars($oldPost['name'] ?? '', ENT_QUOTES) ?>"
                                    placeholder="e.g. J.K. Rowling" maxlength="120" autocomplete="off" autofocus>
                            </div>
                            <?php if (!empty($errors['name'])): ?>
                                <div class="field-error">
                                    <i class='bx bx-error-circle'></i>
                                    <?= htmlspecialchars($errors['name'], ENT_QUOTES) ?>
                                </div>
                            <?php endif; ?>
                            <span class="char-count" id="nameCount">
                                <?= mb_strlen($oldPost['name'] ?? '') ?> / 120
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
                                    value="<?= htmlspecialchars($oldPost['title'] ?? '', ENT_QUOTES) ?>"
                                    placeholder="e.g. Novelist, Poet" maxlength="100" autocomplete="off">
                            </div>
                            <?php if (!empty($errors['title'])): ?>
                                <div class="field-error">
                                    <i class='bx bx-error-circle'></i>
                                    <?= htmlspecialchars($errors['title'], ENT_QUOTES) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label for="author_desc" class="form-label">
                            <span>Biography</span>
                            <span class="lbl-hint">optional</span>
                        </label>
                        <textarea id="author_desc" name="description" class="form-input no-icon" rows="4"
                            placeholder="Write a short biography of the author…"
                            maxlength="1000"><?= htmlspecialchars($oldPost['description'] ?? '', ENT_QUOTES) ?></textarea>
                        <span class="char-count" id="descCount">
                            <?= mb_strlen($oldPost['description'] ?? '') ?> / 1000
                        </span>
                    </div>

                    <!-- ── Media ── -->
                    <div class="section-title">Photo</div>

                    <!-- Image upload zone -->
                    <div class="form-group">
                        <label class="form-label">
                            <span>Author Photo</span>
                            <span class="lbl-hint">JPG, PNG, WEBP · max 3 MB</span>
                        </label>
                        <div class="upload-zone" id="uploadZone">
                            <input type="file" name="image" id="imageInput" accept=".jpg,.jpeg,.png,.webp">
                            <div class="upload-zone-icon">
                                <i class='bx bx-cloud-upload' id="uploadIcon"></i>
                            </div>
                            <div class="upload-zone-label">Drop image here or <span
                                    style="color:var(--accent)">browse</span></div>
                            <div class="upload-zone-hint">
                                <span>JPG</span>, <span>PNG</span>, <span>WEBP</span> — max 3 MB
                            </div>
                            <div class="img-preview-wrap" id="previewWrap">
                                <img id="imgPreview" class="img-preview" src="" alt="Preview">
                                <button type="button" class="img-remove" id="removeImg" title="Remove">
                                    <i class='bx bx-x'></i>
                                </button>
                            </div>
                        </div>
                        <?php if (!empty($errors['image'])): ?>
                            <div class="field-error" style="margin-top:8px">
                                <i class='bx bx-error-circle'></i>
                                <?= htmlspecialchars($errors['image'], ENT_QUOTES) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ── Book Assignment ── -->
                    <div class="section-title">Book Assignment</div>

                    <div class="form-group">
                        <label for="book_id" class="form-label">
                            <span>Assign to Book</span>
                            <span class="lbl-hint">optional · one book per author</span>
                        </label>
                        <div class="input-wrap book-select-wrap">
                            <div class="input-icon"><i class='bx bx-book-open'></i></div>
                            <select name="book_id" id="book_id" class="form-input">
                                <option value="0">— No book selected —</option>
                                <?php foreach ($books as $b): ?>
                                    <option value="<?= (int) $b['id'] ?>" <?= ((int) ($oldPost['book_id'] ?? 0) === (int) $b['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['name'], ENT_QUOTES) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div
                            style="margin-top:6px;font-size:12px;color:var(--gray-400);display:flex;align-items:center;gap:5px">
                            <i class='bx bx-info-circle' style="font-size:13px"></i>
                            You can assign more books later from the author's edit page.
                        </div>
                    </div>

                    <div class="form-divider"></div>

                    <div class="form-actions">
                        <a href="AllAuthors.php" class="btn-cancel">
                            <i class='bx bx-arrow-back'></i> Cancel
                        </a>
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <span class="btn-spinner" id="btnSpinner"></span>
                            <i class='bx bx-user-plus' id="btnIcon"></i>
                            <span id="btnText">Add Author</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <!-- ── RIGHT panel ──────────────────────────────── -->
        <div class="right-panel">

            <!-- Stats -->
            <div class="stat-mini">
                <div class="stat-mini-icon si-purple"><i class='bx bx-group'></i></div>
                <div>
                    <div class="stat-mini-val"><?= number_format($totalAuthors) ?></div>
                    <div class="stat-mini-label">Authors in system</div>
                </div>
            </div>

            <div class="stat-mini">
                <div class="stat-mini-icon si-blue"><i class='bx bx-book-alt'></i></div>
                <div>
                    <div class="stat-mini-val"><?= number_format(count($books)) ?></div>
                    <div class="stat-mini-label">Books available</div>
                </div>
            </div>

            <!-- Tips -->
            <div class="tips-card">
                <div class="tips-card-header">
                    <i class='bx bx-bulb'></i> Tips
                </div>
                <div class="tips-body">
                    <div class="tip-row">
                        <div class="tip-dot"></div>
                        <div class="tip-text">
                            <strong>Full name matters.</strong> Use the author's complete, published name
                            as it appears on their books.
                        </div>
                    </div>
                    <div class="tip-row">
                        <div class="tip-dot" style="background:var(--success)"></div>
                        <div class="tip-text">
                            <strong>One book now.</strong> You can assign additional books from the
                            edit page after saving.
                        </div>
                    </div>
                    <div class="tip-row">
                        <div class="tip-dot" style="background:var(--warning)"></div>
                        <div class="tip-text">
                            <strong>Photo.</strong> Square images work best —
                            aim for at least 200×200 px for a crisp display.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick links -->
            <div class="quick-actions">
                <div class="quick-actions-header">
                    <i class='bx bx-link-alt'></i> Quick Actions
                </div>
                <div class="quick-link-list">
                    <a href="AllAuthors.php" class="quick-link">
                        <i class='bx bx-list-ul'></i> View All Authors
                    </a>
                    <a href="../AllBooks.php" class="quick-link">
                        <i class='bx bx-book-alt'></i> View All Books
                    </a>
                    <a href="../AddBook.php" class="quick-link">
                        <i class='bx bx-plus-circle'></i> Add New Book
                    </a>
                </div>
            </div>

        </div><!-- /.right-panel -->

    </div><!-- /.page-grid -->

    </div><!-- /.dash-content -->
    </section><!-- /.home-section -->

    <script>
        (() => {
            /* ── Char counters ───────────────────────────── */
            function counter(inputId, counterId, max) {
                const el = document.getElementById(inputId);
                const cnt = document.getElementById(counterId);
                if (!el || !cnt) return;
                const update = () => {
                    const len = el.value.length;
                    cnt.textContent = `${len} / ${max}`;
                    cnt.className = 'char-count' + (len >= max ? ' over' : len >= max * .8 ? ' warn' : '');
                };
                el.addEventListener('input', update);
                update();
            }
            counter('author_name', 'nameCount', 120);
            counter('author_desc', 'descCount', 1000);

            /* ── Remove error class on type ─────────────── */
            document.querySelectorAll('.form-input').forEach(i => {
                i.addEventListener('input', () => i.classList.remove('has-error'));
            });

            /* ── Image preview ───────────────────────────── */
            const fileInput = document.getElementById('imageInput');
            const previewWrap = document.getElementById('previewWrap');
            const imgPreview = document.getElementById('imgPreview');
            const removeBtn = document.getElementById('removeImg');
            const uploadZone = document.getElementById('uploadZone');

            fileInput.addEventListener('change', () => showPreview(fileInput.files[0]));

            /* Drag & drop */
            uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
            uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
            uploadZone.addEventListener('drop', e => {
                e.preventDefault();
                uploadZone.classList.remove('drag-over');
                const file = e.dataTransfer.files[0];
                if (file) { showPreview(file); }
            });

            function showPreview(file) {
                if (!file || !file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = e => {
                    imgPreview.src = e.target.result;
                    previewWrap.classList.add('show');
                };
                reader.readAsDataURL(file);
            }

            removeBtn.addEventListener('click', e => {
                e.stopPropagation();
                fileInput.value = '';
                imgPreview.src = '';
                previewWrap.classList.remove('show');
            });

            /* ── Form submit ─────────────────────────────── */
            const form = document.getElementById('authorForm');
            const submitBtn = document.getElementById('submitBtn');
            const spinner = document.getElementById('btnSpinner');
            const btnIcon = document.getElementById('btnIcon');
            const btnText = document.getElementById('btnText');
            const nameInput = document.getElementById('author_name');

            form.addEventListener('submit', e => {
                if (!nameInput.value.trim() || nameInput.value.trim().length < 2) {
                    e.preventDefault();
                    nameInput.classList.add('has-error');
                    nameInput.focus();
                    return;
                }
                submitBtn.classList.add('loading');
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

        function dismissToast() {
            const t = document.getElementById('toast');
            if (!t) return;
            t.classList.add('hiding');
            setTimeout(() => t.remove(), 320);
        }
    </script>
</body>

</html>