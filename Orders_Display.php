<?php
@include('config.php');

$instoreOrders = [];
$deliveryOrders = [];

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Accept/Deny Logic
if (isset($_GET['accept_instore'])) {
    $id = $_GET['accept_instore'];
    $conn->query("UPDATE instore_payment SET status = 'Accepted' WHERE order_id = $id");
    header("Location: orders_display.php");
    exit();
}
if (isset($_GET['deny_instore'])) {
    $id = $_GET['deny_instore'];
    $conn->query("UPDATE instore_payment SET status = 'Denied' WHERE order_id = $id");
    header("Location: orders_display.php");
    exit();
}
if (isset($_GET['accept_delivery'])) {
    $id = intval($_GET['accept_delivery']);
    // Changed delivery_payment_id to id in the WHERE clause
    $conn->query("UPDATE delivery_payment SET status = 'Accepted' WHERE id = $id");
    header("Location: orders_display.php");
    exit();
}

if (isset($_GET['deny_delivery'])) {
    $id = intval($_GET['deny_delivery']);
    // Changed delivery_payment_id to id in the WHERE clause
    $conn->query("UPDATE delivery_payment SET status = 'Denied' WHERE id = $id");
    header("Location: orders_display.php");
    exit();
}

// Fetch In-Store Orders
$stmt = $conn->prepare("SELECT * FROM instore_payment ORDER BY created_at DESC");
if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $instoreOrders[] = $row;
    }
    $stmt->close();
} else {
    echo "Error fetching in-store orders: " . $conn->error;
}

// Fetch Delivery Orders
$stmt = $conn->prepare("
    SELECT 
        id,
        user_id, 
        furniture_name, 
        total_amount, 
        created_at, 
        status 
    FROM delivery_payment 
    ORDER BY created_at DESC
");
if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $deliveryOrders[] = $row;
    }
    $stmt->close();
}

// Summary Counters
$totalOrders = count($instoreOrders) + count($deliveryOrders);
$totalPending = 0;
$totalAccepted = 0;
$totalDenied = 0;

foreach (array_merge($instoreOrders, $deliveryOrders) as $order) {
    if ($order['status'] === 'Pending') $totalPending++;
    if ($order['status'] === 'Accepted') $totalAccepted++;
    if ($order['status'] === 'Denied') $totalDenied++;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Orders</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .card {
            border-radius: 1rem;
        }

        .main-content {
            padding: 20px;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .card-header .btn {
            float: right;
        }
    </style>
</head>

<body>
    <?php include('admin_sidebar.php'); ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="bi bi-receipt"></i> Orders</h2>
        </div>

        <div class="container mt-2">
            <div class="row text-center mb-4">
                <div class="col-md-3">
                    <div class="card  shadow-sm rounded-4">
                        <div class="card-body">
                            <i class="bi bi-cart  text-primary "></i>
                            <h5 class="card-title mb-1">Total Orders</h5>
                            <h3 class="fw-bold"><?php echo $totalOrders; ?></h3>

                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm rounded-4">
                        <div class="card-body">
                            <i class="bi bi-clock  text-warning"></i>
                            <h5 class="card-title mb-1">Pending</h5>
                            <h3 class="fw-bold"><?php echo $totalPending; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card  shadow-sm rounded-4">
                        <div class="card-body">
                            <i class="bi bi-patch-check-fill text-success me-2"></i>
                            <h5 class="card-title mb-1">Accepted</h5>
                            <h3 class="fw-bold"><?php echo $totalAccepted; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card  shadow-sm rounded-4">
                        <div class="card-body">
                            <i class="bi bi-x-octagon-fill text-danger me-2"></i>
                            <h5 class="card-title mb-1">Denied</h5>
                            <h3 class="fw-bold"><?php echo $totalDenied; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <!-- In-Store Orders -->
            <div class="card shadow-sm rounded-4 mb-4">
                <div class="card-header">
                    <h4 class="card-title mb-0 d-inline"><i class="bi bi-shop-window"></i> In-Store Orders</h4>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#instoreSection">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                </div>
                <div class="collapse show" id="instoreSection">
                    <div class="card-body">
                        <table class="table table-bordered table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Furniture Name</th>
                                    <th>Total Amount</th>
                                    <th>Order Date</th>
                                    <th>Delivery Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($instoreOrders as $order) {
                                    if ($order['status'] === 'Pending') { ?>
                                        <tr id="order-row-<?php echo $order['order_id']; ?>">
                                            <td><?php echo $order['order_id']; ?></td>
                                            <td><?php echo $order['user_id']; ?></td>
                                            <td><?php echo $order['furniture_name']; ?></td>
                                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td><?php echo $order['created_at']; ?></td>
                                            <td class="text-center">In-Store</td>
                                            <td>
                                                <a href="#" class="btn btn-success btn-sm accept-order" data-id="<?php echo $order['order_id']; ?>" data-type="instore" data-name="<?php echo htmlspecialchars($order['furniture_name']); ?>" data-user="<?php echo $order['user_id']; ?>" data-amount="<?php echo $order['total_amount']; ?>" data-date="<?php echo $order['created_at']; ?>">
                                                    <i class="bi bi-check-circle"></i>
                                                </a>
                                                <a href="#" class="btn btn-danger btn-sm deny-order" data-id="<?php echo $order['order_id']; ?>" data-type="instore" data-name="<?php echo htmlspecialchars($order['furniture_name']); ?>" data-user="<?php echo $order['user_id']; ?>" data-amount="<?php echo $order['total_amount']; ?>" data-date="<?php echo $order['created_at']; ?>">
                                                    <i class="bi bi-x-circle"></i>
                                                </a>
                                            </td>
                                        </tr>
                                <?php }
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


            <!-- Delivery Orders -->
            <div class="card shadow-sm rounded-4 mb-4">
                <div class="card-header">
                    <h4 class="card-title mb-0 d-inline"><i class="bi bi-truck"></i> Delivery Orders</h4>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#deliverySection">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                </div>
                <div class="collapse show" id="deliverySection">
                    <div class="card-body">
                        <table class="table table-bordered table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>User ID</th>
                                    <th>Furniture Name</th>
                                    <th>Total Amount</th>
                                    <th>Order Date</th>
                                    <th>Delivery Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!empty($deliveryOrders)) {
                                    foreach ($deliveryOrders as $order) {
                                        if ($order['status'] === 'Pending') {
                                            // Make sure we're using the correct ID field
                                            $order_id = isset($order['id']) ? $order['id'] : 0;
                                ?>
                                            <tr id="order-row-<?php echo $order_id; ?>">
                                                <td><?php echo $order_id; ?></td>
                                                <td><?php echo htmlspecialchars($order['user_id']); ?></td>
                                                <td><?php echo htmlspecialchars($order['furniture_name']); ?></td>
                                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td><?php echo $order['created_at']; ?></td>
                                                <td class="text-center">Delivery</td>
                                                <td>
                                                    <a href="#" class="btn btn-success btn-sm accept-order"
                                                        data-id="<?php echo $order_id; ?>"
                                                        data-type="delivery"
                                                        data-name="<?php echo htmlspecialchars($order['furniture_name']); ?>"
                                                        data-user="<?php echo $order['user_id']; ?>"
                                                        data-amount="<?php echo $order['total_amount']; ?>"
                                                        data-date="<?php echo $order['created_at']; ?>">
                                                        <i class="bi bi-check-circle"></i>
                                                    </a>
                                                    <a href="#" class="btn btn-danger btn-sm deny-order"
                                                        data-id="<?php echo $order_id; ?>"
                                                        data-type="delivery"
                                                        data-name="<?php echo htmlspecialchars($order['furniture_name']); ?>"
                                                        data-user="<?php echo $order['user_id']; ?>"
                                                        data-amount="<?php echo $order['total_amount']; ?>"
                                                        data-date="<?php echo $order['created_at']; ?>">
                                                        <i class="bi bi-x-circle"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                    <?php }
                                    }
                                } else { ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No pending delivery orders found</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>





            <!-- Accepted Orders -->
            <div class="card shadow-sm rounded-4 mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-patch-check-fill text-success me-2"></i> Accepted Orders
                    </h4>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#acceptedSection">
                        <i class="bi bi-chevron-up"></i>
                    </button>
                </div>
                <div class="collapse show" id="acceptedSection">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th scope="col">Order ID</th>
                                        <th scope="col">User ID</th>
                                        <th scope="col">Furniture Name</th>
                                        <th scope="col">Total Amount</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Delivery Type</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_merge($instoreOrders, $deliveryOrders) as $order) {
                                        if ($order['status'] === 'Accepted') {
                                            // Check the type and get the correct ID
                                            $type = isset($order['pickup_date']) ? 'In-Store' : 'Delivery';
                                            // Use id for delivery orders and order_id for in-store orders
                                            $order_id = isset($order['id']) ? $order['id'] : (isset($order['order_id']) ? $order['order_id'] : '0');
                                    ?>
                                            <tr>
                                                <td class="text-center"><?php echo $order_id; ?></td>
                                                <td class="text-center"><?php echo htmlspecialchars($order['user_id']); ?></td>
                                                <td class="text-center"><?php echo htmlspecialchars($order['furniture_name']); ?></td>
                                                <td class="text-center">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td class="text-center"><?php echo $order['created_at']; ?></td>
                                                <td class="text-center"><?php echo $type; ?></td>
                                                <td class="text-center text-success">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                </td>
                                            </tr>
                                    <?php }
                                    } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Denied Orders -->
            <div class="card shadow-sm rounded-4 mb-5">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-x-octagon-fill text-danger me-2"></i> Denied Orders
                    </h4>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#deniedSection">
                        <i class="bi bi-chevron-up"></i>
                    </button>
                </div>
                <div class="collapse show" id="deniedSection">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th scope="col">Order ID</th>
                                        <th scope="col">User ID</th>
                                        <th scope="col">Furniture Name</th>
                                        <th scope="col">Total Amount</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Delivery Type</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach (array_merge($instoreOrders, $deliveryOrders) as $order) {
                                        if ($order['status'] == 'Denied' && isset($order['order_id'])) {
                                            $type = isset($order['pickup_id']) ? 'In-Store' : 'Delivery';
                                            $order_id = $order['order_id'];
                                    ?>
                                            <tr>
                                                <td class="text-center"><?php echo $order_id; ?></td>
                                                <td class="text-center"><?php echo $order['user_id']; ?></td>
                                                <td class="text-center"><?php echo htmlspecialchars($order['furniture_name']); ?></td>
                                                <td class="text-center"><?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td class="text-center"><?php echo isset($order['pickup_date']) ? $order['pickup_date'] : (isset($order['delivery_date']) ? $order['delivery_date'] : ''); ?></td>
                                                <td class="text-center"><?php echo $type; ?></td>
                                                <td class="text-center text-danger">
                                                    <i class="bi bi-x-octagon-fill me-1"></i> <?php echo $order['status']; ?>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.accept-order, .deny-order').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.dataset.id;
                const type = this.dataset.type;
                const name = this.dataset.name;
                const user = this.dataset.user;
                const amount = parseFloat(this.dataset.amount).toFixed(2);
                const date = this.dataset.date;
                const action = this.classList.contains('accept-order') ? 'accept' : 'deny';
                const confirmMsg = action === 'accept' ? 'Accept this order?' : 'Deny this order?';
                if (!confirm(confirmMsg)) return;

                window.location.href = `orders_display.php?${action}_${type}=${id}`;
            });
        });
    </script>
</body>

</html>