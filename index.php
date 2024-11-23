<?php
// Include the database connection
include 'db.php';
session_start();

// Fetch categories from the database
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description"
    content="Founded in 2020, Professional Publication Services has rapidly established itself as a trusted provider in the academic and scientific writing industry." />

  <title>Professional Publication Service</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />

  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
</head>

<body>
  <!-- Top Bar with Contact Info -->
  <!-- <div class="top-bar py-2 text-white text-center">
    <div class="container d-flex justify-content-between align-items-center">
      <span>CALL US - +91-95015 44877</span>
      <span>EMAIL - aaa@gmail.com</span>
    </div>
  </div> -->

  <!-- Navbar -->
  <!-- <nav class="navbar navbar-expand-lg navbar-light bg-light" style="z-index: 1">
    <div class="container">
      <a class="navbar-brand" href="#" style="height: 100px">
        <img
          src="data:image/png;base64,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"
          alt="Logo" style="
              display: block;
              border: sold;
              height: 100%;
              position: relative;
              top: 2px;
              max-height: fit-content;
            " />
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mx-auto">
          <li class="nav-item">
            <a class="nav-link" href="#">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Profile</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Publishing</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Books</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Services</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Journals</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Contact</a>
          </li>
        </ul>
        <div class="d-flex">
          <a href="register.php" class="btn btn-link">
            <i class="fas fa-user"></i> Register</a>
          <a href="./login.php" class="btn btn-link">
            <i class="fas fa-sign-in-alt"></i> Login</a>
        </div>
      </div>
    </div>
  </nav> -->

  <?php include 'Header.php'; ?>

  <!-- Hero Section -->
  <div id="carouselExampleCaptions" class="carousel slide" style="width: 100%; margin: 0 auto; max-width: 1600px"
    data-bs-ride="carousel">
    <div class="carousel-inner">
      <div class="carousel-item active">
        <img class="d-block w-100" src="uploads/assets/hero4.jpg" alt="Tokyo" />
        <!-- <div class="carousel-caption d-none d-md-block borde border-" style="margin-bottom: 100px">
          <h5>Tokyo (Japan)</h5>
          <p>
            Some representative placeholder content for the first slide. Lorem
            ipsum dolor sit amet, consectetur adipisicing elit. br Officia,
            repellendus?
          </p>
        </div> -->
      </div>
      <div class="carousel-item">
        <img class="d-block w-100" src="uploads/assets/hero2.jpg" alt="Shanghai" />
        <!-- <div class="carousel-caption d-none d-md-block">
          <h5>Shanghai (China)</h5>
          <p>Some representative placeholder content for the second slide.</p>
        </div> -->
      </div>
      <div class="carousel-item">
        <img class="d-block w-100" src="uploads/assets/hero3.jpg" alt="New York" />
        <!-- <div class="carousel-caption d-none d-md-block">
          <h5>New York (United States)</h5>
          <p>Some representative placeholder content for the third slide.</p>
        </div> -->
      </div>
    </div>
    <button class="carousel-control-prev border" type="button" data-bs-target="#carouselExampleCaptions"
      data-bs-slide="prev" style="
          height: 50px;
          width: 50px;
          border-radius: 50%;
          position: reltive;
          color: black;
          left: 2%;
          background: #000;
          top: 40%;
        ">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next border" style="
          height: 50px;
          width: 50px;
          border-radius: 50%;
          position: reltive;
          color: black;
          right: 2%;
          background: #000;
          top: 40%;
        " type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Next</span>
    </button>
  </div>

  <!-- Tab Section -->





  <div class="container my-5">
    <h2 class="text-center mb-4">
      Best <span style="color: #ff8c00">Books</span>
    </h2>

    <!-- Generate the tabs dynamically -->
    <ul class="nav nav-tabs" id="category-tabs" role="tablist">
      <?php foreach ($categories as $index => $category): ?>
        <li class="nav-item" role="presentation">
          <a class="nav-link <?php echo $index == 0 ? 'active' : ''; ?>" id="<?php echo $category['name']; ?>-tab"
            data-bs-toggle="tab" href="#<?php echo $category['name']; ?>" role="tab"
            aria-controls="<?php echo $category['name']; ?>" aria-selected="true"
            data-category-name="<?php echo $category['name']; ?>">
            <?php echo ucfirst($category['name']); ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <!-- Tab content -->
    <div class="tab-content pt-5 border" id="tab-content">
      <?php foreach ($categories as $index => $category): ?>
        <div class="tab-pane  <?php echo $index == 0 ? 'active d-flex' : ''; ?>  " id="<?php echo $category['name']; ?>"
          role="tabpanel" aria-labelledby="<?php echo $category['name']; ?>-tab">
          <!-- Content for the category will be loaded dynamically -->
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Add custom styles for responsiveness -->
  <style>
    /* Container for book cards */
    .tab-pane {
      flex-wrap: wrap;
      gap: 20px;
    }

    /* Style for each book card */
    .book-card {
      /* flex: 1 1 calc(33.33% - 20px); */
      /* Three cards per row */
      box-sizing: border-box;
      margin-bottom: 20px;
      gap: 20px;
      /* Adds space below each card */
      background: #fff;
      /* border: 1px solid #ddd; */
      border-radius: 5px;
      padding: 15px;
    }

    .book-card img {
      /* width: 100%; */
      /* height: auto; */
      border-radius: 5px;
    }

    .book-card h4 {
      margin-top: 10px;
      font-size: 1.2rem;
    }

    .book-card p {
      font-size: 1rem;
      color: #333;
    }

    .book-card p.price {
      color: #ff8c00;
      font-size: 1.1rem;
      font-weight: bold;
    }

    /* Make the cards responsive on smaller screens */
    @media (max-width: 768px) {
      .book-card {
        flex: 1 1 calc(50% - 20px);
        /* Two cards per row on tablets */
      }
    }

    @media (max-width: 480px) {
      .book-card {
        flex: 1 1 100%;
        /* One card per row on mobile */
      }
    }
  </style>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const navLinks = document.querySelectorAll(".nav-link");

      // Function to load books dynamically for the selected category
      function loadBooks(categoryName) {
        fetch('filterbycat.php?category_name=' + categoryName)
          .then(response => response.json())
          .then(books => {
            const tabPanel = document.getElementById(categoryName);
            tabPanel.innerHTML = ''; // Clear the content

            // Add the d-flex class only to the active tab content
            tabPanel.classList.add('d-flex');

            if (books.length > 0) {
              books.forEach(book => {
                const bookCard = `
                <a href="book_details.php?id=${book.id}">
              <div class="book-card mx-2 border">
                <div class="mx-auto p  d-flex align-items-center justify-content- " style="height: 250px; max-width: 250px">
                    <img src="${book.img}"  style="height: 220px; width: 100%; max-width: 250px" class="border p-1" alt="${book.title}" />
                </div>
                <div>
                    <h4>${book.name}</h4>
                    <p>${book.description}</p>
                    <p class="price">RS ${book.price}</p>
                </div>
              </div>
            </a>
            `;
                tabPanel.innerHTML += bookCard;
              });
            } else {
              tabPanel.innerHTML = "<p>No books available in this category.</p>";
            }
          })
          .catch(error => {
            console.error('Error fetching books:', error);
          });
      }

      // Load books for the default category (first category)
      loadBooks("<?php echo $categories[0]['name']; ?>");

      // Add event listener to each category tab
      navLinks.forEach((link) => {
        link.addEventListener("click", function () {
          // Remove active class from all tabs
          navLinks.forEach((link) => link.classList.remove("active"));

          // Add active class to clicked tab
          this.classList.add("active");

          // Get the category name from the clicked tab
          const categoryName = this.getAttribute("data-category-name");

          // Clear all content in the tab content area
          document.querySelectorAll('.tab-pane').forEach((pane) => {
            pane.classList.remove('active');
            pane.classList.remove('d-flex'); // Remove d-flex class from inactive tabs
          });

          // Activate the selected tab and add d-flex class
          const selectedTab = document.getElementById(categoryName);
          selectedTab.classList.add('active');
          selectedTab.classList.add('d-flex');

          // Load books for the selected category
          loadBooks(categoryName);
        });
      });
    });

  </script>


  <style>
    /* Default tab link styling */
    .custom-tabs .nav-link {
      background-color: transparent;
      color: #333;
      transition: background-color 0.3s, color 0.3s;
    }

    /* Active tab styling with orange background */
    .custom-tabs .nav-link.active-tab {
      background-color: #ff8c00;
      /* Orange background for active tab */
      color: #fff;
    }

    /* Hover effect for tabs */
    .custom-tabs .nav-link:hover {
      background-color: #ffb366;
      color: #fff;
    }
  </style>

  <!-- About Section -->

  <div class="container my-5 about">
    <div class="row align-items-center">
      <h2 class="text-center mb-4">
        <b class="text-center">About
          <span style="color: #ff8c00"> Us </span>
        </b>
      </h2>
      <div class="col-md-6 text-center">
        <!-- Image section -->
        <img src="uploads/assets/about-img.png" alt="" style="height: 300px; width: 100%;">
      </div>
      <div class="col-md-6">
        <!-- Text section -->
        <p class="display-6 fw-normal text-gray opacity-75">
          Professional Publication Services

        </p>
        <p class="mt-4">
          Founded in 2020, Professional Publication Services has rapidly established itself as a trusted provider in the
          academic and scientific writing industry. Our team includes esteemed doctors, faculty members and experienced
          scientists from a wide range of fields, including Medical Sciences, Dental Sciences, Nursing, Paramedical, and
          Life Sciences. We aim to deliver top-quality, comprehensive publication services to meet the diverse needs of
          researchers, academicians, and professionals.
        </p>
        <p>
          Since our inception, we have had the privilege of serving over 1,000 doctors, scientists, and researchers from
          various domains. At Professional Publication Services, we are committed to supporting the academic and
          research community by providing reliable, high-quality, and professional publication services.
        </p>
      </div>
    </div>
  </div>

  <!-- Custom CSS -->
  <style>
    .grayscale-img {
      filter: grayscale(100%);
      max-width: 100%;
      width: 250px;
    }

    .text-accent {
      color: #8a2be2;
      /* Purple accent color */
      font-weight: bold;
      text-decoration: underline;
    }

    h1 {
      font-family: Georgia, serif;
      color: #333;
      line-height: 1.4;
    }

    .about p {
      font-family: Georgia, serif;
      color: #555;
      font-size: 1rem;
      line-height: 1.7;
    }
  </style>


  <style>
    .service-card {
      text-align: center;
      margin-bottom: 30px;
    }

    .service-card img {
      /* height: 250px; */
      object-fit: cover;
      margin-bottom: 10px;
    }

    .service-card h5 {
      margin-top: 10px;
      font-weight: bold;
    }
  </style>


  <div class="container my-5">
    <h2 class="text-center mb-4">
      <b class="text-center">Our
        <span style="color: #ff8c00"> Services </span>
      </b>
    </h2>
    <div id="services-container" class="row"></div>
  </div>

  <?php include 'utils/whatsapp-icon.php'; ?>

  <!-- Footer Section -->
  <?php include 'Footer.php'; ?>

  <script type="module">
    import servicesData from './serviceData.js';

    const servicesContainer = document.getElementById("services-container");

    console.log(servicesData);
    // Loop through servicesData to generate cards dynamically
    servicesData.forEach(service => {
      const card = document.createElement("div");
      card.classList.add("col-md-3", "service-card");

      card.innerHTML = `
        <div class="card p-3 " style="height:460px">
          <img src="${service.image}" alt="${service.title}" class="card-img-top">
          <div class="card-body">
            <h5 class="card-title">${service.title}</h5>
            <p class="card-text">${service.description}</p>
          </div>
        </div>
      `;

      servicesContainer.appendChild(card);
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>