<?php
// Start the session
session_start();

// Include the database connection
include 'db.php';

$message = '';

// Check if form data is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Check if the user exists
    $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // If user is found, check password
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $username, $user_email, $hashedPassword, $role);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashedPassword)) {
            // Start session and store user data
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $user_email;
            $_SESSION['role'] = $role; // Set the role in the session

            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Incorrect password!";
        }
    } else {
        $message = "No user found with that email!";
    }

    // Close the statement
    $stmt->close();
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        /* Page background */
        body {
            background: linear-gradient(135deg, #9b59b6, #e91e63);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        /* Card styling */
        .login-card {
            background-color: #fff;
            border-radius: 15px;
            padding: 30px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        /* Title */
        .login-card h2 {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        /* Form inputs */
        .form-control {
            border-radius: 8px;
            border: 1.5px solid #ccc;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .form-control:focus {
            border-color: #9b59b6;
            box-shadow: none;
        }

        /* Button */
        .btn-primary {
            background: linear-gradient(135deg, #9b59b6, #e91e63);
            border: none;
            color: #fff;
            padding: 10px;
            font-size: 18px;
            border-radius: 8px;
            width: 100%;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #e91e63, #9b59b6);
        }

        /* Links */
        .login-card .text-muted,
        .login-card .text-primary {
            font-size: 14px;
            color: #555;
        }

        .login-card a {
            color: #9b59b6;
            font-weight: bold;
            text-decoration: none;
        }

        .login-card a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body class="" style="padding: 10px">
    <div class="login-card">
        <h2>Login</h2>

        <!-- Display Message -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="" class="mt-4">
            <!-- Email -->
            <input type="email" name="email" class="form-control" placeholder="Email" required />

            <!-- Password -->
            <input type="password" name="password" class="form-control" placeholder="Enter password" required />

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <!-- Footer Links -->
        <p class="mt-3 text-muted">
            Don't have an account? <a href="register.php">Register</a>
        </p>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.min.js"></script>
</body>

</html>