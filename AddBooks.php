<?php
// Include the database connection
include 'db.php';

// Start the session to check user login and role
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Redirect to login if not logged in or not an admin
    exit();
}
// Fetch categories from the database
$categories_query = "SELECT id, name FROM categories";  // Assuming you have a 'categories' table
$categories_result = $conn->query($categories_query);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="dashboard/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <?php include './dashboard/sidebar.php'; ?>
    <div class="dash-content">

        <h2>Add New Book</h2>

        <!-- Add Book Form -->
        <form action="add_book_process.php" method="POST" enctype="multipart/form-data" class="mt-4">
            <!-- <div class="mb-3" hidden>
                <label for="name" class="form-label">Book Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div> -->

            <div class="mb-3">
                <label for="title" class="form-label">Book Title</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>

            <div class="mb-3">
                <label for="authors" class="form-label">Authors</label>
                <input type="text" class="form-control" id="authors" name="authors">
            </div>

            <div class="mb-3">
                <label for="price" class="form-label">Price</label>
                <input type="text" class="form-control" id="price" name="price">
            </div>

            <div class="mb-3">
                <label for="publishers" class="form-label">Publishers</label>
                <input type="text" class="form-control" id="publishers" name="publishers">
            </div>

            <div class="mb-3">
                <label for="img" class="form-label">Book Image</label>
                <input type="file" class="form-control" id="img" name="img">
            </div>

            <div class="mb-3">
                <label for="isbn" class="form-label">Isbn No</label>
                <input type="text" class="form-control" id="isbn" name="isbn" required>

            </div>

            <div class="mb-3">
                <label for="length" class="form-label">Length </label>
                <input type="text" class="form-control" id="length" name="length">

            </div>
            <div class="mb-3">
                <label for="subjects" class="form-label">Subjects</label>
                <input type="text" class="form-control" id="subjects" name="subjects">

            </div>
            <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <?php while ($category = $categories_result->fetch_assoc()) { ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                    <?php } ?>
                </select>
            </div>



            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Add Book</button>
        </form>
    </div>
    </div>

</body>

</html>