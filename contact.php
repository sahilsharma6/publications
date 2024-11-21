<?php
include 'db.php';
session_start();

$heroH2 = "Contact Us";

// Fetch contact information from the database (optional)
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact Us</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
</head>

<body>
    <?php include 'Header.php'; ?>
    <?php include './utils/custom_hero.php'; ?>

    <div class="container my-5">
        <h2 class="text-center mb-4"><?php echo $heroH2; ?></h2>

        <!-- Display Contact Information -->
        <div class="row mb-5">
            <div class="col-md-4 bg-light p-4">
                <h4>Contact Information</h4>
                <p><strong>Address:</strong> <?php echo $contactInfo['address'] ?? '123 Example Street, City'; ?></p>
                <p><strong>Phone:</strong> <?php echo $contactInfo['phone'] ?? '+1 234 567 890'; ?></p>
                <p><strong>Email:</strong> <a
                        href="mailto:<?php echo $contactInfo['email'] ?? 'info@example.com'; ?>"><?php echo $contactInfo['email'] ?? 'info@example.com'; ?></a>
                </p>
            </div>
            <div class="col-md-8">
                <h4>Office Location</h4>
                <!-- Embed Google Maps with the provided coordinates -->
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3532.445923066085!2d77.307172!3d23.268722!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjPCsDE2JzAwLjAiTiA3N8KwMTMnMzMuOCJF!5e0!3m2!1sen!2sin!4v1695117890123"
                    width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>

            </div>
        </div>


    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>