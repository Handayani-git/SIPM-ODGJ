<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "koneksi.php";

$role = $_SESSION['role'] ?? '';
$nama_user = $_SESSION['nama_lengkap'] ?? ($role === 'admin' ? 'Admin' : 'Petugas');
$role_label = ($role === 'admin') ? 'Admin' : 'Petugas';


/* =========================
   AMBIL DATA PASIEN
========================= */

$pasien_list = [];

$result_pasien = mysqli_query(
    $conn,
    "SELECT id_pasien, nama_pasien, nomor_registrasi
     FROM pasien
     ORDER BY nama_pasien ASC"
);

if ($result_pasien) {
    while ($row = mysqli_fetch_assoc($result_pasien)) {
        $pasien_list[] = $row;
    }
}


/* =========================
   PROSES TAMBAH USER
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? '';
    $role         = $_POST['role'] ?? '';
    $status       = $_POST['status'] ?? 'aktif';
    $id_pasien    = (int) ($_POST['id_pasien'] ?? 0);


    /* =========================
       VALIDASI
    ========================= */

    if (
        empty($nama_lengkap) ||
        empty($username) ||
        empty($password) ||
        empty($role)
    ) {

        $error = "Semua data wajib diisi.";

    } elseif ($role === 'keluarga' && $id_pasien <= 0) {

        $error = "Pasien wajib dipilih untuk akun keluarga.";

    } else {

        /* CEK USERNAME */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_user
             FROM users
             WHERE username = ?"
        );

        if (!$stmt) {
            $error = "Query pengecekan username gagal: " . mysqli_error($conn);
        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $username
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            $username_exists = mysqli_num_rows($result) > 0;

            mysqli_stmt_close($stmt);


            if ($username_exists) {

                $error = "Username sudah digunakan. Silakan gunakan username lain.";

            } else {

                /* =========================
                   MULAI TRANSACTION
                ========================= */

                mysqli_begin_transaction($conn);

                try {

                    /* =========================
                       SIMPAN USER
                    ========================= */

                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO users
                        (
                            nama_lengkap,
                            username,
                            password,
                            role,
                            status
                        )
                        VALUES (?, ?, ?, ?, ?)"
                    );

                    if (!$stmt) {
                        throw new Exception(
                            "Query user gagal dibuat: " . mysqli_error($conn)
                        );
                    }

                    mysqli_stmt_bind_param(
                        $stmt,
                        "sssss",
                        $nama_lengkap,
                        $username,
                        $password,
                        $role,
                        $status
                    );

                    if (!mysqli_stmt_execute($stmt)) {
                        $message = mysqli_stmt_error($stmt);
                        mysqli_stmt_close($stmt);
                        throw new Exception("Data user gagal disimpan: " . $message);
                    }

                    $new_user_id = mysqli_insert_id($conn);

                    mysqli_stmt_close($stmt);


                    /* =========================
                       HUBUNGKAN USER DENGAN PASIEN
                       KHUSUS ROLE KELUARGA
                    ========================= */

                    if ($role === 'keluarga') {

                        /* Pastikan pasien benar-benar ada */
                        $stmt = mysqli_prepare(
                            $conn,
                            "SELECT id_pasien
                             FROM pasien
                             WHERE id_pasien = ?
                             LIMIT 1"
                        );

                        if (!$stmt) {
                            throw new Exception(
                                "Query pasien gagal dibuat: " . mysqli_error($conn)
                            );
                        }

                        mysqli_stmt_bind_param(
                            $stmt,
                            "i",
                            $id_pasien
                        );

                        mysqli_stmt_execute($stmt);
                        $result_check_pasien = mysqli_stmt_get_result($stmt);
                        $pasien_exists = mysqli_num_rows($result_check_pasien) > 0;
                        mysqli_stmt_close($stmt);

                        if (!$pasien_exists) {
                            throw new Exception("Data pasien yang dipilih tidak ditemukan.");
                        }


                        /*
                         * Tabel keluarga menghubungkan akun login
                         * dengan pasien melalui id_user dan id_pasien.
                         * nama_keluarga mengikuti nama akun yang dibuat.
                         */
                        $stmt = mysqli_prepare(
                            $conn,
                            "INSERT INTO keluarga
                            (id_user, id_pasien, nama_keluarga)
                            VALUES (?, ?, ?)"
                        );

                        if (!$stmt) {
                            throw new Exception(
                                "Query relasi keluarga gagal dibuat: " . mysqli_error($conn)
                            );
                        }

                        mysqli_stmt_bind_param(
                            $stmt,
                            "iis",
                            $new_user_id,
                            $id_pasien,
                            $nama_lengkap
                        );

                        if (!mysqli_stmt_execute($stmt)) {
                            $message = mysqli_stmt_error($stmt);
                            mysqli_stmt_close($stmt);
                            throw new Exception(
                                "Akun berhasil dibuat, tetapi hubungan dengan pasien gagal disimpan: " . $message
                            );
                        }

                        mysqli_stmt_close($stmt);
                    }


                    /* =========================
                       SELESAI
                    ========================= */

                    mysqli_commit($conn);

                    header(
                        "Location: data_user.php?status=added"
                    );

                    exit;

                } catch (Exception $e) {

                    mysqli_rollback($conn);

                    $error = $e->getMessage();
                }
            }
        }
    }
}

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
        Tambah User - Sistem Informasi ODGJ
    </title>


    <!-- GOOGLE FONT -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
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

        .page-content {
            padding: 28px;
        }


        .page-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

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


        .back-button {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 11px 17px;

            border: 1px solid #dce3ee;

            border-radius: 8px;

            background: white;

            color: #526078;

            text-decoration: none;

            font-size: 13px;

            font-weight: 500;

        }


        .back-button:hover {

            border-color: #2864e6;

            color: #2864e6;

        }


        .form-card {

            background: white;

            border: 1px solid #e1e7f0;

            border-radius: 12px;

            padding: 24px;

        }


        .section-title {

            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 22px;

        }


        .section-icon {

            width: 34px;

            height: 34px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 8px;

            background: #eaf1ff;

            color: #2864e6;

        }


        .section-title h3 {

            margin: 0;

            font-size: 16px;

            font-weight: 600;

            color: #17243a;

        }


        .form-grid {

            display: grid;

            grid-template-columns: repeat(2, 1fr);

            gap: 20px 25px;

        }


        .form-group {

            display: flex;

            flex-direction: column;

        }


        .form-group.full-width {

            grid-column: 1 / -1;

        }


        .form-group label {

            margin-bottom: 8px;

            color: #26334a;

            font-size: 12px;

            font-weight: 500;

        }


        .required {

            color: #e53935;

        }


        .form-group input,
        .form-group select {

            width: 100%;

            box-sizing: border-box;

            padding: 12px 13px;

            border: 1px solid #dce3ee;

            border-radius: 8px;

            background: white;

            color: #26334a;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;

            outline: none;

        }


        .form-group input:focus,
        .form-group select:focus {

            border-color: #2864e6;

        }


        .password-wrapper {

            position: relative;

        }


        .password-wrapper input {

            padding-right: 42px;

        }


        .password-toggle {

            position: absolute;

            right: 12px;

            top: 50%;

            transform: translateY(-50%);

            border: none;

            background: transparent;

            color: #7a8499;

            cursor: pointer;

            font-size: 16px;

        }


        .form-actions {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 25px;

            padding-top: 20px;

            border-top: 1px solid #edf0f5;

        }


        .cancel-button,
        .save-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 11px 17px;

            border-radius: 8px;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;

            font-weight: 500;

            cursor: pointer;

            text-decoration: none;

        }


        .cancel-button {

            background: white;

            border: 1px solid #dce3ee;

            color: #526078;

        }


        .save-button {

            background: #2864e6;

            border: 1px solid #2864e6;

            color: white;

        }


        .save-button:hover {

            background: #1f55c8;

        }


        .error-message {

            margin-bottom: 20px;

            padding: 12px 15px;

            border-radius: 8px;

            background: #ffe5e5;

            color: #c62828;

            font-size: 12px;

        }


        @media (max-width: 800px) {

            .page-content {
                padding: 18px;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: auto;
            }

        }


        /* SIDEBAR FIX */

        .dashboard-layout .sidebar {

            width: 218px !important;

            min-width: 218px !important;

            max-width: 218px !important;

            height: 100vh !important;

            overflow: hidden !important;

            position: fixed !important;

            left: 0 !important;

            top: 0 !important;

            z-index: 1000 !important;

        }


        .dashboard-layout .sidebar-logo {

            width: 100% !important;

            height: auto !important;

            padding: 22px 10px 15px !important;

            margin: 0 !important;

            text-align: center !important;

            box-sizing: border-box !important;

        }


        .dashboard-layout .sidebar-logo img {

            display: block !important;

            width: 58px !important;

            height: 58px !important;

            max-width: 58px !important;

            max-height: 58px !important;

            object-fit: contain !important;

            margin: 0 auto 7px !important;

        }


        .dashboard-layout .sidebar-logo h2 {

            margin: 0 !important;

            font-size: 17px !important;

        }


        .dashboard-layout .sidebar-logo p {

            margin: 3px 0 0 !important;

            font-size: 9px !important;

        }


        .dashboard-layout .main-content {

            margin-left: 218px !important;

            width: calc(100% - 218px) !important;

            min-width: 0 !important;

        }

        /* =====================================================
           PROFILE TOPBAR - SAMA SEPERTI DASHBOARD
        ===================================================== */

        .topbar-profile {
            position: relative;
        }

        .profile-trigger {
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 8px;
            border-radius: 10px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: all 0.2s ease;
        }

        .profile-trigger:hover {
            background: #f4f7fc;
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            line-height: 1.2;
        }

        .profile-info strong {
            font-size: 12px;
            font-weight: 600;
            color: #17243a;
        }

        .profile-info span {
            font-size: 9px;
            color: #8993a6;
            margin-top: 2px;
        }

        .profile-avatar {
            width: 38px;
            height: 38px;
            min-width: 38px;
            min-height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #2864e6;
            border: none;
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 600;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        .profile-avatar:hover {
            background: #2864e6;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 100, 230, 0.15);
        }

        .profile-arrow {
            font-size: 10px;
            color: #8993a6;
            transition: transform 0.2s ease;
        }

        .profile-trigger.active .profile-arrow {
            transform: rotate(180deg);
        }

        /* =========================
           DROPDOWN PROFIL
        ========================= */

        .profile-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 220px;
            background: #ffffff;
            border: 1px solid #e1e7f0;
            border-radius: 12px;
            box-shadow: 0 12px 35px rgba(23, 36, 58, 0.14);
            z-index: 2000;
            overflow: hidden;
        }

        .profile-dropdown.show {
            display: block;
        }

        .profile-dropdown-header {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 12px;
            border-bottom: 1px solid #edf0f5;
        }

        .profile-avatar.large {
            width: 40px;
            height: 40px;
            min-width: 40px;
            min-height: 40px;
            background: #2864e6;
            color: #ffffff;
        }

        .profile-dropdown-header strong {
            display: block;
            color: #17243a;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .profile-dropdown-header span {
            display: block;
            margin-top: 3px;
            color: #7a8499;
            font-size: 9px;
        }

        .profile-divider {
            height: 1px;
            background: #edf0f5;
        }

        .profile-menu-item {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 10px 12px;
            border: none;
            border-radius: 0;
            background: transparent;
            color: #526078;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            font-size: 10px;
            font-weight: 500;
            cursor: pointer;
            box-sizing: border-box;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .profile-menu-item:hover {
            background: #f4f7fc;
            color: #2864e6;
        }

        .profile-menu-item i {
            font-size: 14px;
        }

        .profile-menu-item.logout {
            color: #e23b3b;
        }

        .profile-menu-item.logout:hover {
            background: #fff3f3;
            color: #e23b3b;
        }

    </style>

</head>


<body>


<div class="dashboard-layout">


    <!-- SIDEBAR -->

    <aside class="sidebar">


        <div class="sidebar-logo">

            <img
                src="/SIPM-ODGJ/assets/img/logo YCKA.png"
                alt="Logo Yayasan"
            >

            <h2>
                SIPM ODGJ
            </h2>

            <p>
                Yayasan Cahaya Kasih Amanah
            </p>

        </div>


        <nav class="sidebar-menu">


            <a
                href="dashboard.php"
                class="menu-item"
            >
                <i class="bi bi-grid"></i>
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
                class="menu-item active"
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


    <!-- MAIN -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">

    <h1>
        Tambah User
    </h1>

    <div class="topbar-right">

                <!-- PROFILE -->
                <div class="topbar-profile">

                    <button
                        type="button"
                        class="profile-trigger"
                        id="profileTrigger"
                        aria-label="Menu Profil"
                    >

                        <div class="profile-info">

                            <strong>
                                <?= htmlspecialchars($nama_user); ?>
                            </strong>

                            <span>
                                <?= htmlspecialchars(strtolower($role_label)); ?>
                            </span>

                        </div>

                        <div class="profile-avatar">
                            <?= htmlspecialchars(strtoupper(substr($nama_user, 0, 1))); ?>
                        </div>

                        <i class="bi bi-chevron-down profile-arrow"></i>

                    </button>

                    <!-- DROPDOWN PROFIL -->
                    <div
                        class="profile-dropdown"
                        id="profileDropdown"
                    >

                        <div class="profile-dropdown-header">

                            <div class="profile-avatar large">
                                <?= htmlspecialchars(strtoupper(substr($nama_user, 0, 1))); ?>
                            </div>

                            <div>

                                <strong>
                                    <?= htmlspecialchars($nama_user); ?>
                                </strong>

                                <span>
                                    <?= htmlspecialchars(strtolower($role_label)); ?>
                                </span>

                            </div>

                        </div>

                        <div class="profile-divider"></div>

                        <a
                            href="profil.php"
                            class="profile-menu-item"
                        >
                            <i class="bi bi-person-circle"></i>
                            <span>Profil Saya</span>
                        </a>

                        <a
                            href="logout.php"
                            class="profile-menu-item logout"
                        >
                            <i class="bi bi-box-arrow-left"></i>
                            <span>Logout</span>
                        </a>

                    </div>

                </div>

            </div>

        </header>



        <!-- CONTENT -->

        <div class="page-content">


            <div class="page-header">


                <div class="page-title">

                    <h2>
                        Tambah Data User
                    </h2>

                    <p>
                        Tambahkan pengguna baru ke dalam sistem
                    </p>

                </div>


                <a
                    href="data_user.php"
                    class="back-button"
                >

                    <i class="bi bi-arrow-left"></i>

                    Kembali

                </a>


            </div>


            <?php if (!empty($error)): ?>

                <div class="error-message">

                    <i class="bi bi-exclamation-circle"></i>

                    <?= htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>


            <div class="form-card">


                <div class="section-title">


                    <div class="section-icon">

                        <i class="bi bi-person-plus"></i>

                    </div>


                    <h3>
                        Informasi User
                    </h3>


                </div>


                <form
                    method="POST"
                    action=""
                >


                    <div class="form-grid">


                        <!-- NAMA -->

                        <div class="form-group">

                            <label>
                                Nama Lengkap
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="nama_lengkap"
                                placeholder="Masukkan nama lengkap"
                                value="<?= htmlspecialchars(
                                    $_POST['nama_lengkap'] ?? ''
                                ); ?>"
                                required
                            >

                        </div>


                        <!-- USERNAME -->

                        <div class="form-group">

                            <label>
                                Username
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="username"
                                placeholder="Masukkan username"
                                value="<?= htmlspecialchars(
                                    $_POST['username'] ?? ''
                                ); ?>"
                                required
                            >

                        </div>


                        <!-- PASSWORD -->

                        <div class="form-group">

                            <label>
                                Password
                                <span class="required">*</span>
                            </label>


                            <div class="password-wrapper">

                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    placeholder="Masukkan password"
                                    required
                                >


                                <button
                                    type="button"
                                    class="password-toggle"
                                    onclick="togglePassword()"
                                >

                                    <i
                                        class="bi bi-eye"
                                        id="passwordIcon"
                                    ></i>

                                </button>

                            </div>

                        </div>


                        <!-- ROLE -->

                        <div class="form-group">

                            <label>
                                Role
                                <span class="required">*</span>
                            </label>


                            <select
                                name="role"
                                id="role"
                                onchange="togglePatientField()"
                                required
                            >

                                <option value="">
                                    Pilih role
                                </option>


                                <option
                                    value="admin"
                                    <?= (
                                        ($_POST['role'] ?? '') === 'admin'
                                    )
                                        ? 'selected'
                                        : ''; ?>
                                >
                                    Admin
                                </option>


                                <option
                                    value="petugas"
                                    <?= (
                                        ($_POST['role'] ?? '') === 'petugas'
                                    )
                                        ? 'selected'
                                        : ''; ?>
                                >
                                    Pendamping Yayasan
                                </option>


                                <option
                                    value="keluarga"
                                    <?= (
                                        ($_POST['role'] ?? '') === 'keluarga'
                                    )
                                        ? 'selected'
                                        : ''; ?>
                                >
                                    Keluarga Pasien
                                </option>


                            </select>

                        </div>


                        <!-- PILIH PASIEN - KHUSUS KELUARGA -->

                        <div
                            class="form-group full-width"
                            id="patientField"
                            style="display: none;"
                        >

                            <label for="id_pasien">
                                Pasien yang Dihubungkan
                                <span class="required">*</span>
                            </label>

                            <select
                                name="id_pasien"
                                id="id_pasien"
                            >

                                <option value="">
                                    Pilih pasien
                                </option>

                                <?php foreach ($pasien_list as $pasien): ?>

                                    <option
                                        value="<?= (int) $pasien['id_pasien']; ?>"
                                        <?= ((int)($_POST['id_pasien'] ?? 0) === (int)$pasien['id_pasien']) ? 'selected' : ''; ?>
                                    >
                                        <?= htmlspecialchars($pasien['nama_pasien']); ?>
                                        —
                                        <?= htmlspecialchars($pasien['nomor_registrasi']); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- STATUS -->

                        <div class="form-group">

                            <label>
                                Status
                                <span class="required">*</span>
                            </label>


                            <select
                                name="status"
                                required
                            >

                                <option
                                    value="aktif"
                                    <?= (
                                        ($_POST['status'] ?? 'aktif')
                                        === 'aktif'
                                    )
                                        ? 'selected'
                                        : ''; ?>
                                >
                                    Aktif
                                </option>


                                <option
                                    value="nonaktif"
                                    <?= (
                                        ($_POST['status'] ?? '')
                                        === 'nonaktif'
                                    )
                                        ? 'selected'
                                        : ''; ?>
                                >
                                    Nonaktif
                                </option>


                            </select>

                        </div>


                    </div>


                    <div class="form-actions">


                        <a
                            href="data_user.php"
                            class="cancel-button"
                        >

                            Batal

                        </a>


                        <button
                            type="submit"
                            class="save-button"
                        >

                            <i class="bi bi-check-lg"></i>

                            Simpan Data User

                        </button>


                    </div>


                </form>


            </div>


        </div>


    </main>


</div>


<script>

function togglePatientField() {

    const role = document.getElementById('role');
    const patientField = document.getElementById('patientField');
    const patientSelect = document.getElementById('id_pasien');

    if (role && patientField && patientSelect) {

        const isFamily = role.value === 'keluarga';

        patientField.style.display = isFamily ? 'flex' : 'none';
        patientSelect.required = isFamily;

        if (!isFamily) {
            patientSelect.value = '';
        }
    }
}


function togglePassword() {

    const password =
        document.getElementById('password');

    const icon =
        document.getElementById('passwordIcon');


    if (password.type === 'password') {

        password.type = 'text';

        icon.className = 'bi bi-eye-slash';

    } else {

        password.type = 'password';

        icon.className = 'bi bi-eye';

    }

}


document.addEventListener('DOMContentLoaded', function () {
    togglePatientField();
});

        /* =========================
           PROFILE SCRIPT
        ========================= */

        document.addEventListener('DOMContentLoaded', function () {

            const profileTrigger =
                document.getElementById('profileTrigger');

            const profileDropdown =
                document.getElementById('profileDropdown');

            if (!profileTrigger || !profileDropdown) {
                return;
            }

            profileTrigger.addEventListener('click', function (event) {

                event.preventDefault();
                event.stopPropagation();

                profileDropdown.classList.toggle('show');
                profileTrigger.classList.toggle('active');

            });

            profileDropdown.addEventListener('click', function (event) {
                event.stopPropagation();
            });

            document.addEventListener('click', function () {

                profileDropdown.classList.remove('show');
                profileTrigger.classList.remove('active');

            });

        });

</script>

</body>


</html>