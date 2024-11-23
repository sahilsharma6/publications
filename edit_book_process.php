<?php
include 'db.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Redirect to login if not logged in or not an admin
    exit();
}

// Check if form data is received
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $bookId = $_POST['id'];
    $name = $_POST['name'];
    $title = $_POST['title'];
    $authors = $_POST['authors'];
    $price = $_POST['price'];
    $publishers = $_POST['publishers'];
    $isbn = $_POST['isbn'];
    $length = $_POST['length'];
    $subjects = $_POST['subjects'];
    $contributors = $_POST['contributors'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];
    $imagePath = ''; // Initialize an empty image path variable

    // Check if a new image file is uploaded
    if (isset($_FILES['img']) && $_FILES['img']['error'] == 0) {
        $targetDir = "uploads/"; // Folder to store uploaded images
        $imageName = basename($_FILES['img']['name']);
        $targetFilePath = $targetDir . $imageName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        // Allow only specific file formats
        $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
        if (in_array(strtolower($fileType), $allowedTypes)) {
            // Upload file to the server
            if (move_uploaded_file($_FILES['img']['tmp_name'], $targetFilePath)) {
                $imagePath = $targetFilePath;
            } else {
                echo "Error uploading image file.";
                exit();
            }
        } else {
            echo "Only JPG, JPEG, PNG, and GIF files are allowed.";
            exit();
        }
    }

    // If an image path is set, update the image field in the database
    if ($imagePath) {
        $query = "UPDATE books_data SET name=?, title=?, authors=?, price=?, publishers=?, isbn=?, length=?, subjects=?, contributors=?, category_id=?, description=?, img=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssi", $name, $title, $authors, $price, $publishers, $isbn, $length, $subjects, $contributors, $category_id, $description, $imagePath, $bookId);
    } else {
        // Update without changing the image if no new image is uploaded
        $query = "UPDATE books_data SET name=?, title=?, authors=?, price=?, publishers=?,isbn=?, length=?, subjects=?, contributors=?, category_id=?, description=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssi", $name, $title, $authors, $price, $publishers, $isbn, $length, $subjects, $contributors, $category_id, $description, $bookId);
    }

    // Execute the query and check for success
    if ($stmt->execute()) {
        header("Location: AllBooks.php"); // Redirect to the book list page after successful update
        exit();
    } else {
        echo "Error updating book: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}