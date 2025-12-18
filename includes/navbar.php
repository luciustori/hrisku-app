<?php
// Get current page
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<!-- TOP BAR -->
<div class="top-bar">
    <div class="top-bar-content">
        <!-- Logo -->
        <div class="logo-section">
            <a href="<?php echo BASE_URL; ?>" class="logo-link">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                    <rect width="32" height="32" rx="6" fill="url(#gradient)"/>
                    <path d="M8 10h6v12H8V10zm10 0h6v12h-6V10z" fill="white"/>
                    <defs>
                        <linearGradient id="gradient" x1="0" y1="0" x2="32" y2="32">
                            <stop offset="0%" stop-color="#4F46E5"/>
                            <stop offset="100%" stop-color="#7C3AED"/>
                        </linearGradient>
                    </defs>
                </svg>
                <span class="logo-text">HRISKU</span>
            </a>
        </div>

        <!-- Search Bar -->
        <div class="search-section">
            <svg class="search-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M9 17A8 8 0 1 0 9 1a8 8 0 0 0 0 16zM18 18l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input type="text" class="search-input" placeholder="Search now">
        </div>

        <!-- Actions -->
        <div class="actions-section">
            <!-- Messages -->
            <button class="action-btn" title="Messages">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span class="action-badge">2</span>
            </button>

            <!-- Notifications -->
            <button class="action-btn" title="Notifications">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span class="action-badge danger">5</span>
            </button>

            <!-- User Menu -->
            <div class="user-menu">
                <button class="user-btn">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user']['username']); ?>&background=4F46E5&color=fff&size=128" alt="User" class="user-avatar">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['user']['username']); ?></div>
                        <div class="user-role"><?php echo ucwords(str_replace('_', ' ', $_SESSION['user']['role'])); ?></div>
                    </div>
                    <svg class="user-arrow" width="12" height="12" viewBox="0 0 12 12">
                        <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"/>
                    </svg>
                </button>
                <div class="user-dropdown">
                    <div class="dropdown-header">
                        <div class="header-name"><?php echo htmlspecialchars($_SESSION['user']['username']); ?></div>
                        <div class="header-email"><?php echo htmlspecialchars($_SESSION['user']['role']); ?></div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo BASE_URL; ?>profile.php" class="dropdown-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                            <path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        My Profile
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/settings/index.php" class="dropdown-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 1v6m0 6v10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo BASE_URL; ?>logout.php" class="dropdown-item danger">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MENU BAR -->
<nav class="menu-bar">
    <div class="menu-bar-content">
        <a href="<?php echo BASE_URL; ?>" class="menu-link <?php echo $current_page == 'index' && $current_dir != 'modules' ? 'active' : ''; ?>">
            <svg class="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span>Dashboard</span>
        </a>

        <!-- <a href="<?php echo BASE_URL; ?>modules/karyawan/index.php" class="menu-link">
            <svg class="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="3" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                <rect x="14" y="3" width="7" height="7" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span>Widgets</span>
        </a> -->

        <div class="menu-dropdown">
            <button class="menu-link dropdown-btn <?php echo in_array($current_dir, ['branches', 'departments', 'positions']) ? 'active' : ''; ?>">
                <svg class="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2l10 6v8a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span>Perusahaan</span>
                <svg class="dropdown-icon" width="12" height="12" viewBox="0 0 12 12">
                    <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"/>
                </svg>
            </button>
            <div class="dropdown-menu">
                <a href="<?php echo BASE_URL; ?>modules/company/index.php" class="dropdown-link">Overview</a>
                <a href="<?php echo BASE_URL; ?>modules/company/branches.php" class="dropdown-link">Branches</a>
                <a href="<?php echo BASE_URL; ?>modules/company/departments.php" class="dropdown-link">Departments</a>
                <a href="<?php echo BASE_URL; ?>modules/company/manage_positions.php" class="dropdown-link">Positions</a>
            </div>
        </div>

        <div class="menu-dropdown">
            <button class="menu-link dropdown-btn">
                <svg class="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span>Pengajuan</span>
                <svg class="dropdown-icon" width="12" height="12" viewBox="0 0 12 12">
                    <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"/>
                </svg>
            </button>
            <div class="dropdown-menu">
                <a href="<?php echo BASE_URL; ?>modules/cuti/index.php" class="dropdown-link">Ijin/Cuti</a>
                <a href="<?php echo BASE_URL; ?>modules/lembur/index.php" class="dropdown-link">Lembur</a>
            </div>
        </div>

        <div class="menu-dropdown">
            <button class="menu-link dropdown-btn <?php echo $current_dir == 'payroll' ? 'active' : ''; ?>">
                <svg class="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <path d="M3 3v18h18" stroke="currentColor" stroke-width="2"/>
                    <path d="M7 16l4-4 4 4 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span>Penggajian</span>
                <svg class="dropdown-icon" width="12" height="12" viewBox="0 0 12 12">
                    <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"/>
                </svg>
            </button>
            <div class="dropdown-menu">
                <a href="<?php echo BASE_URL; ?>modules/payroll/components.php" class="dropdown-link">Komponen Gaji</a>
                <a href="<?php echo BASE_URL; ?>modules/payroll/master_salary.php" class="dropdown-link">Master Gaji</a>
                <a href="<?php echo BASE_URL; ?>modules/payroll/index.php" class="dropdown-link">Daftar Gaji</a>
                <a href="<?php echo BASE_URL; ?>modules/payroll/generate.php" class="dropdown-link">Generate Gaji`</a>
            </div>
        </div>

        <a href="<?php echo BASE_URL; ?>modules/employees/index.php" class="menu-link <?php echo $current_dir == 'employees' ? 'active' : ''; ?>">
            <svg class="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                <path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span>Employees</span>
        </a>

        <div class="menu-dropdown">
            <button class="menu-link dropdown-btn">
                <svg class="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span>Sample Pages</span>
                <svg class="dropdown-icon" width="12" height="12" viewBox="0 0 12 12">
                    <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"/>
                </svg>
            </button>
            <div class="dropdown-menu">
                <a href="<?php echo BASE_URL; ?>modules/reports/index.php" class="dropdown-link">Reports</a>
                <a href="<?php echo BASE_URL; ?>modules/settings/index.php" class="dropdown-link">Settings</a>
            </div>
        </div>

        <a href="#" class="menu-link">
            <svg class="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/>
                <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>Apps</span>
        </a>

        <a href="#" class="menu-link">
            <svg class="menu-icon" width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span>Documentation</span>
        </a>
    </div>
</nav>

<style>
/* ============================================
   2-PART NAVBAR SYSTEM - CENTERED LAYOUT
   ============================================ */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif;
    background: #F5F7FA;
}

/* ============================================
   TOP BAR (Logo + Search + Actions)
   ============================================ */

.top-bar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 64px;
    background: #ffffff;
    border-bottom: 1px solid #E5E7EB;
    z-index: 1001;
}

.top-bar-content {
    max-width: 1440px;  /* ← CENTERED! */
    margin: 0 auto;     /* ← CENTERED! */
    display: flex;
    align-items: center;
    height: 100%;
    padding: 0 24px;
    gap: 32px;
}

/* Logo */
.logo-section {
    flex-shrink: 0;
}

.logo-link {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}

.logo-text {
    font-size: 20px;
    font-weight: 700;
    color: #1F2937;
}

/* Search */
.search-section {
    position: relative;
    flex: 1;
    max-width: 600px;
}

.search-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #9CA3AF;
}

.search-input {
    width: 100%;
    height: 40px;
    padding: 0 16px 0 48px;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    font-size: 14px;
    color: #1F2937;
    background: #F9FAFB;
    transition: all 0.2s;
}

.search-input:focus {
    outline: none;
    background: #ffffff;
    border-color: #4F46E5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.search-input::placeholder {
    color: #9CA3AF;
}

/* Actions */
.actions-section {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-left: auto;
}

.action-btn {
    position: relative;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: none;
    color: #6B7280;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn:hover {
    background: #F3F4F6;
    color: #1F2937;
}

.action-badge {
    position: absolute;
    top: 4px;
    right: 4px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #3B82F6;
    color: #ffffff;
    font-size: 10px;
    font-weight: 600;
    border-radius: 9px;
    border: 2px solid #ffffff;
}

.action-badge.danger {
    background: #EF4444;
}

/* User Menu */
.user-menu {
    position: relative;
}

.user-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 4px 12px 4px 4px;
    border: 1px solid #E5E7EB;
    background: #ffffff;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.user-btn:hover {
    border-color: #D1D5DB;
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
}

.user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.user-name {
    font-size: 14px;
    font-weight: 600;
    color: #1F2937;
    line-height: 1.2;
}

.user-role {
    font-size: 12px;
    color: #6B7280;
}

.user-arrow {
    color: #9CA3AF;
    transition: transform 0.2s;
}

.user-menu:hover .user-arrow {
    transform: rotate(180deg);
}

.user-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 240px;
    background: #ffffff;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-8px);
    transition: all 0.2s;
    z-index: 1002;
}

.user-menu:hover .user-dropdown {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-header {
    padding: 16px;
}

.header-name {
    font-size: 14px;
    font-weight: 600;
    color: #1F2937;
}

.header-email {
    font-size: 12px;
    color: #6B7280;
    margin-top: 2px;
}

.dropdown-divider {
    height: 1px;
    background: #E5E7EB;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: #374151;
    font-size: 14px;
    text-decoration: none;
    transition: background 0.2s;
}

.dropdown-item:hover {
    background: #F9FAFB;
}

.dropdown-item.danger {
    color: #EF4444;
}

.dropdown-item.danger:hover {
    background: #FEF2F2;
}

/* ============================================
   MENU BAR (Navigation Links)
   ============================================ */

.menu-bar {
    position: fixed;
    top: 64px;
    left: 0;
    right: 0;
    height: 52px;
    background: #ffffff;
    border-bottom: 1px solid #E5E7EB;
    z-index: 1000;
}

.menu-bar-content {
    max-width: 1440px;  /* ← CENTERED! */
    margin: 0 auto;     /* ← CENTERED! */
    display: flex;
    align-items: center;
    height: 100%;
    padding: 0 24px;
    gap: 8px;
}

.menu-link {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    color: #6B7280;
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
    background: none;
}

.dropdown-btn {
    width: 100%;
}

.menu-link:hover {
    background: #F3F4F6;
    color: #1F2937;
}

.menu-link.active {
    background: #EEF2FF;
    color: #4F46E5;
}

.menu-icon {
    flex-shrink: 0;
    color: currentColor;
}

.dropdown-icon {
    margin-left: 4px;
    color: currentColor;
    transition: transform 0.2s;
}

/* Dropdown */
.menu-dropdown {
    position: relative;
}

.dropdown-menu {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    min-width: 180px;
    background: #ffffff;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    padding: 4px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-8px);
    transition: all 0.2s;
    z-index: 1001;
}

.menu-dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.menu-dropdown:hover .dropdown-icon {
    transform: rotate(180deg);
}

.dropdown-link {
    display: block;
    padding: 10px 12px;
    color: #374151;
    font-size: 14px;
    text-decoration: none;
    border-radius: 4px;
    transition: background 0.2s;
}

.dropdown-link:hover {
    background: #F9FAFB;
}

/* Content Spacing */
body {
    padding-top: 116px; /* 64px + 52px */
}

/* ============================================
   CONTAINER (For page content)
   ============================================ */

.container {
    max-width: 1440px;  /* ← CENTERED! */
    margin: 0 auto;     /* ← CENTERED! */
    padding: 0 24px;
}

/* Responsive */
@media (max-width: 1280px) {
    .menu-bar-content {
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: none;
    }
    
    .menu-bar-content::-webkit-scrollbar {
        display: none;
    }
}

@media (max-width: 768px) {
    .user-info {
        display: none;
    }
    
    .search-section {
        max-width: 300px;
    }
}

@media (max-width: 640px) {
    .search-section {
        display: none;
    }
    
    .logo-text {
        display: none;
    }
    
    .menu-link span {
        display: none;
    }
    
    .menu-link {
        padding: 8px;
    }
}
</style>

