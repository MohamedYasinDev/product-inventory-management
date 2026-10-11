<?php
session_start();


// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "product_inventory_management";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");

// Initialize variables
$message = '';
$message_type = '';
$result = null;

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add New Stock Out
    if (isset($_POST['add_stock_out'])) {
        $productID = intval($_POST['productID']);
        $quantity = intval($_POST['quantity']);
        $sellingPrice = floatval($_POST['sellingPrice']);
        $soldTo = $conn->real_escape_string($_POST['soldTo']);
        $dateOut = $conn->real_escape_string($_POST['dateOut']);
        
        // Check if product has enough stock
        $checkStockSql = "SELECT QuantityInStock, ProductName FROM Products WHERE ProductID = $productID";
        $checkResult = $conn->query($checkStockSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            $productData = $checkResult->fetch_assoc();
            $currentStock = $productData['QuantityInStock'];
            $productName = $productData['ProductName'];
            
            if ($currentStock >= $quantity) {
                // Insert into Stock_Out table
                $sql = "INSERT INTO Stock_Out (ProductID, Quantity, SellingPrice, SoldTo, DateOut) 
                        VALUES ($productID, $quantity, $sellingPrice, '$soldTo', '$dateOut')";
                
                if ($conn->query($sql)) {
                    // Update product quantity
                    $updateSql = "UPDATE Products SET QuantityInStock = QuantityInStock - $quantity 
                                 WHERE ProductID = $productID";
                    $conn->query($updateSql);
                    
                    // Calculate profit
                    $costSql = "SELECT CostPrice FROM Products WHERE ProductID = $productID";
                    $costResult = $conn->query($costSql);
                    $costData = $costResult->fetch_assoc();
                    $costPrice = $costData['CostPrice'];
                    $profit = ($sellingPrice - $costPrice) * $quantity;
                    
                    $message = "Stock sold successfully! Product: $productName, Quantity: $quantity, Profit: $" . number_format($profit, 2);
                    $message_type = "success";
                } else {
                    $message = "Error selling stock: " . $conn->error;
                    $message_type = "error";
                }
            } else {
                $message = "Insufficient stock! Available: $currentStock, Requested: $quantity";
                $message_type = "error";
            }
        }
    }
    
    // Update Stock Out
    if (isset($_POST['update_stock_out'])) {
        $stockOutID = intval($_POST['stockOutID']);
        $productID = intval($_POST['productID']);
        $quantity = intval($_POST['quantity']);
        $sellingPrice = floatval($_POST['sellingPrice']);
        $soldTo = $conn->real_escape_string($_POST['soldTo']);
        $dateOut = $conn->real_escape_string($_POST['dateOut']);
        
        // Get old stock out details
        $oldStockSql = "SELECT ProductID, Quantity FROM Stock_Out WHERE StockOutID = $stockOutID";
        $oldResult = $conn->query($oldStockSql);
        
        if ($oldResult && $oldResult->num_rows > 0) {
            $oldData = $oldResult->fetch_assoc();
            $oldQuantity = $oldData['Quantity'];
            $oldProductID = $oldData['ProductID'];
            
            // Check if new quantity is available
            $checkSql = "SELECT QuantityInStock FROM Products WHERE ProductID = $productID";
            $checkResult = $conn->query($checkSql);
            $checkData = $checkResult->fetch_assoc();
            $currentStock = $checkData['QuantityInStock'];
            
            if ($oldProductID == $productID) {
                // Same product, check if adjustment is possible
                $quantityDiff = $quantity - $oldQuantity;
                $availableStock = $currentStock + $oldQuantity; // Add back old quantity first
                
                if ($quantity <= $availableStock) {
                    // Adjust quantity
                    $adjustSql = "UPDATE Products SET QuantityInStock = $availableStock - $quantity 
                                 WHERE ProductID = $productID";
                    $conn->query($adjustSql);
                } else {
                    $message = "Insufficient stock! Available after adjustment: $availableStock, Requested: $quantity";
                    $message_type = "error";
                    goto end_post_processing;
                }
            } else {
                // Different product, check both
                $oldProductStockSql = "SELECT QuantityInStock FROM Products WHERE ProductID = $oldProductID";
                $oldProductResult = $conn->query($oldProductStockSql);
                $oldProductData = $oldProductResult->fetch_assoc();
                $oldProductStock = $oldProductData['QuantityInStock'];
                
                if ($quantity <= $currentStock) {
                    // Return quantity to old product
                    $returnSql = "UPDATE Products SET QuantityInStock = QuantityInStock + $oldQuantity 
                                 WHERE ProductID = $oldProductID";
                    $conn->query($returnSql);
                    
                    // Deduct from new product
                    $deductSql = "UPDATE Products SET QuantityInStock = QuantityInStock - $quantity 
                                 WHERE ProductID = $productID";
                    $conn->query($deductSql);
                } else {
                    $message = "Insufficient stock in new product! Available: $currentStock, Requested: $quantity";
                    $message_type = "error";
                    goto end_post_processing;
                }
            }
            
            // Update stock out record
            $sql = "UPDATE Stock_Out SET 
                    ProductID = $productID, 
                    Quantity = $quantity, 
                    SellingPrice = $sellingPrice, 
                    SoldTo = '$soldTo', 
                    DateOut = '$dateOut' 
                    WHERE StockOutID = $stockOutID";
            
            if ($conn->query($sql)) {
                $message = "Stock sale updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating stock sale: " . $conn->error;
                $message_type = "error";
            }
        }
    }
}

end_post_processing:

// Delete Stock Out
if (isset($_GET['delete'])) {
    $stockOutID = intval($_GET['delete']);
    
    // Get stock details before deletion
    $stockSql = "SELECT ProductID, Quantity FROM Stock_Out WHERE StockOutID = $stockOutID";
    $stockResult = $conn->query($stockSql);
    
    if ($stockResult && $stockResult->num_rows > 0) {
        $stockData = $stockResult->fetch_assoc();
        $productID = $stockData['ProductID'];
        $quantity = $stockData['Quantity'];
        
        // Return quantity to product
        $updateSql = "UPDATE Products SET QuantityInStock = QuantityInStock + $quantity 
                     WHERE ProductID = $productID";
        $conn->query($updateSql);
        
        // Delete stock record
        $sql = "DELETE FROM Stock_Out WHERE StockOutID = $stockOutID";
        
        if ($conn->query($sql)) {
            $message = "Sale record deleted successfully! Product quantity restored.";
            $message_type = "success";
            header("Location: " . basename($_SERVER['PHP_SELF']));
            exit();
        } else {
            $message = "Error deleting sale record: " . $conn->error;
            $message_type = "error";
        }
    }
}

// Fetch all stock out records with product details and profit calculation
$sql = "SELECT so.*, p.ProductName, p.SKU, p.CategoryID, p.CostPrice,
               c.CategoryName,
               (so.Quantity * so.SellingPrice) as TotalAmount,
               (so.SellingPrice - p.CostPrice) as UnitProfit,
               ((so.SellingPrice - p.CostPrice) * so.Quantity) as TotalProfit
        FROM Stock_Out so
        LEFT JOIN Products p ON so.ProductID = p.ProductID
        LEFT JOIN Categories c ON p.CategoryID = c.CategoryID
        ORDER BY so.DateOut DESC, so.StockOutID DESC";
$result = $conn->query($sql);

// Fetch all products for dropdown
$productSql = "SELECT * FROM Products WHERE QuantityInStock > 0 ORDER BY ProductName ASC";
$productResult = $conn->query($productSql);
$products = [];
if ($productResult && $productResult->num_rows > 0) {
    while ($product = $productResult->fetch_assoc()) {
        $products[] = $product;
    }
}

// Get today's date for default value
$todayDate = date('Y-m-d\TH:i');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Out Management</title>
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
            border-bottom: 2px solid #e74c3c;
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
            background: rgba(231, 76, 60, 0.1);
            color: white;
            border-left: 4px solid #e74c3c;
        }

        .sidebar a.active {
            background: rgba(231, 76, 60, 0.2);
            color: white;
            border-left: 4px solid #e74c3c;
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
            background: linear-gradient(45deg, #e74c3c, #c0392b);
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
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
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
            background: linear-gradient(45deg, #e74c3c, #c0392b);
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
            background: rgba(231, 76, 60, 0.05);
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

        /* Badge Styles */
        .product-badge {
            background: #e8f4fc;
            color: #3498db;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 500;
        }

        .customer-badge {
            background: #fff3e0;
            color: #e67e22;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 14px;
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

        .price-badge {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .unit-price {
            color: #e74c3c;
            background: #fdeaea;
        }

        .total-amount {
            color: #27ae60;
            background: #eafaf1;
        }

        .profit-badge {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .unit-profit {
            color: #3498db;
            background: #e8f4fc;
        }

        .total-profit {
            color: #9b59b6;
            background: #f5e8ff;
        }

        .quantity-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 600;
            background: #fef9e7;
            color: #f39c12;
        }

        .date-badge {
            background: #f5f5f5;
            color: #333;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
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
            background: linear-gradient(45deg, #e74c3c, #c0392b);
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

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #e74c3c;
        }

        .price-inputs {
            display: flex;
            gap: 15px;
        }

        .profit-display {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .profit-positive {
            color: #27ae60;
        }

        .profit-negative {
            color: #e74c3c;
        }

        .profit-neutral {
            color: #7f8c8d;
        }

        .current-stock {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
            display: flex;
            justify-content: space-between;
        }

        .stock-warning {
            color: #e74c3c;
            font-weight: 600;
        }

        .stock-ok {
            color: #27ae60;
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
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
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
            background: #e74c3c;
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
        <a href="Products.php"><i class="fas fa-box"></i> Products</a>
        <a href="../users/view_users.php"><i class="fas fa-users"></i> Users</a>
        <a href="../stock/stock_in.php"><i class="fas fa-arrow-up"></i> Stock In</a>
        <a href="../stock/stock_out.php" class="active"><i class="fas fa-arrow-down"></i> Stock Out</a>
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
            <h1><i class="fas fa-arrow-down"></i> Stock Out / Sales Management</h1>
            <button class="btn-add" onclick="openModal()">
                <i class="fas fa-cart-plus"></i> New Sale
            </button>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($products)): ?>
            <div class="no-data-alert">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>No Products in Stock!</strong> You need to add stock to products before selling. 
                <a href="Products.php">Go to Products Management</a> or 
                <a href="../stock/stock_in.php">Add Stock In</a>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price & Amount</th>
                            <th>Profit</th>
                            <th>Sold To</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            // Determine profit color
                            $profitColorClass = 'profit-positive';
                            if ($row['TotalProfit'] < 0) {
                                $profitColorClass = 'profit-negative';
                            } elseif ($row['TotalProfit'] == 0) {
                                $profitColorClass = 'profit-neutral';
                            }
                            ?>
                            <tr>
                                <td><strong>#<?php echo $row['StockOutID']; ?></strong></td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <span class="product-badge">
                                            <?php echo htmlspecialchars($row['ProductName']); ?>
                                        </span>
                                        <?php if (!empty($row['SKU'])): ?>
                                            <small style="color: #7f8c8d;">SKU: <?php echo htmlspecialchars($row['SKU']); ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($row['CategoryName'])): ?>
                                            <span class="category-badge"><?php echo htmlspecialchars($row['CategoryName']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="quantity-badge">
                                        <i class="fas fa-box"></i>
                                        <?php echo $row['Quantity']; ?> units
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <span class="price-badge unit-price">
                                            Unit: $<?php echo number_format($row['SellingPrice'], 2); ?>
                                        </span>
                                        <span class="price-badge total-amount">
                                            Total: $<?php echo number_format($row['TotalAmount'], 2); ?>
                                        </span>
                                        <small style="font-size: 11px; color: #7f8c8d;">
                                            Cost: $<?php echo number_format($row['CostPrice'], 2); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <span class="profit-badge unit-profit">
                                            Unit: $<?php echo number_format($row['UnitProfit'], 2); ?>
                                        </span>
                                        <span class="profit-badge total-profit <?php echo $profitColorClass; ?>">
                                            Total: $<?php echo number_format($row['TotalProfit'], 2); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($row['SoldTo'])): ?>
                                        <div style="display: flex; flex-direction: column; gap: 3px;">
                                            <span class="customer-badge">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($row['SoldTo']); ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #95a5a6; font-style: italic;">Walk-in customer</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="date-badge">
                                        <i class="far fa-calendar"></i>
                                        <?php echo date('Y-m-d', strtotime($row['DateOut'])); ?>
                                    </span>
                                    <div style="font-size: 11px; color: #7f8c8d; margin-top: 3px;">
                                        <?php echo date('H:i:s', strtotime($row['DateOut'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-edit" onclick="editStockOut(
                                            <?php echo $row['StockOutID']; ?>, 
                                            <?php echo $row['ProductID']; ?>, 
                                            <?php echo $row['Quantity']; ?>, 
                                            <?php echo $row['SellingPrice']; ?>, 
                                            '<?php echo addslashes($row['SoldTo']); ?>', 
                                            '<?php echo date('Y-m-d\TH:i', strtotime($row['DateOut'])); ?>'
                                        )">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn-delete" onclick="deleteStockOut(<?php echo $row['StockOutID']; ?>)">
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
                    <i class="fas fa-shopping-cart"></i>
                    <h3>No Sales Records Found</h3>
                    <p>Click the "New Sale" button to record your first sale.</p>
                    <?php if (!$result): ?>
                        <div class="message info" style="margin-top: 20px;">
                            <i class="fas fa-info-circle"></i> Unable to load sales data. Please check your database connection.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="stockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-cart-plus"></i> <span id="modalTitle">New Sale</span></h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="stockForm" method="POST" action="">
                <input type="hidden" id="stockOutID" name="stockOutID">
                
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="productID"><i class="fas fa-cube"></i> Product *</label>
                            <select id="productID" name="productID" required onchange="updateProductInfo()">
                                <option value="">Select Product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['ProductID']; ?>"
                                            data-cost="<?php echo $product['CostPrice']; ?>"
                                            data-stock="<?php echo $product['QuantityInStock']; ?>"
                                            data-selling="<?php echo $product['SellingPrice']; ?>">
                                        <?php echo htmlspecialchars($product['ProductName']); ?> 
                                        (SKU: <?php echo htmlspecialchars($product['SKU']); ?>)
                                        - Stock: <?php echo $product['QuantityInStock']; ?>
                                        - Price: $<?php echo number_format($product['SellingPrice'], 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="productInfo" class="current-stock">
                                <!-- Product info will be displayed here -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="soldTo"><i class="fas fa-user"></i> Customer Name (Optional)</label>
                            <input type="text" id="soldTo" name="soldTo" placeholder="Enter customer name">
                        </div>
                        
                        <div class="form-group">
                            <label for="dateOut"><i class="far fa-calendar"></i> Sale Date & Time</label>
                            <input type="datetime-local" id="dateOut" name="dateOut" 
                                   value="<?php echo $todayDate; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity"><i class="fas fa-boxes"></i> Quantity *</label>
                            <input type="number" id="quantity" name="quantity" required min="1" value="1" 
                                   oninput="updateProfit()">
                        </div>
                        
                        <div class="form-group">
                            <label for="sellingPrice"><i class="fas fa-tag"></i> Selling Price ($) *</label>
                            <input type="number" id="sellingPrice" name="sellingPrice" required min="0" step="0.01" 
                                   value="0.00" oninput="updateProfit()">
                        </div>
                    </div>
                    
                    <div class="profit-display" id="profitDisplay">
                        <div>
                            <span>Total Amount:</span>
                            <span id="totalAmount">$0.00</span>
                        </div>
                        <div id="profitInfo">
                            <span>Profit:</span>
                            <span id="profitValue" class="profit-neutral">$0.00 (0%)</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit" id="modalSubmitBtn" name="add_stock_out">Complete Sale</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openModal() {
            document.getElementById('stockModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'New Sale';
            document.getElementById('stockForm').reset();
            document.getElementById('modalSubmitBtn').name = 'add_stock_out';
            document.getElementById('modalSubmitBtn').textContent = 'Complete Sale';
            document.getElementById('stockOutID').value = '';
            document.getElementById('quantity').value = '1';
            document.getElementById('sellingPrice').value = '0.00';
            document.getElementById('soldTo').value = '';
            document.getElementById('dateOut').value = '<?php echo $todayDate; ?>';
            updateProductInfo();
            updateProfit();
        }

        function closeModal() {
            document.getElementById('stockModal').style.display = 'none';
        }

        // Edit Stock Out Function
        function editStockOut(id, productID, quantity, sellingPrice, soldTo, dateOut) {
            document.getElementById('stockModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit Sale Record';
            document.getElementById('stockOutID').value = id;
            document.getElementById('productID').value = productID;
            document.getElementById('quantity').value = quantity;
            document.getElementById('sellingPrice').value = sellingPrice;
            document.getElementById('soldTo').value = soldTo || '';
            document.getElementById('dateOut').value = dateOut;
            document.getElementById('modalSubmitBtn').name = 'update_stock_out';
            document.getElementById('modalSubmitBtn').textContent = 'Update Sale';
            updateProductInfo();
            updateProfit();
        }

        // Delete Stock Out Function
        function deleteStockOut(id) {
            if (confirm('Are you sure you want to delete this sale record?\n\nThis will return the quantity to stock!')) {
                window.location.href = window.location.pathname + '?delete=' + id;
            }
        }

        // Update product information and auto-fill selling price
        function updateProductInfo() {
            const productSelect = document.getElementById('productID');
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const productInfo = document.getElementById('productInfo');
            const sellingPriceInput = document.getElementById('sellingPrice');
            
            if (selectedOption && selectedOption.value !== '') {
                const costPrice = parseFloat(selectedOption.getAttribute('data-cost')) || 0;
                const currentStock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
                const sellingPrice = parseFloat(selectedOption.getAttribute('data-selling')) || 0;
                
                // Update product info display
                productInfo.innerHTML = `
                    <div>
                        <span class="${currentStock > 0 ? 'stock-ok' : 'stock-warning'}">
                            <i class="fas fa-box"></i> Current Stock: ${currentStock}
                        </span>
                        <span style="color: #e74c3c;">
                            <i class="fas fa-money-bill"></i> Cost: $${costPrice.toFixed(2)}
                        </span>
                    </div>
                `;
                
                // Auto-fill selling price with product's selling price
                if (sellingPriceInput.value === '0.00' || sellingPriceInput.value === '') {
                    sellingPriceInput.value = sellingPrice.toFixed(2);
                    updateProfit();
                }
                
                // Update max quantity
                const quantityInput = document.getElementById('quantity');
                quantityInput.max = currentStock;
                
                // Show warning if quantity exceeds stock
                if (parseInt(quantityInput.value) > currentStock) {
                    quantityInput.value = currentStock;
                    updateProfit();
                }
            } else {
                productInfo.innerHTML = '';
            }
        }

        // Calculate and update profit
        function updateProfit() {
            const productSelect = document.getElementById('productID');
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const quantity = parseFloat(document.getElementById('quantity').value) || 0;
            const sellingPrice = parseFloat(document.getElementById('sellingPrice').value) || 0;
            
            let costPrice = 0;
            if (selectedOption && selectedOption.value !== '') {
                costPrice = parseFloat(selectedOption.getAttribute('data-cost')) || 0;
            }
            
            const totalAmount = quantity * sellingPrice;
            const totalCost = quantity * costPrice;
            const profit = totalAmount - totalCost;
            const profitPercentage = costPrice > 0 ? (profit / totalCost) * 100 : 0;
            
            // Update displays
            document.getElementById('totalAmount').textContent = `$${totalAmount.toFixed(2)}`;
            
            const profitValue = document.getElementById('profitValue');
            profitValue.textContent = `$${profit.toFixed(2)} (${profitPercentage.toFixed(1)}%)`;
            
            // Color code profit
            profitValue.className = 'profit-neutral';
            if (profit > 0) {
                profitValue.className = 'profit-positive';
            } else if (profit < 0) {
                profitValue.className = 'profit-negative';
            }
            
            // Check stock availability
            if (selectedOption && selectedOption.value !== '') {
                const currentStock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
                const quantityInput = document.getElementById('quantity');
                
                if (quantity > currentStock) {
                    quantityInput.style.borderColor = '#e74c3c';
                    quantityInput.style.backgroundColor = '#fdeaea';
                } else {
                    quantityInput.style.borderColor = '#ddd';
                    quantityInput.style.backgroundColor = 'white';
                }
            }
        }

        // Add event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners for calculation
            const quantityInput = document.getElementById('quantity');
            const sellingPriceInput = document.getElementById('sellingPrice');
            const productSelect = document.getElementById('productID');
            
            if (quantityInput) {
                quantityInput.addEventListener('input', updateProfit);
            }
            if (sellingPriceInput) {
                sellingPriceInput.addEventListener('input', updateProfit);
            }
            if (productSelect) {
                productSelect.addEventListener('change', updateProductInfo);
            }
            
            // Auto-calculate on modal open
            updateProductInfo();
            updateProfit();
        });

        // Toggle Sidebar for Mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('stockModal');
            if (event.target == modal) {
                closeModal();
            }
        };

        // Validate form before submission
        document.getElementById('stockForm').addEventListener('submit', function(e) {
            const productSelect = document.getElementById('productID');
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const sellingPrice = parseFloat(document.getElementById('sellingPrice').value) || 0;
            
            if (!productSelect.value) {
                alert('Please select a product.');
                e.preventDefault();
                return;
            }
            
            if (quantity <= 0) {
                alert('Quantity must be greater than 0.');
                e.preventDefault();
                return;
            }
            
            if (sellingPrice <= 0) {
                alert('Selling price must be greater than 0.');
                e.preventDefault();
                return;
            }
            
            // Check stock availability
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            if (selectedOption && selectedOption.value !== '') {
                const currentStock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
                if (quantity > currentStock) {
                    alert(`Insufficient stock! Available: ${currentStock}`);
                    e.preventDefault();
                    return;
                }
            }
        });

        // Disable add button if no products in stock
        <?php if (empty($products)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            if (document.querySelector('.btn-add')) {
                document.querySelector('.btn-add').disabled = true;
                document.querySelector('.btn-add').style.opacity = '0.6';
                document.querySelector('.btn-add').style.cursor = 'not-allowed';
                document.querySelector('.btn-add').title = 'Add stock to products first';
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