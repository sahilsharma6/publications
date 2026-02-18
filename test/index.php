<?php
include '../db.php';
session_start();

$sql = "SELECT * FROM categories";
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

    <!-- NAVBAR -->
    <!-- <nav class="navbar navbar-expand-lg navbar-modern">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><img src="../uploads/logos/logotest.png" height="100" width="150"
                    alt=""></a>
            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Books</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
                </ul>
                <a href="login.php" class="btn btn-primary-custom">Login</a>
            </div>
        </div>
    </nav> -->
    <?php include 'header.php'; ?>

    <!-- HERO -->
    <section class="hero-carousel">
        <div id="mainCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">

            <div class="carousel-inner">

                <!-- Slide 1 -->
                <div class="carousel-item active">
                    <div class="hero-slide slide-1">
                        <div class="hero-content container">
                            <h1>Professional Publication Services</h1>
                            <p>Trusted academic and scientific publication support since 2020.</p>
                            <a href="#" class="btn btn-hero">Explore Services</a>
                        </div>
                    </div>
                </div>

                <!-- Slide 2 -->
                <div class="carousel-item">
                    <div class="hero-slide slide-2">
                        <div class="hero-content container">
                            <h1>Expert Medical Writing</h1>
                            <p>Manuscripts, journals and editing handled by professionals.</p>
                            <a href="#" class="btn btn-hero">View Services</a>
                        </div>
                    </div>
                </div>

                <!-- Slide 3 -->
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


    <!-- BOOKS -->
    <section class="books-section py-5">
        <div class="container">

            <h2 class="text-center mb-5">
                Best <span class="text-accent">Books</span>
            </h2>

            <!-- Tabs -->
            <ul class="nav justify-content-center custom-tabs mb-5" role="tablist">
                <?php foreach ($categories as $index => $category): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $index == 0 ? 'active' : ''; ?>" data-bs-toggle="tab"
                            data-bs-target="#cat-<?php echo $category['id']; ?>" type="button" role="tab">
                            <?php echo ucfirst($category['name']); ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">

                <?php foreach ($categories as $index => $category): ?>

                    <div class="tab-pane fade <?php echo $index == 0 ? 'show active' : ''; ?>"
                        id="cat-<?php echo $category['id']; ?>" role="tabpanel">

                        <div class="row g-4">

                            <?php
                            include '../db.php';
                            $cat_id = $category['id'];

                            $books_query = "SELECT * FROM books_data WHERE category_id = $cat_id";
                            $books_result = $conn->query($books_query);

                            if ($books_result->num_rows > 0):
                                while ($book = $books_result->fetch_assoc()):
                                    ?>

                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <a href="book_details.php?id=<?php echo $book['id']; ?>" class="text-decoration-none">

                                            <div class="book-card-modern h-100">

                                                <div class="book-img-wrapper">
                                                    <img src="../<?php echo $book['img']; ?>" alt="<?php echo $book['name']; ?>">
                                                </div>

                                                <div class="book-body">
                                                    <h5><?php echo $book['name']; ?></h5>
                                                    <p class="price">₹ <?php echo $book['price']; ?></p>
                                                </div>

                                            </div>

                                        </a>
                                    </div>

                                <?php endwhile; else: ?>

                                <p class="text-center">No books available in this category.</p>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

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

    <!-- SERVICES -->
    <section>
        <div class="container text-center">
            <h2 class="mb-5">Our Services</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="service-card">
                        <i class="fa-solid fa-book-open fa-2x mb-3 "></i>
                        <h5>Manuscript Writing</h5>
                        <p>Professional writing and editing services for research publications.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="service-card">
                        <i class="fa-solid fa-magnifying-glass fa-2x mb-3"></i>
                        <h5>Journal Selection</h5>
                        <p>Expert assistance in choosing the right journal for publication.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="service-card">
                        <i class="fa-solid fa-pen-nib fa-2x mb-3"></i>
                        <h5>Editing Services</h5>
                        <p>Language editing and formatting to meet journal guidelines.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="service-card">
                        <i class="fa-solid fa-file-signature fa-2x mb-3"></i>
                        <h5>Publication Support</h5>
                        <p>End-to-end assistance from submission to final publication.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="container text-center">
            <h5>Professional Publication Services</h5>
            <p>©
                <?php echo date("Y"); ?> All Rights Reserved
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            function loadBooks(categoryName) {
                fetch('filterbycat.php?category_name=' + categoryName)
                    .then(res => res.json())
                    .then(books => {
                        const tab = document.getElementById(categoryName);
                        tab.innerHTML = '';
                        books.forEach(book => {
                            tab.innerHTML += `
<a href="book_details.php?id=${book.id}" class="text-decoration-none">
<div class="book-card">
<img src="${book.img}" class="img-fluid mb-3">
<h5>${book.name}</h5>
<p class="price">RS ${book.price}</p>
</div>
</a>`;
                        });
                    });
            }
            loadBooks("<?php echo $categories[0]['name']; ?>");
            document.querySelectorAll(".nav-link").forEach(link => {
                link.addEventListener("click", function () {
                    loadBooks(this.dataset.categoryName);
                });
            });
        });
    </script>

</body>

</html>