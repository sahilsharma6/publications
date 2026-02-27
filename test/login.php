<?php
session_start();
require_once '../db.php';

$message = '';

// Already logged in → redirect
if (isset($_SESSION['user_id'])) {
    header("Location: ./dashboard");
    exit();
}

// Initialize session tracking
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if (!isset($_SESSION['lock_until'])) {
    $_SESSION['lock_until'] = null;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Check if currently locked
    if ($_SESSION['lock_until'] && time() < $_SESSION['lock_until']) {

        $remaining = $_SESSION['lock_until'] - time();
        $message = "Too many failed attempts. Try again in {$remaining} seconds.";

    } else {

        // If lock expired → reset
        if ($_SESSION['lock_until'] && time() >= $_SESSION['lock_until']) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['lock_until'] = null;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $message = "All fields are required.";
        } else {

            $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {

                    // SUCCESS LOGIN
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];

                    // Reset attempts
                    $_SESSION['login_attempts'] = 0;
                    $_SESSION['lock_until'] = null;

                    header("Location: ../test/dashboard");
                    exit();

                } else {
                    $_SESSION['login_attempts']++;

                    if ($_SESSION['login_attempts'] >= 5) {
                        $_SESSION['lock_until'] = time() + 120; // 2 minutes lock
                        $message = "Too many failed attempts. Locked for 2 minutes.";
                    } else {
                        $message = "Invalid email or password.";
                    }
                }

            } else {
                $_SESSION['login_attempts']++;

                if ($_SESSION['login_attempts'] >= 5) {
                    $_SESSION['lock_until'] = time() + 120; // 2 minutes
                    $message = "Too many failed attempts. Locked for 2 minutes.";
                } else {
                    $message = "Invalid email or password.";
                }
            }

            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>

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

    <div class="login-wrapper d-flex justify-content-center align-items-center min-vh-100 px-3">
        <div class="login-card">

            <h2 class="login-title text-center">Welcome Back</h2>
            <p class="login-subtitle text-center">
                Enter your credentials to access your account
            </p>

            <?php if (!empty($message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>

                <!-- Email -->
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fa-regular fa-envelope"></i>
                        </span>
                        <input type="email" name="email" class="form-control with-icon"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-2">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="passwordField" class="form-control with-icon"
                            required>
                        <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword()">
                            <i class="fa-solid fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>

                <!-- Remember + Forgot -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>
                    <a href="forgot-password.php" class="forgot-link">
                        Forgot Password?
                    </a>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100">
                    <i class="fa-solid fa-right-to-bracket me-2"></i>
                    Login
                </button>
            </form>

            <div class="text-center mt-4 register-text">
                Don’t have an account?
                <a href="register.php">Create Account</a>
            </div>

        </div>
    </div>

    <script>
        function togglePassword() {
            const field = document.getElementById("passwordField");
            const icon = document.getElementById("toggleIcon");

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>