<?php
$current_page = basename($_SERVER['PHP_SELF']); // Get the current page name
?>
<aside id="sidebar" class="sidebar">
    
    <ul class="sidebar-nav" id="sidebar-nav">
    <li class="nav-item">
            <a class="nav-link <?= $current_page == 'index.php' ? 'active' : 'collapsed' ?>" href="index.php">
                <i class="bi bi-grid"></i> <!-- Dashboard Icon -->
                <span>Dashboard</span>
            </a>
        </li><!-- End Dashboard Nav -->
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'directory-listing.php' ? 'active' : 'collapsed' ?>" href="directory-listing.php">
                <i class="bi bi-house-door"></i> <!-- Home Icon -->
                <span>File Management</span>
            </a>
        </li><!-- End File Management Nav -->

        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'recent.php' ? 'active' : 'collapsed' ?>" href="recent.php">
                <i class="bi bi-clock-history"></i> <!-- Recent Icon -->
                <span>Recent</span>
            </a>
        </li><!-- End Recent Nav -->

      
        
        <!-- iFound AI Navigation -->
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'ifound.php' ? 'active' : 'collapsed' ?>" href="ifound.php">
                <i class="bi bi-search"></i> <!-- iFound Icon -->
                <span>iFound</span>
            </a>
        </li><!-- End iFound AI Nav -->
        
        <!-- Trash Navigation -->
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'trash.php' ? 'active' : 'collapsed' ?>" href="trash.php">
                <i class="bi bi-trash"></i> <!-- Trash Icon -->
                <span>Trash</span>
            </a>
        </li><!-- End Trash Nav -->
    </ul>
</aside><!-- End Sidebar-->
