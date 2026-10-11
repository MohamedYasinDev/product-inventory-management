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
    // Add New Stock In
    if (isset($_POST['add_stock_in'])) {
        $productID = intval($_POST['productID']);
        $quantity = intval($_POST['quantity']);
        $unitCost = floatval($_POST['unitCost']);
        $supplierID = intval($_POST['supplierID']);
        $dateReceived = $conn->real_escape_string($_POST['dateReceived']);
        
        // Insert into Stock_In table
        $sql = "INSERT INTO Stock_In (ProductID, Quantity, UnitCost, SupplierID, DateReceived) 
                VALUES ($productID, $quantity, $unitCost, $supplierID, '$dateReceived')";
        
        if ($conn->query($sql)) {
            // Update product quantity
            $updateSql = "UPDATE Products SET QuantityInStock = QuantityInStock + $quantity 
                         WHERE ProductID = $productID";
            $conn->query($updateSql);
            
            $message = "Stock added successfully! Product quantity updated.";
            $message_type = "success";
        } else {
            $message = "Error adding stock: " . $conn->error;
            $message_type = "error";
        }
    }
    
    // Update Stock In
    if (isset($_POST['update_stock_in'])) {
        $stockInID = intval($_POST['stockInID']);
        $productID = intval($_POST['productID']);
        $quantity = intval($_POST['quantity']);
        $unitCost = floatval($_POST['unitCost']);
        $supplierID = intval($_POST['supplierID']);
        $dateReceived = $conn->real_escape_string($_POST['dateReceived']);
        
        // Get old quantity for adjustment
        $oldStockSql = "SELECT ProductID, Quantity FROM Stock_In WHERE StockInID = $stockInID";
        $oldResult = $conn->query($oldStockSql);
        
        if ($oldResult && $oldResult->num_rows > 0) {
            $oldData = $oldResult->fetch_assoc();
            $oldQuantity = $oldData['Quantity'];
            $oldProductID = $oldData['ProductID'];
            
            // Adjust old product quantity
            if ($oldProductID == $productID) {
                // Same product, adjust quantity
                $quantityDiff = $quantity - $oldQuantity;
                $adjustSql = "UPDATE Products SET QuantityInStock = QuantityInStock + $quantityDiff 
                             WHERE ProductID = $productID";
            } else {
                // Different product, remove from old and add to new
                $removeSql = "UPDATE Products SET QuantityInStock = QuantityInStock - $oldQuantity 
                             WHERE ProductID = $oldProductID";
                $addSql = "UPDATE Products SET QuantityInStock = QuantityInStock + $quantity 
                          WHERE ProductID = $productID";
                $conn->query($removeSql);
                $conn->query($addSql);
                $quantityDiff = $quantity;
            }
            
            // Update stock in record
            $sql = "UPDATE Stock_In SET 
                    ProductID = $productID, 
                    Quantity = $quantity, 
                    UnitCost = $unitCost, 
                    SupplierID = $supplierID, 
                    DateReceived = '$dateReceived' 
                    WHERE StockInID = $stockInID";
            
            if ($conn->query($sql)) {
                // Adjust quantity if same product
                if ($oldProductID == $productID && isset($quantityDiff)) {
                    $conn->query($adjustSql);
                }
                
                $message = "Stock record updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating stock: " . $conn->error;
                $message_type = "error";
            }
        }
    }
}

// Delete Stock In
if (isset($_GET['delete'])) {
    $stockInID = intval($_GET['delete']);
    
    // Get stock details before deletion
    $stockSql = "SELECT ProductID, Quantity FROM Stock_In WHERE StockInID = $stockInID";
    $stockResult = $conn->query($stockSql);
    
    if ($stockResult && $stockResult->num_rows > 0) {
        $stockData = $stockResult->fetch_assoc();
        $productID = $stockData['ProductID'];
        $quantity = $stockData['Quantity'];
        
        // Reduce product quantity
        $updateSql = "UPDATE Products SET QuantityInStock = QuantityInStock - $quantity 
                     WHERE ProductID = $productID";
        $conn->query($updateSql);
        
        // Delete stock record
        $sql = "DELETE FROM Stock_In WHERE StockInID = $stockInID";
        
        if ($conn->query($sql)) {
            $message = "Stock record deleted successfully! Product quantity adjusted.";
            $message_type = "success";
            header("Location: " . basename($_SERVER['PHP_SELF']));
            exit();
        } else {
            $message = "Error deleting stock record: " . $conn->error;
            $message_type = "error";
        }
    }
}

// Fetch all stock in records with product and supplier details
$sql = "SELECT si.*, p.ProductName, p.SKU, p.CategoryID, 
               s.SupplierName, s.Phone as SupplierPhone,
               c.CategoryName,
               (si.Quantity * si.UnitCost) as TotalCost
        FROM Stock_In si
        LEFT JOIN Products p ON si.ProductID = p.ProductID
        LEFT JOIN Suppliers s ON si.SupplierID = s.SupplierID
        LEFT JOIN Categories c ON p.CategoryID = c.CategoryID
        ORDER BY si.DateReceived DESC, si.StockInID DESC";
$result = $conn->query($sql);

// Fetch all products for dropdown
$productSql = "SELECT * FROM Products ORDER BY ProductName ASC";
$productResult = $conn->query($productSql);
$products = [];
if ($productResult && $productResult->num_rows > 0) {
    while ($product = $productResult->fetch_assoc()) {
        $products[] = $product;
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

// Get today's date for default value
$todayDate = date('Y-m-d\TH:i');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock In Management</title>
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

        /* Sidebar Styles (same as your Products page) */
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
            background: linear-gradient(45deg, #3498db, #2980b9);
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
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
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
            background: linear-gradient(45deg, #3498db, #2980b9);
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
            background: rgba(52, 152, 219, 0.05);
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

        .supplier-badge {
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

        .cost-badge {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .unit-cost {
            color: #e74c3c;
            background: #fdeaea;
        }

        .total-cost {
            color: #27ae60;
            background: #eafaf1;
        }

        .quantity-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 600;
            background: #eafaf1;
            color: #27ae60;
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
            background: linear-gradient(45deg, #3498db, #2980b9);
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
            border-color: #3498db;
        }

        .cost-inputs {
            display: flex;
            gap: 15px;
        }

        .total-cost-display {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            font-weight: 600;
            color: #27ae60;
        }

        .current-stock {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
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
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
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
        <a href="Products.php"><i class="fas fa-box"></i> Products</a>
        <a href="../users/view_users.php"><i class="fas fa-users"></i> Users</a>
        <a href="../stock/stock_in.php" class="active"><i class="fas fa-arrow-up"></i> Stock In</a>
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
            <h1><i class="fas fa-arrow-up"></i> Stock In Management</h1>
            <button class="btn-add" onclick="openModal()">
                <i class="fas fa-plus"></i> Add New Stock
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
                <strong>No Products Found!</strong> You need to create products first before adding stock. 
                <a href="Products.php">Go to Products Management</a>
            </div>
        <?php endif; ?>

        <?php if (empty($suppliers)): ?>
            <div class="no-data-alert">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>No Suppliers Found!</strong> You need to create suppliers first before adding stock. 
                <a href="Suppliers.php">Go to Suppliers Management</a>
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
                            <th>Cost</th>
                            <th>Supplier</th>
                            <th>Date Received</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $row['StockInID']; ?></strong></td>
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
                                        <span class="cost-badge unit-cost">
                                            Unit: $<?php echo number_format($row['UnitCost'], 2); ?>
                                        </span>
                                        <span class="cost-badge total-cost">
                                            Total: $<?php echo number_format($row['TotalCost'], 2); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($row['SupplierName'])): ?>
                                        <div style="display: flex; flex-direction: column; gap: 3px;">
                                            <span class="supplier-badge">
                                                <?php echo htmlspecialchars($row['SupplierName']); ?>
                                            </span>
                                            <?php if (!empty($row['SupplierPhone'])): ?>
                                                <small style="color: #7f8c8d; font-size: 11px;">
                                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['SupplierPhone']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #95a5a6; font-style: italic;">No supplier</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="date-badge">
                                        <i class="far fa-calendar"></i>
                                        <?php echo date('Y-m-d', strtotime($row['DateReceived'])); ?>
                                    </span>
                                    <div style="font-size: 11px; color: #7f8c8d; margin-top: 3px;">
                                        <?php echo date('H:i:s', strtotime($row['DateReceived'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-edit" onclick="editStockIn(
                                            <?php echo $row['StockInID']; ?>, 
                                            <?php echo $row['ProductID']; ?>, 
                                            <?php echo $row['Quantity']; ?>, 
                                            <?php echo $row['UnitCost']; ?>, 
                                            <?php echo $row['SupplierID'] ?? 'null'; ?>, 
                                            '<?php echo date('Y-m-d\TH:i', strtotime($row['DateReceived'])); ?>'
                                        )">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn-delete" onclick="deleteStockIn(<?php echo $row['StockInID']; ?>)">
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
                    <h3>No Stock Records Found</h3>
                    <p>Click the "Add New Stock" button to record your first stock intake.</p>
                    <?php if (!$result): ?>
                        <div class="message info" style="margin-top: 20px;">
                            <i class="fas fa-info-circle"></i> Unable to load stock data. Please check your database connection.
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
                <h2><i class="fas fa-arrow-up"></i> <span id="modalTitle">Add New Stock</span></h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="stockForm" method="POST" action="">
                <input type="hidden" id="stockInID" name="stockInID">
                
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="productID"><i class="fas fa-cube"></i> Product *</label>
                            <select id="productID" name="productID" required onchange="updateProductInfo()">
                                <option value="">Select Product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['ProductID']; ?>"
                                            data-cost="<?php echo $product['CostPrice']; ?>"
                                            data-stock="<?php echo $product['QuantityInStock']; ?>">
                                        <?php echo htmlspecialchars($product['ProductName']); ?> 
                                        (SKU: <?php echo htmlspecialchars($product['SKU']); ?>)
                                        - Stock: <?php echo $product['QuantityInStock']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="productInfo" class="current-stock"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="supplierID"><i class="fas fa-truck"></i> Supplier</label>
                            <select id="supplierID" name="supplierID">
                                <option value="">Select Supplier (Optional)</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['SupplierID']; ?>">
                                        <?php echo htmlspecialchars($supplier['SupplierName']); ?> 
                                        (<?php echo htmlspecialchars($supplier['Phone']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity"><i class="fas fa-boxes"></i> Quantity *</label>
                            <input type="number" id="quantity" name="quantity" required min="1" value="1" 
                                   oninput="calculateTotalCost()">
                        </div>
                        
                        <div class="form-group">
                            <label for="unitCost"><i class="fas fa-money-bill-wave"></i> Unit Cost ($) *</label>
                            <input type="number" id="unitCost" name="unitCost" required min="0" step="0.01" 
                                   value="0.00" oninput="calculateTotalCost()">
                        </div>
                        
                        <div class="form-group">
                            <label for="dateReceived"><i class="far fa-calendar"></i> Date Received</label>
                            <input type="datetime-local" id="dateReceived" name="dateReceived" 
                                   value="<?php echo $todayDate; ?>">
                        </div>
                    </div>
                    
                    <div class="total-cost-display" id="totalCostDisplay">
                        Total Cost: $0.00
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit" id="modalSubmitBtn" name="add_stock_in">Add Stock</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openModal() {
            document.getElementById('stockModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Add New Stock';
            document.getElementById('stockForm').reset();
            document.getElementById('modalSubmitBtn').name = 'add_stock_in';
            document.getElementById('modalSubmitBtn').textContent = 'Add Stock';
            document.getElementById('stockInID').value = '';
            document.getElementById('quantity').value = '1';
            document.getElementById('unitCost').value = '0.00';
            document.getElementById('dateReceived').value = '<?php echo $todayDate; ?>';
            updateProductInfo();
            calculateTotalCost();
        }

        function closeModal() {
            document.getElementById('stockModal').style.display = 'none';
        }

        // Edit Stock In Function
        function editStockIn(id, productID, quantity, unitCost, supplierID, dateReceived) {
            document.getElementById('stockModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit Stock Record';
            document.getElementById('stockInID').value = id;
            document.getElementById('productID').value = productID;
            document.getElementById('quantity').value = quantity;
            document.getElementById('unitCost').value = unitCost;
            document.getElementById('supplierID').value = supplierID || '';
            document.getElementById('dateReceived').value = dateReceived;
            document.getElementById('modalSubmitBtn').name = 'update_stock_in';
            document.getElementById('modalSubmitBtn').textContent = 'Update Stock';
            updateProductInfo();
            calculateTotalCost();
        }

        // Delete Stock In Function
        function deleteStockIn(id) {
            if (confirm('Are you sure you want to delete this stock record?\n\nThis will also reduce the product quantity!')) {
                window.location.href = window.location.pathname + '?delete=' + id;
            }
        }

        // Calculate total cost
        function calculateTotalCost() {
            const quantity = parseFloat(document.getElementById('quantity').value) || 0;
            const unitCost = parseFloat(document.getElementById('unitCost').value) || 0;
            const totalCost = quantity * unitCost;
            
            const totalCostDisplay = document.getElementById('totalCostDisplay');
            if (totalCostDisplay) {
                totalCostDisplay.textContent = `Total Cost: $${totalCost.toFixed(2)}`;
            }
        }

        // Update product information
        function updateProductInfo() {
            const productSelect = document.getElementById('productID');
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const productInfo = document.getElementById('productInfo');
            
            if (selectedOption && selectedOption.value !== '') {
                const costPrice = selectedOption.getAttribute('data-cost');
                const currentStock = selectedOption.getAttribute('data-stock');
                
                productInfo.innerHTML = `
                    <div style="margin-top: 5px;">
                        <span style="color: #27ae60;">
                            <i class="fas fa-box"></i> Current Stock: ${currentStock}
                        </span>
                        <span style="color: #3498db; margin-left: 10px;">
                            <i class="fas fa-money-bill"></i> Cost Price: $${parseFloat(costPrice).toFixed(2)}
                        </span>
                    </div>
                `;
                
                // Set unit cost to product's cost price if not already set
                if (document.getElementById('unitCost').value === '0.00') {
                    document.getElementById('unitCost').value = parseFloat(costPrice).toFixed(2);
                    calculateTotalCost();
                }
            } else {
                productInfo.innerHTML = '';
            }
        }

        // Add event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners for calculation
            const quantityInput = document.getElementById('quantity');
            const unitCostInput = document.getElementById('unitCost');
            const productSelect = document.getElementById('productID');
            
            if (quantityInput) {
                quantityInput.addEventListener('input', calculateTotalCost);
            }
            if (unitCostInput) {
                unitCostInput.addEventListener('input', calculateTotalCost);
            }
            if (productSelect) {
                productSelect.addEventListener('change', updateProductInfo);
            }
            
            // Auto-calculate on modal open
            updateProductInfo();
            calculateTotalCost();
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

        // Disable add button if no products or suppliers
        <?php if (empty($products) || empty($suppliers)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            if (document.querySelector('.btn-add')) {
                document.querySelector('.btn-add').disabled = true;
                document.querySelector('.btn-add').style.opacity = '0.6';
                document.querySelector('.btn-add').style.cursor = 'not-allowed';
                document.querySelector('.btn-add').title = 'Create products and suppliers first';
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