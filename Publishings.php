<?php
include 'db.php';
session_start();

$heroH2 = "Journals";

// Fetch all publishing images from the database
$imagesQuery = $conn->query("SELECT * FROM publishing_images");
$images = $imagesQuery->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Publishing Images</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
</head>

<body>
    <?php include 'Header.php'; ?>
    <?php include './utils/custom_hero.php'; ?>
    <div class="container mt-5">
        <h2 class="text-center mb-4"> Journal Publication Support</h2>

        <!-- Check if there are images to display -->
        <?php if (count($images) > 0): ?>
            <div class="row">
                <?php foreach ($images as $image): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <img src="<?php echo $image['image_path']; ?> " style="height: 210px; width: 100%;"
                                class="card-img-top" alt="Publishing Image">

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center">No publishing images available.</div>
        <?php endif; ?>
    </div>
    <?php include 'Footer.php'; ?>
    <?php include 'utils/whatsapp-icon.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>