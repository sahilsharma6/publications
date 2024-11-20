<?php
// Include the database connection
include 'db.php';

// Get the category name from the request
$category_name = isset($_GET['category_name']) ? $_GET['category_name'] : '';

// Prepare the query to fetch books based on category name
$sql = "SELECT books_data.id, books_data.name, books_data.title, books_data.authors, books_data.price, books_data.publishers, books_data.img, books_data.description, categories.name AS category_name, books_data.created_at 
        FROM books_data 
        INNER JOIN categories ON books_data.category_id = categories.id";


// Filter the query based on category_name if provided
if ($category_name) {
    $sql .= " WHERE categories.name = ?";
}

// Prepare and execute the query
$stmt = $conn->prepare($sql);

if ($category_name) {
    $stmt->bind_param("s", $category_name);
}

$stmt->execute();
$result = $stmt->get_result();

// Fetch all books
$books = [];
while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}

// Output the books in JSON format
echo json_encode($books);

// Close the statement and connection
$stmt->close();
$conn->close();