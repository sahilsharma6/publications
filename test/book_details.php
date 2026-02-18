<?php
include '../db.php';
session_start();

// Check if the book ID is passed in the URL
$book_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$book_id) {
    echo "Book not found.";
    exit();
}

// Fetch book details from the database
$stmt = $conn->prepare("SELECT b.title, b.authors, b.price, b.publishers,b.isbn, b.length, b.subjects, b.contributors, b.category_id, b.description, c.name AS category_name, b.img AS book_image
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
$whatsapp_number = '+919752747384'; // Change this to your business or personal WhatsApp number

// Create the WhatsApp message with book details
$message = "Hi, I'm interested in buying the book: " . $book['title'] . "\n\n";
$message .= "Author: " . $book['authors'] . "\n";
$message .= "Price: $" . $book['price'] . "\n";
$message .= "Publisher: " . $book['publishers'] . "\n";
$message .= "Category: " . $book['category_name'] . "\n";
$message .= "Length: " . $book['length'] . "\n";
$message .= "Subjects: " . $book['subjects'] . "\n";
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
    <title>
        <?php echo $book['title']; ?> - Book Details
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />

</head>

<body>
    <?php include 'header.php'; ?>


    <div class="container book-detail-container">

        <div class="row g-5">

            <!-- LEFT: Book Images -->
            <div class="col-lg-6">

                <div class="book-main-image">
                    <img src="../<?php echo $book['book_image']; ?>" alt="<?php echo $book['title']; ?>">
                </div>

                <?php if (!empty($images)): ?>
                    <div class="book-gallery mt-4 d-flex gap-3 flex-wrap">
                        <?php foreach ($images as $image): ?>
                            <div class="gallery-thumb">
                                <img src="../<?php echo $image['image_path']; ?>" alt="Image for <?php echo $book['title']; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>

            <!-- RIGHT: Book Info -->
            <div class="col-lg-6">

                <div class="book-info-card">

                    <h1 class="book-title"><?php echo $book['title']; ?></h1>
                    <p class="book-author">By <?php echo $book['authors']; ?></p>

                    <div class="book-price">₹ <?php echo $book['price']; ?></div>

                    <div class="book-meta-grid mt-4">
                        <div><strong>Publisher:</strong> <?php echo $book['publishers']; ?></div>
                        <div><strong>ISBN:</strong> <?php echo $book['isbn']; ?></div>
                        <div><strong>Category:</strong> <?php echo $book['category_name']; ?></div>
                        <div><strong>Length:</strong> <?php echo $book['length']; ?></div>
                        <div><strong>Subjects:</strong> <?php echo $book['subjects']; ?></div>
                    </div>

                    <hr>

                    <div class="book-description">
                        <h5>Description</h5>
                        <p><?php echo nl2br($book['description']); ?></p>
                    </div>

                    <a href="<?php echo $whatsapp_url; ?>" class="btn btn-buy mt-4" target="_blank">
                        <i class="fab fa-whatsapp me-2"></i> Buy via WhatsApp
                    </a>

                </div>

            </div>

        </div>

    </div>

    <?php include '../Footer.php'; ?>
    <?php include '../utils/whatsapp-icon.php'; ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>