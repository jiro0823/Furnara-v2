<?php
@include 'config.php';


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Use prepared statement for security
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists
if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
} else {
    echo "User not found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inria+Serif&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="user_sidebar.css" />
</head>

<body>

    <div class="d-flex">
        <!-- Sidebar -->
        <?php include('user_sidebar.php'); ?>

        <!-- Main Content -->
        <div class="flex-grow-1 p-4" style="margin-left: 250px;">
            <div class="text-center mb-4">
                <h1 class="custom-heading">User Profile</h1>
            </div>

            <!-- Profile Section -->
            <div class="d-flex flex-column align-items-center">
                <div class="profile-image-container mb-3">
                    <img src="https://i.pinimg.com/736x/3c/ae/07/3cae079ca0b9e55ec6bfc1b358c9b1e2.jpg"
                        class="profile-image rounded-circle border border-3 border-secondary"
                        alt="Profile Picture" width="180" height="180" />
                </div>

                <label for="upload" class="upload-label btn btn-outline-secondary mb-4">Upload Image</label>
                <input type="file" id="upload" accept="image/*" style="display:none;">
            </div>

            <!-- User Info -->
            <div class="profile-info">
                <div class="form-group mb-3 text-start">
                    <label class="text-custom">Name</label>
                    <div class="static-text p-2 border rounded bg-light"><?= htmlspecialchars($user['name']) ?></div>
                </div>

                <div class="form-group mb-3 text-start">
                    <label class="text-custom">Email</label>
                    <div class="static-text p-2 border rounded bg-light"><?= htmlspecialchars($user['email']) ?></div>
                </div>

                <div class="form-group mb-3 text-start">
                    <label class="text-custom">Role</label>
                    <div class="static-text p-2 border rounded bg-light"><?= htmlspecialchars($user['user_type']) ?></div>
                </div>
            </div>
        </div>


</body>

</html>