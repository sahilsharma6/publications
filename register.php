<?php
// Include the database connection
include 'db.php';

$message = '';

// Check if form data is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Retrieve and sanitize form inputs
  if (isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT); // Hash the password

    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
      $message = "Email is already registered!";
      $checkEmail->close();
    } else {
      // Insert user into the database
      $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
      $stmt->bind_param("sss", $username, $email, $hashedPassword);

      if ($stmt->execute()) {
        $message = "Registration successful! <a href='login.php'>Login here</a>";
      } else {
        $message = "Error: " . $stmt->error;
      }

      $stmt->close();
    }

    // Close the prepared statement
    $checkEmail->close();
  } else {
    $message = "Please fill in all fields!";
  }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign Up</title>

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
    .signup-card {
      background-color: #fff;
      border-radius: 15px;
      padding: 30px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
      text-align: center;
    }

    /* Title */
    .signup-card h2 {
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
    .signup-card a {
      color: #9b59b6;
      font-weight: bold;
      text-decoration: none;
    }

    .signup-card a:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body style="padding: 10px">
  <div class="signup-card">
    <!-- Title -->
    <h2>Signup</h2>

    <!-- Display Message -->
    <?php if (!empty($message)): ?>
      <p class="text-info"><?php echo $message; ?></p>
    <?php endif; ?>

    <!-- Signup Form -->
    <form action="register.php" method="POST" class="mt-4">
      <!-- Username -->
      <input type="text" name="username" class="form-control" placeholder="Username" required />

      <!-- Email -->
      <input type="email" name="email" class="form-control" placeholder="Email Id" required />

      <!-- Password -->
      <input type="password" name="password" class="form-control" placeholder="Create password" required />

      <!-- Terms and Conditions -->
      <p class="text-muted">
        By creating an account, I agree to
        <a href="#">Terms and Conditions</a>
      </p>

      <!-- Submit Button -->
      <button type="submit" class="btn btn-primary">Create Account</button>
    </form>

    <!-- Footer Links -->
    <p class="mt-3 text-muted">
      Already have an account? <a href="login.php">Login</a>
    </p>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.min.js"></script>
</body>

</html>