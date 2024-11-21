<?php
include 'db.php';
session_start();

// Check if the book ID is passed in the URL
$book_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$book_id) {
    echo "Book not found.";
    exit();
}

// Fetch book details from the database
$stmt = $conn->prepare("SELECT b.title, b.authors, b.price, b.publishers, b.category_id, b.description, c.name AS category_name, b.img AS book_image
                        FROM books_data b
                        JOIN categories c ON b.category_id = c.id
                        WHERE b.id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    echo "Book not found.";
    exit();
}

// Fetch images for this book
$stmt = $conn->prepare("SELECT image_path FROM book_images WHERE book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$images_result = $stmt->get_result();
$images = $images_result->fetch_all(MYSQLI_ASSOC);

// Define the phone number for WhatsApp (use the phone number in international format without + or spaces)
$whatsapp_number = '6239001129'; // Change this to your business or personal WhatsApp number

// Create the WhatsApp message with book details
$message = "Hi, I'm interested in buying the book: " . $book['title'] . "\n\n";
$message .= "Author: " . $book['authors'] . "\n";
$message .= "Price: $" . $book['price'] . "\n";
$message .= "Publisher: " . $book['publishers'] . "\n";
$message .= "Category: " . $book['category_name'] . "\n";
$message .= "Description: " . $book['description'] . "\n";
$message .= "Please let me know more details.";

// URL-encode the message to ensure it is properly formatted for a URL
$encoded_message = urlencode($message);
$whatsapp_url = "https://wa.me/$whatsapp_number?text=$encoded_message";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $book['title']; ?> - Book Details</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />

</head>

<body>

    <?php include 'Header.php'; ?>

    <div class="container my-5">
        <h2 class="text-center mb-4"><?php echo $book['title']; ?></h2>
        <div class="row">
            <!-- Book details -->
            <div class="col-md-6">
                <img src="<?php echo $book['book_image']; ?>" class="img-fluid" alt="<?php echo $book['title']; ?>"
                    style="height: 400px; width: 100%;" />
            </div>
            <div class="col-md-6 mt-2">
                <p><strong>Author:</strong> <?php echo $book['authors']; ?></p>
                <p><strong>Price:</strong> $<?php echo $book['price']; ?></p>
                <p><strong>Publisher:</strong> <?php echo $book['publishers']; ?></p>
                <p><strong>Category:</strong> <?php echo $book['category_name']; ?></p>
                <p><strong>Description:</strong> <?php echo $book['description']; ?></p>
                <!-- WhatsApp Button -->
                <a href="<?php echo $whatsapp_url; ?>" class="btn btn-primary mt-4" target="_blank">
                    Buy Now
                </a>
            </div>
        </div>

        <div class="mt-5">
            <h3>Additional Images</h3>
            <div class="row">
                <?php foreach ($images as $image): ?>
                    <div class="col-md-4 mb-4">
                        <img src="<?php echo $image['image_path']; ?>" class="img-fluid"
                            alt="Image for <?php echo $book['title']; ?>" />
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>