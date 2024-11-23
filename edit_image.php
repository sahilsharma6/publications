<?php
// Include database connection
include 'db.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get the image ID from the query string
$image_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$image_id) {
    header("Location: manage_images.php");
    exit();
}

// Fetch categories for the dropdown
$categoriesQuery = $conn->query("SELECT id, name FROM categories");
$categories = $categoriesQuery->fetch_all(MYSQLI_ASSOC);

// Fetch current image data from the database
$stmt = $conn->prepare("SELECT id, book_id, category_id, image_path FROM book_images WHERE id = ?");
$stmt->bind_param("i", $image_id);
$stmt->execute();
$result = $stmt->get_result();

$image = $result->fetch_assoc();
if (!$image) {
    echo "Image not found.";
    exit();
}

// Fetch books for the selected category
$books = [];
if ($image['category_id']) {
    $stmt = $conn->prepare("SELECT id, title FROM books_data WHERE category_id = ?");
    $stmt->bind_param("i", $image['category_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $books = $result->fetch_all(MYSQLI_ASSOC);
}

// Handle form submission (Update image details)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = $_POST['book_id'];
    $category_id = $_POST['category_id'];

    // Check if a new image is uploaded
    if (!empty($_FILES['images']['name'][0])) {
        $uploadedImages = $_FILES['images'];

        // Define upload directory
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Create the directory if it doesn't exist
        }

        foreach ($uploadedImages['tmp_name'] as $key => $tmp_name) {
            $imageName = $uploadedImages['name'][$key];
            $imageTmpName = $uploadedImages['tmp_name'][$key];
            $targetPath = $uploadDir . basename($imageName);

            // Move uploaded file to target directory
            if (move_uploaded_file($imageTmpName, $targetPath)) {
                // Delete old image file from server
                if (file_exists($image['image_path'])) {
                    unlink($image['image_path']);
                }

                // Update image data in the database
                $stmt = $conn->prepare("UPDATE book_images SET book_id = ?, category_id = ?, image_path = ? WHERE id = ?");
                $stmt->bind_param("iisi", $book_id, $category_id, $targetPath, $image_id);
                $stmt->execute();

                // Redirect after successful update
                header("Location: manage_book_images.php");
                exit();
            }
        }
    } else {
        // If no new image is uploaded, just update book and category
        $stmt = $conn->prepare("UPDATE book_images SET book_id = ?, category_id = ? WHERE id = ?");
        $stmt->bind_param("iii", $book_id, $category_id, $image_id);
        $stmt->execute();

        // Redirect after successful update
        header("Location: manage_book_images.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Image</title>
    <link rel="stylesheet" href="dashboard/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <?php include 'dashboard/sidebar.php'; ?>

    <div class="container mt-5">
        <h2 class="text-center mb-4">Edit Image for Book</h2>

        <form action="edit_image.php?id=<?php echo $image_id; ?>" method="POST" enctype="multipart/form-data">
            <div class="mb-3" style="display: none;">
                <label for="category" class="form-label">Select Category</label>
                <select name="category_id" id="category" class="form-control" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $image['category_id'] ? 'selected' : ''; ?>>
                            <?php echo ucfirst($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3" style="display: none;">
                <label for="book" class="form-label">Select Book</label>
                <select name="book_id" id="book" class="form-control" required>
                    <option value="">-- Select Book --</option>
                    <?php foreach ($books as $book): ?>
                        <option value="<?php echo $book['id']; ?>" <?php echo $book['id'] == $image['book_id'] ? 'selected' : ''; ?>>
                            <?php echo $book['title']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="images" class="form-label">Upload New Image</label>
                <input type="file" name="images[]" id="images" class="form-control" multiple accept="image/*">
                <small>If you don't want to change the image, leave this empty.</small>
            </div>

            <div class="mb-3">
                <img src="<?php echo $image['image_path']; ?>" alt="Current Image" width="100">
            </div>

            <button type="submit" class="btn btn-primary">Update Image</button>
        </form>

        <a href="manage_book_images.php" class="btn btn-secondary mt-3">Back to Manage Images</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>