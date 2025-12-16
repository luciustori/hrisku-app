<nav class="navbar">
    <div class="navbar-brand">
        <a href="<?php echo BASE_URL; ?>modules/dashboard/index.php">
            <?php echo APP_NAME; ?>
        </a>
    </div>
    
    <ul class="navbar-menu">
        <li>
            <a href="<?php echo BASE_URL; ?>modules/dashboard/index.php">
                Dashboard
            </a>
        </li>
        
        <?php if (in_array(getUserRole(), ['super_admin', 'admin'])): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>modules/kepegawaian/index.php">
                Kepegawaian
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>modules/company/index.php">
                Perusahaan
            </a>
        </li>
        <?php endif; ?>
    </ul>
    
    <div class="navbar-user">
        <span class="username">
            <?php echo getUsername(); ?>
            <span class="badge-role"><?php echo getUserRole(); ?></span>
        </span>
        <a href="<?php echo BASE_URL; ?>modules/auth/logout.php" class="btn-logout">
            Logout
        </a>
    </div>
</nav>
