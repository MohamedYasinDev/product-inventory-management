


<?php
include_once '../config/conn.php'; // connection

// Fetch main menus
$menus_sql = "SELECT * FROM menues ORDER BY menuID ASC";
$menus_result = $conn->query($menus_sql);
?>

<div class="sidebar">
    <h2>My Dashboard</h2>

    <?php
    if ($menus_result->num_rows > 0) {
        while ($menu = $menus_result->fetch_assoc()) {
            echo '<div class="menu-item">';
            echo '<a href="'.$menu['menuUrl'].'">'; 
            if(!empty($menu['menuIcon'])){
                echo '<i class="'.$menu['menuIcon'].'"></i> ';
            }
            echo $menu['menuName'].'</a>';

            // Fetch submenus for this menu
            $menuID = $menu['menuID'];
            $sub_sql = "SELECT * FROM submenues WHERE menuID = $menuID ORDER BY submenuID ASC";
            $sub_result = $conn->query($sub_sql);

            if($sub_result->num_rows > 0){
                echo '<div class="submenu-container" style="padding-left:15px;">';
                while($sub = $sub_result->fetch_assoc()){
                    echo '<a href="'.$sub['submenuUrl'].'">';
                    if(!empty($sub['submenuIcon'])){
                        echo '<i class="'.$sub['submenuIcon'].'"></i> ';
                    }
                    echo $sub['submenuName'].'</a>';
                }
                echo '</div>';
            }

            echo '</div>';
        }
    } else {
        echo '<p>No menu found</p>';
    }
    ?>

    <a href="../login/logout.php">🚪 Log Out</a>

    <script src="../assets/script/script.js"></script>

</div>
