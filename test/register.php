<?php
session_start();
require_once '../db.php';

$message = '';
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
    } else {

        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Email is already registered.";
        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insert = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            $insert->bind_param("sss", $username, $email, $hashedPassword);

            if ($insert->execute()) {
                $success = true;
                $message = "Registration successful! You can now login.";
            } else {
                $message = "Something went wrong. Please try again.";
            }

            $insert->close();
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            margin: 0;
            font-family: "Inter", sans-serif;
            background: url("https://images.unsplash.com/photo-1512820790803-83ca734da794?q=80&w=1920&auto=format&fit=crop") no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: rgba(15, 20, 30, 0.60);
            backdrop-filter: blur(2px);
            z-index: 0;
        }
    </style>
</head>

<body>

    <div class="login-wrapper d-flex justify-content-center align-items-center">
        <div class="login-card">

            <h2 class="login-title text-center">Create Account</h2>
            <p class="login-subtitle text-center">
                Join us by filling the information below
            </p>

            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>

                <!-- Username -->
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="passwordField" class="form-control" required>

                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirmPasswordField" class="form-control"
                            required>
                        <span class="input-group-text" style="cursor:pointer;"
                            onclick="togglePassword('confirmPasswordField','toggleIcon2')">
                            <i class="fa-solid fa-eye" id="toggleIcon2"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100">
                    <i class="fa-solid fa-user-plus me-2"></i>
                    Create Account
                </button>
            </form>

            <div class="text-center mt-4 register-text">
                Already have an account?
                <a href="login.php">Login</a>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);

            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                field.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>

</body>

</html>