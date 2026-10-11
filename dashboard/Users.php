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
    // Add New User
    if (isset($_POST['add_user'])) {
        $username = $conn->real_escape_string($_POST['username']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        $role = $conn->real_escape_string($_POST['role']);
        
        // Validate inputs
        if (empty($username) || empty($password) || empty($confirmPassword)) {
            $message = "All fields are required!";
            $message_type = "error";
        } elseif ($password !== $confirmPassword) {
            $message = "Passwords do not match!";
            $message_type = "error";
        } elseif (strlen($password) < 6) {
            $message = "Password must be at least 6 characters long!";
            $message_type = "error";
        } else {
            // Check if username already exists
            $checkSql = "SELECT UserID FROM Users WHERE Username = '$username'";
            $checkResult = $conn->query($checkSql);
            
            if ($checkResult && $checkResult->num_rows > 0) {
                $message = "Username already exists!";
                $message_type = "error";
            } else {
                // Hash the password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO Users (Username, PasswordHash, Role) 
                        VALUES ('$username', '$passwordHash', '$role')";
                
                if ($conn->query($sql)) {
                    $message = "User added successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error adding user: " . $conn->error;
                    $message_type = "error";
                }
            }
        }
    }
    
    // Update User
    if (isset($_POST['update_user'])) {
        $userID = intval($_POST['userID']);
        $username = $conn->real_escape_string($_POST['username']);
        $role = $conn->real_escape_string($_POST['role']);
        $changePassword = isset($_POST['change_password']);
        
        // Check if username already exists (excluding current user)
        $checkSql = "SELECT UserID FROM Users WHERE Username = '$username' AND UserID != $userID";
        $checkResult = $conn->query($checkSql);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            $message = "Username already exists!";
            $message_type = "error";
        } else {
            if ($changePassword) {
                $password = $_POST['password'];
                $confirmPassword = $_POST['confirm_password'];
                
                if (empty($password) || empty($confirmPassword)) {
                    $message = "Password fields are required when changing password!";
                    $message_type = "error";
                } elseif ($password !== $confirmPassword) {
                    $message = "Passwords do not match!";
                    $message_type = "error";
                } elseif (strlen($password) < 6) {
                    $message = "Password must be at least 6 characters long!";
                    $message_type = "error";
                } else {
                    // Hash the new password
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $sql = "UPDATE Users SET 
                            Username = '$username', 
                            PasswordHash = '$passwordHash', 
                            Role = '$role' 
                            WHERE UserID = $userID";
                    
                    if ($conn->query($sql)) {
                        $message = "User updated successfully! Password changed.";
                        $message_type = "success";
                    } else {
                        $message = "Error updating user: " . $conn->error;
                        $message_type = "error";
                    }
                }
            } else {
                // Update without changing password
                $sql = "UPDATE Users SET 
                        Username = '$username', 
                        Role = '$role' 
                        WHERE UserID = $userID";
                
                if ($conn->query($sql)) {
                    $message = "User updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error updating user: " . $conn->error;
                    $message_type = "error";
                }
            }
        }
    }
}

// Delete User (Prevent deleting self)
if (isset($_GET['delete'])) {
    $userID = intval($_GET['delete']);
    
    // Prevent deleting yourself
    if ($userID == $_SESSION['user_id']) {
        $message = "You cannot delete your own account!";
        $message_type = "error";
    } else {
        $sql = "DELETE FROM Users WHERE UserID = $userID";
        
        if ($conn->query($sql)) {
            $message = "User deleted successfully!";
            $message_type = "success";
            header("Location: " . basename($_SERVER['PHP_SELF']));
            exit();
        } else {
            $message = "Error deleting user: " . $conn->error;
            $message_type = "error";
        }
    }
}

// Fetch all users
$sql = "SELECT * FROM Users ORDER BY CreatedAt DESC, UserID DESC";
$result = $conn->query($sql);

// Check for query error
if ($result === false) {
    $message = "Database error: " . $conn->error;
    $message_type = "error";
    $result = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management</title>
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

        .btn-delete:disabled {
            background: rgba(149, 165, 166, 0.1);
            color: #95a5a6;
            cursor: not-allowed;
        }

        .btn-delete:disabled:hover {
            background: rgba(149, 165, 166, 0.1);
            color: #95a5a6;
        }

        /* Badge Styles */
        .user-id-badge {
            background: #e8f4fc;
            color: #3498db;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            font-family: monospace;
        }

        .username-badge {
            background: #eafaf1;
            color: #27ae60;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 600;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-admin {
            background: linear-gradient(45deg, #9b59b6, #8e44ad);
            color: white;
        }

        .role-manager {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
        }

        .role-cashier {
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
        }

        .date-badge {
            background: #f5f5f5;
            color: #333;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-family: monospace;
        }

        .current-user {
            background: rgba(46, 204, 113, 0.1);
            border-left: 4px solid #2ecc71;
        }

        .current-user:hover {
            background: rgba(46, 204, 113, 0.15) !important;
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

        .password-requirements {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }

        .password-strength {
            height: 4px;
            background: #eee;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }

        .password-strength-fill {
            height: 100%;
            width: 0%;
            background: #e74c3c;
            transition: width 0.3s, background-color 0.3s;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .checkbox-group label {
            margin-bottom: 0;
            cursor: pointer;
        }

        .password-fields {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .password-fields.active {
            display: block;
            animation: fadeIn 0.3s ease;
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
        <a href="Users.php" class="active"><i class="fas fa-users"></i> Users</a>
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
            <h1><i class="fas fa-users"></i> Users Management</h1>
            <button class="btn-add" onclick="openModal()">
                <i class="fas fa-user-plus"></i> Add New User
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
                            <th>Username</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>

                          
                            <tr class="<?php echo $rowClass; ?>">
                                <td>
                                    <span class="user-id-badge">
                                        #<?php echo $row['UserID']; ?>
                                    </span>
                                   
                                </td>
                                <td>
                                    <span class="username-badge">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($row['Username']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="role-badge <?php echo $roleClass; ?>">
                                        <?php echo htmlspecialchars($row['Role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="date-badge">
                                        <i class="far fa-calendar"></i>
                                        <?php echo date('Y-m-d', strtotime($row['CreatedAt'])); ?>
                                    </span>
                                    <div style="font-size: 11px; color: #7f8c8d; margin-top: 3px;">
                                        <?php echo date('H:i:s', strtotime($row['CreatedAt'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-edit" onclick="editUser(
                                            <?php echo $row['UserID']; ?>, 
                                            '<?php echo addslashes($row['Username']); ?>', 
                                            '<?php echo $row['Role']; ?>'
                                        )">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn-delete" 
                                                onclick="deleteUser(<?php echo $row['UserID']; ?>, '<?php echo addslashes($row['Username']); ?>', <?php echo $isCurrentUser ? 'true' : 'false'; ?>)"
                                              
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
                    <i class="fas fa-users"></i>
                    <h3>No Users Found</h3>
                    <p>Click the "Add New User" button to create your first user.</p>
                    <?php if (!$result): ?>
                        <div class="message info" style="margin-top: 20px;">
                            <i class="fas fa-info-circle"></i> Unable to load users data. Please check your database connection.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> <span id="modalTitle">Add New User</span></h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="userForm" method="POST" action="">
                <input type="hidden" id="userID" name="userID">
                
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username"><i class="fas fa-user"></i> Username *</label>
                            <input type="text" id="username" name="username" required 
                                   placeholder="Enter username" autocomplete="off">
                        </div>
                        
                        <div class="form-group">
                            <label for="role"><i class="fas fa-user-tag"></i> Role *</label>
                            <select id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="admin">Administrator</option>
                                <option value="manager">Manager</option>
                                <option value="cashier" selected>Cashier</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="addPasswordFields">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password"><i class="fas fa-lock"></i> Password *</label>
                                <input type="password" id="password" name="password" required 
                                       placeholder="Enter password" oninput="checkPasswordStrength()">
                                <div class="password-requirements">
                                    At least 6 characters
                                </div>
                                <div class="password-strength">
                                    <div class="password-strength-fill" id="passwordStrengthFill"></div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required 
                                       placeholder="Confirm password" oninput="checkPasswordMatch()">
                                <div class="password-requirements" id="passwordMatchMessage"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="editPasswordFields" style="display: none;">
                        <div class="checkbox-group">
                            <input type="checkbox" id="change_password" name="change_password" 
                                   onchange="togglePasswordFields()">
                            <label for="change_password">Change Password</label>
                        </div>
                        
                        <div class="password-fields" id="passwordFields">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="edit_password"><i class="fas fa-lock"></i> New Password</label>
                                    <input type="password" id="edit_password" name="password" 
                                           placeholder="Enter new password" oninput="checkPasswordStrength()">
                                    <div class="password-requirements">
                                        At least 6 characters
                                    </div>
                                    <div class="password-strength">
                                        <div class="password-strength-fill" id="editPasswordStrengthFill"></div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_confirm_password"><i class="fas fa-lock"></i> Confirm New Password</label>
                                    <input type="password" id="edit_confirm_password" name="confirm_password" 
                                           placeholder="Confirm new password" oninput="checkPasswordMatch()">
                                    <div class="password-requirements" id="editPasswordMatchMessage"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit" id="modalSubmitBtn" name="add_user">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openModal() {
            document.getElementById('userModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Add New User';
            document.getElementById('userForm').reset();
            document.getElementById('modalSubmitBtn').name = 'add_user';
            document.getElementById('modalSubmitBtn').textContent = 'Add User';
            document.getElementById('userID').value = '';
            document.getElementById('role').value = 'cashier';
            document.getElementById('addPasswordFields').style.display = 'block';
            document.getElementById('editPasswordFields').style.display = 'none';
            resetPasswordFields();
        }

        function closeModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        // Edit User Function
        function editUser(id, username, role) {
            document.getElementById('userModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('userID').value = id;
            document.getElementById('username').value = username;
            document.getElementById('role').value = role;
            document.getElementById('modalSubmitBtn').name = 'update_user';
            document.getElementById('modalSubmitBtn').textContent = 'Update User';
            document.getElementById('addPasswordFields').style.display = 'none';
            document.getElementById('editPasswordFields').style.display = 'block';
            
            // Reset password fields
            document.getElementById('change_password').checked = false;
            document.getElementById('passwordFields').classList.remove('active');
            resetPasswordFields();
        }

        // Delete User Function
        function deleteUser(id, username, isCurrentUser) {
            if (isCurrentUser) {
                alert('You cannot delete your own account!');
                return;
            }
            
            if (confirm('Are you sure you want to delete user: ' + username + '?\n\nThis action cannot be undone!')) {
                window.location.href = window.location.pathname + '?delete=' + id;
            }
        }

        // Toggle password fields in edit mode
        function togglePasswordFields() {
            const changePasswordCheckbox = document.getElementById('change_password');
            const passwordFields = document.getElementById('passwordFields');
            
            if (changePasswordCheckbox.checked) {
                passwordFields.classList.add('active');
                document.getElementById('edit_password').required = true;
                document.getElementById('edit_confirm_password').required = true;
            } else {
                passwordFields.classList.remove('active');
                document.getElementById('edit_password').required = false;
                document.getElementById('edit_confirm_password').required = false;
            }
        }

        // Check password strength
        function checkPasswordStrength() {
            const password = document.getElementById('password') ? document.getElementById('password').value : 
                           (document.getElementById('edit_password') ? document.getElementById('edit_password').value : '');
            const strengthFill = document.getElementById('passwordStrengthFill') ? 
                               document.getElementById('passwordStrengthFill') : 
                               document.getElementById('editPasswordStrengthFill');
            
            if (!strengthFill) return;
            
            let strength = 0;
            let color = '#e74c3c'; // Red
            
            if (password.length >= 6) {
                strength += 25;
                color = '#f39c12'; // Orange
            }
            if (password.length >= 8) {
                strength += 25;
                color = '#f1c40f'; // Yellow
            }
            if (/[A-Z]/.test(password)) {
                strength += 25;
                color = '#2ecc71'; // Green
            }
            if (/[0-9]/.test(password) || /[^A-Za-z0-9]/.test(password)) {
                strength += 25;
                color = '#27ae60'; // Dark Green
            }
            
            strengthFill.style.width = strength + '%';
            strengthFill.style.backgroundColor = color;
        }

        // Check password match
        function checkPasswordMatch() {
            const password = document.getElementById('password') ? document.getElementById('password').value : 
                           (document.getElementById('edit_password') ? document.getElementById('edit_password').value : '');
            const confirmPassword = document.getElementById('confirm_password') ? 
                                  document.getElementById('confirm_password').value : 
                                  (document.getElementById('edit_confirm_password') ? document.getElementById('edit_confirm_password').value : '');
            const matchMessage = document.getElementById('passwordMatchMessage') ? 
                               document.getElementById('passwordMatchMessage') : 
                               document.getElementById('editPasswordMatchMessage');
            
            if (!matchMessage) return;
            
            if (confirmPassword === '') {
                matchMessage.textContent = '';
                matchMessage.style.color = '#7f8c8d';
            } else if (password === confirmPassword) {
                matchMessage.textContent = '✓ Passwords match';
                matchMessage.style.color = '#2ecc71';
            } else {
                matchMessage.textContent = '✗ Passwords do not match';
                matchMessage.style.color = '#e74c3c';
            }
        }

        // Reset password fields
        function resetPasswordFields() {
            const passwordInputs = document.querySelectorAll('input[type="password"]');
            passwordInputs.forEach(input => {
                input.value = '';
                input.style.borderColor = '#ddd';
            });
            
            const strengthFills = document.querySelectorAll('.password-strength-fill');
            strengthFills.forEach(fill => {
                fill.style.width = '0%';
            });
            
            const matchMessages = document.querySelectorAll('.password-requirements');
            matchMessages.forEach(msg => {
                if (msg.id.includes('MatchMessage')) {
                    msg.textContent = '';
                }
            });
        }

        // Toggle Sidebar for Mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target == modal) {
                closeModal();
            }
        };

        // Form validation
        document.getElementById('userForm').addEventListener('submit', function(e) {
            const isAddMode = document.getElementById('modalSubmitBtn').name === 'add_user';
            const isEditMode = document.getElementById('modalSubmitBtn').name === 'update_user';
            const username = document.getElementById('username').value.trim();
            const password = isAddMode ? document.getElementById('password').value : 
                           (isEditMode && document.getElementById('change_password').checked ? 
                            document.getElementById('edit_password').value : '');
            const confirmPassword = isAddMode ? document.getElementById('confirm_password').value : 
                                  (isEditMode && document.getElementById('change_password').checked ? 
                                   document.getElementById('edit_confirm_password').value : '');
            
            // Username validation
            if (username.length < 3) {
                alert('Username must be at least 3 characters long.');
                e.preventDefault();
                return;
            }
            
            // Password validation for add mode
            if (isAddMode) {
                if (password.length < 6) {
                    alert('Password must be at least 6 characters long.');
                    e.preventDefault();
                    return;
                }
                if (password !== confirmPassword) {
                    alert('Passwords do not match.');
                    e.preventDefault();
                    return;
                }
            }
            
            // Password validation for edit mode
            if (isEditMode && document.getElementById('change_password').checked) {
                if (password.length < 6) {
                    alert('Password must be at least 6 characters long.');
                    e.preventDefault();
                    return;
                }
                if (password !== confirmPassword) {
                    alert('Passwords do not match.');
                    e.preventDefault();
                    return;
                }
            }
        });

        // Add event listeners for password strength
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInputs = document.querySelectorAll('input[type="password"]');
            passwordInputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.id === 'password' || this.id === 'edit_password') {
                        checkPasswordStrength();
                    }
                    if (this.id === 'confirm_password' || this.id === 'edit_confirm_password') {
                        checkPasswordMatch();
                    }
                });
            });
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