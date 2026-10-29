<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';

$error = []; // Initialize error array

if ($_SERVER["REQUEST_METHOD"] == "POST") { // Ensure only POST requests are processed
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = mysqli_real_escape_string($conn, $_POST['password']);

    // Fetch user data from the database
    $query = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // Check if password matches (supporting hashed and md5 passwords)
        if (password_verify($pass, $row['password']) || md5($pass) == $row['password']) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_type'] = $row['user_type'];

            // Redirect based on user type
            if ($row['user_type'] == 'admin') {
                header('Location: ' . app_url('pages/admin/admin.php'));
                exit();
            } elseif ($row['user_type'] == 'user') {
                header('Location: ' . app_url('pages/public/user.php'));
                exit();
            } else {
                $error[] = 'Unknown user type.';
            }
        } else {
            $error[] = 'Incorrect email or password.';
        }
    } else {
        $error[] = 'Incorrect email or password.';
    }
}
?>

<!-- HTML for Login Form -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Now</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/login_Style.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/components/site_components.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/support_chat_widget.css') ?>">
</head>


<body class="bg-light">
    <div class="container">
        <div class="login-form">
            <h2>Login Now</h2>



            <form action="" method="post">
                <?php if (!empty($error)): ?>
                    <?php foreach ($error as $err): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $err; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="form-group">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required
                        placeholder="Enter your email">
                </div>


                <div class="form-group password-wrapper">
                    <label for="password" class="form-label">Password</label>
                    <div class="position-relative">
                        <input type="password" class="form-control" id="password" name="password" required
                            placeholder="Enter your password">
                        <i class="bi bi-eye-slash position-absolute toggle-password" id="togglePassword"></i>
                    </div>
                </div>


                <div class="form-options">
                    <label><input type="checkbox" checked /> Remember me</label>
                    <a href="#" class="text-dark">Forgot Password?</a>
                </div>


                <button type="submit" name="submit" class="btn btn-primary w-100 mb-3">
                    Login Now
                </button>


                <div class="register-link">
                    <p class="text-center mb-0">
                        Don't have an account?
                        <a href="<?= app_url('pages/auth/register.php') ?>" class="text-decoration-none">Register now</a>
                    </p>
                </div>
            </form>
        </div>
        <div class="image-side"></div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePasswordVisibility(inputId, togglerId) {
            const input = document.getElementById(inputId);
            const toggler = document.getElementById(togglerId);

            toggler.addEventListener('click', function() {
                const icon = this;

                icon.style.transition = 'transform 0.2s ease';
                icon.style.transform = 'translateY(-50%) scale(0.9)';

                setTimeout(() => {
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.replace('bi-eye-slash', 'bi-eye');
                    } else {
                        input.type = 'password';
                        icon.classList.replace('bi-eye', 'bi-eye-slash');
                    }
                    icon.style.transform = 'translateY(-50%) scale(1)';
                }, 100);
            });
        }

        // Initialize password toggles
        togglePasswordVisibility('password', 'togglePassword');
        togglePasswordVisibility('cpassword', 'toggleCPassword');
    </script>
    <?php require dirname(__DIR__, 2) . '/components/support_chat_widget.php'; ?>
</body>

</html>
