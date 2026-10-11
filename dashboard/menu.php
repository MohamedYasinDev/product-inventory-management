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
    // Add New Menu
    if (isset($_POST['add_menu'])) {
        $menuName = $conn->real_escape_string($_POST['menuName']);
        $menuIcon = $conn->real_escape_string($_POST['menuIcon']);
        $menuUrl = $conn->real_escape_string($_POST['menuUrl']);
        $regdate = date('Y-m-d');
        
        $sql = "INSERT INTO menues (menuName, menuIcon, menuUrl, regdate) 
                VALUES ('$menuName', '$menuIcon', '$menuUrl', '$regdate')";
        
        if ($conn->query($sql)) {
            $message = "Menu added successfully!";
            $message_type = "success";
        } else {
            $message = "Error adding menu: " . $conn->error;
            $message_type = "error";
        }
    }
    
    // Update Menu
    if (isset($_POST['update_menu'])) {
        $menuID = intval($_POST['menuID']);
        $menuName = $conn->real_escape_string($_POST['menuName']);
        $menuIcon = $conn->real_escape_string($_POST['menuIcon']);
        $menuUrl = $conn->real_escape_string($_POST['menuUrl']);
        
        $sql = "UPDATE menues SET 
                menuName = '$menuName', 
                menuIcon = '$menuIcon', 
                menuUrl = '$menuUrl' 
                WHERE menuID = $menuID";
        
        if ($conn->query($sql)) {
            $message = "Menu updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating menu: " . $conn->error;
            $message_type = "error";
        }
    }
}

// Delete Menu
if (isset($_GET['delete'])) {
    $menuID = intval($_GET['delete']);
    
    $sql = "DELETE FROM menues WHERE menuID = $menuID";
    
    if ($conn->query($sql)) {
        $message = "Menu deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting menu: " . $conn->error;
        $message_type = "error";
    }
}

// Fetch all menus
$sql = "SELECT * FROM menues ORDER BY menuName ASC";
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
    <title>Menu Management</title>
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
            background: linear-gradient(45deg, #00b09b, #96c93d);
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
            box-shadow: 0 4px 15px rgba(0, 176, 155, 0.3);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 176, 155, 0.4);
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

        .icon-preview {
            font-size: 20px;
            width: 30px;
            text-align: center;
            color: #3498db;
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
            max-width: 500px;
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

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
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
            background: linear-gradient(45deg, #00b09b, #96c93d);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 176, 155, 0.3);
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
        <a href="../dashboard/dashboard.php"><i class="fas fa-home"></i> Home</a>
        <a href="menu.php" class="active"><i class="fas fa-bars"></i> Menu Management</a>
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
            <h1><i class="fas fa-bars"></i> Menu Management</h1>
            <button class="btn-add" onclick="openModal()">
                <i class="fas fa-plus"></i> Add New Menu
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
                            <th>Icon</th>
                            <th>Menu Name</th>
                            <th>URL</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['menuID']; ?></td>
                                <td class="icon-preview">
                                    <?php 
                                    if (!empty($row['menuIcon'])) {
                                        echo '<i class="' . htmlspecialchars($row['menuIcon']) . '"></i>';
                                    } else {
                                        echo '<i class="fas fa-question-circle"></i>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['menuName']); ?></td>
                                <td><?php echo htmlspecialchars($row['menuUrl']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['regdate'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-edit" onclick="editMenu(<?php echo $row['menuID']; ?>, '<?php echo addslashes($row['menuName']); ?>', '<?php echo addslashes($row['menuIcon']); ?>', '<?php echo addslashes($row['menuUrl']); ?>')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn-delete" onclick="deleteMenu(<?php echo $row['menuID']; ?>)">
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
                    <i class="fas fa-bars"></i>
                    <h3>No Menus Found</h3>
                    <p>Click the "Add New Menu" button to create your first menu item.</p>
                    <?php if (!$result): ?>
                        <div class="message info" style="margin-top: 20px;">
                            <i class="fas fa-info-circle"></i> Unable to load menu data. Please check your database connection.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="menuModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-bars"></i> <span id="modalTitle">Add New Menu</span></h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="menuForm" method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" id="menuID" name="menuID">
                    
                    <div class="form-group">
                        <label for="menuName"><i class="fas fa-heading"></i> Menu Name</label>
                        <input type="text" id="menuName" name="menuName" required placeholder="Enter menu name">
                    </div>
                    
                    <div class="form-group">
                        <label for="menuIcon"><i class="fas fa-icons"></i> Menu Icon (FontAwesome)</label>
                        <input type="text" id="menuIcon" name="menuIcon" required placeholder="e.g., fas fa-home" value="fas fa-">
                        <small style="color: #666; margin-top: 5px; display: block;">
                            Use FontAwesome icons (e.g., fas fa-home, fas fa-user, fas fa-box)
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="menuUrl"><i class="fas fa-link"></i> Menu URL</label>
                        <input type="text" id="menuUrl" name="menuUrl" required placeholder="e.g., dashboard.php">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit" id="modalSubmitBtn" name="add_menu">Add Menu</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openModal() {
            document.getElementById('menuModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Add New Menu';
            document.getElementById('menuForm').reset();
            document.getElementById('modalSubmitBtn').name = 'add_menu';
            document.getElementById('modalSubmitBtn').textContent = 'Add Menu';
            document.getElementById('menuID').value = '';
        }

        function closeModal() {
            document.getElementById('menuModal').style.display = 'none';
        }

        // Edit Menu Function
        function editMenu(id, name, icon, url) {
            document.getElementById('menuModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit Menu';
            document.getElementById('menuID').value = id;
            document.getElementById('menuName').value = name;
            document.getElementById('menuIcon').value = icon;
            document.getElementById('menuUrl').value = url;
            document.getElementById('modalSubmitBtn').name = 'update_menu';
            document.getElementById('modalSubmitBtn').textContent = 'Update Menu';
        }

        // Delete Menu Function
        function deleteMenu(id) {
            if (confirm('Are you sure you want to delete this menu?')) {
                window.location.href = 'menu.php?delete=' + id;
            }
        }

        // Toggle Sidebar for Mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('menuModal');
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
    </script>
</body>
</html>
<?php 
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>