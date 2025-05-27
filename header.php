<!-- Navigation Bar -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header</title>
</head>
<style>
    .navbar {
        padding: 0.2rem 0 !important;
        min-height: 45px;
        background-color: #fff !important;
        position: relative;
    }

    .navbar-brand {
        padding: 0;
        display: flex;
        align-items: center;
        position: relative;
        z-index: 2;
    }

    #logo {
        height: 7rem !important;
        width: auto;
        max-width: 220px;
        object-fit: contain;
        margin: -1.5rem 0;
        position: relative;
    }

    .navbar .container {
        padding: 0 1rem;
    }

    .navbar-nav {
        height: 45px;
        align-items: center;
    }
</style>

<body>

    <nav class="shadow-sm navbar navbar-expand-lg bg-light">
        <div class="container">
            <a class="navbar-brand" href="user.php">
                <img src="pictures/furnara_header_logo.png" alt="Logo" height="40" id="logo">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="gap-5 mx-auto navbar-nav">
                    <li class="nav-item"><a class="nav-link text-dark" href="user.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="furniture_clone.php">Furniture</a></li>
                    <li class="nav-item"><a class="nav-link" href="Designer.php">Designer</a></li>
                    <li class="nav-item"><a class="nav-link" href="AboutUs.php">About Us</a></li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <a href="search.php" class="text-dark fs-5"><i class="bi bi-search"></i></a>

                    <?php if (isset($_SESSION['user_name'])): ?>
                        <!-- If logged in, show Cart, Profile, and Logout -->
                        <a href="#" class="text-dark fs-5 position-relative" id="cart-icon">
                            <i class="bi bi-cart"></i>
                        </a>
                        <a href="profile.php" class="text-dark fs-5"><i class="bi bi-person-circle"></i></a>

                        <form action="logout.php" method="POST" class="d-inline">
                            <button type="submit" class="btn btn-danger">Logout</button>
                        </form>
                    <?php else: ?>
                        <!-- If NOT logged in, show Login/Register -->
                        <a href="login.php" class="btn btn-primary">Login</a>
                        <a href="register.php" class="btn btn-secondary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    </div>
</body>

</html>