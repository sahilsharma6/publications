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

// Handle the category deletion
if (isset($_GET['id'])) {
    $category_id = $_GET['id'];

    // Prepare the SQL query to delete the category
    $sql = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_id);

    if ($stmt->execute()) {
        header("Location: AllCategories.php"); // Redirect to categories list after deletion
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Close the database connection
$conn->close();