<?php
session_start();
@include('config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Add Discount
if (isset($_POST['add_discount'])) {
    $furniture_id = $_POST['furniture_id'];
    $discount_percentage = $_POST['discount_percentage'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Get the original price
        $get_price = mysqli_query($conn, "SELECT price FROM furniture WHERE furniture_id = '$furniture_id'");
        $product = mysqli_fetch_assoc($get_price);
        $original_price = $product['price'];

        // Calculate discounted price
        $discounted_price = $original_price * (1 - ($discount_percentage / 100));

        // Update furniture table
        $update_query = "UPDATE furniture SET 
            discount_percentage = '$discount_percentage',
            discounted_price = '$discounted_price',
            discount_start = '$start_date',
            discount_end = '$end_date'
            WHERE furniture_id = '$furniture_id'";

        if (!mysqli_query($conn, $update_query)) {
            throw new Exception("Failed to add discount: " . mysqli_error($conn));
        }

        // Commit transaction
        mysqli_commit($conn);
        $message[] = 'Discount added successfully';
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $message[] = $e->getMessage();
    }
}

// Remove Discount
if (isset($_GET['remove_discount'])) {
    $furniture_id = $_GET['remove_discount'];

    mysqli_begin_transaction($conn);

    try {
        $update_query = "UPDATE furniture SET 
            discount_percentage = NULL,
            discounted_price = NULL,
            discount_start = NULL,
            discount_end = NULL
            WHERE furniture_id = '$furniture_id'";

        if (!mysqli_query($conn, $update_query)) {
            throw new Exception("Failed to remove discount: " . mysqli_error($conn));
        }

        mysqli_commit($conn);
        $message[] = 'Discount removed successfully';
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $message[] = $e->getMessage();
    }
}

// Fetch Products with their discount information
$products = mysqli_query($conn, "SELECT * FROM furniture ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Discounts</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>

<body>
    <?php include('admin_sidebar.php'); ?>

    <div class="main-content">
        <div class="container mt-4">
            <h2 class="mb-4"><i class="bi bi-tag"></i> Manage Discounts</h2>

            <?php
            if (isset($message)) {
                foreach ($message as $msg) {
                    echo '<div class="alert alert-info alert-dismissible fade show" role="alert">
                        ' . $msg . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
                }
            }
            ?>

            <!-- Add Discount Form -->
            <div class="card shadow-sm rounded-4 mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-3"><i class="bi bi-plus-circle"></i> Add New Discount</h4>
                    <form action="" method="POST" class="mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Select Product</label>
                                    <select name="furniture_id" class="form-select" required>
                                        <option value="">Choose a product...</option>
                                        <?php
                                        mysqli_data_seek($products, 0);
                                        while ($row = mysqli_fetch_assoc($products)) {
                                            echo "<option value='" . $row['furniture_id'] . "'>" . $row['name'] . " - ₱" . number_format($row['price'], 2) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Discount Percentage</label>
                                    <div class="input-group">
                                        <input type="number" name="discount_percentage" class="form-control" min="1" max="99" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="add_discount" class="btn btn-dark">
                            <i class="bi bi-plus-circle"></i> Add Discount
                        </button>
                    </form>
                </div>
            </div>

            <!-- Products with Discounts Table -->
            <div class="card shadow-sm rounded-4">
                <div class="card-body">
                    <h4 class="card-title mb-3"><i class="bi bi-table"></i> Current Discounts</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Original Price</th>
                                    <th>Discount</th>
                                    <th>Discounted Price</th>
                                    <th>Valid Period</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($products, 0);
                                while ($row = mysqli_fetch_assoc($products)) {
                                    $has_discount = !is_null($row['discount_percentage']);
                                    $is_active = $has_discount &&
                                        strtotime($row['discount_start']) <= time() &&
                                        strtotime($row['discount_end']) >= time();
                                ?>
                                    <tr>
                                        <td><?php echo $row['name']; ?></td>
                                        <td>₱<?php echo number_format($row['price'], 2); ?></td>
                                        <td><?php echo $has_discount ? $row['discount_percentage'] . '%' : '-'; ?></td>
                                        <td><?php echo $has_discount ? '₱' . number_format($row['discounted_price'], 2) : '-'; ?></td>
                                        <td>
                                            <?php
                                            if ($has_discount) {
                                                echo date('M d, Y', strtotime($row['discount_start'])) . ' to ' .
                                                    date('M d, Y', strtotime($row['discount_end']));
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($has_discount): ?>
                                                <?php if ($is_active): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php elseif (strtotime($row['discount_start']) > time()): ?>
                                                    <span class="badge bg-warning">Scheduled</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Expired</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No Discount</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($has_discount): ?>
                                                <a href="?remove_discount=<?php echo $row['furniture_id']; ?>"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Are you sure you want to remove this discount?')">
                                                    <i class="bi bi-trash"></i> Remove
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date for start_date to today
        document.querySelector('input[name="start_date"]').min = new Date().toISOString().split('T')[0];

        // Update end_date minimum when start_date changes
        document.querySelector('input[name="start_date"]').addEventListener('change', function() {
            document.querySelector('input[name="end_date"]').min = this.value;
        });
    </script>
</body>

</html>