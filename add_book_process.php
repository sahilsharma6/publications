<?php
// Start the session to check user login and role
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Redirect to login if not logged in or not an admin
    exit();
}

// Include the database connection
include 'db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = $_POST['name'] ?? null;
    $title = $_POST['title'];
    $authors = $_POST['authors'] ?? null;
    $price = $_POST['price'] ?? null;
    $publishers = $_POST['publishers'] ?? null;
    $length = $_POST['length'] ?? null;
    $subjects = $_POST['subjects'] ?? null;
    $isbn = $_POST['isbn'];
    $category_id = $_POST['category_id'];
    // $description = $_POST['description'] ?: null;
    $description = !empty($_POST['description']) ? $_POST['description'] : null;


    // Handle image upload (optional)
    $img = null;
    if (isset($_FILES['img']) && $_FILES['img']['error'] == 0) {
        $img = 'uploads/' . basename($_FILES['img']['name']);
        move_uploaded_file($_FILES['img']['tmp_name'], $img);
    }

    // Prepare SQL query to insert the book
// Prepare SQL query to insert the book
    $stmt = $conn->prepare("INSERT INTO books_data (name, title, authors, price, publishers, img, isbn, length, subjects, description, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssi", $name, $title, $authors, $price, $publishers, $img, $isbn, $length, $subjects, $description, $category_id);

    if ($stmt->execute()) {
        // Redirect to dashboard with success message
        header("Location: dashboard.php?message=Book added successfully");
    } else {
        echo "Error: " . $stmt->error;
    }


    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
