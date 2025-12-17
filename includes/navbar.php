<?php
$user = $_SESSION['user'] ?? ['username' => 'Guest', 'role' => 'guest'];
$role = $user['role'];
$isAdmin = in_array($role, ['super_admin', 'admin', 'hrd']);
$current_module = basename(dirname($_SERVER['PHP_SELF']));
?>

<nav class="nav-container">
    <div class="nav-content">
        <!-- Brand -->
        <a href="<?php echo BASE_URL; ?>modules/dashboard/index.php" class="nav-brand">
            <svg class="brand-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
            </svg>
            <span>HRISKU</span>
        </a>

        <!-- Desktop Menu -->
        <div class="nav-menu">
            <a href="<?php echo BASE_URL; ?>modules/dashboard/index.php" 
               class="nav-link <?php echo $current_module == 'dashboard' ? 'active' : ''; ?>">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            <?php if ($isAdmin): ?>
            <!-- Company Menu -->
            <div class="nav-dropdown">
                <button class="nav-link <?php echo $current_module == 'company' ? 'active' : ''; ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Company
                    <svg class="dropdown-arrow" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div class="dropdown-menu">
                    <a href="<?php echo BASE_URL; ?>modules/company/index.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Overview</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/company/branches.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Cabang</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/company/departments.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <span>Departemen</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/company/manage_positions.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span>Jabatan</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Kepegawaian Menu -->
            <div class="nav-dropdown">
                <button class="nav-link <?php echo $current_module == 'kepegawaian' ? 'active' : ''; ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Karyawan
                    <svg class="dropdown-arrow" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div class="dropdown-menu">
                    <a href="<?php echo BASE_URL; ?>modules/kepegawaian/index.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/kepegawaian/employees.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span>Data Karyawan</span>
                    </a>
                    <?php if ($isAdmin): ?>
                    <a href="<?php echo BASE_URL; ?>modules/kepegawaian/add_employee.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        <span>Tambah Karyawan</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payroll -->
            <div class="nav-dropdown">
                <button class="nav-link <?php echo $current_module == 'payroll' ? 'active' : ''; ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Payroll
                    <svg class="dropdown-arrow" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div class="dropdown-menu">
                    <a href="<?php echo BASE_URL; ?>modules/payroll/index.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/payroll/components.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Komponen Gaji</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/payroll/master_salary.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <span>Master Gaji</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/payroll/generate.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span>Generate Payroll</span>
                    </a>
                </div>
            </div>            
            <!-- Cuti Menu -->
            <div class="nav-dropdown">
                <button class="nav-link <?php echo $current_module == 'cuti' ? 'active' : ''; ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Cuti
                    <svg class="dropdown-arrow" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div class="dropdown-menu">
                    <a href="<?php echo BASE_URL; ?>modules/cuti/index.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/cuti/pengajuan.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Ajukan Cuti</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/cuti/<?php echo $isAdmin ? 'list_cuti' : 'my_cuti'; ?>.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span><?php echo $isAdmin ? 'Semua Cuti' : 'Cuti Saya'; ?></span>
                    </a>
                </div>
            </div>

            <!-- Lembur Menu -->
            <div class="nav-dropdown">
                <button class="nav-link <?php echo $current_module == 'lembur' ? 'active' : ''; ?>">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Lembur
                    <svg class="dropdown-arrow" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div class="dropdown-menu">
                    <a href="<?php echo BASE_URL; ?>modules/lembur/index.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/lembur/pengajuan.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Ajukan Lembur</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>modules/lembur/my_lembur.php" class="dropdown-item">
                        <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span>Lembur Saya</span>
                    </a>
                </div>
            </div>
        </div>



        <!-- User Profile -->
        <div class="nav-user">
            <button class="user-btn" onclick="toggleUser()">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['full_name'] ?? $user['username'], 0, 2)); ?>
                </div>
                <span class="user-name"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></span>
                <svg class="user-arrow" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
            <div class="user-dropdown" id="userMenu">
                <div class="user-info">
                    <div class="user-info-name"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></div>
                    <div class="user-info-role"><?php echo ucfirst($role); ?></div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="<?php echo BASE_URL; ?>modules/profile/index.php" class="dropdown-item">
                    <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>Profile</span>
                </a>
                <a href="<?php echo BASE_URL; ?>modules/settings/index.php" class="dropdown-item">
                    <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Settings</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?php echo BASE_URL; ?>logout.php" class="dropdown-item text-danger">
                    <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Mobile Toggle -->
        <button class="mobile-toggle" onclick="toggleMobile()">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <a href="<?php echo BASE_URL; ?>modules/dashboard/index.php" class="mobile-link">Dashboard</a>
        
        <?php if ($isAdmin): ?>
        <div class="mobile-group">
            <div class="mobile-group-title">Company</div>
            <a href="<?php echo BASE_URL; ?>modules/company/branches.php" class="mobile-sublink">Cabang</a>
            <a href="<?php echo BASE_URL; ?>modules/company/departments.php" class="mobile-sublink">Departemen</a>
            <a href="<?php echo BASE_URL; ?>modules/company/manage_positions.php" class="mobile-sublink">Jabatan</a>
        </div>
        <?php endif; ?>
        
        <div class="mobile-group">
            <div class="mobile-group-title">Kepegawaian</div>
            <a href="<?php echo BASE_URL; ?>modules/kepegawaian/employees.php" class="mobile-sublink">Data Karyawan</a>
            <?php if ($isAdmin): ?>
            <a href="<?php echo BASE_URL; ?>modules/kepegawaian/add_employee.php" class="mobile-sublink">Tambah Karyawan</a>
            <?php endif; ?>
        </div>
        
        <div class="mobile-group">
            <div class="mobile-group-title">Cuti & Lembur</div>
            <a href="<?php echo BASE_URL; ?>modules/cuti/pengajuan.php" class="mobile-sublink">Ajukan Cuti</a>
            <a href="<?php echo BASE_URL; ?>modules/lembur/pengajuan.php" class="mobile-sublink">Ajukan Lembur</a>
            <a href="<?php echo BASE_URL; ?>modules/cuti/my_cuti.php" class="mobile-sublink">Cuti Saya</a>
            <a href="<?php echo BASE_URL; ?>modules/lembur/my_lembur.php" class="mobile-sublink">Lembur Saya</a>
        </div>

        <div class="mobile-group">
            <div class="mobile-group-title">Penggajian (Payroll)</div>
            <a href="<?php echo BASE_URL; ?>modules/payroll/index.php" class="mobile-sublink">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>modules/payroll/components.php" class="mobile-sublink">Komponen Gaji</a>
            <a href="<?php echo BASE_URL; ?>modules/payraoll/master_salary.php" class="mobile-sublink">Master Gaji</a>
            <a href="<?php echo BASE_URL; ?>modules/payroll/generate.php" class="mobile-sublink">Generate payrol</a>
        </div>
        
        <div class="mobile-divider"></div>
        <a href="<?php echo BASE_URL; ?>logout.php" class="mobile-link text-danger">Logout</a>
    </div>
</nav>

<style>
/* Container */
.nav-container { background: #fff; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 50; }
.nav-content { max-width: 1400px; margin: 0 auto; padding: 0 1rem; display: flex; align-items: center; height: 4rem; gap: 1rem; }

/* Brand */
.nav-brand { display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; font-weight: 700; color: #111827; text-decoration: none; }
.brand-icon { width: 2rem; height: 2rem; color: #6366f1; }

/* Menu */
.nav-menu { display: flex; align-items: center; gap: 0.25rem; flex: 1; margin-left: 2rem; }
.nav-link { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; color: #6b7280; font-weight: 500; font-size: 0.875rem; border-radius: 0.5rem; background: transparent; border: none; cursor: pointer; transition: all 0.15s; text-decoration: none; }
.nav-link:hover { background: #f3f4f6; color: #111827; }
.nav-link.active { background: #6366f1; color: #fff; }
.nav-icon { width: 1.25rem; height: 1.25rem; }

/* Dropdown */
.nav-dropdown { position: relative; }
.dropdown-arrow { width: 1rem; height: 1rem; transition: transform 0.15s; margin-left: 0.25rem; }
.nav-dropdown:hover .dropdown-arrow { transform: rotate(180deg); }
.dropdown-menu { position: absolute; top: calc(100% + 0.5rem); left: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); min-width: 14rem; padding: 0.5rem; opacity: 0; visibility: hidden; transform: translateY(-0.5rem); transition: all 0.15s; z-index: 10; }
.nav-dropdown:hover .dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }
.dropdown-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.625rem 0.75rem; color: #374151; font-size: 0.875rem; font-weight: 500; border-radius: 0.375rem; text-decoration: none; transition: all 0.15s; }
.dropdown-item:hover { background: #f3f4f6; color: #111827; }
.dropdown-icon { width: 1.125rem; height: 1.125rem; }
.dropdown-divider { height: 1px; background: #e5e7eb; margin: 0.5rem 0; }
.text-danger { color: #ef4444 !important; }

/* User */
.nav-user { position: relative; margin-left: auto; }
.user-btn { display: flex; align-items: center; gap: 0.5rem; padding: 0.375rem 0.75rem; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; cursor: pointer; transition: all 0.15s; }
.user-btn:hover { border-color: #d1d5db; }
.user-avatar { width: 2rem; height: 2rem; border-radius: 9999px; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.75rem; }
.user-name { font-size: 0.875rem; font-weight: 500; color: #111827; }
.user-arrow { width: 1rem; height: 1rem; color: #9ca3af; }
.user-dropdown { position: absolute; top: calc(100% + 0.5rem); right: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); min-width: 14rem; padding: 0.5rem; opacity: 0; visibility: hidden; transform: translateY(-0.5rem); transition: all 0.15s; z-index: 10; }
.user-dropdown.show { opacity: 1; visibility: visible; transform: translateY(0); }
.user-info { padding: 0.75rem; }
.user-info-name { font-weight: 600; color: #111827; font-size: 0.875rem; }
.user-info-role { color: #6b7280; font-size: 0.75rem; margin-top: 0.125rem; }

/* Mobile */
.mobile-toggle { display: none; flex-direction: column; gap: 0.25rem; background: none; border: none; cursor: pointer; padding: 0.5rem; }
.mobile-toggle span { width: 1.5rem; height: 2px; background: #374151; border-radius: 2px; transition: all 0.2s; }
.mobile-menu { display: none; background: #fff; border-top: 1px solid #e5e7eb; padding: 1rem; }
.mobile-menu.show { display: block; }
.mobile-link { display: block; padding: 0.75rem 1rem; color: #374151; font-weight: 500; text-decoration: none; border-radius: 0.5rem; transition: all 0.15s; }
.mobile-link:hover { background: #f3f4f6; }
.mobile-group { margin: 1rem 0; }
.mobile-group-title { font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; padding: 0 1rem; margin-bottom: 0.5rem; }
.mobile-sublink { display: block; padding: 0.625rem 1rem 0.625rem 2rem; color: #6b7280; font-size: 0.875rem; text-decoration: none; border-radius: 0.5rem; transition: all 0.15s; }
.mobile-sublink:hover { background: #f3f4f6; color: #111827; }
.mobile-divider { height: 1px; background: #e5e7eb; margin: 1rem 0; }

@media (max-width: 1024px) {
    .nav-menu { margin-left: 1rem; gap: 0; }
    .nav-link span, .user-name { display: none; }
}

@media (max-width: 768px) {
    .nav-menu { display: none; }
    .mobile-toggle { display: flex; }
    .user-btn { padding: 0.375rem; }
}
</style>

<script>
function toggleUser() { document.getElementById('userMenu').classList.toggle('show'); }
function toggleMobile() { document.getElementById('mobileMenu').classList.toggle('show'); }
document.addEventListener('click', (e) => {
    if (!e.target.closest('.nav-user')) document.getElementById('userMenu').classList.remove('show');
    if (!e.target.closest('.nav-content')) document.getElementById('mobileMenu').classList.remove('show');
});
</script>
