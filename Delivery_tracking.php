<?php
session_start();
@include('config.php');

// Security check - prevent unauthorized access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Handle status update
if (isset($_POST['update_status'])) {
    $tracking_id = intval($_POST['tracking_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);

    $update_status_query = mysqli_query($conn, "
        UPDATE delivery_tracking dt
        INNER JOIN orders o ON dt.order_id = o.order_id
        SET dt.status = '$new_status' 
        WHERE dt.tracking_id = $tracking_id 
        AND o.status = 'Accepted'
    ");

    if ($update_status_query) {
        $message = "Delivery status updated successfully!";
    } else {
        $message = "Failed to update delivery status: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Tracking</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin_style.css">
</head>

<body>

    <?php include('admin_sidebar.php'); ?>

    <div class="main-content">
        <h3 class="mb-4">Delivery Tracking</h3>

        <?php if (isset($message)): ?>
            <div class="alert alert-info">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card p-4 shadow-sm">
            <h5><i class="bi bi-truck text-warning"></i> Manage Delivery Orders</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tracking ID</th>
                            <th>Order ID</th>
                            <th>Customer Name</th>
                            <th>Delivery Address</th>
                            <th>Status</th>
                            <th>Estimated Delivery</th>
                            <th>Update Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        // Fetch only accepted delivery orders
                        $delivery_tracking_query = mysqli_query($conn, "
        SELECT 
            dt.tracking_id, 
            dt.order_id, 
            dt.customer_name, 
            dt.delivery_address, 
            dt.status,
            DATE_FORMAT(
                CASE 
                    WHEN dt.estimated_delivery IS NOT NULL 
                    THEN dt.estimated_delivery
                    ELSE DATE_ADD(NOW(), INTERVAL 3 DAY)
                END,
                '%M %d, %Y'
            ) as estimated_delivery
        FROM delivery_tracking dt
        INNER JOIN orders o ON dt.order_id = o.order_id
        WHERE o.status = 'Accepted' AND dt.status != 'Delivered'
        ORDER BY dt.tracking_id DESC
    ");

                        if ($delivery_tracking_query && mysqli_num_rows($delivery_tracking_query) > 0) {
                            while ($tracking = mysqli_fetch_assoc($delivery_tracking_query)) { ?>
                                <tr>
                                    <td><?php echo $tracking['tracking_id']; ?></td>
                                    <td><?php echo $tracking['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($tracking['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($tracking['delivery_address']); ?></td>
                                    <td><?php echo htmlspecialchars($tracking['status']); ?></td>
                                    <td><?php echo $tracking['estimated_delivery']; ?></td>
                                    <td>
                                        <form method="POST" action="">
                                            <input type="hidden" name="tracking_id" value="<?php echo $tracking['tracking_id']; ?>">
                                            <select name="new_status" class="form-select form-select-sm" required>
                                                <option value="Preparing" <?php echo ($tracking['status'] == 'Preparing') ? 'selected' : ''; ?>>Preparing</option>
                                                <option value="Out for Delivery" <?php echo ($tracking['status'] == 'Out for Delivery') ? 'selected' : ''; ?>>Out for Delivery</option>
                                                <option value="Delivered" <?php echo ($tracking['status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-dark mt-2">Update</button>
                                        </form>
                                    </td>
                                </tr>
                        <?php }
                        } else {
                            echo '<tr><td colspan="7" class="text-center">No active delivery orders found</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>