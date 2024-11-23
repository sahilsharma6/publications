<?php
// Include database connection
include 'db.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get image id from the query string
$image_id = isset($_GET['id']) ? $_GET['id'] : null;
if ($image_id) {
    // Fetch image data from the database
    $stmt = $conn->prepare("SELECT image_path FROM book_images WHERE id = ?");
    $stmt->bind_param("i", $image_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $image = $result->fetch_assoc();
    $image_path = $image['image_path'];

    // Delete image from the database
    $stmt = $conn->prepare("DELETE FROM book_images WHERE id = ?");
    $stmt->bind_param("i", $image_id);
    $stmt->execute();

    // Delete image file from the server
    if (file_exists($image_path)) {
        unlink($image_path);
    }

    // Redirect to the manage images page
    header("Location: manage_book_images.php");
    exit();
} else {
    echo "Invalid image ID.";
}
