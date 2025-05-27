<?php
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo 'User not logged in.';
    exit;
}

$user_id = $_SESSION['user_id'];

// In-Store Payment Query - updated to use order_id
$instore_sql = "SELECT * FROM instore_payment WHERE user_id = ? ORDER BY created_at DESC";
$instore_stmt = $conn->prepare($instore_sql);
$instore_stmt->bind_param("i", $user_id);
$instore_stmt->execute();
$instore_result = $instore_stmt->get_result();

// Delivery Payment Query - updated to use created_at for sorting
$delivery_sql = "SELECT * FROM delivery_payment WHERE user_id = ? ORDER BY created_at DESC";
$delivery_stmt = $conn->prepare($delivery_sql);
$delivery_stmt->bind_param("i", $user_id);
$delivery_stmt->execute();
$delivery_result = $delivery_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
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
                <h1 class="mb-4">Your Orders</h1>

                <!-- In-Store Pickup Table -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 text-white">In-Store Pickups</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table custom-table mb-0">

                            <thead class="table-light">
                                <tr>
                                    <th>Furniture name</th>
                                    <th>Total Amount</th>
                                    <th>Pickup Date</th>
                                    <th>Pickup Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($instore = $instore_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($instore['furniture_name']); ?></td>
                                        <td>₱<?php echo number_format($instore['total_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($instore['pickup_date']); ?></td>
                                        <td><?php echo htmlspecialchars($instore['pickup_time']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning editOrderBtn"
                                                data-id="<?php echo $instore['order_id']; ?>"
                                                data-type="instore"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editOrderModal">
                                                Edit
                                            </button>
                                            <a href="cancel_order.php?id=<?php echo $instore['order_id']; ?>&type=instore"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to cancel this order?');">
                                                Cancel
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Delivery Orders Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0 text-white">Delivery Orders</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table custom-table mb-0">

                            <thead class="table-light">
                                <tr>


                                    <th>Delivery Address</th>
                                    <th>Total Amount</th>
                                    <th>Delivery Date</th>
                                    <th>Delivery Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($delivery = $delivery_result->fetch_assoc()): ?>
                                    <tr>


                                        <td><?php echo htmlspecialchars($delivery['delivery_address']); ?></td>
                                        <td>₱<?php echo number_format($delivery['total_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($delivery['delivery_date']); ?></td>
                                        <td><?php echo htmlspecialchars($delivery['delivery_time']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning editOrderBtn"
                                                data-id="<?php echo $delivery['order_id']; ?>"
                                                data-type="delivery"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editOrderModal">
                                                Edit
                                            </button>
                                            <a href="cancel_order.php?id=<?php echo $delivery['order_id']; ?>&type=delivery"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to cancel this order?');">
                                                Cancel
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Keep your original modals and scripts here — no changes -->
    <!-- Edit Order Modal -->
    <div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" action="update_order.php">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">

                        <input type="hidden" name="order_id" id="order_id">
                        <input type="hidden" name="order_type" id="order_type">

                        <!-- In-Store Fields -->
                        <div id="instoreFields" style="display: none;">
                            <div class="mb-3">
                                <label>Pickup Date</label>
                                <input type="date" name="pickup_date" id="pickup_date" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Pickup Time</label>
                                <input type="time" name="pickup_time" id="pickup_time" class="form-control">
                            </div>
                        </div>

                        <!-- Delivery Fields -->
                        <div id="deliveryFields" style="display: none;">
                            <div class="mb-3">
                                <label>Delivery Name</label>
                                <input type="text" name="delivery_name" id="delivery_name" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Delivery Address</label>
                                <input type="text" name="delivery_address" id="delivery_address" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Delivery Date</label>
                                <input type="date" name="delivery_date" id="delivery_date" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Delivery Time</label>
                                <input type="time" name="delivery_time" id="delivery_time" class="form-control">
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- JS Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.querySelectorAll('.editOrderBtn').forEach(button => {
            button.addEventListener('click', () => {
                const orderId = button.getAttribute('data-id');
                const orderType = button.getAttribute('data-type');

                document.getElementById('order_id').value = orderId;
                document.getElementById('order_type').value = orderType;

                if (orderType === 'instore') {
                    document.getElementById('instoreFields').style.display = 'block';
                    document.getElementById('deliveryFields').style.display = 'none';
                } else {
                    document.getElementById('instoreFields').style.display = 'none';
                    document.getElementById('deliveryFields').style.display = 'block';
                }

                fetch(`fetch_order.php?id=${orderId}&type=${orderType}`)
                    .then(response => response.json())
                    .then(data => {
                        if (orderType === 'instore') {
                            document.getElementById('pickup_date').value = data.pickup_date;
                            document.getElementById('pickup_time').value = data.pickup_time;
                        } else {
                            document.getElementById('delivery_name').value = data.delivery_name;
                            document.getElementById('delivery_address').value = data.delivery_address;
                            document.getElementById('delivery_date').value = data.delivery_date;
                            document.getElementById('delivery_time').value = data.delivery_time;
                        }
                    });
            });
        });
    </script>
</body>

</html>