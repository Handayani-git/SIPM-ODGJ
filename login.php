<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Sistem Informasi ODGJ</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="/SIPM-ODGJ/assets/css/style.css">
</head>

<body>

    <main class="login-page">

        <!-- BAGIAN KIRI -->
        <section class="login-left">

            <div class="illustration-wrapper">
                <img 
                    src="assets/img/login-illustration.png" 
                    alt="Ilustrasi Sistem Informasi ODGJ"
                    class="login-illustration"
                >
            </div>

            <div class="information-card">

                <h2>
                    Dedikasi Untuk Kesehatan
                    <br>
                    Mental
                </h2>

                <p>
                    Mendukung pemulihan dan monitoring pasien dengan
                    sistem yang terintegrasi, transparan, dan penuh kasih
                    amanah.
                </p>

                <div class="security-badge">
                    <i class="bi bi-check-circle-fill"></i>
                    TERAKREDITASI & AMAN
                </div>

            </div>

        </section>


        <!-- BAGIAN KANAN -->
        <section class="login-right">

            <div class="login-card">

                <!-- LOGO -->
                <div class="logo-wrapper">

                    <img 
                        src="assets/img/logo YCKA.png"
                        alt="Logo Yayasan Cahaya Kasih Amanah"
                        class="logo-ycka"
                    >

                </div>


                <!-- JUDUL -->
                <div class="login-title">

                    <h1>
                        Sistem Informasi Pendataan dan
                        Monitoring ODGJ
                    </h1>

                </div>


                <!-- FORM LOGIN -->
                <form action="proses_login.php" method="POST">

                    <!-- USERNAME -->
                    <div class="form-group">

                        <label for="username">
                            Nama Pengguna
                        </label>

                        <div class="input-wrapper">

                            <i class="bi bi-person"></i>

                            <input
                                type="text"
                                id="username"
                                name="username"
                                placeholder="Masukkan username"
                                required
                            >

                        </div>

                    </div>


                    <!-- PASSWORD -->
                    <div class="form-group">

                        <div class="password-label">

                            <label for="password">
                                Kata Sandi
                            </label>

                            <a href="#">
                                Lupa Sandi?
                            </a>

                        </div>

                        <div class="input-wrapper">

                            <i class="bi bi-lock"></i>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Masukkan kata sandi"
                                required
                            >

                            <button
                                type="button"
                                class="toggle-password"
                                onclick="togglePassword()"
                            >
                                <i class="bi bi-eye" id="eyeIcon"></i>
                            </button>

                        </div>

                    </div>


                    <!-- REMEMBER ME -->
                    <div class="remember-me">

                        <label>

                            <input
                                type="checkbox"
                                name="remember"
                            >

                            <span>
                                Ingat saya di perangkat ini
                            </span>

                        </label>

                    </div>


                    <!-- BUTTON LOGIN -->
                    <button
                        type="submit"
                        class="login-button"
                    >

                        Masuk ke Sistem

                        <i class="bi bi-arrow-right"></i>

                    </button>

                </form>


                <!-- SECURITY INFO -->
                <div class="security-info">

                    <i class="bi bi-shield-lock"></i>

                    <span>
                        SISTEM KEAMANAN TERENKRIPSI AES-256
                    </span>

                </div>


                <!-- COPYRIGHT -->
                <div class="copyright">

                    © 2026 Yayasan Cahaya Kasih Amanah
                    <br>
                    Sistem Informasi Pendataan & Monitoring ODGJ

                </div>

            </div>


            <!-- HELP -->
            <div class="help-text">

                Mengalami kendala?

                <a href="#">
                    Hubungi Administrator
                </a>

            </div>

        </section>

    </main>


    <!-- JAVASCRIPT -->
    <script>

        function togglePassword() {

            const passwordInput = document.getElementById("password");
            const eyeIcon = document.getElementById("eyeIcon");

            if (passwordInput.type === "password") {

                passwordInput.type = "text";

                eyeIcon.classList.remove("bi-eye");
                eyeIcon.classList.add("bi-eye-slash");

            } else {

                passwordInput.type = "password";

                eyeIcon.classList.remove("bi-eye-slash");
                eyeIcon.classList.add("bi-eye");

            }

        }

    </script>

</body>
</html>