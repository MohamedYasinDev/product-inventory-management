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
    // Add New Supplier
    if (isset($_POST['add_supplier'])) {
        $supplierName = $conn->real_escape_string($_POST['supplierName']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $email = $conn->real_escape_string($_POST['email']);
        $address = $conn->real_escape_string($_POST['address']);
        
        $sql = "INSERT INTO Suppliers (SupplierName, Phone, Email, Address) 
                VALUES ('$supplierName', '$phone', '$email', '$address')";
        
        if ($conn->query($sql)) {
            $message = "Supplier added successfully!";
            $message_type = "success";
        } else {
            $message = "Error adding supplier: " . $conn->error;
            $message_type = "error";
        }
    }
    
    // Update Supplier
    if (isset($_POST['update_supplier'])) {
        $supplierID = intval($_POST['supplierID']);
        $supplierName = $conn->real_escape_string($_POST['supplierName']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $email = $conn->real_escape_string($_POST['email']);
        $address = $conn->real_escape_string($_POST['address']);
        
        $sql = "UPDATE Suppliers SET 
                SupplierName = '$supplierName', 
                Phone = '$phone', 
                Email = '$email', 
                Address = '$address' 
                WHERE SupplierID = $supplierID";
        
        if ($conn->query($sql)) {
            $message = "Supplier updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating supplier: " . $conn->error;
            $message_type = "error";
        }
    }
}

// Delete Supplier
if (isset($_GET['delete'])) {
    $supplierID = intval($_GET['delete']);
    
    $sql = "DELETE FROM Suppliers WHERE SupplierID = $supplierID";
    
    if ($conn->query($sql)) {
        $message = "Supplier deleted successfully!";
        $message_type = "success";
        // Redirect to same page without delete parameter
        header("Location: " . basename($_SERVER['PHP_SELF']));
        exit();
    } else {
        $message = "Error deleting supplier: " . $conn->error;
        $message_type = "error";
    }
}

// Fetch all suppliers
$sql = "SELECT * FROM Suppliers ORDER BY SupplierName ASC";
$result = $conn->query($sql);

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
    <title>Suppliers Management</title>
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
            background: linear-gradient(45deg, #f39c12, #e67e22);
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
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(243, 156, 18, 0.4);
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
            background: linear-gradient(45deg, #f39c12, #e67e22);
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
            background: rgba(243, 156, 18, 0.05);
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

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .phone-badge, .email-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 500;
        }

        .phone-badge {
            background: #e8f4fc;
            color: #3498db;
        }

        .email-badge {
            background: #f0f8ff;
            color: #9b59b6;
        }

        .address-text {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .address-text:hover {
            white-space: normal;
            overflow: visible;
            position: absolute;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 10;
            max-width: 300px;
        }

        .supplier-id {
            font-weight: 600;
            color: #f39c12;
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
            max-width: 600px;
            margin: 50px auto;
            border-radius: 15px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s ease;
        }

        .modal-header {
            padding: 20px;
            background: linear-gradient(45deg, #f39c12, #e67e22);
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #f39c12;
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
            background: linear-gradient(45deg, #f39c12, #e67e22);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
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
            
            .contact-info {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 5px;
            }
            
            .address-text {
                max-width: 120px;
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
        <a href="Suppliers.php" class="active"><i class="fas fa-truck"></i> Suppliers</a>
        <a href="../users/view_users.php"><i class="fas fa-users"></i> Users</a>
        <a href="../products/view_products.php"><i class="fas fa-box"></i> Products</a>
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
            <h1><i class="fas fa-truck"></i> Suppliers Management</h1>
            <button class="btn-add" onclick="openModal()">
                <i class="fas fa-plus"></i> Add New Supplier
            </button>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier Name</th>
                            <th>Contact Info</th>
                            <th>Address</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><span class="supplier-id">#<?php echo $row['SupplierID']; ?></span></td>
                                <td><strong><?php echo htmlspecialchars($row['SupplierName']); ?></strong></td>
                                <td>
                                    <div class="contact-info">
                                        <?php if (!empty($row['Phone'])): ?>
                                            <span class="phone-badge">
                                                <i class="fas fa-phone"></i>
                                                <?php echo htmlspecialchars($row['Phone']); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($row['Email'])): ?>
                                            <span class="email-badge">
                                                <i class="fas fa-envelope"></i>
                                                <?php echo htmlspecialchars($row['Email']); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if (empty($row['Phone']) && empty($row['Email'])): ?>
                                            <span style="color: #95a5a6; font-style: italic;">No contact info</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="address-text" title="<?php echo htmlspecialchars($row['Address']); ?>">
                                        <?php 
                                        $address = htmlspecialchars($row['Address']);
                                        echo !empty($address) ? $address : '<span style="color: #95a5a6; font-style: italic;">No address</span>';
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $createdAt = strtotime($row['CreatedAt']);
                                    echo date('M d, Y', $createdAt) . '<br><small style="color: #7f8c8d;">' . date('h:i A', $createdAt) . '</small>';
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-edit" onclick="editSupplier(
                                            <?php echo $row['SupplierID']; ?>, 
                                            '<?php echo addslashes($row['SupplierName']); ?>', 
                                            '<?php echo addslashes($row['Phone']); ?>', 
                                            '<?php echo addslashes($row['Email']); ?>', 
                                            '<?php echo addslashes($row['Address']); ?>'
                                        )">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn-delete" onclick="deleteSupplier(<?php echo $row['SupplierID']; ?>)">
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
                    <i class="fas fa-truck"></i>
                    <h3>No Suppliers Found</h3>
                    <p>Click the "Add New Supplier" button to create your first supplier.</p>
                    <?php if (!$result): ?>
                        <div class="message info" style="margin-top: 20px;">
                            <i class="fas fa-info-circle"></i> Unable to load suppliers data. Please check your database connection.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="supplierModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-truck"></i> <span id="modalTitle">Add New Supplier</span></h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="supplierForm" method="POST" action="">
                <input type="hidden" id="supplierID" name="supplierID">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="supplierName"><i class="fas fa-building"></i> Supplier Name *</label>
                        <input type="text" id="supplierName" name="supplierName" required placeholder="Enter supplier name">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="Enter phone number">
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter email address">
                    </div>
                    
                    <div class="form-group">
                        <label for="address"><i class="fas fa-map-marker-alt"></i> Address</label>
                        <textarea id="address" name="address" placeholder="Enter supplier address"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit" id="modalSubmitBtn" name="add_supplier">Add Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openModal() {
            document.getElementById('supplierModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Add New Supplier';
            document.getElementById('supplierForm').reset();
            document.getElementById('modalSubmitBtn').name = 'add_supplier';
            document.getElementById('modalSubmitBtn').textContent = 'Add Supplier';
            document.getElementById('supplierID').value = '';
        }

        function closeModal() {
            document.getElementById('supplierModal').style.display = 'none';
        }

        // Edit Supplier Function
        function editSupplier(id, name, phone, email, address) {
            document.getElementById('supplierModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit Supplier';
            document.getElementById('supplierID').value = id;
            document.getElementById('supplierName').value = name;
            document.getElementById('phone').value = phone;
            document.getElementById('email').value = email;
            document.getElementById('address').value = address;
            document.getElementById('modalSubmitBtn').name = 'update_supplier';
            document.getElementById('modalSubmitBtn').textContent = 'Update Supplier';
        }

        // Delete Supplier Function
        function deleteSupplier(id) {
            if (confirm('Are you sure you want to delete this supplier?')) {
                // Get current page URL without query parameters
                var currentUrl = window.location.origin + window.location.pathname;
                window.location.href = currentUrl + '?delete=' + id;
            }
        }

        // Toggle Sidebar for Mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('supplierModal');
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

        // Address hover effect
        document.addEventListener('DOMContentLoaded', function() {
            const addressElements = document.querySelectorAll('.address-text');
            addressElements.forEach(element => {
                element.addEventListener('mouseenter', function() {
                    if (this.scrollWidth > this.clientWidth) {
                        this.style.whiteSpace = 'normal';
                        this.style.overflow = 'visible';
                        this.style.position = 'absolute';
                        this.style.background = 'white';
                        this.style.padding = '10px';
                        this.style.borderRadius = '5px';
                        this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
                        this.style.zIndex = '10';
                        this.style.maxWidth = '300px';
                    }
                });
                
                element.addEventListener('mouseleave', function() {
                    this.style.whiteSpace = 'nowrap';
                    this.style.overflow = 'hidden';
                    this.style.position = 'static';
                    this.style.background = 'transparent';
                    this.style.padding = '0';
                    this.style.borderRadius = '0';
                    this.style.boxShadow = 'none';
                    this.style.zIndex = '1';
                    this.style.maxWidth = '200px';
                });
            });
        });

        // Phone number formatting
        document.getElementById('phone')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.substring(0, 15);
                e.target.value = value;
            }
        });
    </script>
</body>
</html>
<?php 
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>