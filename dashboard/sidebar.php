<div>
    <?php
    $role = $_SESSION['role'];
    $username = $_SESSION['username'];
    ?>

    <div class="sidebar close">
        <div class="logo-details">
            <i class='bx bxl-circle'></i>
            <span class="logo_name"></span>
        </div>
        <ul class="nav-links">
            <li>
                <a href="dashboard.php">
                    <i class='bx bx-grid-alt'></i>
                    <span class="link_name">Dashboard</span>
                </a>
                <ul class="sub-menu blank">
                    <li><a class="link_name" href="#">Dashboard</a></li>
                </ul>
            </li>
            <li>
                <div class="iocn-link">
                    <a href="#">
                        <i class='bx bx-collection'></i>
                        <span class="link_name">Category</span>
                    </a>
                    <i class='bx bxs-chevron-down arrow'></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="">Category</a></li>
                    <li><a href="AllCategories.php">Categories</a></li>
                    <li><a href="./AddCategories.php">Add Categories</a></li>
                </ul>
            </li>
            <li>
                <div class="iocn-link">
                    <a href="#">
                        <i class='bx bx-book-alt'></i>
                        <span class="link_name">Add Book</span>
                    </a>
                    <i class='bx bxs-chevron-down arrow'></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="#">Add Book</a></li>
                    <li><a href="AllBooks.php">Books</a></li>
                    <li><a href="AddBooks.php">Add Books</a></li>
                    <li><a href="add_book_images.php">Add Book Images</a></li>
                    <li><a href="manage_book_images.php">Manage Book Images</a></li>

                </ul>

            </li>
            <li>
                <div class="iocn-link">
                    <a href="#">
                        <i class='bx bx-book-alt'></i>
                        <span class="link_name">Publishings</span>
                    </a>
                    <i class='bx bxs-chevron-down arrow'></i>
                </div>
                <ul class="sub-menu">
                    <li><a class="link_name" href="#">Publishings</a></li>
                    <li><a href="add_publishing.php">Add Publishing</a></li>

                </ul>

            </li>
            <li>
                <div class="iocn-link">
                    <a href="#">
                        <i class='bx bx-cog'></i>
                        <span class="link_name">Logo </span>
                    </a>
                    <i class='bx bxs-chevron-down arrow'>Logo</i>
                </div>
                <ul class="sub-menu">
                    <li><a href="add_logo.php">Add Logo</a></li>
                </ul>
            </li>

            <!-- <li> -->
            <?php
            if ($_SESSION['role'] == 'SuperAdmin') {
                echo '
      <li>
        <div class="iocn-link">
          <a href="#">
            <i class="bx bx-user"></i>
            <span class="link_name">Users</span>
          </a>
          <i class="bx bxs-chevron-down arrow"></i>
        </div>
        <ul class="sub-menu">
          <li><a class="link_name" href="#">Users</a></li>
          <li><a href="manage.php">manage_users</a></li>
          <li><a href="register.php">register_users</a></li>
          <li><a href="Allcomments.php">Comments</a></li>
        </ul>
      </li>';
            }
            ?>


            <?php
            if ($_SESSION['role'] == 'SuperAdmin') {
                echo '
     <li>
        <a href="Settings.php">
          <i class="bx bx-cog"></i>
          <span class="link_name">Website Settings</span>
        </a>
        <ul class="sub-menu blank">
          <li><a class="link_name" href="">Settings</a></li>
        </ul>
      </li>
      ';
            }
            ?>

            <li>
                <a href="logout.php">
                    <!-- <i class="bx bx-user"></i> -->
                    <i class='bx bx-log-out'></i>
                    <span class="link_name">Logout</span>
                </a>
                <ul class="sub-menu blank">t
                    <li><a class="link_name" href="#">Logout</a></li>
                </ul>
                </a>
            </li>

            <li>
                <div class="profile-details">

            </li>
            <!-- ?> -->





            <li>
                <div class="profile-details">

                    <div class="name-job" style="position: relative; left: 10%;">
                        <?php
                        // echo $_SESSION['username'];
                        
                        ?>
                        <div class="profile_name"> <?php echo $username ?></div>
                        <div class="job"><?php echo $role ?></div>
                    </div>
                    <a href="logout.php">

                        <i class='bx bx-log-out'></i>
                    </a>
                </div>
            </li>
        </ul>
    </div>
    <section class="home-section">
        <div class="home-content">
            <i class='bx bx-menu'></i>
        </div>
    </section>
</div>

<script>
    let arrow = document.querySelectorAll(".arrow");
    for (var i = 0; i < arrow.length; i++) {
        arrow[i].addEventListener("click", (e) => {
            let arrowParent = e.target.parentElement.parentElement;//selecting main parent of arrow
            arrowParent.classList.toggle("showMenu");
        });
    }
    let sidebar = document.querySelector(".sidebar");
    let sidebarBtn = document.querySelector(".bx-menu");
    console.log(sidebarBtn);
    sidebarBtn.addEventListener("click", () => {
        sidebar.classList.toggle("close");
    });
</script>
</body>