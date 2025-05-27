<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Furnara Sidebar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inria+Serif&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="user_sidebar.css" />
</head>
<style>
    #logo {
        height: 7rem !important;
        width: auto;
        max-width: 220px;
        object-fit: contain;
        margin: -1.5rem 0;
        position: relative;
    }
</style>

<body>

    <div class="sidebar">
        <img src="pictures/furnara_logo_white.png" alt="Logo" height="40" id="logo">

        <a href="profile.php" class="nav-link text-white">
            <i class="bi bi-person-circle me-2"></i> Profile
        </a>

        <a href="orders.php" class="nav-link text-white">
            <i class="bi bi-bar-chart-line me-2"></i> Orders
        </a>
        <a href="user_delivery_tracking.php" class="nav-link text-white"><i class="bi bi-truck"></i></i> Tracking </a>
        <a href="logout.php" class="nav-link text-white">
            <i class="bi bi-box-arrow-left me-2"></i> Logout
        </a>


        <a href="user.php" class="btn btn-outline-light w-100 back-btn">
            ← Back to Homepage
        </a>
    </div>




</body>

</html>