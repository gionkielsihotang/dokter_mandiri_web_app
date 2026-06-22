<?php
session_start();

// Jika pengguna sudah login, langsung alihkan ke halaman dashboard
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}

// Menangkap pesan error dari proses_login.php (jika ada)
$error_message = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dokter Mandiri</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h2>Dokter Mandiri</h2>
            
            <?php if (!empty($error_message)): ?>
                <div style="color: #dc3545; background-color: #fff; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 0.85rem; text-align: center; font-weight: 600; border: 1px solid #dc3545;">
                    <?php 
                        if ($error_message === 'invalid') {
                            echo "Username atau password salah!";
                        } elseif ($error_message === 'empty') {
                            echo "Semua kolom wajib diisi!";
                        } else {
                            echo htmlspecialchars($error_message);
                        }
                    ?>
                </div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="../proses/proses_login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           placeholder="Masukkan username" 
                           pattern="^[a-zA-Z0-9_]{3,30}$" 
                           title="Username hanya boleh berisi huruf, angka, underscore, dan panjang 3-30 karakter."
                           required autocomplete="off">
                </div>
                <div class="form-group" style="position: relative;">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Masukkan password" required>
                    <i class="fa-solid fa-eye" id="togglePassword" style="position: absolute; right: 12px; bottom: 10px; cursor: pointer; color: #7f8c8d;"></i>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
            <p style="text-align: center; margin-top: 1rem; font-size: 0.85rem; color: #7f8c8d;">
                By Kelompok 3 - Dokter Mandiri
            </p>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const togglePassword = document.querySelector("#togglePassword");
            const password = document.querySelector("#password");
            const form = document.querySelector("#loginForm");

            // Fungsionalitas menyembunyikan/menampilkan password
            if (togglePassword && password) {
                togglePassword.addEventListener("click", function () {
                    const type = password.getAttribute("type") === "password" ? "text" : "password";
                    password.setAttribute("type", type);
                    
                    // Toggle ikon mata
                    this.classList.toggle("fa-eye");
                    this.classList.toggle("fa-eye-slash");
                });
            }

            // Input Validation: Pembersihan spasi ekstra sebelum submit
            if (form) {
                form.addEventListener("submit", function(e) {
                    const usernameInput = document.querySelector("#username");
                    usernameInput.value = usernameInput.value.trim();
                });
            }
        });
    </script>
</body>
</html>