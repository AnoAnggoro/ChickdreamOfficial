<?php
require_once 'functions.php';
requireLogin();

$dashboardStats = getDashboardStats();
$recentActivities = getRecentActivities();
$dashboardInfo = getDashboardInfo();
$attendanceList = getTodayAttendanceList();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PT. CHICKDREAM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php echo renderSidebar('dashboard'); ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <?php echo renderTopBar('Dashboard'); ?>
            
            <!-- Content -->
            <div class="content">
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <h3>Selamat datang, <?php echo $_SESSION['user_name']; ?></h3>
                    <p>Berikut ringkasan data kepegawaian dan aktivitas terbaru - <?php echo date('d F Y'); ?></p>
                    <?php 
                    $userInfo = getUserDisplayInfo();
                    if ($_SESSION['user_role'] === 'super_admin'): 
                    ?>
                    <div style="margin-top: var(--space-md); padding: var(--space-md); background: linear-gradient(135deg, #e0f2fe, #e8f5e8); border-radius: var(--radius-lg); border-left: 4px solid var(--primary-color);">
                        <p style="margin: 0; color: var(--text-primary); font-weight: 600;">
                            🔐 Anda memiliki akses penuh sebagai Super Administrator. Anda dapat mengelola semua modul sistem dan mengatur hak akses untuk pengguna lain.
                        </p>
                    </div>
                    <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
                    <div style="margin-top: var(--space-md); padding: var(--space-md); background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: var(--radius-lg); border-left: 4px solid var(--warning-color);">
                        <p style="margin: 0; color: var(--text-primary); font-weight: 600;">
                            ⚙️ Anda memiliki akses Administrator. Anda dapat mengelola data dan sistem kecuali pengaturan sistem tingkat lanjut.
                        </p>
                    </div>
                    <?php elseif ($_SESSION['user_role'] === 'hr'): ?>
                    <div style="margin-top: var(--space-md); padding: var(--space-md); background: linear-gradient(135deg, #dbeafe, #bfdbfe); border-radius: var(--radius-lg); border-left: 4px solid var(--info-color);">
                        <p style="margin: 0; color: var(--text-primary); font-weight: 600;">
                            👥 Anda memiliki akses HR Manager. Anda dapat mengelola data pegawai, absensi, dan payroll.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Stats Cards -->
                <div class="stats-section">
                    <h4 class="section-title">Ringkasan Hari Ini</h4>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number green"><?php echo $dashboardStats['today_present']; ?></div>
                            <div class="stat-label">Hadir Hari Ini</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number blue"><?php echo $dashboardStats['active_employees']; ?></div>
                            <div class="stat-label">Karyawan Aktif</div>
                        </div>
                        <div class="stat-card alert-warning">
                            <div class="stat-number orange"><?php echo $dashboardStats['grace_period_critical']; ?></div>
                            <div class="stat-label">Kontrak Kritis</div>
                            <div class="stat-sublabel">(≤ 30 hari)</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number purple"><?php echo count($dashboardInfo['absent_today']); ?></div>
                            <div class="stat-label">Tidak Hadir</div>
                        </div>
                    </div>
                </div>

                <!-- Contract & Placement Stats -->
                <div class="stats-section">
                    <h4 class="section-title">Status Kontrak & Penempatan</h4>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number blue"><?php echo $dashboardStats['contract_active']; ?></div>
                            <div class="stat-label">Kontrak Aktif</div>
                            <div class="stat-sublabel">Masih berlaku</div>
                        </div>
                        <div class="stat-card alert-critical">
                            <div class="stat-number red"><?php echo $dashboardStats['contract_expired']; ?></div>
                            <div class="stat-label">Kontrak Habis</div>
                            <div class="stat-sublabel">Sudah berakhir</div>
                        </div>
                        <div class="stat-card alert-warning">
                            <div class="stat-number orange"><?php echo $dashboardStats['contract_ending_soon']; ?></div>
                            <div class="stat-label">Kontrak Berakhir</div>
                            <div class="stat-sublabel">30 hari ke depan</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number green"><?php echo $dashboardStats['active_placements']; ?></div>
                            <div class="stat-label">Penempatan Aktif</div>
                            <div class="stat-sublabel">Sedang berjalan</div>
                        </div>
                        <div class="stat-card alert-warning">
                            <div class="stat-number orange"><?php echo $dashboardStats['placement_ending_soon']; ?></div>
                            <div class="stat-label">Penempatan Berakhir</div>
                            <div class="stat-sublabel">30 hari ke depan</div>
                        </div>
                    </div>
                </div>

                <!-- Information Grid -->
                <div class="info-grid">
                    <!-- Kontrak Habis -->
                    <?php if (!empty($dashboardInfo['expired_contracts'])): ?>
                    <div class="info-card alert-section contract-expired">
                        <div class="info-header">
                            <h4>🚫 Kontrak Sudah Habis</h4>
                            <a href="leave-period.php" class="btn-view">Detail</a>
                        </div>
                        <div class="info-list">
                            <?php foreach ($dashboardInfo['expired_contracts'] as $contract): ?>
                            <div class="info-item critical">
                                <div class="info-content">
                                    <strong><?php echo $contract['name']; ?></strong>
                                    <small><?php echo $contract['nip']; ?> - <?php echo $contract['department']; ?></small>
                                    <small>Berakhir: <?php echo date('d/m/Y', strtotime($contract['grace_period'])); ?></small>
                                </div>
                                <div class="info-badge">
                                    <span class="status-badge status-terminated">
                                        <?php echo $contract['days_expired']; ?> hari lalu
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Kontrak Aktif -->
                    <?php if (!empty($dashboardInfo['active_contracts'])): ?>
                    <div class="info-card contract-active">
                        <div class="info-header">
                            <h4>📄 Kontrak Aktif</h4>
                            <a href="leave-period.php" class="btn-view">Kelola</a>
                        </div>
                        <div class="info-list">
                            <?php foreach ($dashboardInfo['active_contracts'] as $contract): ?>
                            <div class="info-item">
                                <div class="info-content">
                                    <strong><?php echo $contract['name']; ?></strong>
                                    <small><?php echo $contract['nip']; ?> - <?php echo $contract['department']; ?></small>
                                    <small>Berakhir: <?php echo date('d/m/Y', strtotime($contract['grace_period'])); ?></small>
                                </div>
                                <div class="info-badge">
                                    <span class="status-badge status-hadir">
                                        <?php echo $contract['days_remaining']; ?> hari
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Penempatan Berakhir -->
                    <?php if (!empty($dashboardInfo['placements_ending_soon'])): ?>
                    <div class="info-card placement-ending">
                        <div class="info-header">
                            <h4>📍 Penempatan Berakhir</h4>
                            <a href="placement.php" class="btn-view">Kelola</a>
                        </div>
                        <div class="info-list">
                            <?php foreach ($dashboardInfo['placements_ending_soon'] as $placement): ?>
                            <div class="info-item">
                                <div class="info-content">
                                    <strong><?php echo $placement['name']; ?></strong>
                                    <small><?php echo $placement['location']; ?></small>
                                    <small>Berakhir: <?php echo date('d/m/Y', strtotime($placement['end_date'])); ?></small>
                                </div>
                                <div class="info-badge">
                                    <span class="status-badge status-izin">
                                        <?php echo $placement['days_remaining']; ?> hari
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Kontrak & Magang Berakhir -->
                    <?php if (!empty($dashboardInfo['expiring_contracts'])): ?>
                    <div class="info-card alert-section">
                        <div class="info-header">
                            <h4>⚠️ Kontrak & Magang Berakhir</h4>
                            <a href="leave-period.php" class="btn-view">Kelola</a>
                        </div>
                        <div class="info-list">
                            <?php foreach ($dashboardInfo['expiring_contracts'] as $contract): ?>
                            <div class="info-item <?php echo $contract['days_remaining'] <= 30 ? 'critical' : ''; ?>">
                                <div class="info-content">
                                    <strong><?php echo $contract['name']; ?></strong>
                                    <small><?php echo $contract['nip']; ?> - <?php echo $contract['department']; ?></small>
                                    <small><?php echo ucfirst($contract['employment_type']); ?> - <?php echo date('d/m/Y', strtotime($contract['grace_period'])); ?></small>
                                </div>
                                <div class="info-badge">
                                    <span class="status-badge <?php echo $contract['days_remaining'] <= 30 ? 'status-alpha' : 'status-izin'; ?>">
                                        <?php echo $contract['days_remaining']; ?> hari
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Tidak Hadir Hari Ini -->
                    <?php if (!empty($dashboardInfo['absent_today'])): ?>
                    <div class="info-card">
                        <div class="info-header">
                            <h4>👤 Tidak Hadir Hari Ini</h4>
                            <a href="attendance.php" class="btn-view">Detail</a>
                        </div>
                        <div class="info-list">
                            <?php foreach (array_slice($dashboardInfo['absent_today'], 0, 5) as $absent): ?>
                            <div class="info-item">
                                <div class="info-content">
                                    <strong><?php echo $absent['name']; ?></strong>
                                    <small><?php echo $absent['nip']; ?> - <?php echo $absent['department']; ?></small>
                                </div>
                                <div class="info-badge">
                                    <span class="status-badge status-<?php echo $absent['status'] ?: 'alpha'; ?>">
                                        <?php echo $absent['status'] ? ucfirst($absent['status']) : 'Alpha'; ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Ulang Tahun Bulan Ini -->
                    <?php if (!empty($dashboardInfo['birthday_this_month'])): ?>
                    <div class="info-card birthday-card">
                        <div class="info-header">
                            <h4>🎂 Ulang Tahun Bulan Ini</h4>
                            <a href="employees.php" class="btn-view">Lihat</a>
                        </div>
                        <div class="info-list">
                            <?php foreach ($dashboardInfo['birthday_this_month'] as $birthday): ?>
                            <div class="info-item">
                                <div class="info-content">
                                    <strong><?php echo $birthday['name']; ?></strong>
                                    <small><?php echo $birthday['position']; ?> - <?php echo $birthday['department']; ?></small>
                                    <small><?php echo date('d F', strtotime($birthday['birth_date'])); ?></small>
                                </div>
                                <div class="info-badge">
                                    <span class="birthday-badge">🎉</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Pegawai Baru -->
                    <?php if (!empty($dashboardInfo['newest_employees'])): ?>
                    <div class="info-card">
                        <div class="info-header">
                            <h4>👋 Pegawai Baru</h4>
                            <a href="employees.php" class="btn-view">Kelola</a>
                        </div>
                        <div class="info-list">
                            <?php foreach ($dashboardInfo['newest_employees'] as $new_emp): ?>
                            <div class="info-item">
                                <div class="info-content">
                                    <strong><?php echo $new_emp['name']; ?></strong>
                                    <small><?php echo $new_emp['position']; ?> - <?php echo $new_emp['department']; ?></small>
                                    <small>Bergabung: <?php echo date('d/m/Y', strtotime($new_emp['hire_date'])); ?></small>
                                </div>
                                <div class="info-badge">
                                    <span class="status-badge status-hadir">Baru</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Statistik Departemen -->
                    <div class="info-card">
                        <div class="info-header">
                            <h4>🏢 Statistik Departemen</h4>
                            <a href="employees.php" class="btn-view">Detail</a>
                        </div>
                        <div class="info-list">
                            <?php foreach (array_slice($dashboardInfo['department_stats'], 0, 5) as $dept): ?>
                            <div class="info-item">
                                <div class="info-content">
                                    <strong><?php echo $dept['department']; ?></strong>
                                    <small>Aktif: <?php echo $dept['active_count']; ?> dari <?php echo $dept['total']; ?> orang</small>
                                    <small>Tetap: <?php echo $dept['permanent_count']; ?> | Kontrak: <?php echo $dept['contract_count']; ?> | Magang: <?php echo $dept['intern_count']; ?></small>
                                </div>
                                <div class="info-badge">
                                    <span class="dept-badge"><?php echo $dept['total']; ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Monthly Statistics -->
                <div class="stats-section">
                    <h4 class="section-title">Statistik Bulan <?php echo date('F Y'); ?></h4>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number green"><?php echo $dashboardInfo['monthly_attendance_stats']['hadir']; ?></div>
                            <div class="stat-label">Total Hadir</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number orange"><?php echo $dashboardInfo['monthly_attendance_stats']['sakit']; ?></div>
                            <div class="stat-label">Total Sakit</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number blue"><?php echo $dashboardInfo['monthly_attendance_stats']['izin']; ?></div>
                            <div class="stat-label">Total Izin</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number red"><?php echo $dashboardInfo['monthly_attendance_stats']['alpha']; ?></div>
                            <div class="stat-label">Total Alpha</div>
                        </div>
                    </div>
                </div>
                
                <!-- Today Attendance Table -->
                <div class="attendance-section">
                    <div class="section-header">
                        <h4>Riwayat Absen Hari Ini</h4>
                        <div class="table-controls">
                            <a href="attendance.php" class="btn-add">Kelola Absensi</a>
                        </div>
                    </div>
                    
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>NIP</th>
                                <th>Nama</th>
                                <th>Departemen</th>
                                <th>Jam Masuk</th>
                                <th>Jam Keluar</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($attendanceList)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #7f8c8d;">
                                    Belum ada data absensi hari ini
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($attendanceList as $attendance): ?>
                                <tr>
                                    <td><?php echo $attendance['nip']; ?></td>
                                    <td><?php echo $attendance['name']; ?></td>
                                    <td><?php echo $attendance['department'] ?? '-'; ?></td>
                                    <td><?php echo $attendance['check_in'] ?: '-'; ?></td>
                                    <td><?php echo $attendance['check_out'] ?: '-'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $attendance['status']; ?>">
                                            <?php echo ucfirst($attendance['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                PT. CHICKDREAM MULYA JADI WONOSOBO - Dashboard Terintegrasi
            </div>
        </div>
    </div>
    <script>
        // Simple animation and interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth animations to cards
            const cards = document.querySelectorAll('.stat-card, .info-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.style.animation = 'fadeInUp 0.6s ease-out forwards';
            });
            
            // Add click events to action buttons
            const actionButtons = document.querySelectorAll('.btn-action');
            actionButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.classList.contains('btn-delete')) {
                        if (!confirm('Yakin ingin menghapus data ini?')) {
                            e.preventDefault();
                        }
                    }
                });
            });
        });
        
        // Add CSS for animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
            
            function closeSidebar() {
                sidebar.classList.remove('mobile-active', 'tablet-active');
                mainContent.classList.remove('sidebar-open');
                overlay.classList.remove('active');
            }
            
            // Handle touch swipe to close sidebar on mobile
            let startX = 0;
            let currentX = 0;
            let isDragging = false;
            
            sidebar.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
                isDragging = true;
            });
            
            sidebar.addEventListener('touchmove', function(e) {
                if (!isDragging) return;
                currentX = e.touches[0].clientX;
                const diffX = startX - currentX;
                
                if (diffX > 50 && window.innerWidth < 1024) {
                    closeSidebar();
                    isDragging = false;
                }
            });
            
            sidebar.addEventListener('touchend', function() {
                isDragging = false;
            });
        }
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initMobileSidebar();
            
            // Add responsive table wrapper
            const tables = document.querySelectorAll('.attendance-table');
            tables.forEach(table => {
                if (!table.parentElement.classList.contains('responsive-table-wrapper')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'responsive-table-wrapper';
                    table.parentNode.insertBefore(wrapper, table);
                    wrapper.appendChild(table);
                }
            });
        });
        
        // Responsive utilities
        function isMobile() {
            return window.innerWidth < 768;
        }
        
        function isTablet() {
            return window.innerWidth >= 768 && window.innerWidth < 1024;
        }
        
        function isDesktop() {
            return window.innerWidth >= 1024;
        }
        
        // Optimize touch interactions on mobile
        if ('ontouchstart' in window) {
            document.body.classList.add('touch-device');
        }
    </script>
</body>
</html>
