<?php
// Include database connection
include 'db.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch all images from the book_images table
$images = [];
$stmt = $conn->prepare("SELECT id, book_id, category_id, image_path FROM book_images");
$stmt->execute();
$result = $stmt->get_result();

// Fetch images
while ($row = $result->fetch_assoc()) {
    $images[] = $row;

    // Close the statement
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Book Images</title>
    <link rel="stylesheet" href="dashboard/sidebar.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <?php include 'dashboard/sidebar.php'; ?>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Manage Book Images</h2>

        <?php if (!empty($images)): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Book ID</th>
                        <th>Category ID</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($images as $image): ?>
                        <tr>
                            <td><img src="<?php echo $image['image_path']; ?>" alt="Image" width="30"></td>
                            <td><?php echo $image['book_id']; ?></td>
                            <td><?php echo $image['category_id']; ?></td>
                            <td>
                                <!-- Add Edit and Delete buttons -->
                                <a href="edit_image.php?id=<?php echo $image['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_image.php?id=<?php echo $image['id']; ?>" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Are you sure you want to delete this image?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No images available.</p>
        <?php endif; ?>

        <a href="add_book_images.php" class="btn btn-primary">Go Back to Upload Images</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>