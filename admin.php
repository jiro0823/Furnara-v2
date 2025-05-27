<?php
@include('config.php');

// Security check - prevent unauthorized access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Initialize arrays to avoid potential undefined errors
$weekly_labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$weekly_data = array_fill(0, 7, 0);
$monthly_labels = [];
$monthly_data = [];
$yearly_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$yearly_data = array_fill(0, 12, 0);

// Weekly Data: Revenue per day this week (Mon to Sun)
$week_query = mysqli_query($conn, "
    SELECT DAYOFWEEK(order_date) AS day, SUM(total_price) AS total 
    FROM orders 
    WHERE YEARWEEK(order_date, 1) = YEARWEEK(CURDATE(), 1) AND status = 'accepted' 
    GROUP BY DAYOFWEEK(order_date)
");
if ($week_query) {
    while ($row = mysqli_fetch_assoc($week_query)) {
        $day_index = $row['day'] - 2; // Monday is 2 in DAYOFWEEK, so index 0
        if ($day_index < 0) $day_index = 6; // Sunday case
        $weekly_data[$day_index] = round($row['total'], 2);
    }
}

// Monthly Data: Revenue for each day this month
$days_in_month = date('t');
for ($i = 1; $i <= $days_in_month; $i++) {
    $date_label = date('M ') . str_pad($i, 2, '0', STR_PAD_LEFT);
    $monthly_labels[] = $date_label;
    $monthly_data[$i] = 0;
}

$month_query = mysqli_query($conn, "
    SELECT DAY(order_date) AS day, SUM(total_price) AS total 
    FROM orders 
    WHERE MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE()) AND status = 'accepted' 
    GROUP BY DAY(order_date)
");
if ($month_query) {
    while ($row = mysqli_fetch_assoc($month_query)) {
        $monthly_data[$row['day']] = round($row['total'], 2);
    }
}
$monthly_data = array_values($monthly_data);

// Yearly Data: Revenue per month
$year_query = mysqli_query($conn, "
    SELECT MONTH(order_date) AS month, SUM(total_price) AS total 
    FROM orders 
    WHERE YEAR(order_date) = YEAR(CURDATE()) AND status = 'accepted' 
    GROUP BY MONTH(order_date)
");
if ($year_query) {
    while ($row = mysqli_fetch_assoc($year_query)) {
        $yearly_data[$row['month'] - 1] = round($row['total'], 2);
    }
}

// Function to safely get database values
function safe_query($conn, $query, $default = 0)
{
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return isset($row['value']) ? $row['value'] : $default;
    }
    return $default;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Furnera Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin_style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <?php include('admin_sidebar.php'); ?>

    <div class="main-content">
        <h3 class="mb-4">Welcome, Admin!</h3>

        <!-- First Row of Stats Cards -->
        <div class="row g-4 mb-4">
            <!-- Total Products -->
            <div class="col-md-4">
                <div class="card p-4 text-center shadow-sm">
                    <h5><i class="bi bi-box-seam text-primary"></i> Total Furnitures</h5>
                    <p class="display-6 text-primary">
                        <?php
                        $product_query = mysqli_query($conn, "SELECT COUNT(*) AS value FROM furniture");
                        $product_count = $product_query ? mysqli_fetch_assoc($product_query)['value'] : 0;
                        echo $product_count;
                        ?>
                    </p>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="col-md-4">
                <div class="card p-4 text-center shadow-sm">
                    <h5><i class="bi bi-cash-coin text-danger"></i> Total Revenue</h5>
                    <p class="display-6 text-danger">
                        <?php
                        $revenue_query = mysqli_query($conn, "SELECT IFNULL(SUM(total_price), 0) AS value FROM orders WHERE status = 'accepted'");
                        $total_revenue = $revenue_query ? mysqli_fetch_assoc($revenue_query)['value'] : 0;
                        echo '₱' . number_format($total_revenue, 2);
                        ?>
                    </p>
                </div>
            </div>

            <!-- In-Store Orders -->
            <div class="col-md-4">
                <div class="card p-4 text-center shadow-sm">
                    <h5><i class="bi bi-shop text-success"></i> In-Store Orders</h5>
                    <p class="display-6 text-success">
                        <?php
                        $instore_count_query = mysqli_query($conn, "SELECT COUNT(*) AS value FROM orders WHERE delivery_type='In-Store' AND status = 'accepted'");
                        $instore_count = $instore_count_query ? mysqli_fetch_assoc($instore_count_query)['value'] : 0;
                        echo $instore_count;
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Second Row of Stats Cards -->
        <div class="row g-4 mb-4">
            <!-- Delivery Orders -->
            <div class="col-md-4">
                <div class="card p-4 text-center shadow-sm">
                    <h5><i class="bi bi-truck text-warning"></i> Delivery Orders</h5>
                    <p class="display-6 text-warning">
                        <?php
                        $delivery_count_query = mysqli_query($conn, "SELECT COUNT(*) AS value FROM orders WHERE delivery_type='Delivery' AND status = 'accepted'");
                        $delivery_count = $delivery_count_query ? mysqli_fetch_assoc($delivery_count_query)['value'] : 0;
                        echo $delivery_count;
                        ?>
                    </p>
                </div>
            </div>

            <!-- Today's Revenue -->
            <div class="col-md-4">
                <div class="card p-4 text-center shadow-sm">
                    <h5><i class="bi bi-calendar-day text-info"></i> Today's Revenue</h5>
                    <p class="display-6 text-info">
                        <?php
                        // Today's Revenue query (only for accepted orders)
                        $today_query = mysqli_query($conn, "
                            SELECT IFNULL(SUM(total_price), 0) AS value 
                            FROM orders 
                            WHERE DATE(order_date) = CURDATE() AND status = 'accepted'
                        ");

                        $today_revenue = $today_query ? mysqli_fetch_assoc($today_query)['value'] : 0;
                        echo '₱' . number_format($today_revenue, 2);
                        ?>
                    </p>
                </div>
            </div>

            <!-- This Month's Revenue -->
            <div class="col-md-4">
                <div class="card p-4 text-center shadow-sm">
                    <h5><i class="bi bi-calendar-month text-primary"></i> This Month's Revenue</h5>
                    <p class="display-6 text-primary">
                        <?php
                        $month_query = mysqli_query($conn, "
                            SELECT IFNULL(SUM(total_price), 0) AS value 
                            FROM orders 
                            WHERE MONTH(order_date) = MONTH(CURRENT_DATE()) 
                            AND YEAR(order_date) = YEAR(CURRENT_DATE()) AND status = 'accepted'
                        ");
                        $month_revenue = $month_query ? mysqli_fetch_assoc($month_query)['value'] : 0;
                        echo '₱' . number_format($month_revenue, 2);
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <!-- Sales Overview Graph -->
            <div class="col-md-8">
                <div class="card p-4 shadow-sm">
                    <h5><i class="bi bi-bar-chart-line text-primary"></i> Sales Overview</h5>
                    <canvas id="salesChart" height="200"></canvas>
                    <div class="mt-3 text-center">
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-primary active" id="weekBtn" onclick="updateChart('weekly')">Weekly</button>
                            <button class="btn btn-sm btn-outline-primary" id="monthBtn" onclick="updateChart('monthly')">Monthly</button>
                            <button class="btn btn-sm btn-outline-primary" id="yearBtn" onclick="updateChart('yearly')">Yearly</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales by Category Doughnut Chart -->
            <div class="col-md-4">
                <div class="card p-4 shadow-sm">
                    <h5><i class="bi bi-pie-chart-fill text-success"></i> Sales by Category</h5>
                    <canvas id="categoryChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Tables Row -->
        <div class="row g-4">
            <!-- Recent Orders -->
            <div class="col-md-6">
                <div class="card p-4 shadow-sm">
                    <h5><i class="bi bi-clock-history text-primary"></i> Recent Orders</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID </th>
                                    <th>Total</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recent_orders = mysqli_query($conn, "SELECT order_id, total_price, order_date FROM orders WHERE status = 'accepted' ORDER BY order_date DESC LIMIT 5");
                                if ($recent_orders && mysqli_num_rows($recent_orders) > 0) {
                                    while ($order = mysqli_fetch_assoc($recent_orders)) {
                                        echo '<tr>
                                            <td>' . $order['order_id'] . '</td>
                                            <td>₱' . number_format($order['total_price'], 2) . '</td>
                                            <td>' . date('Y-m-d', strtotime($order['order_date'])) . '</td>
                                        </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="3" class="text-center">No recent orders</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Selling Products -->
            <div class="col-md-6">
                <div class="card p-4 shadow-sm">
                    <h5><i class="bi bi-star-fill text-warning"></i> Top Selling Products</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Furniture Name</th>
                                    <th>Total Sold</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $top_selling = mysqli_query($conn, "
                                    SELECT o.furniture_name, SUM(o.total_amount) AS total_sold 
                                    FROM orders o
                                    WHERE o.status = 'accepted'
                                    GROUP BY o.furniture_name
                                    ORDER BY total_sold DESC 
                                    LIMIT 5
                                ");
                                if ($top_selling && mysqli_num_rows($top_selling) > 0) {
                                    while ($product = mysqli_fetch_assoc($top_selling)) {
                                        echo '<tr>
                                            <td>' . htmlspecialchars($product['furniture_name']) . '</td>
                                            <td>' . $product['total_sold'] . '</td>
                                        </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="2" class="text-center">No products sold yet</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Prepare sales data from PHP to JS
        const salesData = {
            weekly: <?php echo json_encode($weekly_data); ?>,
            monthly: <?php echo json_encode($monthly_data); ?>,
            yearly: <?php echo json_encode($yearly_data); ?>
        };

        const salesLabels = {
            weekly: <?php echo json_encode($weekly_labels); ?>,
            monthly: <?php echo json_encode($monthly_labels); ?>,
            yearly: <?php echo json_encode($yearly_labels); ?>
        };

        // Get chart contexts
        const ctx = document.getElementById('salesChart').getContext('2d');
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');

        // Default chart settings
        let currentChartType = 'weekly';
        let salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: salesLabels[currentChartType],
                datasets: [{
                    label: 'Revenue',
                    data: salesData[currentChartType],
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true,
                    tension: 0.4 // Adds smooth curves to the line
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value;
                            }
                        }
                    }
                }
            }
        });

        // Category chart with dummy data (you'll need to replace with real data from database)
        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['Chairs', 'Tables', 'Beds', 'Cabinets', 'Others'],
                datasets: [{
                    data: [30, 25, 20, 15, 10],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });

        // Update chart based on button click
        function updateChart(type) {
            currentChartType = type;

            // Update active button state
            document.getElementById('weekBtn').classList.remove('active');
            document.getElementById('monthBtn').classList.remove('active');
            document.getElementById('yearBtn').classList.remove('active');

            if (type === 'weekly') {
                document.getElementById('weekBtn').classList.add('active');
            } else if (type === 'monthly') {
                document.getElementById('monthBtn').classList.add('active');
            } else {
                document.getElementById('yearBtn').classList.add('active');
            }

            // Update chart data
            salesChart.data.labels = salesLabels[type];
            salesChart.data.datasets[0].data = salesData[type];
            salesChart.update();
        }

        // Initialize tooltips and popovers if using Bootstrap 5
        if (typeof bootstrap !== 'undefined') {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        }
    </script>
</body>

</html>