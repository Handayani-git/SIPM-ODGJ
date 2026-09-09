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
   DATA USER
========================================================= */

$nama_user = $_SESSION['nama_lengkap'] ?? 'Petugas';
$id_user   = $_SESSION['id_user'] ?? '-';


/* =========================================================
   INISIAL USER
========================================================= */

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
   AMBIL ID MONITORING DARI URL
========================================================= */

$id_monitoring = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


/* =========================================================
   JIKA ID TIDAK VALID
========================================================= */

if ($id_monitoring <= 0) {
    header("Location: monitoring_petugas.php");
    exit;
}


/* =========================================================
   AMBIL DATA MONITORING
========================================================= */

$query_monitoring = mysqli_query(
    $conn,
    "SELECT
        m.id_monitoring,
        m.id_pasien,
        m.tanggal_monitoring,
        m.kondisi,
        m.aktivitas_harian,
        m.perilaku,
        m.catatan,
        p.nama_pasien
     FROM monitoring m
     INNER JOIN pasien p
        ON p.id_pasien = m.id_pasien
     WHERE m.id_monitoring = $id_monitoring
     LIMIT 1"
);


/* =========================================================
   JIKA QUERY GAGAL
========================================================= */

if (!$query_monitoring) {

    die(
        "Gagal mengambil data monitoring: "
        . htmlspecialchars(mysqli_error($conn))
    );

}


/* =========================================================
   JIKA DATA TIDAK DITEMUKAN
========================================================= */

if (mysqli_num_rows($query_monitoring) === 0) {
    die("Data monitoring tidak ditemukan.");
}


/* =========================================================
   AMBIL DATA
========================================================= */

$data = mysqli_fetch_assoc($query_monitoring);


/* =========================================================
   PROSES UPDATE
========================================================= */

$error = '';


/* =========================================================
   NILAI AWAL DARI DATABASE
========================================================= */

$tanggal_monitoring = $data['tanggal_monitoring'] ?? '';
$kondisi            = $data['kondisi'] ?? '';
$aktivitas_harian   = $data['aktivitas_harian'] ?? '';
$perilaku           = $data['perilaku'] ?? '';
$catatan            = $data['catatan'] ?? '';


/* =========================================================
   JIKA FORM DISUBMIT
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /* -------------------------------------------------
       AMBIL INPUT
    ------------------------------------------------- */

    $tanggal_input = trim(
        $_POST['tanggal_monitoring'] ?? ''
    );


    $kondisi = trim(
        $_POST['kondisi'] ?? ''
    );


    $aktivitas_harian = trim(
        $_POST['aktivitas_harian'] ?? ''
    );


    $perilaku = trim(
        $_POST['perilaku'] ?? ''
    );


    $catatan = trim(
        $_POST['catatan'] ?? ''
    );


    /* -------------------------------------------------
       SIMPAN NILAI TANGGAL UNTUK FORM
    ------------------------------------------------- */

    $tanggal_monitoring = $tanggal_input;


    /* -------------------------------------------------
       VALIDASI
    ------------------------------------------------- */

    if ($tanggal_input === '') {

        $error = "Tanggal monitoring wajib diisi.";

    } elseif ($kondisi === '') {

        $error = "Kondisi wajib diisi.";

    } elseif ($aktivitas_harian === '') {

        $error = "Aktivitas harian wajib diisi.";

    } elseif ($perilaku === '') {

        $error = "Perilaku wajib diisi.";

    } else {


        /* -------------------------------------------------
           UBAH FORMAT DATETIME
           dari:
           2026-08-11T11:40

           menjadi:
           2026-08-11 11:40:00
        ------------------------------------------------- */

        $tanggal_database = '';

        $timestamp = strtotime($tanggal_input);


        if ($timestamp !== false) {

            $tanggal_database =
                date(
                    'Y-m-d H:i:s',
                    $timestamp
                );

        } else {

            $error = "Format tanggal monitoring tidak valid.";

        }


        /* -------------------------------------------------
           UPDATE DATABASE
        ------------------------------------------------- */

        if ($error === '') {


            $stmt = mysqli_prepare(
                $conn,
                "UPDATE monitoring
                 SET
                    tanggal_monitoring = ?,
                    kondisi = ?,
                    aktivitas_harian = ?,
                    perilaku = ?,
                    catatan = ?
                 WHERE id_monitoring = ?"
            );


            if (!$stmt) {

                $error =
                    "Gagal menyiapkan proses update: "
                    . mysqli_error($conn);

            } else {


                mysqli_stmt_bind_param(
                    $stmt,
                    "sssssi",
                    $tanggal_database,
                    $kondisi,
                    $aktivitas_harian,
                    $perilaku,
                    $catatan,
                    $id_monitoring
                );


                /* -------------------------------------------------
                   EKSEKUSI UPDATE
                ------------------------------------------------- */

                if (mysqli_stmt_execute($stmt)) {

                    mysqli_stmt_close($stmt);


                    /* -------------------------------------------------
                       BERHASIL → KEMBALI KE MONITORING PETUGAS
                    ------------------------------------------------- */

                    header(
                        "Location: monitoring_petugas.php?status=updated"
                    );

                    exit;


                } else {

                    $error =
                        "Gagal memperbarui data monitoring: "
                        . mysqli_stmt_error($stmt);

                    mysqli_stmt_close($stmt);

                }

            }

        }

    }

}


/* =========================================================
   FORMAT TANGGAL UNTUK INPUT DATETIME-LOCAL
========================================================= */

$tanggal_form = '';


if (!empty($tanggal_monitoring)) {

    $timestamp_form = strtotime($tanggal_monitoring);


    if ($timestamp_form !== false) {

        $tanggal_form =
            date(
                'Y-m-d\TH:i',
                $timestamp_form
            );

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
        Edit Monitoring - Petugas | SIPM ODGJ
    </title>


    <!-- =====================================================
         GOOGLE FONT
    ===================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         BOOTSTRAP ICON
    ===================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- =====================================================
         DASHBOARD CSS
    ===================================================== -->

    <link
        rel="stylesheet"
        href="/SIPM-ODGJ/assets/css/dashboard.css"
    >


    <style>

        /* =====================================================
           GLOBAL
        ===================================================== */

        * {
            box-sizing: border-box;
        }


        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }


        body {

            font-family: 'Poppins', sans-serif;

            background: #f5f7fb;

            color: #14213d;
        }


        .dashboard-layout {

            display: flex;

            width: 100%;

            min-height: 100vh;
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {

            width: 218px;

            min-width: 218px;

            min-height: 100vh;

            position: fixed;

            left: 0;

            top: 0;

            z-index: 1000;
        }


        .main-content {

            flex: 1;

            min-width: 0;

            margin-left: 218px;

            width: calc(100% - 218px);
        }


        /* =====================================================
           PAGE CONTENT
        ===================================================== */

        .page-content {

            padding: 28px;
        }


        .page-header {

            margin-bottom: 24px;
        }


        .page-header h2 {

            margin: 0;

            font-size: 24px;

            font-weight: 700;

            color: #14213d;
        }


        .page-header p {

            margin: 6px 0 0;

            color: #7a8499;

            font-size: 13px;
        }


        /* =====================================================
           CONTENT CARD
        ===================================================== */

        .content-card {

            background: #fff;

            border: 1px solid #e1e7f0;

            border-radius: 12px;

            overflow: hidden;
        }


        .card-header {

            display: flex;

            align-items: center;

            gap: 7px;

            padding: 20px 22px;

            border-bottom: 1px solid #edf0f5;
        }


        .card-header h3 {

            margin: 0;

            font-size: 16px;

            font-weight: 600;

            color: #14213d;
        }


        .card-header i {

            color: #17243a;

            font-size: 16px;
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
           FORM GROUP
        ===================================================== */

        .form-group {

            margin-bottom: 20px;
        }


        .form-label {

            display: block;

            margin-bottom: 7px;

            font-size: 11px;

            font-weight: 500;

            color: #374151;
        }


        .required {

            color: #dc3545;
        }


        /* =====================================================
           FORM CONTROL
        ===================================================== */

        .form-control {

            width: 100%;

            height: 36px;

            padding: 0 12px;

            border: 1px solid #dce3ee;

            border-radius: 7px;

            outline: none;

            background: #fff;

            color: #374151;

            font-family: 'Poppins', sans-serif;

            font-size: 11px;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }


        .form-control:focus {

            border-color: #2864e6;

            box-shadow:
                0 0 0 2px
                rgba(40, 100, 230, 0.08);
        }


        /* =====================================================
           TEXTAREA
        ===================================================== */

        textarea.form-control {

            height: 88px;

            padding: 10px 12px;

            resize: vertical;

            line-height: 1.5;
        }


        /* =====================================================
           PASIEN
        ===================================================== */

        .patient-info {

            width: 100%;

            min-height: 38px;

            display: flex;

            align-items: center;

            gap: 7px;

            padding: 0 12px;

            border: 1px solid #dce3ee;

            border-radius: 7px;

            background: #f8faff;

            color: #17243a;

            font-size: 11px;
        }


        .patient-info i {

            font-size: 12px;

            color: #374151;
        }


        .patient-info strong {

            font-weight: 600;
        }


        /* =====================================================
           FORM ACTIONS
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

            transition:
                background 0.2s ease,
                transform 0.1s ease;
        }


        .btn:active {

            transform: translateY(1px);
        }


        /* =====================================================
           BUTTON BATAL
        ===================================================== */

        .btn-secondary {

            background: #f1f4f8;

            color: #4b5563;
        }


        .btn-secondary:hover {

            background: #e7ebf1;

            color: #374151;
        }


        /* =====================================================
           BUTTON SIMPAN
        ===================================================== */

        .btn-primary {

            background: #2864e6;

            color: #fff;
        }


        .btn-primary:hover {

            background: #1e55ca;

            color: #fff;
        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {

            position: relative !important;

            z-index: 1000 !important;

            display: flex !important;

            align-items: center !important;

            justify-content: space-between !important;

            min-height: 70px !important;

            padding: 0 24px !important;

            background: #ffffff !important;

            border-bottom: 1px solid #e5eaf2 !important;
        }


        .topbar h1 {

            margin: 0 !important;

            color: #2864e6 !important;

            font-size: 20px !important;

            font-weight: 700 !important;
        }


        .topbar-right {

            display: flex !important;

            align-items: center !important;

            margin-left: auto !important;

            position: relative !important;

            z-index: 1001 !important;
        }


        /* =====================================================
           PROFILE PETUGAS - BIRU
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


        /* =====================================================
           NAMA + ROLE
        ===================================================== */

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


        /* =====================================================
           AVATAR BIRU
        ===================================================== */

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


        /* =====================================================
           CHEVRON
        ===================================================== */

        .profile-arrow {

            font-size: 11px;

            color: #7a8499;

            margin-left: 1px;

            transition:
                transform .2s ease;
        }


        .profile-button:hover .profile-arrow {

            color: #2864e6;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 700px) {

            .sidebar {

                width: 180px;

                min-width: 180px;
            }


            .main-content {

                margin-left: 180px;

                width: calc(100% - 180px);
            }


            .page-content {

                padding: 18px;
            }


            .form-actions {

                flex-direction: column-reverse;

                align-items: stretch;
            }


            .btn {

                width: 100%;
            }


            .topbar {

                padding: 0 16px !important;
            }


            .profile-top-text {

                display: none;
            }


            .profile-top-avatar {

                width: 40px;

                height: 40px;

                min-width: 40px;

                font-size: 15px;
            }

        }

    </style>

</head>


<body>


<div class="dashboard-layout">


    <!-- =====================================================
         SIDEBAR PETUGAS
    ===================================================== -->

    <aside class="sidebar">


        <!-- LOGO -->

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


        <!-- MENU -->

        <nav class="sidebar-menu">


            <!-- DASHBOARD -->

            <a
                href="dashboard_petugas.php"
                class="menu-item"
            >

                <i class="bi bi-grid-1x2"></i>


                <span>
                    Dashboard
                </span>

            </a>


            <!-- DATA PASIEN -->

            <a
                href="data_pasien_petugas.php"
                class="menu-item"
            >

                <i class="bi bi-people"></i>


                <span>
                    Data Pasien
                </span>

            </a>


            <!-- MONITORING -->

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


        <!-- LOGOUT -->

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
         MAIN CONTENT
    ===================================================== -->

    <main class="main-content">


        <!-- =================================================
             TOPBAR
        ================================================= -->

        <header class="topbar">


            <h1>
                Edit Monitoring
            </h1>


            <div class="topbar-right">


                <!-- =================================================
                     PROFILE PETUGAS
                ================================================= -->

                <div
                    class="user-profile"
                    id="userProfile"
                >


                    <button
                        type="button"
                        class="profile-button"
                        id="profileButton"
                    >


                        <!-- NAMA -->

                        <span class="profile-top-text">


                            <strong>

                                <?= htmlspecialchars(
                                    $nama_user
                                ); ?>

                            </strong>


                            <small>
                                petugas
                            </small>


                        </span>


                        <!-- AVATAR -->

                        <span class="profile-top-avatar">


                            <?= htmlspecialchars(
                                $inisial_user
                            ); ?>


                        </span>


                        <!-- CHEVRON -->

                        <i
                            class="bi bi-chevron-down profile-arrow"
                        ></i>


                    </button>


                </div>


            </div>


        </header>


        <!-- =================================================
             PAGE CONTENT
        ================================================= -->

        <div class="page-content">


            <!-- =================================================
                 PAGE HEADER
            ================================================= -->

            <div class="page-header">


                <h2>
                    Edit Monitoring
                </h2>


                <p>
                    Perbarui data hasil monitoring pasien.
                </p>


            </div>


            <!-- =================================================
                 FORM CARD
            ================================================= -->

            <div class="content-card">


                <!-- CARD HEADER -->

                <div class="card-header">


                    <i class="bi bi-pencil-square"></i>


                    <h3>
                        Form Edit Monitoring
                    </h3>


                </div>


                <!-- CARD BODY -->

                <div class="card-body">


                    <!-- ERROR -->

                    <?php if ($error !== ''): ?>


                        <div class="error-data">


                            <?= htmlspecialchars(
                                $error
                            ); ?>


                        </div>


                    <?php endif; ?>


                    <!-- =================================================
                         FORM
                    ================================================= -->

                    <form
                        method="POST"
                        action=""
                    >


                        <!-- =================================================
                             NAMA PASIEN
                        ================================================= -->

                        <div class="form-group">


                            <label class="form-label">

                                Nama Pasien

                            </label>


                            <div class="patient-info">


                                <i class="bi bi-person"></i>


                                <strong>

                                    <?= htmlspecialchars(
                                        $data['nama_pasien'] ?? '-'
                                    ); ?>

                                </strong>


                            </div>


                        </div>


                        <!-- =================================================
                             TANGGAL MONITORING
                        ================================================= -->

                        <div class="form-group">


                            <label
                                for="tanggal_monitoring"
                                class="form-label"
                            >

                                Tanggal Monitoring

                            </label>


                            <input
                                type="datetime-local"
                                id="tanggal_monitoring"
                                name="tanggal_monitoring"
                                class="form-control"
                                value="<?= htmlspecialchars($tanggal_form); ?>"
                                required
                            >


                        </div>


                        <!-- =================================================
                             KONDISI
                        ================================================= -->

                        <div class="form-group">


                            <label
                                for="kondisi"
                                class="form-label"
                            >

                                Kondisi

                            </label>


                            <input
                                type="text"
                                id="kondisi"
                                name="kondisi"
                                class="form-control"
                                value="<?= htmlspecialchars($kondisi); ?>"
                                placeholder="Masukkan kondisi pasien..."
                                required
                            >


                        </div>


                        <!-- =================================================
                             AKTIVITAS HARIAN
                        ================================================= -->

                        <div class="form-group">


                            <label
                                for="aktivitas_harian"
                                class="form-label"
                            >

                                Aktivitas Harian


                                <span class="required">
                                    *
                                </span>


                            </label>


                            <textarea
                                id="aktivitas_harian"
                                name="aktivitas_harian"
                                class="form-control"
                                placeholder="Masukkan aktivitas harian pasien..."
                                required
                            ><?= htmlspecialchars($aktivitas_harian); ?></textarea>


                        </div>


                        <!-- =================================================
                             PERILAKU
                        ================================================= -->

                        <div class="form-group">


                            <label
                                for="perilaku"
                                class="form-label"
                            >

                                Perilaku


                                <span class="required">
                                    *
                                </span>


                            </label>


                            <textarea
                                id="perilaku"
                                name="perilaku"
                                class="form-control"
                                placeholder="Masukkan kondisi atau perilaku pasien..."
                                required
                            ><?= htmlspecialchars($perilaku); ?></textarea>


                        </div>


                        <!-- =================================================
                             CATATAN
                        ================================================= -->

                        <div class="form-group">


                            <label
                                for="catatan"
                                class="form-label"
                            >

                                Catatan

                            </label>


                            <textarea
                                id="catatan"
                                name="catatan"
                                class="form-control"
                                placeholder="Masukkan catatan tambahan..."
                            ><?= htmlspecialchars($catatan); ?></textarea>


                        </div>


                        <!-- =================================================
                             BUTTON
                        ================================================= -->

                        <div class="form-actions">


                            <!-- BATAL -->

                            <a
                                href="monitoring_petugas.php"
                                class="btn btn-secondary"
                            >

                                <i class="bi bi-arrow-left"></i>


                                Batal

                            </a>


                            <!-- SIMPAN -->

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


        </div>


    </main>


</div>


</body>

</html>