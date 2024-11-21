<?php
include 'db.php'; // Include database connection
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle form submission to upload an image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $imageName = $_FILES['image']['name'];
        $imageTmpName = $_FILES['image']['tmp_name'];
        $description = $_POST['description'] ?? '';

        // Define upload directory
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Create directory if it doesn't exist
        }

        $targetPath = $uploadDir . basename($imageName);

        // Move uploaded file to target directory
        if (move_uploaded_file($imageTmpName, $targetPath)) {
            // Insert record into the publishing_images table
            $stmt = $conn->prepare("INSERT INTO publishing_images (image_path, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $targetPath, $description);
            $stmt->execute();

            echo "<script>alert('Image uploaded successfully.');</script>";
        } else {
            echo "<script>alert('Failed to upload image.');</script>";
        }
    } else {
        echo "<script>alert('Please select an image to upload.');</script>";
    }
}

// Handle edit action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $editId = $_POST['edit_id'];
    $description = $_POST['description'] ?? '';

    // Check if a new image is uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $imageName = $_FILES['image']['name'];
        $imageTmpName = $_FILES['image']['tmp_name'];

        $uploadDir = "uploads/";
        $targetPath = $uploadDir . basename($imageName);

        if (move_uploaded_file($imageTmpName, $targetPath)) {
            // Fetch the old image path to delete the file
            $stmt = $conn->prepare("SELECT image_path FROM publishing_images WHERE id = ?");
            $stmt->bind_param("i", $editId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $oldImagePath = $row['image_path'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Update image path and description
            $stmt = $conn->prepare("UPDATE publishing_images SET image_path = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $targetPath, $description, $editId);
        }
    } else {
        // Update only the description
        $stmt = $conn->prepare("UPDATE publishing_images SET description = ? WHERE id = ?");
        $stmt->bind_param("si", $description, $editId);
    }

    $stmt->execute();
    echo "<script>alert('Image updated successfully.'); window.location.href='add_publishing.php';</script>";
}

// Handle delete action
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];

    // Fetch the image path to delete the file from the server
    $stmt = $conn->prepare("SELECT image_path FROM publishing_images WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $imagePath = $row['image_path'];

        // Delete the image file from the server
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        // Delete the record from the database
        $deleteStmt = $conn->prepare("DELETE FROM publishing_images WHERE id = ?");
        $deleteStmt->bind_param("i", $deleteId);
        $deleteStmt->execute();

        echo "<script>alert('Image deleted successfully.'); window.location.href='add_publishing.php';</script>";
    }
}

// Fetch all publishing images
$imagesQuery = $conn->query("SELECT * FROM publishing_images");
$images = $imagesQuery->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publishing Images Dashboard</title>
    <link rel="stylesheet" href="dashboard/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>
    <?php include 'dashboard/sidebar.php'; ?>

    <div class="container mt-5">
        <h2 class="text-center mb-4">Manage Publishing Images</h2>

        <!-- Form to Upload Images -->
        <form action="add_publishing.php" method="POST" enctype="multipart/form-data" class="mb-5">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
                <label for="image" class="form-label">Upload Image</label>
                <input type="file" name="image" id="image" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3"
                    placeholder="Add a description (optional)"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>

        <!-- Table to Display Images -->
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Image</th>
                    <th>Description</th>
                    <th>Uploaded At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($images) > 0): ?>
                    <?php foreach ($images as $index => $image): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><img src="<?php echo $image['image_path']; ?>" alt="Image" width="100"></td>
                            <td><?php echo $image['description'] ?: 'No description'; ?></td>
                            <td><?php echo $image['uploaded_at']; ?></td>
                            <td>
                                <!-- Edit Form -->
                                <form action="add_publishing.php" method="POST" enctype="multipart/form-data"
                                    style="display: inline;">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="edit_id" value="<?php echo $image['id']; ?>">
                                    <div class="mb-2">
                                        <input type="file" name="image" class="form-control">
                                    </div>
                                    <textarea name="description" class="form-control mb-2" rows="2"
                                        placeholder="Edit description"><?php echo $image['description']; ?></textarea>
                                    <button type="submit" class="btn btn-success btn-sm">Save</button>
                                </form>
                                <a href="add_publishing.php?delete_id=<?php echo $image['id']; ?>" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Are you sure you want to delete this image?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No images available.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>