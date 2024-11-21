<?php
// Include database connection
include 'db.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch categories for dropdowns
$categoriesQuery = $conn->query("SELECT id, name FROM categories");
$categories = $categoriesQuery->fetch_all(MYSQLI_ASSOC);

// Fetch books based on selected category if category_id is set
$books = [];
if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
    $category_id = $_GET['category_id'];

    // Prepare query to fetch books for the selected category
    $stmt = $conn->prepare("SELECT id, title FROM books_data WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch books
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }

    // Close the statement
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure book_id and category_id are set before processing
    if (isset($_POST['book_id']) && isset($_POST['category_id'])) {
        $book_id = $_POST['book_id'];
        $category_id = $_POST['category_id'];

        if (!empty($_FILES['images']['name'][0])) {
            $uploadedImages = $_FILES['images'];

            foreach ($uploadedImages['tmp_name'] as $key => $tmp_name) {
                $imageName = $uploadedImages['name'][$key];
                $imageTmpName = $uploadedImages['tmp_name'][$key];

                // Define upload directory
                $uploadDir = "uploads/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true); // Create the directory if it doesn't exist
                }

                $targetPath = $uploadDir . basename($imageName);

                // Move uploaded file to target directory
                if (move_uploaded_file($imageTmpName, $targetPath)) {
                    // Insert image data into book_images table
                    $stmt = $conn->prepare("INSERT INTO book_images (book_id, category_id, image_path) VALUES (?, ?, ?)");
                    $stmt->bind_param("iis", $book_id, $category_id, $targetPath);
                    $stmt->execute();
                }
            }
            echo "<script>alert('Images uploaded successfully.');</script>";
        } else {
            echo "<script>alert('Please select at least one image to upload.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Book Images</title>
    <link rel="stylesheet" href="dashboard/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>

<body>
    <?php include 'dashboard/sidebar.php'; ?>
    <div class="dash-content mt-5">

        <h2 class="text-center mb-4">Upload Images for Books</h2>

        <form action="add_book_images.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="category" class="form-label">Select Category</label>
                <select name="category_id" id="category" class="form-control" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo isset($_GET['category_id']) && $_GET['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo ucfirst($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="books-dropdown-container">
                <?php if (!empty($books)): ?>
                    <div class="mb-3">
                        <label for="book" class="form-label">Select Book</label>
                        <select name="book_id" id="book" class="form-control" required>
                            <option value="">-- Select Book --</option>
                            <?php foreach ($books as $book): ?>
                                <option value="<?php echo $book['id']; ?>"><?php echo $book['title']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">No books available in the selected category.</div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="images" class="form-label">Upload Images</label>
                <input type="file" name="images[]" id="images" class="form-control" multiple accept="image/*" required>
            </div>

            <button type="submit" class="btn btn-primary">Upload Images</button>
        </form>
    </div>

    <script>
        // Use jQuery to handle category change and fetch books asynchronously
        $(document).ready(function () {
            $('#category').change(function () {
                var category_id = $(this).val();

                // Check if category is selected
                if (category_id) {
                    // Send AJAX request to fetch books
                    $.ajax({
                        url: 'get_book.php', // PHP file to get books based on category
                        type: 'GET',
                        data: { category_id: category_id },
                        success: function (data) {
                            $('#books-dropdown-container').html(data); // Update books dropdown
                        }
                    });
                } else {
                    $('#books-dropdown-container').html(''); // Clear books dropdown if no category is selected
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>