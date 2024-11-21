<?php
include 'db.php'; // Include database connection
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Define upload directory and logo filename
$uploadDir = "uploads/logos/";
$logoFilename = "logo.png"; // Fixed filename for the logo
$targetPath = $uploadDir . $logoFilename;

// Create upload directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle form submission to upload a logo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_logo') {
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        // Delete the old logo if it exists
        if (file_exists($targetPath)) {
            unlink($targetPath);
        }

        // Move uploaded file to target directory with fixed filename
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
            echo "<script>alert('Logo uploaded successfully.'); window.location.href='add_logo.php';</script>";
        } else {
            echo "<script>alert('Failed to upload logo.');</script>";
        }
    } else {
        echo "<script>alert('Please select a logo to upload.');</script>";
    }
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    // Delete the logo file if it exists
    if (file_exists($targetPath)) {
        unlink($targetPath);
        echo "<script>alert('Logo deleted successfully.'); window.location.href='add_logo.php';</script>";
    } else {
        echo "<script>alert('No logo found to delete.'); window.location.href='add_logo.php';</script>";
    }
}

// Check if a logo exists
$logoExists = file_exists($targetPath);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logo Management</title>
    <link rel="stylesheet" href="dashboard/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>
    <?php include 'dashboard/sidebar.php'; ?>

    <div class="container mt-5">
        <h2 class="text-center mb-4">Manage Logo</h2>

        <!-- Display the current logo if it exists -->
        <div class="text-center mb-4">
            <?php if ($logoExists): ?>
                <p>Current Logo:</p>
                <img src="<?php echo $targetPath; ?>" alt="Logo" width="150">
                <br><br>
                <a href="add_logo.php?action=delete" class="btn btn-danger"
                    onclick="return confirm('Are you sure you want to delete the current logo?');">Delete Logo</a>
            <?php else: ?>
                <p>No logo currently uploaded.</p>
            <?php endif; ?>
        </div>

        <!-- Form to Upload a New Logo -->
        <form action="add_logo.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_logo">
            <div class="mb-3">
                <label for="logo" class="form-label">Upload New Logo</label>
                <input type="file" name="logo" id="logo" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Upload Logo</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
