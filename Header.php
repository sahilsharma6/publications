<?php
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

?>

<div class="top-bar py-2 text-white text-center">
    <div class="container d-flex justify-content-between align-items-center">
        <span>CALL US - +91-8708299825,</span>
        <span>EMAIL - info@professionalpublicationservice.com</span>
    </div>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light position-fixed w-100 top-0 " style="z-index: 1000">
    <div class="container">
        <a class="navbar-brand" href="./" style="height: 100px">
            <img src="uploads/logos/logo.png" alt="Logo" style="
              display: block;
              border: sold;
              height: 100%;
              position: relative;
              top: 2px;
              max-height: fit-content;
            " />
            <!-- Replace with actual logo path -->
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link" href="./">Home</a>
                </li>


                <li class="nav-item">
                    <a class="nav-link" href="books.php">Books</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="./#services-container">Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="Publishings.php">Publishings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php">Contact</a>
                </li>
            </ul>

            <!-- <div class="   postion-relative  " style="">
                <div class="position-absolute   rounded p-2"
                    style="background-color: white; box-shadow: 0 0 10px rgba(0, 0, 0, 0.3); right: 5.5%; top: 70%;">
                    <div class="d-flex flex-column gap-3 mt-2">
                        <a href="dashboard.php">
                            <i class="fas fa-user"></i> Dashboard
                        </a>
                        <a href="">
                            <i class="fas fa-sign-in-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div> -->
            <div>
                <!-- Call us Button -->
                <a href="tel:+918708299825," class="btn btn-primary">Call Us</a>
            </div>

            <script>
                let userIcon = document.querySelector(".user-icon");
                let userDropdown = document.querySelector(".user-dropdown");
                userIcon.addEventListener("click", () => {
                    // window.location.href = "profile.php";
                    userDropdown.classList.toggle("d-none");

                });
            </script>
        </div>
    </div>
</nav>