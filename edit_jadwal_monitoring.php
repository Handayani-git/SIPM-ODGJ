<?php

session_start();

/* =========================================================
   CEK LOGIN
========================================================= */

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}


/* =========================================================
   CEK ROLE PETUGAS
========================================================= */

if (($_SESSION['role'] ?? '') !== 'petugas') {
    die("Anda tidak memiliki akses ke halaman ini.");
}


require_once "koneksi.php";


/* =========================================================
   DATA USER LOGIN
========================================================= */

$nama_user = $_SESSION['nama_lengkap'] ?? 'Petugas';

$inisial_user = strtoupper(
    substr(
        trim($nama_user),
        0,
        1
    )
);

if ($inisial_user === '') {
    $inisial_user = 'P';
}


/* =========================================================
   CEK ID JADWAL
========================================================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: jadwal_monitoring.php");
    exit;
}

$id_jadwal = (int) $_GET['id'];

if ($id_jadwal <= 0) {
    header("Location: jadwal_monitoring.php");
    exit;
}


/* =========================================================
   AMBIL DATA JADWAL
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        j.id_jadwal,
        j.id_pasien,
        j.tanggal_monitoring,
        j.waktu_monitoring,
        j.keterangan,
        j.status,
        p.nama_pasien,
        p.nomor_registrasi,
        p.status_lokasi
     FROM jadwal_monitoring j
     INNER JOIN pasien p
        ON p.id_pasien = j.id_pasien
     WHERE j.id_jadwal = ?
       AND p.status_lokasi = 'Luar Yayasan'
     LIMIT 1"
);

if (!$stmt) {
    die(
        "Gagal menyiapkan data jadwal: " .
        htmlspecialchars(mysqli_error($conn))
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_jadwal
);

if (!mysqli_stmt_execute($stmt)) {
    $error_query = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);

    die(
        "Gagal mengambil data jadwal: " .
        htmlspecialchars($error_query)
    );
}

$result = mysqli_stmt_get_result($stmt);

$jadwal = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   CEK DATA
========================================================= */

if (!$jadwal) {
    die("Data jadwal monitoring tidak ditemukan.");
}


/* =========================================================
   NILAI AWAL FORM
========================================================= */

$tanggal_monitoring = $jadwal['tanggal_monitoring'] ?? '';
$waktu_monitoring   = $jadwal['waktu_monitoring'] ?? '';
$keterangan         = $jadwal['keterangan'] ?? '';
$status             = $jadwal['status'] ?? 'Terjadwal';

$error = '';


/* =========================================================
   PROSES UPDATE
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tanggal_monitoring = trim(
        $_POST['tanggal_monitoring'] ?? ''
    );

    $waktu_monitoring = trim(
        $_POST['waktu_monitoring'] ?? ''
    );

    $keterangan = trim(
        $_POST['keterangan'] ?? ''
    );

    $status = trim(
        $_POST['status'] ?? 'Terjadwal'
    );


    /* =====================================================
       VALIDASI
    ===================================================== */

    if ($tanggal_monitoring === '') {

        $error = "Tanggal monitoring wajib diisi.";

    } elseif ($waktu_monitoring === '') {

        $error = "Waktu monitoring wajib diisi.";

    } elseif (
        !in_array(
            $status,
            ['Terjadwal', 'Selesai', 'Dibatalkan'],
            true
        )
    ) {

        $error = "Status jadwal tidak valid.";

    }


    /* =====================================================
       UPDATE DATABASE
    ===================================================== */

    if ($error === '') {

        $stmt_update = mysqli_prepare(
            $conn,
            "UPDATE jadwal_monitoring
             SET
                tanggal_monitoring = ?,
                waktu_monitoring = ?,
                keterangan = ?,
                status = ?
             WHERE id_jadwal = ?"
        );


        if (!$stmt_update) {

            $error =
                "Gagal menyiapkan update jadwal: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt_update,
                "ssssi",
                $tanggal_monitoring,
                $waktu_monitoring,
                $keterangan,
                $status,
                $id_jadwal
            );


            if (mysqli_stmt_execute($stmt_update)) {

                mysqli_stmt_close($stmt_update);

                header(
                    "Location: jadwal_monitoring.php?status=updated"
                );

                exit;

            } else {

                $error =
                    "Jadwal gagal diperbarui: " .
                    mysqli_stmt_error($stmt_update);

                mysqli_stmt_close($stmt_update);
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

    <title>Edit Jadwal Monitoring - SIPM ODGJ</title>


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
            color: #0757d5;
            font-size: 20px;
            font-weight: 700;
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
            background: #ffffff;
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
           PROFILE BIRU
        ===================================================== */

        .user-profile {
            position: relative;
            display: flex;
            align-items: center;
        }


        .profile-button {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding: 5px 8px;
            border: none;
            background: transparent;
            border-radius: 12px;
            color: #17243a;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: background .2s ease;
        }


        .profile-button:hover {
            background: #f4f7fc;
        }


        .profile-top-text {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            line-height: 1.15;
            min-width: 80px;
        }


        .profile-top-text strong {
            margin: 0;
            color: #17243a;
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
        }


        .profile-top-text small {
            margin-top: 3px;
            color: #8a95a8;
            font-size: 10px;
            font-weight: 400;
            text-transform: lowercase;
        }


        .profile-top-avatar {
            width: 48px;
            height: 48px;
            min-width: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #2864e6;
            color: #ffffff;
            font-size: 18px;
            font-weight: 600;
            line-height: 1;
        }


        .profile-arrow {
            font-size: 11px;
            color: #7a8499;
            margin-left: 1px;
            transition: transform .2s ease;
        }


        .profile-button:hover .profile-arrow {
            color: #2864e6;
        }


        /* =====================================================
           CONTENT
        ===================================================== */

        .content {
            padding: 30px;
        }


        .page-header {
            margin-bottom: 24px;
        }


        .page-header h1 {
            margin: 0 0 6px;
            color: #17243a;
            font-size: 25px;
            font-weight: 700;
        }


        .page-header p {
            margin: 0;
            color: #7a8499;
            font-size: 13px;
        }


        /* =====================================================
           CARD
        ===================================================== */

        .card {
            background: #ffffff;
            border: 1px solid #e1e7f0;
            border-radius: 12px;
            overflow: hidden;
        }


        .card-header {
            padding: 20px 22px;
            border-bottom: 1px solid #edf0f5;
            display: flex;
            align-items: center;
            gap: 8px;
        }


        .card-header i {
            color: #2864e6;
            font-size: 16px;
        }


        .card-header h3 {
            margin: 0;
            color: #17243a;
            font-size: 16px;
            font-weight: 600;
        }


        .card-body {
            padding: 22px;
        }


        /* =====================================================
           ERROR
        ===================================================== */

        .error-data {
            margin-bottom: 20px;
            padding: 13px 15px;
            border-radius: 8px;
            background: #fff0f0;
            border: 1px solid #ffd0d0;
            color: #b42318;
            font-size: 12px;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-group {
            margin-bottom: 20px;
        }


        .form-label {
            display: block;
            margin-bottom: 7px;
            color: #374151;
            font-size: 11px;
            font-weight: 500;
        }


        .form-control {
            width: 100%;
            height: 38px;
            padding: 0 12px;
            border: 1px solid #dce3ee;
            border-radius: 7px;
            outline: none;
            background: #ffffff;
            color: #374151;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
        }


        .form-control:focus {
            border-color: #2864e6;
            box-shadow: 0 0 0 2px rgba(40, 100, 230, .08);
        }


        textarea.form-control {
            height: 90px;
            padding: 10px 12px;
            resize: vertical;
            line-height: 1.5;
        }


        .patient-info {
            min-height: 38px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 12px;
            border: 1px solid #dce3ee;
            border-radius: 7px;
            background: #f8faff;
            color: #17243a;
            font-size: 11px;
        }


        .patient-info i {
            color: #2864e6;
            font-size: 13px;
        }


        .patient-info strong {
            font-weight: 600;
        }


        .patient-registration {
            margin-left: 3px;
            color: #8a95a8;
            font-size: 9px;
        }


        /* =====================================================
           ACTION
        ===================================================== */

        .form-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #edf0f5;
        }


        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 36px;
            padding: 0 14px;
            border-radius: 7px;
            border: none;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
        }


        .btn-secondary {
            background: #f1f4f8;
            color: #4b5563;
        }


        .btn-secondary:hover {
            background: #e7ebf1;
        }


        .btn-primary {
            background: #2864e6;
            color: #ffffff;
        }


        .btn-primary:hover {
            background: #1f55c8;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 800px) {

            .sidebar {
                width: 210px;
            }


            .main {
                margin-left: 210px;
            }


            .content {
                padding: 20px;
            }


            .topbar {
                padding: 0 20px;
            }

        }


        @media (max-width: 600px) {

            .profile-top-text {
                display: none;
            }


            .profile-top-avatar {
                width: 40px;
                height: 40px;
                min-width: 40px;
                font-size: 15px;
            }


            .content {
                padding: 15px;
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
            Edit Jadwal Monitoring
        </div>


        <div class="user-profile" id="userProfile">


            <button
                type="button"
                class="profile-button"
                id="profileButton"
                aria-expanded="false"
            >


                <span class="profile-top-text">

                    <strong>
                        <?= htmlspecialchars($nama_user); ?>
                    </strong>

                    <small>
                        petugas
                    </small>

                </span>


                <span class="profile-top-avatar">

                    <?= htmlspecialchars($inisial_user); ?>

                </span>


                <i class="bi bi-chevron-down profile-arrow"></i>


            </button>


        </div>


    </header>


    <!-- CONTENT -->

    <section class="content">


        <div class="page-header">

            <h1>
                Edit Jadwal Monitoring
            </h1>

            <p>
                Perbarui jadwal monitoring pasien luar yayasan.
            </p>

        </div>


        <!-- CARD -->

        <div class="card">


            <div class="card-header">

                <i class="bi bi-pencil-square"></i>

                <h3>
                    Form Edit Jadwal Monitoring
                </h3>

            </div>


            <div class="card-body">


                <?php if ($error !== ''): ?>

                    <div class="error-data">
                        <?= htmlspecialchars($error); ?>
                    </div>

                <?php endif; ?>


                <form
                    method="POST"
                    action=""
                >


                    <!-- PASIEN -->

                    <div class="form-group">

                        <label class="form-label">
                            Nama Pasien
                        </label>


                        <div class="patient-info">

                            <i class="bi bi-person"></i>


                            <strong>
                                <?= htmlspecialchars(
                                    $jadwal['nama_pasien'] ?? '-'
                                ); ?>
                            </strong>


                            <span class="patient-registration">

                                No. Registrasi:

                                <?= htmlspecialchars(
                                    $jadwal['nomor_registrasi'] ?? '-'
                                ); ?>

                            </span>

                        </div>

                    </div>


                    <!-- TANGGAL -->

                    <div class="form-group">

                        <label
                            for="tanggal_monitoring"
                            class="form-label"
                        >
                            Tanggal Monitoring
                        </label>


                        <input
                            type="date"
                            id="tanggal_monitoring"
                            name="tanggal_monitoring"
                            class="form-control"
                            value="<?= htmlspecialchars($tanggal_monitoring); ?>"
                            required
                        >

                    </div>


                    <!-- WAKTU -->

                    <div class="form-group">

                        <label
                            for="waktu_monitoring"
                            class="form-label"
                        >
                            Waktu Monitoring
                        </label>


                        <input
                            type="time"
                            id="waktu_monitoring"
                            name="waktu_monitoring"
                            class="form-control"
                            value="<?= htmlspecialchars($waktu_monitoring); ?>"
                            required
                        >

                    </div>


                    <!-- STATUS -->

                    <div class="form-group">

                        <label
                            for="status"
                            class="form-label"
                        >
                            Status
                        </label>


                        <select
                            id="status"
                            name="status"
                            class="form-control"
                        >

                            <option
                                value="Terjadwal"
                                <?= $status === 'Terjadwal' ? 'selected' : ''; ?>
                            >
                                Terjadwal
                            </option>


                            <option
                                value="Selesai"
                                <?= $status === 'Selesai' ? 'selected' : ''; ?>
                            >
                                Selesai
                            </option>


                            <option
                                value="Dibatalkan"
                                <?= $status === 'Dibatalkan' ? 'selected' : ''; ?>
                            >
                                Dibatalkan
                            </option>

                        </select>

                    </div>


                    <!-- KETERANGAN -->

                    <div class="form-group">

                        <label
                            for="keterangan"
                            class="form-label"
                        >
                            Keterangan
                        </label>


                        <textarea
                            id="keterangan"
                            name="keterangan"
                            class="form-control"
                            placeholder="Masukkan keterangan jadwal monitoring..."
                        ><?= htmlspecialchars($keterangan); ?></textarea>

                    </div>


                    <!-- ACTION -->

                    <div class="form-actions">


                        <a
                            href="jadwal_monitoring.php"
                            class="btn btn-secondary"
                        >

                            <i class="bi bi-arrow-left"></i>

                            Batal

                        </a>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >

                            <i class="bi bi-save"></i>

                            Simpan Perubahan

                        </button>


                    </div>


                </form>


            </div>


        </div>


    </section>


</main>


</body>

</html>
