<?php
include '../db.php';
session_start();

// Fetch only categories that have at least 1 best book
$sql = "SELECT DISTINCT c.id, c.name
        FROM categories c
        INNER JOIN best_books bb ON bb.category_id = c.id
        INNER JOIN books_data b  ON b.id = bb.book_id
        ORDER BY c.name ASC";
$result = $conn->query($sql);

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Publication Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include 'header.php'; ?>

    <!-- HERO -->
    <section class="hero-carousel">
        <div id="mainCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
            <div class="carousel-inner">

                <div class="carousel-item active">
                    <div class="hero-slide slide-1">
                        <div class="hero-content container">
                            <h1>Professional Publication Services</h1>
                            <p>Trusted academic and scientific publication support since 2020.</p>
                            <a href="#" class="btn btn-hero">Explore Services</a>
                        </div>
                    </div>
                </div>

                <div class="carousel-item">
                    <div class="hero-slide slide-2">
                        <div class="hero-content container">
                            <h1>Expert Medical Writing</h1>
                            <p>Manuscripts, journals and editing handled by professionals.</p>
                            <a href="#" class="btn btn-hero">View Services</a>
                        </div>
                    </div>
                </div>

                <div class="carousel-item">
                    <div class="hero-slide slide-3">
                        <div class="hero-content container">
                            <h1>End-to-End Publication Support</h1>
                            <p>From drafting to journal submission — we manage it all.</p>
                            <a href="#" class="btn btn-hero">Contact Us</a>
                        </div>
                    </div>
                </div>

            </div>
            <button class="carousel-control-prev custom-arrow" type="button" data-bs-target="#mainCarousel"
                data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next custom-arrow" type="button" data-bs-target="#mainCarousel"
                data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>
    </section>


    <!-- ══════════════════════════════════════
         BEST BOOKS SECTION
    ══════════════════════════════════════ -->
    <section class="books-section py-5">
        <div class="container">

            <h2 class="text-center mb-2">
                Best <span class="text-accent">Books</span>
            </h2>
            <p class="text-center text-muted mb-5" style="font-size:15px">
                Handpicked titles — curated by category
            </p>

            <?php if (empty($categories)): ?>
                <p class="text-center text-muted">No featured books yet. Check back soon.</p>
            <?php else: ?>

                <!-- Category tabs -->
                <ul class="nav justify-content-center custom-tabs mb-5" role="tablist">
                    <?php foreach ($categories as $index => $category): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $index === 0 ? 'active' : '' ?>" data-bs-toggle="tab"
                                data-bs-target="#cat-<?= (int) $category['id'] ?>" type="button" role="tab">
                                <?= htmlspecialchars(ucfirst($category['name']), ENT_QUOTES) ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Tab content -->
                <div class="tab-content">
                    <?php
                    include '../db.php';

                    foreach ($categories as $index => $category):
                        $cat_id = (int) $category['id'];

                        // ── KEY CHANGE: only best books, in admin-defined order ──
                        $bStmt = $conn->prepare("
                        SELECT b.id, b.title, b.img, b.price
                        FROM   books_data b
                        INNER JOIN best_books bb ON bb.book_id = b.id
                        WHERE  bb.category_id = ?
                        ORDER  BY bb.sort_order ASC
                    ");
                        $bStmt->bind_param("i", $cat_id);
                        $bStmt->execute();
                        $bRes = $bStmt->get_result();
                        $bStmt->close();
                        ?>
                        <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" id="cat-<?= $cat_id ?>"
                            role="tabpanel">

                            <?php if ($bRes && $bRes->num_rows > 0): ?>
                                <div class="row g-4">
                                    <?php while ($book = $bRes->fetch_assoc()): ?>
                                        <div class="col-lg-3 col-md-4 col-sm-6">
                                            <a href="bd.php?id=<?= (int) $book['id'] ?>" class="text-decoration-none">
                                                <div class="book-card-modern h-100">
                                                    <div class="book-img-wrapper">
                                                        <img src="../<?= htmlspecialchars($book['img'], ENT_QUOTES) ?>"
                                                            alt="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>">
                                                    </div>
                                                    <div class="book-body">
                                                        <h5><?= htmlspecialchars($book['title'], ENT_QUOTES) ?></h5>
                                                        <p class="price">₹ <?= htmlspecialchars($book['price'], ENT_QUOTES) ?></p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endwhile; ?>
                                </div>

                            <?php else: ?>
                                <p class="text-center text-muted py-4">No featured books in this category yet.</p>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                </div><!-- /.tab-content -->

            <?php endif; ?>

        </div>
    </section>


    <!-- ABOUT -->
    <section class="about-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <img src="../uploads/assets/about-img.png" class="img-fluid">
                </div>
                <div class="col-md-6">
                    <h2>About Us</h2>
                    <p class="mt-4">
                        Founded in 2020, Professional Publication Services has established itself as a trusted provider
                        in academic and scientific publishing.
                    </p>
                    <p>
                        We have served over 1,000 doctors, researchers and scientists across various domains.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- SERVICES — dynamic from DB -->
    <?php
    include '../db.php';
    $svcRes = $conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
    $svcList = $svcRes ? $svcRes->fetch_all(MYSQLI_ASSOC) : [];
    $conn->close();
    ?>
    <?php if (!empty($svcList)): ?>
        <section>
            <div class="container text-center">
                <h2 class="mb-5">Our Services</h2>
                <div class="row g-4">
                    <?php
                    // Distribute into Bootstrap columns: 4 per row
                    $count = count($svcList);
                    $colCls = $count <= 3 ? 'col-md-' . (int) (12 / $count) : 'col-md-3';
                    foreach ($svcList as $svc):
                        ?>
                        <div class="<?= $colCls ?> col-sm-6">
                            <div class="service-card">
                                <i class="<?= htmlspecialchars($svc['icon'], ENT_QUOTES) ?> fa-2x mb-3"></i>
                                <h5><?= htmlspecialchars($svc['title'], ENT_QUOTES) ?></h5>
                                <?php if (!empty($svc['description'])): ?>
                                    <p><?= htmlspecialchars($svc['description'], ENT_QUOTES) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>