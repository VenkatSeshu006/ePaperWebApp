<?php
// Admin navigation component
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-tachometer-alt me-2"></i>Admin Panel
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="fas fa-home me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'upload.php' ? 'active' : '' ?>" href="upload.php">
                        <i class="fas fa-upload me-1"></i>Upload Edition
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'manage_editions.php' ? 'active' : '' ?>" href="manage_editions.php">
                        <i class="fas fa-newspaper me-1"></i>Manage Editions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'categories.php' ? 'active' : '' ?>" href="categories.php">
                        <i class="fas fa-folder me-1"></i>Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'watermark_settings.php' ? 'active' : '' ?>" href="watermark_settings.php">
                        <i class="fas fa-image me-1"></i>Watermark
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'analytics.php' ? 'active' : '' ?>" href="analytics.php">
                        <i class="fas fa-chart-line me-1"></i>Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'settings.php' ? 'active' : '' ?>" href="settings.php">
                        <i class="fas fa-cog me-1"></i>Settings
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="../index.php" target="_blank">
                        <i class="fas fa-external-link-alt me-1"></i>View Site
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
