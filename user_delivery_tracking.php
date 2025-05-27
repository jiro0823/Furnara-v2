<?php
session_start();
include 'config.php';

// Security check - prevent unauthorized access
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch delivery tracking data with estimated delivery calculation
$sql = "SELECT 
    dt.tracking_id, 
    dt.order_id, 
    dt.customer_name,
    dt.status,
    dt.delivery_address,
    DATE_FORMAT(
        CASE 
            WHEN dt.estimated_delivery IS NOT NULL 
            THEN dt.estimated_delivery
            ELSE DATE_ADD(NOW(), INTERVAL 3 DAY)
        END,
        '%M %d, %Y'
    ) as estimated_delivery
    FROM delivery_tracking dt
    WHERE dt.user_id = ? 
    ORDER BY dt.tracking_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Deliveries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="user_sidebar.css" />
</head>

<body style="background-color: #f8f9fa;">

    <div class="d-flex"> <!-- Flex container -->

        <!-- Sidebar -->
        <div style="min-width: 250px; background-color: #343a40; min-height: 100vh;">
            <?php include('user_sidebar.php'); ?>
        </div>

        <!-- Page Content -->
        <div class="flex-grow-1 p-4">

            <div class="container-fluid">
                <h1 class="mb-4">My Deliveries</h1>

                <!-- Delivery Tracking Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-truck"></i> Delivery Status</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table custom-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tracking ID</th>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Estimated Delivery</th>
                                        <th>Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['tracking_id']); ?></td>

                                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                                <td><?php echo htmlspecialchars($row['estimated_delivery']); ?></td>
                                                <td><?php echo htmlspecialchars($row['delivery_address']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No delivery records found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>