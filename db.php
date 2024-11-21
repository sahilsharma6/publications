<?php
// Database credentials
// $servername = "localhost";
// $username = "root";
// $password = "";
// $dbname = "books";

$servername = "localhost";
$username = "u199140766_publications";
$password = "Kuldeep@$1990";
$dbname = "u199140766_publications";


// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}