<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="header">
            <h1>Welcome, Suheyba!</h1>
        </div>

        <div class="card-container">
            <div class="card">
                <h3>Total Users</h3>
                <p><?php echo rand(100, 500); ?> Users</p>
            </div>
            <div class="card">
                <h3>New Orders</h3>
                <p><?php echo rand(10, 100); ?> Orders</p>
            </div>
            <div class="card">
                <h3>Revenue</h3>
                <p>$<?php echo rand(1000, 5000); ?></p>
            </div>
            <div class="card">
                <h3>Pending Tasks</h3>
                <p><?php echo rand(1, 20); ?> Tasks</p>
            </div>
        </div>
    </div>

</body>
</html>
