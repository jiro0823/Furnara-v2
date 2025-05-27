<?php
session_start();
@include('config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Add Product
// Add Product
if (isset($_POST['add_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $furniture_type = mysqli_real_escape_string($conn, $_POST['furniture_type']); // Add this line
    $stock = mysqli_real_escape_string($conn, $_POST['stock']);
    $image = $_FILES['image']['name'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_folder = 'uploads/' . $image;

    mysqli_begin_transaction($conn);

    try {
        // Insert product
        $insert_product = mysqli_query($conn, "INSERT INTO furniture (name, price, description, furniture_type, stock, image) 
                                           VALUES ('$name', '$price', '$description', '$furniture_type', '$stock', '$image')");

        if (!$insert_product) {
            throw new Exception("Failed to add product: " . mysqli_error($conn));
        }

        // Get the inserted product ID
        $product_id = mysqli_insert_id($conn);

        // Log the addition in furniture_logs table with all fields
        $insert_log = "INSERT INTO furniture_logs (
            furniture_id, action, old_name, new_name, 
            old_price, new_price, old_stock, new_stock, 
            old_image, new_image, old_furniture_type, 
            new_furniture_type, old_description, new_description
        ) VALUES (
            '$product_id', 'add', '', '$name', 
            '0.00', '$price', '0', '$stock', 
            '', '$image', '', '$furniture_type', 
            '', '$description'
        )";

        $log_query = mysqli_query($conn, $insert_log);

        if (!$log_query) {
            throw new Exception("Failed to log product addition: " . mysqli_error($conn));
        }

        // Move the uploaded file
        if (!move_uploaded_file($image_tmp_name, $image_folder)) {
            throw new Exception("Failed to upload image");
        }

        mysqli_commit($conn);
        $message[] = 'Product added successfully';
        header('location: manage_products.php');
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        if (file_exists($image_folder)) {
            unlink($image_folder); // Remove uploaded file if transaction failed
        }
        $message[] = 'Failed to add product: ' . $e->getMessage();
    }
}

// Delete Product
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Get product details before deleting
        $select_product = mysqli_query($conn, "SELECT * FROM furniture WHERE furniture_id = '$id'");
        if (!$select_product) {
            throw new Exception("Failed to retrieve product details: " . mysqli_error($conn));
        }

        $row = mysqli_fetch_assoc($select_product);
        if (!$row) {
            throw new Exception("Product not found");
        }

        $old_name = $row['name'];
        $old_price = $row['price'];
        $old_stock = $row['stock'];
        $old_image = $row['image'];

        // First, log the deletion in furniture_logs table
        $insert_log = "INSERT INTO furniture_logs (furniture_id, action, old_name, new_name, old_price, new_price, old_stock, new_stock, old_image, new_image) 
                      VALUES ('$id', 'delete', '$old_name', '', '$old_price', '0.00', '$old_stock', '0', '$old_image', '')";

        $log_query = mysqli_query($conn, $insert_log);

        if (!$log_query) {
            throw new Exception("Failed to log product deletion: " . mysqli_error($conn));
        }

        // Then delete the product
        $delete_product = mysqli_query($conn, "DELETE FROM furniture WHERE furniture_id = '$id'");

        if ($delete_product) {
            // Delete the image file if it exists
            if (file_exists('uploads/' . $old_image)) {
                unlink('uploads/' . $old_image);
            }

            // Commit transaction
            mysqli_commit($conn);
            $message[] = 'Product deleted successfully';
            header('location: manage_products.php');
        } else {
            throw new Exception("Failed to delete product: " . mysqli_error($conn));
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $message[] = 'Failed to delete product: ' . $e->getMessage();
    }
}

// Update Product
// Replace the Update Product section with this:
if (isset($_POST['update_product'])) {
    mysqli_begin_transaction($conn);

    try {
        $update_id = mysqli_real_escape_string($conn, $_POST['update_id']);
        $update_name = mysqli_real_escape_string($conn, $_POST['update_name']);
        $update_price = mysqli_real_escape_string($conn, $_POST['update_price']);
        $update_description = mysqli_real_escape_string($conn, $_POST['update_description']);
        $update_furniture_type = mysqli_real_escape_string($conn, $_POST['update_furniture_type']); // Add this line
        $update_stock = mysqli_real_escape_string($conn, $_POST['update_stock']);
        $old_image = $_POST['old_image'];

        // Get current product details before update
        $select_current = mysqli_query($conn, "SELECT * FROM furniture WHERE furniture_id = '$update_id' FOR UPDATE");
        if (!$select_current) {
            throw new Exception("Failed to retrieve current product details: " . mysqli_error($conn));
        }

        $current = mysqli_fetch_assoc($select_current);
        if (!$current) {
            throw new Exception("Product not found");
        }

        $new_image = $old_image;
        if (!empty($_FILES['update_image']['name'])) {
            $new_image = $_FILES['update_image']['name'];
            $image_tmp = $_FILES['update_image']['tmp_name'];
            $image_folder = 'uploads/' . $new_image;
        }

        // Update the product - include furniture_type in the UPDATE query
        $update_query = "UPDATE furniture SET 
            name = '$update_name', 
            price = '$update_price', 
            description = '$update_description',
            furniture_type = '$update_furniture_type', 
            stock = '$update_stock'" .
            (!empty($_FILES['update_image']['name']) ? ", image = '$new_image'" : "") .
            " WHERE furniture_id = '$update_id'";

        $update_result = mysqli_query($conn, $update_query);
        if (!$update_result) {
            throw new Exception("Failed to update product: " . mysqli_error($conn));
        }

        // Log the update - include furniture_type in the logging
        $insert_log = "INSERT INTO furniture_logs (
                furniture_id, action, old_name, new_name, 
                old_price, new_price, old_stock, new_stock, 
                old_image, new_image, old_furniture_type, 
                new_furniture_type, old_description, new_description
            ) VALUES (
                '$update_id', 'update',
                '{$current['name']}', '$update_name',
                '{$current['price']}', '$update_price',
                '{$current['stock']}', '$update_stock',
                '{$current['image']}', '$new_image',
                '{$current['furniture_type']}', '$update_furniture_type',
                '{$current['description']}', '$update_description'
            )";

        $log_query = mysqli_query($conn, $insert_log);


        if (!$log_query) {
            throw new Exception("Failed to log product update: " . mysqli_error($conn));
        }

        // Handle image upload after successful update
        if (!empty($_FILES['update_image']['name'])) {
            if (!move_uploaded_file($image_tmp, $image_folder)) {
                throw new Exception("Failed to upload new image");
            }
            // Remove old image if different from new one
            if ($old_image != $new_image && file_exists('uploads/' . $old_image)) {
                unlink('uploads/' . $old_image);
            }
        }

        mysqli_commit($conn);
        $message[] = 'Product updated successfully';
        header('location: manage_products.php');
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $message[] = 'Failed to update product: ' . $e->getMessage();
    }
}

// Fetch Products
$products = mysqli_query($conn, "SELECT * FROM furniture");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Products</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="manage_products.css">

</head>

<body>
    <?php include('admin_sidebar.php'); ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="bi bi-box-seam"></i> Manage Products</h2>

        </div>

        <div class="container mt-2">
            <hr>

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

            <!-- Add Product Form -->
            <div class="card shadow-sm rounded-4 mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-3"><i class="bi bi-plus-circle"></i> Add New Product</h4>
                    <form action="" method="POST" enctype="multipart/form-data" class="mb-3">
                        <input type="text" name="name" placeholder="Product Name" class="form-control mb-2" required>
                        <input type="text" name="price" placeholder="Price" class="form-control mb-2" required>
                        <textarea name="description" placeholder="Description" class="form-control mb-2" required></textarea>
                        <!-- Add furniture type selection -->
                        <select name="furniture_type" class="form-control mb-2" required>
                            <option value="">Select Furniture Type</option>
                            <option value="chair">Chair</option>
                            <option value="table">Table</option>
                            <option value="sofa">Sofa</option>
                            <option value="bed">Bed</option>
                            <option value="cabinet">Cabinet</option>
                            <option value="desk">Desk</option>
                        </select>
                        <input type="number" name="stock" placeholder="Stock" class="form-control mb-2" required>
                        <input type="file" name="image" class="form-control mb-3" required>
                        <button type="submit" name="add_product" class="btn btn-success"><i class="bi bi-save"></i> Add Product</button>
                    </form>
                </div>
            </div>

            <!-- Edit Product Modal -->
            <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="update_id" id="update_id">
                                <input type="hidden" name="old_image" id="old_image">
                                <div class="mb-3">
                                    <label for="update_name" class="form-label">Product Name</label>
                                    <input type="text" name="update_name" id="update_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="update_price" class="form-label">Price</label>
                                    <input type="text" name="update_price" id="update_price" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="update_description" class="form-label">Description</label>
                                    <textarea name="update_description" id="update_description" class="form-control" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="update_furniture_type" class="form-label">Furniture Type</label>
                                    <select name="update_furniture_type" id="update_furniture_type" class="form-control" required>
                                        <option value="chair">Chair</option>
                                        <option value="table">Table</option>
                                        <option value="sofa">Sofa</option>
                                        <option value="bed">Bed</option>
                                        <option value="cabinet">Cabinet</option>
                                        <option value="desk">Desk</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="update_stock" class="form-label">Stock</label>
                                    <input type="number" name="update_stock" id="update_stock" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="update_image" class="form-label">Image (leave empty to keep current image)</label>
                                    <input type="file" name="update_image" id="update_image" class="form-control">
                                    <div class="mt-2">
                                        <small>Current Image:</small>
                                        <img id="current_image" src="" alt="Current Product Image" style="max-width: 100px; max-height: 100px;">
                                    </div>
                                </div>
                                <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Table -->
            <div class="card shadow-sm rounded-4">
                <div class="card-body">
                    <h4 class="card-title mb-3"><i class="bi bi-table"></i> Manage Products</h4>
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <!-- Update the table headers -->
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Description</th>
                                <th>Type</th> <!-- Add this column -->
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($products)) { ?>
                                <tr>
                                    <td><?php echo $row['furniture_id']; ?></td>
                                    <td><img src="uploads/<?php echo $row['image']; ?>" alt="Product Image"></td>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['price']; ?></td>
                                    <td style="max-width: 250px;"><?php echo $row['description']; ?></td>
                                    <td><?php echo ucfirst($row['furniture_type']); ?></td> <!-- Add this column -->
                                    <td><?php echo $row['stock']; ?> left</td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-warning btn-sm edit-btn"
                                                data-furniture_id="<?php echo $row['furniture_id']; ?>"
                                                data-name="<?php echo $row['name']; ?>"
                                                data-price="<?php echo $row['price']; ?>"
                                                data-description="<?php echo $row['description']; ?>"
                                                data-furniture-type="<?php echo $row['furniture_type']; ?>"
                                                data-stock="<?php echo $row['stock']; ?>"
                                                data-image="<?php echo $row['image']; ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <a href="manage_products.php?delete=<?php echo $row['furniture_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">
                                                <i class="bi bi-trash3"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-btn');
            const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));

            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-furniture_id');
                    const name = this.getAttribute('data-name');
                    const price = this.getAttribute('data-price');
                    const description = this.getAttribute('data-description');
                    const furnitureType = this.getAttribute('data-furniture-type');
                    const stock = this.getAttribute('data-stock');
                    const image = this.getAttribute('data-image');

                    document.getElementById('update_id').value = id;
                    document.getElementById('update_name').value = name;
                    document.getElementById('update_price').value = price;
                    document.getElementById('update_description').value = description;
                    document.getElementById('update_furniture_type').value = furnitureType;
                    document.getElementById('update_stock').value = stock;
                    document.getElementById('old_image').value = image;
                    document.getElementById('current_image').src = 'uploads/' + image;

                    editModal.show();
                });
            });
        });
    </script>
</body>

</html>