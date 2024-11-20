<?php
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

?>

<div class="top-bar py-2 text-white text-center">
    <div class="container d-flex justify-content-between align-items-center">
        <span>CALL US - +91-95015 44877</span>
        <span>EMAIL - aaa@gmail.com</span>
    </div>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light" style="z-index: 1">
    <div class="container">
        <a class="navbar-brand" href="#" style="height: 100px">
            <img src="data:image/png;base64,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"
                alt="Logo" style="
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
                    <a class="nav-link" href="#">Home</a>
                </li>


                <li class="nav-item">
                    <a class="nav-link" href="books.php">Books</a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" href="#">Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Journals</a>
                </li> -->
                <li class="nav-item">
                    <a class="nav-link" href="#">Contact</a>
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