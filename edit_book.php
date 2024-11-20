<?php
include 'db.php';

// Start the session to check user login and role
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Redirect to login if not logged in or not an admin
    exit();
}

// Fetch the book details based on the book ID
$bookId = $_GET['id'];
$query = "SELECT * FROM books_data WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $bookId);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();

$categories_result = $conn->query("SELECT * FROM categories"); // Assuming 'categories' is your category table
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book</title>
    <link rel="stylesheet" href="dashboard/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* .form-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-title {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #333;
        } */

        #imgPreview {
            max-width: 100%;
            height: auto;
            margin-top: 10px;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <?php include './dashboard/sidebar.php'; ?>

    <div class="container  form-container">
        <h2 class="form-title">Edit Book</h2>
        <form action="edit_book_process.php" method="POST" enctype="multipart/form-data" class="mt-4">
            <input type="hidden" name="id" value="<?php echo $book['id']; ?>">

            <!-- Book Name -->
            <div class="mb-3">
                <label for="name" class="form-label">Book Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo $book['name']; ?>"
                    required>
            </div>

            <!-- Book Title -->
            <div class="mb-3">
                <label for="title" class="form-label">Book Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo $book['title']; ?>"
                    required>
            </div>

            <!-- Authors -->
            <div class="mb-3">
                <label for="authors" class="form-label">Authors</label>
                <input type="text" class="form-control" id="authors" name="authors"
                    value="<?php echo $book['authors']; ?>" required>
            </div>

            <!-- Price -->
            <div class="mb-3">
                <label for="price" class="form-label">Price</label>
                <input type="text" class="form-control" id="price" name="price" value="<?php echo $book['price']; ?>"
                    required>
            </div>

            <!-- Publishers -->
            <div class="mb-3">
                <label for="publishers" class="form-label">Publishers</label>
                <input type="text" class="form-control" id="publishers" name="publishers"
                    value="<?php echo $book['publishers']; ?>" required>
            </div>

            <!-- Current Book Image -->
            <div class="mb-3">
                <label for="img" class="form-label">Current Book Image</label><br>
                <img id="imgPreview" src="<?php echo $book['img']; ?>" alt="Current Book Image">
            </div>

            <!-- New Book Image Upload -->
            <div class="mb-3">
                <label for="img" class="form-label">Upload New Image (Optional)</label>
                <input type="file" class="form-control" id="img" name="img" onchange="previewImage(event)">
            </div>

            <!-- Category Selection -->
            <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <?php while ($category = $categories_result->fetch_assoc()) { ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $book['category_id']) ? 'selected' : ''; ?>>
                            <?php echo $category['name']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <!-- Description -->
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"
                    required><?php echo $book['description']; ?></textarea>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">Update Book</button>
        </form>
    </div>

    <script>
        // Image preview
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function () {
                const output = document.getElementById('imgPreview');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>

</body>

</html>