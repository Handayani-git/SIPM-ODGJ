<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "koneksi.php";


// =====================================================
// AMBIL ID USER YANG SEDANG LOGIN
// =====================================================

$id_user = (int) $_SESSION['id_user'];


// =====================================================
// AMBIL DATA USER
// =====================================================

$query = mysqli_query(
    $conn,
    "SELECT
        id_user,
        nama_lengkap,
        username,
        role,
        status,
        created_at
     FROM users
     WHERE id_user = $id_user
     LIMIT 1"
);

if (!$query) {
    die("Query gagal: " . mysqli_error($conn));
}

$user = mysqli_fetch_assoc($query);

if (!$user) {
    die("Data user tidak ditemukan.");
}


// =====================================================
// PROSES UPDATE PROFIL
// =====================================================

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? '';
    $konfirmasi   = $_POST['konfirmasi_password'] ?? '';


    // =================================================
    // VALIDASI
    // =================================================

    if ($nama_lengkap === '') {

        $error = "Nama lengkap wajib diisi.";

    } elseif ($username === '') {

        $error = "Username wajib diisi.";

    } else {


        // =============================================
        // CEK USERNAME
        // =============================================

        $username_safe = mysqli_real_escape_string(
            $conn,
            $username
        );

        $check_username = mysqli_query(
            $conn,
            "SELECT id_user
             FROM users
             WHERE username = '$username_safe'
             AND id_user != $id_user
             LIMIT 1"
        );

        if (!$check_username) {

            $error = "Gagal memeriksa username.";

        } elseif (mysqli_num_rows($check_username) > 0) {

            $error = "Username sudah digunakan oleh user lain.";

        } else {


            // =========================================
            // UPDATE TANPA PASSWORD
            // =========================================

            if ($password === '') {

                $nama_safe = mysqli_real_escape_string(
                    $conn,
                    $nama_lengkap
                );

                $update = mysqli_query(
                    $conn,
                    "UPDATE users
                     SET
                        nama_lengkap = '$nama_safe',
                        username = '$username_safe'
                     WHERE id_user = $id_user"
                );

            } else {


                // =====================================
                // VALIDASI PASSWORD
                // =====================================

                if (strlen($password) < 6) {

                    $error =
                        "Password baru minimal 6 karakter.";

                } elseif ($password !== $konfirmasi) {

                    $error =
                        "Konfirmasi password tidak sama.";

                } else {


                    // =================================
                    // HASH PASSWORD
                    // =================================

                    $password_hash =
                        password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                    $password_safe =
                        mysqli_real_escape_string(
                            $conn,
                            $password_hash
                        );

                    $nama_safe =
                        mysqli_real_escape_string(
                            $conn,
                            $nama_lengkap
                        );


                    $update = mysqli_query(
                        $conn,
                        "UPDATE users
                         SET
                            nama_lengkap = '$nama_safe',
                            username = '$username_safe',
                            password = '$password_safe'
                         WHERE id_user = $id_user"
                    );

                }
            }


            // =========================================
            // HASIL UPDATE
            // =========================================

            if (
                $error === "" &&
                isset($update)
            ) {

                if ($update) {

                    // Update session jika nama/username berubah
                    $_SESSION['nama_lengkap'] =
                        $nama_lengkap;

                    $_SESSION['username'] =
                        $username;


                    // Redirect agar form tidak submit ulang
                    header(
                        "Location: profil.php?success=updated"
                    );

                    exit;

                } else {

                    $error =
                        "Gagal menyimpan perubahan: " .
                        mysqli_error($conn);

                }
            }
        }
    }
}


// =====================================================
// DATA UNTUK DITAMPILKAN
// =====================================================

$nama_tampil =
    $user['nama_lengkap'] ?? '';

$username_tampil =
    $user['username'] ?? '';

$role_tampil =
    $user['role'] ?? '';

$status_tampil =
    $user['status'] ?? '';

$created_at =
    $user['created_at'] ?? '';

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Edit Profil - Sistem Informasi ODGJ
    </title>


    <!-- GOOGLE FONT -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- BOOTSTRAP ICON -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- DASHBOARD CSS -->

    <link
        rel="stylesheet"
        href="/SIPM-ODGJ/assets/css/dashboard.css"
    >


    <style>

        /* =====================================================
           PAGE
        ===================================================== */

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100%;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f7f9fc;
            color: #26334a;
            overflow-x: hidden;
        }


        /* =====================================================
           LAYOUT
        ===================================================== */

        .dashboard-layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }


        .sidebar {
            position: fixed !important;

            top: 0 !important;
            left: 0 !important;
            bottom: 0 !important;

            width: 208px !important;
            min-width: 208px !important;

            height: 100vh !important;

            z-index: 1000;
        }


        .main-content {
            width: calc(100% - 208px) !important;

            margin-left: 208px !important;

            min-height: 100vh;

            box-sizing: border-box;
        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {
            width: 100%;

            height: 76px;
            min-height: 76px;

            padding: 0 32px;

            box-sizing: border-box;

            display: flex;
            align-items: center;
            justify-content: space-between;

            background: #ffffff;

            border-bottom: 1px solid #edf0f5;
        }


        .topbar h1 {
            margin: 0;

            font-size: 20px;
            font-weight: 600;

            color: #17243a;
        }


        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }


        .profile-avatar {
            width: 40px;
            height: 40px;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #eaf1ff;

            color: #2864e6;

            font-size: 14px;
            font-weight: 700;

            text-decoration: none;

            border: 2px solid #d7e4ff;

            box-sizing: border-box;

            transition: all 0.2s ease;
        }


        .profile-avatar:hover {
            background: #2864e6;
            color: #ffffff;

            border-color: #2864e6;

            transform: translateY(-1px);
        }


        /* =====================================================
           CONTENT
        ===================================================== */

        .page-content {
            padding: 28px;
        }


        .page-header {
            margin-bottom: 24px;
        }


        .page-title h2 {
            margin: 0;

            font-size: 24px;
            font-weight: 700;

            color: #17243a;
        }


        .page-title p {
            margin: 6px 0 0;

            color: #7a8499;

            font-size: 13px;
        }


        /* =====================================================
           FORM CARD
        ===================================================== */

        .profile-card {
            width: 100%;

            max-width: 850px;

            background: #ffffff;

            border: 1px solid #e1e7f0;

            border-radius: 14px;

            overflow: hidden;
        }


        .profile-card-header {
            display: flex;

            align-items: center;

            gap: 12px;

            padding: 20px 24px;

            border-bottom: 1px solid #edf0f5;
        }


        .profile-card-icon {
            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 10px;

            background: #eaf1ff;

            color: #2864e6;

            font-size: 17px;
        }


        .profile-card-header h3 {
            margin: 0;

            color: #17243a;

            font-size: 16px;

            font-weight: 600;
        }


        .profile-card-header p {
            margin: 3px 0 0;

            color: #8993a6;

            font-size: 10px;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .profile-form {
            padding: 26px 24px 24px;
        }


        .form-group {
            margin-bottom: 20px;
        }


        .form-group label {
            display: block;

            margin-bottom: 8px;

            color: #344054;

            font-size: 11px;

            font-weight: 600;
        }


        .form-group label span {
            color: #ef4444;
        }


        .input-wrapper {
            position: relative;
        }


        .input-wrapper i {
            position: absolute;

            left: 13px;
            top: 50%;

            transform: translateY(-50%);

            color: #98a2b3;

            font-size: 14px;

            pointer-events: none;
        }


        .form-control {
            width: 100%;

            box-sizing: border-box;

            padding: 11px 13px 11px 38px;

            border: 1px solid #dce3ee;

            border-radius: 8px;

            outline: none;

            background: #ffffff;

            color: #26334a;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;

            transition: all 0.2s ease;
        }


        .form-control:focus {
            border-color: #2864e6;

            box-shadow:
                0 0 0 3px
                rgba(40, 100, 230, 0.08);
        }


        .form-control::placeholder {
            color: #a4adbc;
        }


        .form-help {
            display: block;

            margin-top: 6px;

            color: #98a2b3;

            font-size: 9px;
        }


        /* =====================================================
           READ ONLY INFORMATION
        ===================================================== */

        .account-information {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 14px;

            margin-top: 8px;

            margin-bottom: 24px;

            padding: 16px;

            background: #f8fafc;

            border: 1px solid #edf0f5;

            border-radius: 10px;
        }


        .info-item {
            display: flex;

            flex-direction: column;

            gap: 4px;
        }


        .info-label {
            color: #8993a6;

            font-size: 9px;

            font-weight: 500;
        }


        .info-value {
            color: #344054;

            font-size: 11px;

            font-weight: 600;
        }


        .role-badge {
            display: inline-flex;

            width: fit-content;

            align-items: center;

            padding: 5px 10px;

            border-radius: 20px;

            background: #fff0d9;

            color: #d97706;

            font-size: 9px;

            font-weight: 600;
        }


        .status-badge {
            display: inline-flex;

            width: fit-content;

            align-items: center;

            padding: 5px 10px;

            border-radius: 20px;

            background: #dcf8e8;

            color: #159447;

            font-size: 9px;

            font-weight: 600;
        }


        /* =====================================================
           PASSWORD SECTION
        ===================================================== */

        .section-divider {
            margin: 25px 0;

            border: 0;

            border-top: 1px solid #edf0f5;
        }


        .section-title {
            margin: 0 0 4px;

            color: #17243a;

            font-size: 13px;

            font-weight: 600;
        }


        .section-description {
            margin: 0 0 18px;

            color: #8993a6;

            font-size: 10px;
        }


        .password-wrapper {
            position: relative;
        }


        .password-wrapper .form-control {
            padding-right: 42px;
        }


        .toggle-password {
            position: absolute;

            right: 10px;
            top: 50%;

            transform: translateY(-50%);

            width: 30px;
            height: 30px;

            border: none;

            background: transparent;

            color: #98a2b3;

            cursor: pointer;

            border-radius: 6px;
        }


        .toggle-password:hover {
            background: #f1f5f9;

            color: #526078;
        }


        /* =====================================================
           ALERT
        ===================================================== */

        .alert {
            display: flex;

            align-items: flex-start;

            gap: 9px;

            padding: 11px 13px;

            margin-bottom: 20px;

            border-radius: 8px;

            font-size: 10px;

            line-height: 1.5;
        }


        .alert i {
            font-size: 14px;

            margin-top: 1px;
        }


        .alert-error {
            background: #fff0f0;

            border: 1px solid #ffd2d2;

            color: #c53030;
        }


        /* =====================================================
           FORM ACTIONS
        ===================================================== */

        .form-actions {
            display: flex;

            justify-content: flex-end;

            align-items: center;

            gap: 10px;

            margin-top: 28px;

            padding-top: 20px;

            border-top: 1px solid #edf0f5;
        }


        .btn {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            min-width: 105px;

            padding: 10px 17px;

            border-radius: 8px;

            font-family: 'Poppins', sans-serif;

            font-size: 11px;

            font-weight: 500;

            text-decoration: none;

            cursor: pointer;

            box-sizing: border-box;

            transition: all 0.2s ease;
        }


        .btn-cancel {
            background: #ffffff;

            border: 1px solid #dce3ee;

            color: #526078;
        }


        .btn-cancel:hover {
            background: #f7f9fc;
        }


        .btn-save {
            background: #2864e6;

            border: 1px solid #2864e6;

            color: #ffffff;
        }


        .btn-save:hover {
            background: #1f56cc;

            border-color: #1f56cc;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 900px) {

            .sidebar {
                width: 180px !important;
                min-width: 180px !important;
            }

            .main-content {
                width: calc(100% - 180px) !important;
                margin-left: 180px !important;
            }

        }


        @media (max-width: 700px) {

            .sidebar {
                display: none !important;
            }

            .main-content {
                width: 100% !important;
                margin-left: 0 !important;
            }

            .page-content {
                padding: 18px;
            }

            .account-information {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 500px) {

            .topbar {
                padding: 0 18px;
            }

            .page-content {
                padding: 15px;
            }

            .profile-form {
                padding: 20px 17px;
            }

            .profile-card-header {
                padding: 17px;
            }

            .form-actions {
                flex-direction: column-reverse;

                align-items: stretch;
            }

            .btn {
                width: 100%;
            }

        }

    </style>

</head>


<body>

<div class="dashboard-layout">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">

        <div class="sidebar-brand">

            <img
                src="/SIPM-ODGJ/assets/img/logo YCKA.png"
                alt="Logo Yayasan Cahaya Kasih Amanah"
                class="sidebar-logo"
            >

            <h2>SIPM ODGJ</h2>

            <p>
                Yayasan Cahaya Kasih Amanah
            </p>

        </div>


        <nav class="sidebar-menu">

            <a
                href="dashboard.php"
                class="menu-item"
            >
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>


            <a
                href="data_pasien.php"
                class="menu-item"
            >
                <i class="bi bi-people"></i>
                <span>Data Pasien</span>
            </a>


            <a
                href="data_user.php"
                class="menu-item"
            >
                <i class="bi bi-person-gear"></i>
                <span>Data User</span>
            </a>


            <a
                href="monitoring.php"
                class="menu-item"
            >
                <i class="bi bi-clipboard2-pulse"></i>
                <span>Monitoring</span>
            </a>


            <a
                href="laporan.php"
                class="menu-item"
            >
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Laporan</span>
            </a>

        </nav>


        <div class="sidebar-bottom">

            <a
                href="logout.php"
                class="logout-button"
            >
                <i class="bi bi-box-arrow-left"></i>
                <span>Logout</span>
            </a>

        </div>

    </aside>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">

            <h1>
                Edit Profil
            </h1>


            <div class="topbar-right">

                <a
                    href="profil.php"
                    class="profile-avatar"
                    title="Profil Saya"
                >
                    <?= strtoupper(
                        substr(
                            $nama_tampil ?: 'A',
                            0,
                            1
                        )
                    ); ?>
                </a>

            </div>

        </header>


        <!-- =================================================
             PAGE CONTENT
        ================================================== -->

        <div class="page-content">


            <!-- PAGE HEADER -->

            <div class="page-header">

                <div class="page-title">

                    <h2>
                        Edit Profil
                    </h2>

                    <p>
                        Perbarui informasi akun Anda
                    </p>

                </div>

            </div>


            <!-- =================================================
                 PROFILE CARD
            ================================================== -->

            <div class="profile-card">


                <!-- CARD HEADER -->

                <div class="profile-card-header">

                    <div class="profile-card-icon">

                        <i class="bi bi-person-gear"></i>

                    </div>

                    <div>

                        <h3>
                            Informasi Profil
                        </h3>

                        <p>
                            Kelola informasi akun yang sedang digunakan
                        </p>

                    </div>

                </div>


                <!-- FORM -->

                <form
                    method="POST"
                    action=""
                    class="profile-form"
                >


                    <?php if ($error !== ''): ?>

                        <div class="alert alert-error">

                            <i class="bi bi-exclamation-circle"></i>

                            <div>
                                <?= htmlspecialchars($error); ?>
                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- NAMA LENGKAP -->

                    <div class="form-group">

                        <label for="nama_lengkap">
                            Nama Lengkap
                            <span>*</span>
                        </label>


                        <div class="input-wrapper">

                            <i class="bi bi-person"></i>

                            <input
                                type="text"
                                id="nama_lengkap"
                                name="nama_lengkap"
                                class="form-control"
                                value="<?= htmlspecialchars($nama_tampil); ?>"
                                placeholder="Masukkan nama lengkap"
                                autocomplete="name"
                                required
                            >

                        </div>

                    </div>


                    <!-- USERNAME -->

                    <div class="form-group">

                        <label for="username">
                            Username
                            <span>*</span>
                        </label>


                        <div class="input-wrapper">

                            <i class="bi bi-at"></i>

                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-control"
                                value="<?= htmlspecialchars($username_tampil); ?>"
                                placeholder="Masukkan username"
                                autocomplete="username"
                                required
                            >

                        </div>

                        <small class="form-help">
                            Username digunakan untuk login ke sistem.
                        </small>

                    </div>


                    <!-- INFORMASI AKUN -->

                    <div class="account-information">


                        <div class="info-item">

                            <span class="info-label">
                                Role
                            </span>

                            <span class="role-badge">

                                <i
                                    class="bi bi-shield-check"
                                    style="margin-right:5px;"
                                ></i>

                                <?= htmlspecialchars(
                                    ucfirst($role_tampil)
                                ); ?>

                            </span>

                        </div>


                        <div class="info-item">

                            <span class="info-label">
                                Status Akun
                            </span>

                            <span class="status-badge">

                                <i
                                    class="bi bi-check-circle"
                                    style="margin-right:5px;"
                                ></i>

                                <?= htmlspecialchars(
                                    ucfirst($status_tampil)
                                ); ?>

                            </span>

                        </div>


                    </div>


                    <!-- PASSWORD -->

                    <hr class="section-divider">


                    <h3 class="section-title">
                        Ubah Password
                    </h3>

                    <p class="section-description">
                        Kosongkan jika tidak ingin mengubah password.
                    </p>


                    <!-- PASSWORD BARU -->

                    <div class="form-group">

                        <label for="password">
                            Password Baru
                        </label>


                        <div class="password-wrapper">

                            <div class="input-wrapper">

                                <i class="bi bi-lock"></i>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="Masukkan password baru"
                                    autocomplete="new-password"
                                >

                            </div>


                            <button
                                type="button"
                                class="toggle-password"
                                data-target="password"
                                title="Tampilkan password"
                            >

                                <i class="bi bi-eye"></i>

                            </button>

                        </div>


                        <small class="form-help">
                            Minimal 6 karakter.
                        </small>

                    </div>


                    <!-- KONFIRMASI PASSWORD -->

                    <div class="form-group">

                        <label for="konfirmasi_password">
                            Konfirmasi Password Baru
                        </label>


                        <div class="password-wrapper">

                            <div class="input-wrapper">

                                <i class="bi bi-lock-fill"></i>

                                <input
                                    type="password"
                                    id="konfirmasi_password"
                                    name="konfirmasi_password"
                                    class="form-control"
                                    placeholder="Ulangi password baru"
                                    autocomplete="new-password"
                                >

                            </div>


                            <button
                                type="button"
                                class="toggle-password"
                                data-target="konfirmasi_password"
                                title="Tampilkan password"
                            >

                                <i class="bi bi-eye"></i>

                            </button>

                        </div>

                    </div>


                    <!-- ACTION -->

                    <div class="form-actions">

                        <a
                            href="profil.php"
                            class="btn btn-cancel"
                        >

                            <i class="bi bi-arrow-left"></i>

                            Batal

                        </a>


                        <button
                            type="submit"
                            class="btn btn-save"
                        >

                            <i class="bi bi-check-lg"></i>

                            Simpan Perubahan

                        </button>

                    </div>


                </form>

            </div>

        </div>

    </main>

</div>


<!-- =====================================================
     PASSWORD TOGGLE
====================================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const toggleButtons =
            document.querySelectorAll(
                '.toggle-password'
            );


        toggleButtons.forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        const targetId =
                            this.getAttribute(
                                'data-target'
                            );

                        const input =
                            document.getElementById(
                                targetId
                            );

                        const icon =
                            this.querySelector(
                                'i'
                            );


                        if (!input || !icon) {
                            return;
                        }


                        if (
                            input.type ===
                            'password'
                        ) {

                            input.type = 'text';

                            icon.className =
                                'bi bi-eye-slash';

                            this.title =
                                'Sembunyikan password';

                        } else {

                            input.type =
                                'password';

                            icon.className =
                                'bi bi-eye';

                            this.title =
                                'Tampilkan password';

                        }

                    }
                );

            }
        );

    }
);

</script>


</body>

</html>