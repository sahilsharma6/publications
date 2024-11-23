<?php
include 'db.php';

// Start session and check if user is an admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Redirect if not an admin
    exit();
}

// Fetch all books from the database
$query = "SELECT books_data.*, categories.name AS category_name FROM books_data 
          JOIN categories ON books_data.category_id = categories.id";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="dashboard/sidebar.css">

    <title>Admin - Manage Books</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <?php include './dashboard/sidebar.php'; ?>

    <div class="dash-content my-5">
        <h2 class="text-center mb-4">Manage Books</h2>

        <!-- Book Table -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Price</th>
                    <th>Publisher</th>
                    <th>Category</th>
                    <th>Image</th>
                    <th>ISBN</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($book = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?php echo $book['title']; ?></td>
                        <td><?php echo $book['authors']; ?></td>
                        <td><?php echo $book['price']; ?></td>
                        <td><?php echo $book['publishers']; ?></td>
                        <td><?php echo $book['category_name']; ?></td>
                        <td><img src="<?php echo $book['img']; ?>" width="50" alt="Book Image"></td>
                        <td><?php echo $book['isbn']; ?></td>
                        <td><?php echo substr($book['description'], 0, 50) . '...'; ?></td>
                        <td>
                            <a href="edit_book.php?id=<?php echo $book['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="delete_book.php?id=<?php echo $book['id']; ?>" class="btn btn-danger btn-sm"
                                onclick="return confirm('Are you sure you want to delete this book?');">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <a href="AddBooks.php" class="btn btn-success">Add New Book</a>
    </div>

</body>

</html>