<?php
include 'db.php';

if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
    $category_id = $_GET['category_id'];

    // Fetch books based on category_id
    $stmt = $conn->prepare("SELECT id, title FROM books_data WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Output books dropdown
    if ($result->num_rows > 0) {
        echo '<div class="mb-3">
                <label for="book" class="form-label">Select Book</label>
                <select name="book_id" id="book" class="form-control" required>
                    <option value="">-- Select Book --</option>';
        while ($row = $result->fetch_assoc()) {
            echo '<option value="' . $row['id'] . '">' . $row['title'] . '</option>';
        }
        echo '</select>
              </div>';
    } else {
        echo '<div class="alert alert-warning">No books available in the selected category.</div>';
    }

    $stmt->close();
}
?>