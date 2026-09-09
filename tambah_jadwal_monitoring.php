<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'petugas') {
    die("Anda tidak memiliki akses ke halaman ini.");
}

require_once "koneksi.php";

$nama_user = $_SESSION['nama_lengkap'] ?? 'Petugas';
$pesan_error = "";

$inisial_user = strtoupper(substr(trim($nama_user), 0, 1));

if ($inisial_user === '') {
    $inisial_user = 'P';
}


/* =========================================================
   PROSES SIMPAN JADWAL MONITORING
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_pasien = (int) ($_POST['id_pasien'] ?? 0);
    $tanggal_monitoring = trim($_POST['tanggal_monitoring'] ?? '');
    $waktu_monitoring = trim($_POST['waktu_monitoring'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    $id_user = (int) ($_SESSION['id_user'] ?? 0);
    $status = 'Terjadwal';


    /* =========================
       VALIDASI
    ========================= */

    if ($id_user <= 0) {

        $pesan_error =
            "ID petugas tidak ditemukan. Silakan logout lalu login kembali.";

    } elseif ($id_pasien <= 0) {

        $pesan_error =
            "Silakan pilih pasien.";

    } elseif ($tanggal_monitoring === '') {

        $pesan_error =
            "Tanggal monitoring wajib diisi.";

    } elseif ($waktu_monitoring === '') {

        $pesan_error =
            "Waktu monitoring wajib diisi.";

    } else {


        /* =========================
           CEK PASIEN LUAR YAYASAN
        ========================= */

        $stmt_cek = mysqli_prepare(
            $conn,
            "SELECT id_pasien
             FROM pasien
             WHERE id_pasien = ?
               AND status_lokasi = 'Luar Yayasan'
             LIMIT 1"
        );

        if (!$stmt_cek) {

            $pesan_error =
                "Gagal menyiapkan pengecekan pasien: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt_cek,
                "i",
                $id_pasien
            );

            if (!mysqli_stmt_execute($stmt_cek)) {

                $pesan_error =
                    "Gagal mengecek pasien: " .
                    mysqli_stmt_error($stmt_cek);

            } else {

                mysqli_stmt_store_result($stmt_cek);

                if (mysqli_stmt_num_rows($stmt_cek) === 0) {

                    $pesan_error =
                        "Pasien yang dipilih bukan pasien luar yayasan.";
                }
            }

            mysqli_stmt_close($stmt_cek);
        }


        /* =========================
           INSERT KE JADWAL_MONITORING
        ========================= */

        if ($pesan_error === '') {

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO jadwal_monitoring
                (
                    id_pasien,
                    id_user,
                    tanggal_monitoring,
                    waktu_monitoring,
                    keterangan,
                    status,
                    created_at
                )
                VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );

            if (!$stmt) {

                $pesan_error =
                    "INSERT tidak dapat dibuat: " .
                    mysqli_error($conn);

            } else {

                mysqli_stmt_bind_param(
                    $stmt,
                    "iissss",
                    $id_pasien,
                    $id_user,
                    $tanggal_monitoring,
                    $waktu_monitoring,
                    $keterangan,
                    $status
                );


                if (!mysqli_stmt_execute($stmt)) {

                    $pesan_error =
                        "Jadwal BELUM masuk database. MySQL: " .
                        mysqli_stmt_error($stmt);

                    mysqli_stmt_close($stmt);

                } else {

                    $id_jadwal_baru =
                        mysqli_insert_id($conn);

                    mysqli_stmt_close($stmt);


                    if ($id_jadwal_baru <= 0) {

                        $pesan_error =
                            "Query berhasil dijalankan, tetapi ID jadwal baru tidak ditemukan.";

                    } else {

                        header(
                            "Location: monitoring_petugas.php?jadwal=berhasil&id_jadwal=" .
                            $id_jadwal_baru
                        );

                        exit;
                    }
                }
            }
        }
    }
}


/* =========================================================
   AMBIL PASIEN LUAR YAYASAN
========================================================= */

$query_pasien = mysqli_query(
    $conn,
    "SELECT
        id_pasien,
        nomor_registrasi,
        nama_pasien
     FROM pasien
     WHERE status_lokasi = 'Luar Yayasan'
     ORDER BY nama_pasien ASC"
);

if (!$query_pasien) {

    $pesan_error =
        "Data pasien gagal dimuat: " .
        mysqli_error($conn);
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
        Tambah Jadwal Monitoring - SIPM ODGJ
    </title>


    <!-- GOOGLE FONT -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- BOOTSTRAP ICON -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: #f5f7fb;
            color: #17243a;
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {

            position: fixed;

            left: 0;
            top: 0;

            width: 230px;
            height: 100vh;

            background: #eef4ff;

            border-right: 1px solid #dce6f5;

            display: flex;

            flex-direction: column;

            z-index: 1000;
        }


        .sidebar-brand {

            text-align: center;

            padding: 24px 15px 18px;
        }


        .sidebar-logo {

            width: 65px;
            height: 65px;

            object-fit: contain;

            display: block;

            margin: 0 auto 8px;
        }


        .sidebar-brand h2 {

            margin: 0 0 5px;

            color: #2864e6;

            font-size: 20px;
        }


        .sidebar-brand p {

            margin: 0;

            color: #718096;

            font-size: 10px;
        }


        .sidebar-menu {

            padding: 18px 10px;
        }


        .menu-item {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 13px 14px;

            margin-bottom: 7px;

            border-radius: 9px;

            color: #40516b;

            text-decoration: none;

            font-size: 13px;

            transition: .2s;
        }


        .menu-item:hover {

            background: #dce9ff;

            color: #0757d5;
        }


        .menu-item.active {

            background: #d5e5ff;

            color: #0757d5;

            font-weight: 600;
        }


        .menu-item i {

            width: 20px;

            text-align: center;

            font-size: 17px;
        }


        .sidebar-bottom {

            margin-top: auto;

            padding: 15px;
        }


        .logout-button {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 10px;

            color: #ef4444;

            text-decoration: none;

            font-size: 13px;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .main {

            margin-left: 230px;

            min-height: 100vh;
        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {

            height: 70px;

            padding: 0 30px;

            background: #fff;

            border-bottom: 1px solid #e5eaf1;

            display: flex;

            align-items: center;

            justify-content: space-between;
        }


        .topbar-title {

            color: #1457d4;

            font-size: 24px;

            font-weight: 700;
        }


        /* =====================================================
           PROFIL PETUGAS
        ===================================================== */

        .topbar-right {

            display: flex;

            align-items: center;

            gap: 14px;
        }


        .user-profile {

            position: relative;

            display: flex;

            align-items: center;
        }


        .profile-button {

            display: flex;

            align-items: center;

            gap: 8px;

            border: 0;

            background: transparent;

            padding: 5px 6px;

            border-radius: 9px;

            cursor: pointer;

            font-family: 'Poppins', sans-serif;

            transition: .2s;
        }


        .profile-button:hover {

            background: #f4f7fb;
        }


        .profile-avatar-top {

            width: 32px;
            height: 32px;

            min-width: 32px;

            border-radius: 50%;

            background: #2864e6;

            color: #fff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 11px;

            font-weight: 700;
        }


        .profile-text {

            display: flex;

            flex-direction: column;

            align-items: flex-start;

            line-height: 1.2;
        }


        .profile-text strong {

            color: #172033;

            font-size: 10px;

            font-weight: 700;

            max-width: 130px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .profile-text small {

            color: #8a95a8;

            font-size: 8px;

            margin-top: 2px;
        }


        .profile-arrow {

            color: #7b8ba2;

            font-size: 9px;

            transition: transform .2s;
        }


        .profile-button.active .profile-arrow {

            transform: rotate(180deg);
        }


        /* =====================================================
           PROFILE DROPDOWN
        ===================================================== */

        .profile-dropdown {

            position: absolute;

            right: 0;

            top: calc(100% + 9px);

            width: 220px;

            background: #fff;

            border: 1px solid #e1e8f2;

            border-radius: 11px;

            box-shadow:
                0 10px 28px
                rgba(23, 33, 51, .12);

            overflow: hidden;

            opacity: 0;

            visibility: hidden;

            transform: translateY(-5px);

            transition: .2s;

            z-index: 3000;
        }


        .profile-dropdown.show {

            opacity: 1;

            visibility: visible;

            transform: translateY(0);
        }


        .profile-dropdown-header {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 14px;
        }


        .profile-avatar-dropdown {

            width: 38px;
            height: 38px;

            min-width: 38px;

            border-radius: 50%;

            background: #2864e6;

            color: #fff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 14px;

            font-weight: 700;
        }


        .profile-info {

            display: flex;

            flex-direction: column;

            min-width: 0;
        }


        .profile-info strong {

            color: #172033;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .profile-info span {

            color: #8a95a8;

            font-size: 8px;

            margin-top: 2px;
        }


        .profile-divider {

            height: 1px;

            background: #edf1f5;
        }


        .profile-menu-item {

            width: 100%;

            display: flex;

            align-items: center;

            gap: 9px;

            padding: 10px 14px;

            border: 0;

            background: #fff;

            color: #394b63;

            text-decoration: none;

            font: 500 9px 'Poppins', sans-serif;

            text-align: left;

            cursor: pointer;
        }


        .profile-menu-item:hover {

            background: #f7faff;

            color: #2864e6;
        }


        .profile-menu-item i {

            width: 15px;

            text-align: center;

            font-size: 13px;
        }


        .profile-menu-item.logout-item {

            color: #dc3545;
        }


        .profile-menu-item.logout-item:hover {

            background: #fff5f5;

            color: #dc3545;
        }


        /* =====================================================
           CONTENT
        ===================================================== */

        .content {

            padding: 30px;
        }


        .page-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 24px;
        }


        .page-header h1 {

            margin: 0 0 6px;

            font-size: 25px;
        }


        .page-header p {

            margin: 0;

            color: #7a8499;

            font-size: 13px;
        }


        /* =====================================================
           BUTTON
        ===================================================== */

        .button {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 11px 17px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 12px;

            font-weight: 600;
        }


        .button-back {

            color: #526078;

            background: #fff;

            border: 1px solid #dce3ee;
        }


        .button-back:hover {

            border-color: #2864e6;

            color: #2864e6;
        }


        .button-primary {

            border: none;

            background: #2864e6;

            color: #fff;

            cursor: pointer;
        }


        .button-primary:hover {

            background: #1f55c8;
        }


        /* =====================================================
           CARD
        ===================================================== */

        .card {

            background: #fff;

            border: 1px solid #e1e7f0;

            border-radius: 12px;

            padding: 28px;
        }


        .card-title {

            margin: 0 0 22px;

            font-size: 17px;
        }


        /* =====================================================
           ERROR
        ===================================================== */

        .alert {

            padding: 13px 16px;

            margin-bottom: 20px;

            border-radius: 8px;

            background: #fff0f0;

            color: #dc3545;

            border: 1px solid #ffd2d2;

            font-size: 12px;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 20px;
        }


        .form-group {

            display: flex;

            flex-direction: column;

            gap: 7px;
        }


        .form-group.full {

            grid-column: 1 / -1;
        }


        label {

            font-size: 12px;

            font-weight: 500;

            color: #40516b;
        }


        label span {

            color: #dc3545;
        }


        input,
        select,
        textarea {

            width: 100%;

            border: 1px solid #dce3ee;

            border-radius: 8px;

            padding: 12px 13px;

            font-family: inherit;

            font-size: 12px;

            color: #40516b;

            background: #fff;

            outline: none;

            transition: .2s;
        }


        input:focus,
        select:focus,
        textarea:focus {

            border-color: #2864e6;

            box-shadow:
                0 0 0 3px
                rgba(40, 100, 230, .08);
        }


        textarea {

            min-height: 110px;

            resize: vertical;
        }


        .help-text {

            color: #8a95a8;

            font-size: 10px;
        }


        /* =====================================================
           FORM ACTIONS
        ===================================================== */

        .form-actions {

            margin-top: 25px;

            padding-top: 20px;

            border-top: 1px solid #edf0f5;

            display: flex;

            justify-content: flex-end;

            gap: 10px;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media(max-width: 800px) {

            .sidebar {

                width: 210px;
            }


            .main {

                margin-left: 210px;
            }


            .content {

                padding: 20px;
            }


            .form-grid {

                grid-template-columns: 1fr;
            }


            .form-group.full {

                grid-column: auto;
            }


            .topbar {

                padding: 0 20px;
            }


            .profile-text strong {

                max-width: 100px;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <div class="sidebar-brand">

        <img
            src="/SIPM-ODGJ/assets/img/logo YCKA.png"
            alt="Logo Yayasan"
            class="sidebar-logo"
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
            href="dashboard_petugas.php"
            class="menu-item"
        >

            <i class="bi bi-grid-1x2"></i>

            <span>
                Dashboard
            </span>

        </a>


        <a
            href="data_pasien_petugas.php"
            class="menu-item"
        >

            <i class="bi bi-people"></i>

            <span>
                Data Pasien
            </span>

        </a>


        <a
            href="monitoring_petugas.php"
            class="menu-item active"
        >

            <i class="bi bi-clipboard2-pulse"></i>

            <span>
                Monitoring
            </span>

        </a>


    </nav>


    <div class="sidebar-bottom">

        <a
            href="logout.php"
            class="logout-button"
        >

            <i class="bi bi-box-arrow-left"></i>

            <span>
                Logout
            </span>

        </a>

    </div>

</aside>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- TOPBAR -->

    <header class="topbar">


        <div class="topbar-title">

            Tambah Jadwal Monitoring

        </div>


        <div class="topbar-right">


            <!-- PROFILE -->

            <div
                class="user-profile"
                id="userProfile"
            >


                <button
                    type="button"
                    class="profile-button"
                    id="profileButton"
                >


                    <span class="profile-avatar-top">

                        <?= htmlspecialchars($inisial_user); ?>

                    </span>


                    <span class="profile-text">

                        <strong>

                            <?= htmlspecialchars($nama_user); ?>

                        </strong>


                        <small>

                            Petugas

                        </small>

                    </span>


                    <i class="bi bi-chevron-down profile-arrow"></i>


                </button>


                <!-- DROPDOWN -->

                <div
                    class="profile-dropdown"
                    id="profileDropdown"
                >


                    <div class="profile-dropdown-header">


                        <div class="profile-avatar-dropdown">

                            <?= htmlspecialchars($inisial_user); ?>

                        </div>


                        <div class="profile-info">


                            <strong>

                                <?= htmlspecialchars($nama_user); ?>

                            </strong>


                            <span>

                                Petugas

                            </span>


                        </div>


                    </div>


                    <div class="profile-divider"></div>


                    <button
                        type="button"
                        class="profile-menu-item"
                        id="profileSaya"
                    >

                        <i class="bi bi-person"></i>

                        <span>
                            Profil Saya
                        </span>

                    </button>


                    <a
                        href="logout.php"
                        class="profile-menu-item logout-item"
                    >

                        <i class="bi bi-box-arrow-left"></i>

                        <span>
                            Logout
                        </span>

                    </a>


                </div>


            </div>


        </div>

    </header>


    <!-- =====================================================
         CONTENT
    ===================================================== -->

    <section class="content">


        <div class="page-header">


            <div>


                <h1>

                    Tambah Jadwal Monitoring

                </h1>


                <p>

                    Buat jadwal monitoring untuk pasien luar yayasan.

                </p>


            </div>


            <a
                href="jadwal_monitoring.php"
                class="button button-back"
            >

                <i class="bi bi-arrow-left"></i>

                Kembali

            </a>


        </div>


        <!-- CARD -->

        <div class="card">


            <h3 class="card-title">


                <i class="bi bi-calendar-plus"></i>

                Data Jadwal Monitoring


            </h3>


            <!-- ERROR -->

            <?php if ($pesan_error !== ''): ?>


                <div class="alert">


                    <i class="bi bi-exclamation-circle"></i>


                    <?= htmlspecialchars($pesan_error); ?>


                </div>


            <?php endif; ?>


            <!-- FORM -->

            <form
                method="POST"
                action=""
            >


                <div class="form-grid">


                    <!-- PASIEN -->

                    <div class="form-group">


                        <label>

                            Pasien

                            <span>*</span>

                        </label>


                        <select
                            name="id_pasien"
                            required
                        >


                            <option value="">

                                Pilih pasien luar yayasan

                            </option>


                            <?php

                            if (
                                $query_pasien &&
                                mysqli_num_rows($query_pasien) > 0
                            ):

                                while (
                                    $pasien =
                                    mysqli_fetch_assoc(
                                        $query_pasien
                                    )
                                ):

                            ?>


                                <option
                                    value="<?= (int)$pasien['id_pasien']; ?>"
                                    <?= (
                                        isset($_POST['id_pasien']) &&
                                        $_POST['id_pasien'] ==
                                        $pasien['id_pasien']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >


                                    <?= htmlspecialchars(
                                        $pasien['nama_pasien']
                                    ); ?>


                                    -


                                    <?= htmlspecialchars(
                                        $pasien['nomor_registrasi']
                                    ); ?>


                                </option>


                            <?php

                                endwhile;

                            endif;

                            ?>


                        </select>


                        <span class="help-text">

                            Hanya pasien dengan status Luar Yayasan yang dapat dijadwalkan.

                        </span>


                    </div>


                    <!-- TANGGAL -->

                    <div class="form-group">


                        <label>

                            Tanggal Monitoring

                            <span>*</span>

                        </label>


                        <input
                            type="date"
                            name="tanggal_monitoring"
                            value="<?= htmlspecialchars(
                                $_POST['tanggal_monitoring'] ?? ''
                            ); ?>"
                            required
                        >


                    </div>


                    <!-- WAKTU -->

                    <div class="form-group">


                        <label>

                            Waktu Monitoring

                            <span>*</span>

                        </label>


                        <input
                            type="time"
                            name="waktu_monitoring"
                            value="<?= htmlspecialchars(
                                $_POST['waktu_monitoring'] ?? ''
                            ); ?>"
                            required
                        >


                    </div>


                    <!-- STATUS -->

                    <div class="form-group">


                        <label>

                            Status

                        </label>


                        <input
                            type="text"
                            value="Terjadwal"
                            disabled
                        >


                    </div>


                    <!-- KETERANGAN -->

                    <div class="form-group full">


                        <label>

                            Keterangan

                        </label>


                        <textarea
                            name="keterangan"
                            placeholder="Masukkan keterangan jadwal monitoring..."
                        ><?= htmlspecialchars(
                            $_POST['keterangan'] ?? ''
                        ); ?></textarea>


                    </div>


                </div>


                <!-- ACTION -->

                <div class="form-actions">


                    <a
                        href="jadwal_monitoring.php"
                        class="button button-back"
                    >

                        Batal

                    </a>


                    <button
                        type="submit"
                        class="button button-primary"
                    >

                        <i class="bi bi-check-lg"></i>

                        Simpan Jadwal Monitoring

                    </button>


                </div>


            </form>


        </div>


    </section>


</main>


<!-- =====================================================
     JAVASCRIPT PROFILE
===================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const button =
            document.getElementById("profileButton");


        const dropdown =
            document.getElementById("profileDropdown");


        const profile =
            document.getElementById("userProfile");


        button?.addEventListener(
            "click",
            function (e) {

                e.preventDefault();

                e.stopPropagation();


                dropdown?.classList.toggle("show");

                button?.classList.toggle("active");

            }
        );


        document.addEventListener(
            "click",
            function (e) {

                if (
                    profile &&
                    !profile.contains(e.target)
                ) {

                    dropdown?.classList.remove("show");

                    button?.classList.remove("active");

                }

            }
        );


    }
);

</script>


</body>

</html>