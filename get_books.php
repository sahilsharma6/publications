<?php
// Include the database connection
include 'db.php';

// Start the session to check user login and role (if needed)
session_start();

// Check if the user is logged in (optional, depending on your use case)
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(["error" => "User not logged in"]);
//     exit();
// }

// Query to fetch all books along with category name
$sql = "
    SELECT 
        b.id, 
        b.name, 
        b.title, 
        b.authors, 
        b.price, 
        b.publishers, 
        b.isbn, 
        b.length,
        b.subjects,
        b.contributors,
        b.img, 
        b.description, 
        c.name AS category_name,  -- Fetch category name instead of ID
        b.created_at
    FROM books_data b
    LEFT JOIN categories c ON b.category_id = c.id
";

$result = $conn->query($sql);

$books = [];

// Check if there are any rows returned
if ($result->num_rows > 0) {
    // Fetch all rows as associative arrays
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }

    // Return books as JSON
    echo json_encode($books);
} else {
    // If no books found, return an empty array
    echo json_encode([]);
}

// Close the database connection
$conn->close();
?>