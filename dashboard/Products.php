<?php
// Database connection code here
$host = "localhost";
$user = "root";
$pass = ""; // haddii password ku leeyahay geli halkan
$dbname = "product_inventory_management";

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");

// Initialize variables
$message = '';
$message_type = '';
$result = null; // Initialize $result variable

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add New Product
    if (isset($_POST['add_product'])) {
        $productName = $conn->real_escape_string($_POST['productName']);
        $categoryID = intval($_POST['categoryID']);
        $sku = $conn->real_escape_string($_POST['sku']);
        $costPrice = floatval($_POST['costPrice']);
        $sellingPrice = floatval($_POST['sellingPrice']);
        $quantityInStock = intval($_POST['quantityInStock']);
        $reorderLevel = intval($_POST['reorderLevel']);
        $supplierID = intval($_POST['supplierID']);
        
        $sql = "INSERT INTO Products (ProductName, CategoryID, SKU, CostPrice, SellingPrice, QuantityInStock, ReorderLevel, SupplierID) 
                VALUES ('$productName', '$categoryID', '$sku', '$costPrice', '$sellingPrice', '$quantityInStock', '$reorderLevel', '$supplierID')";
        
        if ($conn->query($sql)) {
            $message = "Product added successfully!";
            $message_type = "success";
        } else {
            $message = "Error adding product: " . $conn->error;
            $message_type = "error";
        }
    }
    
    // Update Product
    if (isset($_POST['update_product'])) {
        $productID = intval($_POST['productID']);
        $productName = $conn->real_escape_string($_POST['productName']);
        $categoryID = intval($_POST['categoryID']);
        $sku = $conn->real_escape_string($_POST['sku']);
        $costPrice = floatval($_POST['costPrice']);
        $sellingPrice = floatval($_POST['sellingPrice']);
        $quantityInStock = intval($_POST['quantityInStock']);
        $reorderLevel = intval($_POST['reorderLevel']);
        $supplierID = intval($_POST['supplierID']);
        
        $sql = "UPDATE Products SET 
                ProductName = '$productName', 
                CategoryID = '$categoryID', 
                SKU = '$sku', 
                CostPrice = '$costPrice', 
                SellingPrice = '$sellingPrice', 
                QuantityInStock = '$quantityInStock', 
                ReorderLevel = '$reorderLevel', 
                SupplierID = '$supplierID' 
                WHERE ProductID = $productID";
        
        if ($conn->query($sql)) {
            $message = "Product updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating product: " . $conn->error;
            $message_type = "error";
        }
    }
}

// Delete Product
if (isset($_GET['delete'])) {
    $productID = intval($_GET['delete']);
    
    $sql = "DELETE FROM Products WHERE ProductID = $productID";
    
    if ($conn->query($sql)) {
        $message = "Product deleted successfully!";
        $message_type = "success";
        // Redirect to same page without delete parameter
        header("Location: " . basename($_SERVER['PHP_SELF']));
        exit();
    } else {
        $message = "Error deleting product: " . $conn->error;
        $message_type = "error";
    }
}

// Fetch all products with category and supplier names
$sql = "SELECT p.*, c.CategoryName, s.SupplierName 
        FROM Products p 
        LEFT JOIN Categories c ON p.CategoryID = c.CategoryID 
        LEFT JOIN Suppliers s ON p.SupplierID = s.SupplierID 
        ORDER BY p.ProductName ASC";
$result = $conn->query($sql);

// Fetch all categories for dropdown
$categorySql = "SELECT * FROM Categories ORDER BY CategoryName ASC";
$categoryResult = $conn->query($categorySql);
$categories = [];
if ($categoryResult && $categoryResult->num_rows > 0) {
    while ($category = $categoryResult->fetch_assoc()) {
        $categories[] = $category;
    }
}

// Fetch all suppliers for dropdown
$supplierSql = "SELECT * FROM Suppliers ORDER BY SupplierName ASC";
$supplierResult = $conn->query($supplierSql);
$suppliers = [];
if ($supplierResult && $supplierResult->num_rows > 0) {
    while ($supplier = $supplierResult->fetch_assoc()) {
        $suppliers[] = $supplier;
    }
}

// Check for query error
if ($result === false) {
    $message = "Database error: " . $conn->error;
    $message_type = "error";
    $result = null; // Ensure $result is null if there's an error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background: #f5f7fa;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #2c3e50, #1a252f);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 20px;
            font-size: 22px;
            color: #ecf0f1;
            border-bottom: 2px solid #3498db;
            padding-bottom: 15px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .sidebar a i {
            margin-right: 12px;
            font-size: 18px;
            width: 25px;
            text-align: center;
        }

        .sidebar a:hover {
            background: rgba(52, 152, 219, 0.1);
            color: white;
            border-left: 4px solid #3498db;
        }

        .sidebar a.active {
            background: rgba(52, 152, 219, 0.2);
            color: white;
            border-left: 4px solid #3498db;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .header h1 {
            color: #2c3e50;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-add {
            background: linear-gradient(45deg, #1abc9c, #16a085);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 15px rgba(26, 188, 156, 0.3);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 188, 156, 0.4);
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 500;
            animation: slideIn 0.5s ease;
        }

        .success {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
            border: 1px solid #2ecc71;
        }

        .error {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }

        .info {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
            border: 1px solid #3498db;
        }

        .warning {
            background: rgba(241, 196, 15, 0.2);
            color: #f39c12;
            border: 1px solid #f39c12;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(45deg, #1abc9c, #16a085);
        }

        th {
            padding: 18px 15px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        tbody tr {
            border-bottom: 1px solid #eee;
            transition: all 0.3s;
        }

        tbody tr:hover {
            background: rgba(26, 188, 156, 0.05);
        }

        td {
            padding: 15px;
            color: #333;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-edit, .btn-delete {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .btn-edit {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }

        .btn-edit:hover {
            background: #3498db;
            color: white;
        }

        .btn-delete {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .btn-delete:hover {
            background: #e74c3c;
            color: white;
        }

        .stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 600;
        }

        .stock-low {
            background: #fdeaea;
            color: #e74c3c;
        }

        .stock-ok {
            background: #eafaf1;
            color: #27ae60;
        }

        .stock-warning {
            background: #fef9e7;
            color: #f39c12;
        }

        .price-badge {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .cost-price {
            color: #e74c3c;
            background: #fdeaea;
        }

        .selling-price {
            color: #27ae60;
            background: #eafaf1;
        }

        .profit-badge {
            color: #3498db;
            background: #e8f4fc;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
        }

        .category-badge {
            background: #f5e8ff;
            color: #9b59b6;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .supplier-badge {
            background: #fff3e0;
            color: #e67e22;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .sku-badge {
            background: #e8f4fc;
            color: #3498db;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            font-family: monospace;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 700px;
            margin: 50px auto;
            border-radius: 15px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s ease;
        }

        .modal-header {
            padding: 20px;
            background: linear-gradient(45deg, #1abc9c, #16a085);
            color: white;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .close-btn:hover {
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 25px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #1abc9c;
        }

        .price-inputs {
            display: flex;
            gap: 15px;
        }

        .price-input {
            flex: 1;
        }

        .price-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .profit-display {
            font-size: 12px;
            color: #3498db;
            font-weight: 600;
        }

        .modal-footer {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 0 0 15px 15px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-submit, .btn-cancel {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-submit {
            background: linear-gradient(45deg, #1abc9c, #16a085);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 188, 156, 0.3);
        }

        .btn-cancel {
            background: #95a5a6;
            color: white;
        }

        .btn-cancel:hover {
            background: #7f8c8d;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }

        .no-data-alert {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            text-align: center;
        }

        .no-data-alert a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
        }

        .no-data-alert a:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                width: 220px;
            }
            .main-content {
                margin-left: 220px;
            }
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                margin-bottom: 20px;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            th, td {
                padding: 10px 5px;
                font-size: 14px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
        }

        /* Toggle button for mobile */
        .menu-toggle {
            display: none;
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 15px;
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }
            
            .sidebar {
                display: none;
            }
            
            .sidebar.active {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
        <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
        <a href="menu.php"><i class="fas fa-bars"></i> Menu Management</a>
        <a href="submenu.php"><i class="fas fa-indent"></i> Submenu Management</a>
        <a href="Categories.php"><i class="fas fa-tags"></i> Categories</a>
        <a href="Suppliers.php"><i class="fas fa-truck"></i> Suppliers</a>
        <a href="Products.php" class="active"><i class="fas fa-box"></i> Products</a>
        <a href="../users/view_users.php"><i class="fas fa-users"></i> Users</a>
        <a href="../stock/stock_in.php"><i class="fas fa-arrow-up"></i> Stock In</a>
        <a href="../stock/stock_out.php"><i class="fas fa-arrow-down"></i> Stock Out</a>
        <a href="#"><i class="fas fa-chart-bar"></i> Analytics</a>
        <a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Mobile Menu Toggle Button -->
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i> Menu
        </button>

        <div class="header">
            <h1><i class="fas fa-box"></i> Products Management</h1>
            <button class="btn-add" onclick="openModal()">
                <i class="fas fa-plus"></i> Add New Product
            </button>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($categories)): ?>
            <div class="no-data-alert">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>No Categories Found!</strong> You need to create categories first before adding products. 
                <a href="Categories.php">Go to Categories Management</a>
            </div>
        <?php endif; ?>

        <?php if (empty($suppliers)): ?>
            <div class="no-data-alert">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>No Suppliers Found!</strong> You need to create suppliers first before adding products. 
                <a href="Suppliers.php">Go to Suppliers Management</a>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Prices</th>
                            <th>Stock</th>
                            <th>Supplier</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            // Calculate profit
                            $profit = $row['SellingPrice'] - $row['CostPrice'];
                            $profitPercentage = $row['CostPrice'] > 0 ? ($profit / $row['CostPrice']) * 100 : 0;
                            
                            // Determine stock status
                            $stockStatus = '';
                            $stockClass = '';
                            if ($row['QuantityInStock'] <= 0) {
                                $stockStatus = 'Out of Stock';
                                $stockClass = 'stock-low';
                            } elseif ($row['QuantityInStock'] <= $row['ReorderLevel']) {
                                $stockStatus = 'Low Stock';
                                $stockClass = 'stock-warning';
                            } else {
                                $stockStatus = 'In Stock';
                                $stockClass = 'stock-ok';
                            }
                            ?>
                            <tr>
                                <td><strong>#<?php echo $row['ProductID']; ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($row['ProductName']); ?></strong></td>
                                <td>
                                    <?php if (!empty($row['SKU'])): ?>
                                        <span class="sku-badge"><?php echo htmlspecialchars($row['SKU']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #95a5a6; font-style: italic;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['CategoryName'])): ?>
                                        <span class="category-badge"><?php echo htmlspecialchars($row['CategoryName']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #95a5a6; font-style: italic;">No category</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <div>
                                            <span class="price-badge cost-price">
                                                Cost: $<?php echo number_format($row['CostPrice'], 2); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="price-badge selling-price">
                                                Sell: $<?php echo number_format($row['SellingPrice'], 2); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="profit-badge">
                                                Profit: $<?php echo number_format($profit, 2); ?> 
                                                (<?php echo number_format($profitPercentage, 1); ?>%)
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="stock-badge <?php echo $stockClass; ?>">
                                        <i class="fas fa-box"></i>
                                        <?php echo $row['QuantityInStock']; ?> units
                                        <small style="margin-left: 5px;">(<?php echo $stockStatus; ?>)</small>
                                    </div>
                                    <?php if ($row['ReorderLevel'] > 0): ?>
                                        <div style="font-size: 11px; color: #7f8c8d; margin-top: 3px;">
                                            Reorder: <?php echo $row['ReorderLevel']; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['SupplierName'])): ?>
                                        <span class="supplier-badge"><?php echo htmlspecialchars($row['SupplierName']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #95a5a6; font-style: italic;">No supplier</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-edit" onclick="editProduct(
                                            <?php echo $row['ProductID']; ?>, 
                                            '<?php echo addslashes($row['ProductName']); ?>', 
                                            <?php echo $row['CategoryID'] ?? 'null'; ?>, 
                                            '<?php echo addslashes($row['SKU']); ?>', 
                                            <?php echo $row['CostPrice']; ?>, 
                                            <?php echo $row['SellingPrice']; ?>, 
                                            <?php echo $row['QuantityInStock']; ?>, 
                                            <?php echo $row['ReorderLevel']; ?>, 
                                            <?php echo $row['SupplierID'] ?? 'null'; ?>
                                        )">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn-delete" onclick="deleteProduct(<?php echo $row['ProductID']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box"></i>
                    <h3>No Products Found</h3>
                    <p>Click the "Add New Product" button to create your first product.</p>
                    <?php if (!$result): ?>
                        <div class="message info" style="margin-top: 20px;">
                            <i class="fas fa-info-circle"></i> Unable to load products data. Please check your database connection.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-box"></i> <span id="modalTitle">Add New Product</span></h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="productForm" method="POST" action="">
                <input type="hidden" id="productID" name="productID">
                
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="productName"><i class="fas fa-cube"></i> Product Name *</label>
                            <input type="text" id="productName" name="productName" required placeholder="Enter product name">
                        </div>
                        
                        <div class="form-group">
                            <label for="sku"><i class="fas fa-barcode"></i> SKU (Stock Keeping Unit)</label>
                            <input type="text" id="sku" name="sku" placeholder="Enter SKU">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="categoryID"><i class="fas fa-tags"></i> Category *</label>
                            <select id="categoryID" name="categoryID" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['CategoryID']; ?>">
                                        <?php echo htmlspecialchars($category['CategoryName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="supplierID"><i class="fas fa-truck"></i> Supplier</label>
                            <select id="supplierID" name="supplierID">
                                <option value="">Select Supplier (Optional)</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['SupplierID']; ?>">
                                        <?php echo htmlspecialchars($supplier['SupplierName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="costPrice"><i class="fas fa-money-bill-wave"></i> Cost Price ($) *</label>
                            <input type="number" id="costPrice" name="costPrice" required min="0" step="0.01" placeholder="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label for="sellingPrice"><i class="fas fa-tag"></i> Selling Price ($) *</label>
                            <input type="number" id="sellingPrice" name="sellingPrice" required min="0" step="0.01" placeholder="0.00">
                            <div class="profit-display" id="profitDisplay"></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantityInStock"><i class="fas fa-boxes"></i> Quantity in Stock</label>
                            <input type="number" id="quantityInStock" name="quantityInStock" min="0" value="0" placeholder="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="reorderLevel"><i class="fas fa-exclamation-triangle"></i> Reorder Level</label>
                            <input type="number" id="reorderLevel" name="reorderLevel" min="0" value="10" placeholder="10">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit" id="modalSubmitBtn" name="add_product">Add Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openModal() {
            document.getElementById('productModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Add New Product';
            document.getElementById('productForm').reset();
            document.getElementById('modalSubmitBtn').name = 'add_product';
            document.getElementById('modalSubmitBtn').textContent = 'Add Product';
            document.getElementById('productID').value = '';
            document.getElementById('quantityInStock').value = '0';
            document.getElementById('reorderLevel').value = '10';
            updateProfitDisplay();
            
            <?php if (!empty($categories)): ?>
                document.getElementById('categoryID').selectedIndex = 1;
            <?php endif; ?>
        }

        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        // Edit Product Function
        function editProduct(id, name, categoryID, sku, costPrice, sellingPrice, quantity, reorderLevel, supplierID) {
            document.getElementById('productModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('productID').value = id;
            document.getElementById('productName').value = name;
            document.getElementById('categoryID').value = categoryID || '';
            document.getElementById('sku').value = sku || '';
            document.getElementById('costPrice').value = costPrice;
            document.getElementById('sellingPrice').value = sellingPrice;
            document.getElementById('quantityInStock').value = quantity;
            document.getElementById('reorderLevel').value = reorderLevel;
            document.getElementById('supplierID').value = supplierID || '';
            document.getElementById('modalSubmitBtn').name = 'update_product';
            document.getElementById('modalSubmitBtn').textContent = 'Update Product';
            updateProfitDisplay();
        }

        // Delete Product Function
        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                // Get current page URL without query parameters
                var currentUrl = window.location.origin + window.location.pathname;
                window.location.href = currentUrl + '?delete=' + id;
            }
        }

        // Calculate and display profit
        function updateProfitDisplay() {
            const costPrice = parseFloat(document.getElementById('costPrice').value) || 0;
            const sellingPrice = parseFloat(document.getElementById('sellingPrice').value) || 0;
            const profit = sellingPrice - costPrice;
            const profitPercentage = costPrice > 0 ? (profit / costPrice) * 100 : 0;
            
            const profitDisplay = document.getElementById('profitDisplay');
            if (profitDisplay) {
                profitDisplay.innerHTML = `Profit: $${profit.toFixed(2)} (${profitPercentage.toFixed(1)}%)`;
                
                // Color code based on profit
                if (profit > 0) {
                    profitDisplay.style.color = '#27ae60';
                } else if (profit < 0) {
                    profitDisplay.style.color = '#e74c3c';
                } else {
                    profitDisplay.style.color = '#7f8c8d';
                }
            }
        }

        // Add event listeners for price inputs
        document.addEventListener('DOMContentLoaded', function() {
            const costPriceInput = document.getElementById('costPrice');
            const sellingPriceInput = document.getElementById('sellingPrice');
            
            if (costPriceInput) {
                costPriceInput.addEventListener('input', updateProfitDisplay);
            }
            if (sellingPriceInput) {
                sellingPriceInput.addEventListener('input', updateProfitDisplay);
            }
        });

        // Toggle Sidebar for Mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (window.innerWidth <= 768 && 
                sidebar.classList.contains('active') && 
                !sidebar.contains(event.target) && 
                !menuToggle.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        });

        // Disable add button if no categories or suppliers
        <?php if (empty($categories) || empty($suppliers)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            if (document.querySelector('.btn-add')) {
                document.querySelector('.btn-add').disabled = true;
                document.querySelector('.btn-add').style.opacity = '0.6';
                document.querySelector('.btn-add').style.cursor = 'not-allowed';
                document.querySelector('.btn-add').title = 'Create categories and suppliers first';
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
<?php 
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>