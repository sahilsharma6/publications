<?php
include 'db.php';
session_start();


$heroH2 = "Books";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
</head>

<body>
    <?php include 'Header.php'; ?>
    <?php include './utils/custom_hero.php'; ?>
    <div class="container my-5">
        <h2 class="text-center mb-4">Available Books</h2>
        <div id="books-list" class="row"></div> <!-- Container to display books -->
        <button id="load-more-btn" class="btn btn-primary mt-4 d-block mx-auto">Load More</button>
    </div>

    <script>
        let allBooks = []; // Array to hold all books fetched from server
        let booksToShow = 9; // Number of books to show at a time
        let currentIndex = 0; // Track how many books have been displayed

        // Function to display books in batches
        function displayBooks() {
            const booksList = document.getElementById('books-list');

            // Loop through the next batch of books
            for (let i = currentIndex; i < currentIndex + booksToShow && i < allBooks.length; i++) {
                const book = allBooks[i];

                // Create a new card element for each book
                const bookCard = document.createElement('div');
                bookCard.classList.add('col-md-4', 'mb-4');
                bookCard.innerHTML = `
                <a href="book_details.php?id=${book.id} style="text-decoration: none; color: black">
                    <div class="card">
                        <img src="${book.img}" class="card-img-top" height="400" alt="${book.title}">
                        <div class="card-body " style="color: black">
                            <h5 class="card-title">${book.title}</h5>
                            <p class="card-text"><strong>Author:</strong> ${book.authors}</p>
                            <p class="card-text"><strong>Price:</strong> $${book.price}</p>
                            <p class="card-text"><strong>Publisher:</strong> ${book.publishers}</p>
                            <p class="card-text"><strong>Category:</strong> ${book.category_name}</p>
                            <p class="card-text">${book.description}</p>
                        </div>
                    </div>
                </a>
                `;
                booksList.appendChild(bookCard);
            }

            // Update the current index
            currentIndex += booksToShow;

            // Hide "Load More" button if all books are displayed
            if (currentIndex >= allBooks.length) {
                document.getElementById('load-more-btn').style.display = 'none';
            }
        }

        // Fetch books from server and initialize the list
        fetch('get_books.php')
            .then(response => response.json())
            .then(data => {
                allBooks = data; // Store fetched data
                displayBooks(); // Display initial batch
            })
            .catch(error => console.error('Error fetching books:', error));

        // Event listener for "Load More" button
        document.getElementById('load-more-btn').addEventListener('click', displayBooks);
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>