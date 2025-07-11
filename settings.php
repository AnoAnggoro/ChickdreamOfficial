<?php
require_once 'functions.php';
requireLogin();
requirePermission('settings', 'lihat');

// Handle settings update
if ($_POST && isset($_POST['update_settings'])) {
    global $pdo;
    
    foreach ($_POST as $key => $value) {
        if ($key !== 'update_settings') {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
    }
    
    header('Location: settings.php?success=1');
    exit;
}

// Get current settings - fix the FETCH_KEY_PAIR query
global $pdo;
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings ORDER BY setting_key");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - PT. CHICKDREAM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php echo renderSidebar('settings'); ?>
        
        <div class="main-content">
            <?php echo renderTopBar('Pengaturan Sistem'); ?>
            
            <div class="content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        Pengaturan berhasil disimpan!
                    </div>
                <?php endif; ?>

                <form method="POST" class="settings-form">
                    <!-- Company Information -->
                    <div class="form-container">
                        <div class="form-header">
                            <h4>🏢 Informasi Perusahaan</h4>
                        </div>
                        
                        <div class="form-grid-compact">
                            <div class="form-group">
                                <label>Nama Perusahaan</label>
                                <input type="text" name="company_name" class="form-control" 
                                       value="<?php echo $settings['company_name'] ?? 'PT. CHICKDREAM ABADI WONOSOBO'; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Alamat Perusahaan</label>
                                <textarea name="company_address" class="form-control" rows="3" required><?php echo $settings['company_address'] ?? 'Jl. Raya Wonosobo No. 123, Wonosobo, Jawa Tengah'; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row-compact">
                            <div class="form-group">
                                <label>Nomor Telepon</label>
                                <input type="text" name="company_phone" class="form-control" 
                                       value="<?php echo $settings['company_phone'] ?? '0286-123456'; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email Perusahaan</label>
                                <input type="email" name="company_email" class="form-control" 
                                       value="<?php echo $settings['company_email'] ?? 'info@chickdream.com'; ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Working Hours -->
                    <div class="form-container">
                        <div class="form-header">
                            <h4>⏰ Jam Kerja</h4>
                        </div>
                        
                        <div class="form-row-compact">
                            <div class="form-group">
                                <label>Jam Masuk</label>
                                <input type="time" name="working_hours_start" class="form-control" 
                                       value="<?php echo $settings['working_hours_start'] ?? '08:00'; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Jam Pulang</label>
                                <input type="time" name="working_hours_end" class="form-control" 
                                       value="<?php echo $settings['working_hours_end'] ?? '17:00'; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row-compact">
                            <div class="form-group">
                                <label>Maksimal Terlambat (menit)</label>
                                <input type="number" name="max_late_minutes" class="form-control" 
                                       value="<?php echo $settings['max_late_minutes'] ?? '15'; ?>" min="0" max="60" required>
                                <small>Batas toleransi keterlambatan sebelum dianggap alpha</small>
                            </div>
                            <div class="form-group">
                                <label>Rate Lembur (per jam)</label>
                                <input type="number" name="overtime_rate" class="form-control" step="0.1" 
                                       value="<?php echo $settings['overtime_rate'] ?? '1.5'; ?>" min="1" max="3" required>
                                <small>Pengali gaji untuk lembur (contoh: 1.5 = 150%)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Payroll Settings -->
                    <div class="form-container">
                        <div class="form-header">
                            <h4>💰 Pengaturan Payroll</h4>
                        </div>
                        
                        <div class="form-row-compact">
                            <div class="form-group">
                                <label>Persentase Tunjangan (%)</label>
                                <input type="number" name="allowance_rate" class="form-control" step="0.1" 
                                       value="<?php echo $settings['allowance_rate'] ?? '10'; ?>" min="0" max="50" required>
                                <small>Persentase tunjangan dari gaji pokok</small>
                            </div>
                            <div class="form-group">
                                <label>Persentase Pajak (%)</label>
                                <input type="number" name="tax_rate" class="form-control" step="0.1" 
                                       value="<?php echo $settings['tax_rate'] ?? '5'; ?>" min="0" max="30" required>
                                <small>Persentase pajak yang dipotong dari gaji</small>
                            </div>
                        </div>
                        
                        <div class="form-row-compact">
                            <div class="form-group">
                                <label>Persentase Potongan (%)</label>
                                <input type="number" name="deduction_rate" class="form-control" step="0.1" 
                                       value="<?php echo $settings['deduction_rate'] ?? '2'; ?>" min="0" max="10">
                                <small>Persentase potongan lain-lain dari gaji pokok</small>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Cut-off Payroll</label>
                                <input type="number" name="payroll_cutoff_date" class="form-control" 
                                       value="<?php echo $settings['payroll_cutoff_date'] ?? '25'; ?>" min="1" max="31">
                                <small>Tanggal cut-off untuk perhitungan gaji bulanan</small>
                            </div>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div class="form-container">
                        <div class="form-header">
                            <h4>⚙️ Pengaturan Sistem</h4>
                        </div>
                        
                        <div class="form-row-compact">
                            <div class="form-group">
                                <label>Backup Otomatis</label>
                                <select name="backup_auto" class="form-control">
                                    <option value="1" <?php echo ($settings['backup_auto'] ?? '1') == '1' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="0" <?php echo ($settings['backup_auto'] ?? '1') == '0' ? 'selected' : ''; ?>>Tidak Aktif</option>
                                </select>
                                <small>Backup database otomatis setiap hari</small>
                            </div>
                            <div class="form-group">
                                <label>Periode Backup (hari)</label>
                                <input type="number" name="backup_period" class="form-control" 
                                       value="<?php echo $settings['backup_period'] ?? '7'; ?>" min="1" max="30">
                                <small>Interval backup otomatis dalam hari</small>
                            </div>
                        </div>
                        
                        <div class="form-row-compact">
                            <div class="form-group">
                                <label>Session Timeout (menit)</label>
                                <input type="number" name="session_timeout" class="form-control" 
                                       value="<?php echo $settings['session_timeout'] ?? '120'; ?>" min="30" max="480">
                                <small>Waktu logout otomatis jika tidak ada aktivitas</small>
                            </div>
                            <div class="form-group">
                                <label>Maksimal Login Gagal</label>
                                <input type="number" name="max_login_attempts" class="form-control" 
                                       value="<?php echo $settings['max_login_attempts'] ?? '5'; ?>" min="3" max="10">
                                <small>Maksimal percobaan login sebelum akun dikunci</small>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="form-container">
                        <div class="form-header">
                            <h4>🔔 Pengaturan Notifikasi</h4>
                        </div>
                        
                        <div class="form-row-compact">
                            <div class="form-group">
                                <label>Notifikasi Kontrak Berakhir (hari)</label>
                                <input type="number" name="contract_expiry_notification" class="form-control" 
                                       value="<?php echo $settings['contract_expiry_notification'] ?? '30'; ?>" min="7" max="90">
                                <small>Notifikasi sebelum kontrak berakhir</small>
                            </div>
                            <div class="form-group">
                                <label>Notifikasi Ulang Tahun (hari)</label>
                                <input type="number" name="birthday_notification" class="form-control" 
                                       value="<?php echo $settings['birthday_notification'] ?? '7'; ?>" min="1" max="30">
                                <small>Notifikasi sebelum ulang tahun pegawai</small>
                            </div>
                        </div>
                        
                        <div class="form-row-compact">
                            <div class="form-group">
                                <label>Email Notifikasi</label>
                                <select name="email_notifications" class="form-control">
                                    <option value="1" <?php echo ($settings['email_notifications'] ?? '1') == '1' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="0" <?php echo ($settings['email_notifications'] ?? '1') == '0' ? 'selected' : ''; ?>>Tidak Aktif</option>
                                </select>
                                <small>Kirim notifikasi via email</small>
                            </div>
                            <div class="form-group">
                                <label>SMTP Server</label>
                                <input type="text" name="smtp_server" class="form-control" 
                                       value="<?php echo $settings['smtp_server'] ?? 'smtp.gmail.com'; ?>">
                                <small>Server SMTP untuk mengirim email</small>
                            </div>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div class="form-container">
                        <div class="form-header">
                            <h4>🔒 Pengaturan Keamanan</h4>
                        </div>
                        
                        <div class="form-row-compact">
                            <div class="form-group">
                                <label>Minimum Panjang Password</label>
                                <input type="number" name="min_password_length" class="form-control" 
                                       value="<?php echo $settings['min_password_length'] ?? '8'; ?>" min="6" max="20">
                                <small>Panjang minimum password untuk user baru</small>
                            </div>
                            <div class="form-group">
                                <label>Password Kompleks</label>
                                <select name="password_complexity" class="form-control">
                                    <option value="1" <?php echo ($settings['password_complexity'] ?? '1') == '1' ? 'selected' : ''; ?>>Wajib</option>
                                    <option value="0" <?php echo ($settings['password_complexity'] ?? '1') == '0' ? 'selected' : ''; ?>>Tidak Wajib</option>
                                </select>
                                <small>Wajib menggunakan huruf besar, kecil, angka, dan simbol</small>
                            </div>
                        </div>
                        
                        <div class="form-row-compact">
                            <div class="form-group">
                                <label>Audit Log</label>
                                <select name="audit_log" class="form-control">
                                    <option value="1" <?php echo ($settings['audit_log'] ?? '1') == '1' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="0" <?php echo ($settings['audit_log'] ?? '1') == '0' ? 'selected' : ''; ?>>Tidak Aktif</option>
                                </select>
                                <small>Catat semua aktivitas user dalam sistem</small>
                            </div>
                            <div class="form-group">
                                <label>Periode Simpan Log (hari)</label>
                                <input type="number" name="log_retention_days" class="form-control" 
                                       value="<?php echo $settings['log_retention_days'] ?? '90'; ?>" min="30" max="365">
                                <small>Berapa lama log disimpan sebelum dihapus otomatis</small>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-container">
                        <div class="form-actions">
                            <?php if (hasPermission('settings', 'edit')): ?>
                                <button type="submit" name="update_settings" class="btn-primary">💾 Simpan Pengaturan</button>
                            <?php endif; ?>
                            <button type="reset" class="btn-secondary">🔄 Reset</button>
                            <?php if (hasPermission('settings', 'backup')): ?>
                                <button type="button" class="btn-add" onclick="createBackup()">📦 Backup Sekarang</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="footer">
                PT. CHICKDREAM MULYA JADI WONOSOBO
            </div>
        </div>
    </div>

    <script>
        function createBackup() {
            if (confirm('Yakin ingin membuat backup database sekarang?')) {
                // This would typically make an AJAX call to a backup script
                alert('Backup sedang diproses...');
                window.location.href = 'system-admin.php?action=backup';
            }
        }
        
        // Auto-save notification
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.settings-form');
            let hasChanges = false;
            
            form.addEventListener('change', function() {
                hasChanges = true;
            });
            
            window.addEventListener('beforeunload', function(e) {
                if (hasChanges) {
                    e.preventDefault();
                    e.returnValue = 'Ada perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?';
                }
            });
            
            form.addEventListener('submit', function() {
                hasChanges = false;
            });
        });
    </script>
</body>
</html>
