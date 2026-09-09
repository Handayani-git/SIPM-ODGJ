<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| HALAMAN KHUSUS PETUGAS
|--------------------------------------------------------------------------
*/
if (($_SESSION['role'] ?? '') !== 'petugas') {
    header("Location: login.php");
    exit;
}

require_once "koneksi.php";

$id_user = (int) $_SESSION['id_user'];
$nama_user = $_SESSION['nama_lengkap'] ?? 'Petugas';

/*
|--------------------------------------------------------------------------
| INISIAL USER
|--------------------------------------------------------------------------
*/
$inisial_user = strtoupper(
    substr(trim($nama_user), 0, 1)
);

if ($inisial_user === '') {
    $inisial_user = 'P';
}

$error = "";


/*
|--------------------------------------------------------------------------
| PROSES SIMPAN MONITORING
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_pasien = isset($_POST['id_pasien'])
        ? (int) $_POST['id_pasien']
        : 0;

    $tanggal_monitoring = $_POST['tanggal_monitoring'] ?? '';

    $berat_badan = (
        isset($_POST['berat_badan']) &&
        $_POST['berat_badan'] !== ''
    )
        ? (float) $_POST['berat_badan']
        : null;

    $kondisi = trim($_POST['kondisi'] ?? '');
    $aktivitas_harian = trim($_POST['aktivitas_harian'] ?? '');
    $perilaku = trim($_POST['perilaku'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */
    if (
        $id_pasien <= 0 ||
        empty($tanggal_monitoring) ||
        empty($kondisi) ||
        empty($aktivitas_harian) ||
        empty($perilaku)
    ) {

        $error = "Data wajib belum lengkap.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | CEK PASIEN
        |--------------------------------------------------------------------------
        */
        $cekPasien = mysqli_prepare(
            $conn,
            "SELECT id_pasien
             FROM pasien
             WHERE id_pasien = ?"
        );

        if (!$cekPasien) {

            $error = "Gagal memeriksa data pasien.";

        } else {

            mysqli_stmt_bind_param(
                $cekPasien,
                "i",
                $id_pasien
            );

            mysqli_stmt_execute($cekPasien);

            $hasilPasien = mysqli_stmt_get_result($cekPasien);

            if (mysqli_num_rows($hasilPasien) === 0) {

                $error = "Pasien tidak ditemukan.";

            }

            mysqli_stmt_close($cekPasien);
        }


        /*
        |--------------------------------------------------------------------------
        | SIMPAN DATA
        |--------------------------------------------------------------------------
        */
        if (empty($error)) {

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO monitoring
                (
                    id_pasien,
                    id_user,
                    tanggal_monitoring,
                    berat_badan,
                    kondisi,
                    aktivitas_harian,
                    perilaku,
                    catatan
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );


            if (!$stmt) {

                $error =
                    "Query gagal dibuat: "
                    . mysqli_error($conn);

            } else {

                mysqli_stmt_bind_param(
                    $stmt,
                    "iisdssss",
                    $id_pasien,
                    $id_user,
                    $tanggal_monitoring,
                    $berat_badan,
                    $kondisi,
                    $aktivitas_harian,
                    $perilaku,
                    $catatan
                );


                if (mysqli_stmt_execute($stmt)) {

                    mysqli_stmt_close($stmt);

                    /*
                    |--------------------------------------------------------------------------
                    | KEMBALI KE MONITORING PETUGAS
                    |--------------------------------------------------------------------------
                    */
                    header(
                        "Location: monitoring_petugas.php?status=added"
                    );

                    exit;

                } else {

                    $error =
                        "Data monitoring gagal disimpan: "
                        . mysqli_stmt_error($stmt);

                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA PASIEN
|--------------------------------------------------------------------------
*/
$pasienQuery = mysqli_query(
    $conn,
    "SELECT
        id_pasien,
        nama_pasien,
        nomor_registrasi
     FROM pasien
     ORDER BY nama_pasien ASC"
);

if (!$pasienQuery) {
    $error = "Data pasien gagal dimuat: " . mysqli_error($conn);
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

    <title>Tambah Monitoring - SIPM ODGJ</title>


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

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .dashboard-layout .sidebar {

            width: 230px !important;
            min-width: 230px !important;
            max-width: 230px !important;

            height: 100vh !important;

            position: fixed !important;

            left: 0;
            top: 0;

            overflow: hidden !important;

            z-index: 1000;
        }


        .dashboard-layout .sidebar-logo {

            width: 100% !important;

            text-align: center !important;

            padding: 25px 10px 18px !important;

            margin: 0 !important;
        }


        .dashboard-layout .sidebar-logo img {

            display: block !important;

            width: 58px !important;
            height: 58px !important;

            object-fit: contain !important;

            margin: 0 auto 8px !important;
        }


        .dashboard-layout .sidebar-logo h2 {

            margin: 0 !important;

            font-size: 17px !important;

            line-height: 1.3 !important;

            color: #2864e6 !important;
        }


        .dashboard-layout .sidebar-logo p {

            margin: 3px 0 0 !important;

            font-size: 9px !important;

            line-height: 1.3 !important;
        }


        .dashboard-layout .main-content {

            margin-left: 230px !important;

            width: calc(100% - 230px) !important;

            min-width: 0 !important;
        }


        /* =====================================================
           PAGE
        ===================================================== */

        .page-content {

            padding: 30px;
        }


        .page-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 24px;
        }


        .page-title h2 {

            margin: 0;

            color: #17243a;

            font-size: 25px;

            font-weight: 700;
        }


        .page-title p {

            margin: 6px 0 0;

            color: #7a8499;

            font-size: 13px;
        }


        /* =====================================================
           BACK BUTTON
        ===================================================== */

        .back-button {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 11px 17px;

            border: 1px solid #dce3ee;

            border-radius: 8px;

            background: #fff;

            color: #526078;

            text-decoration: none;

            font-size: 12px;

            font-weight: 500;
        }


        .back-button:hover {

            border-color: #2864e6;

            color: #2864e6;
        }


        /* =====================================================
           FORM CARD
        ===================================================== */

        .form-card {

            background: #fff;

            border: 1px solid #e1e7f0;

            border-radius: 12px;

            padding: 25px;
        }


        .section-title {

            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 24px;
        }


        .section-icon {

            width: 36px;
            height: 36px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 8px;

            background: #eaf1ff;

            color: #2864e6;
        }


        .section-title h3 {

            margin: 0;

            color: #17243a;

            font-size: 17px;

            font-weight: 600;
        }


        /* =====================================================
           ERROR
        ===================================================== */

        .alert-error {

            margin-bottom: 20px;

            padding: 13px 15px;

            border: 1px solid #ffd0d0;

            border-radius: 8px;

            background: #fff3f3;

            color: #d83b3b;

            font-size: 12px;
        }


        /* =====================================================
           FORM GRID
        ===================================================== */

        .form-grid {

            display: grid;

            grid-template-columns: repeat(2, 1fr);

            gap: 20px 28px;
        }


        .form-group {

            display: flex;

            flex-direction: column;
        }


        .form-group.full-width {

            grid-column: 1 / -1;
        }


        .form-group label {

            margin-bottom: 7px;

            color: #526078;

            font-size: 11px;

            font-weight: 500;
        }


        .required {

            color: #e53935;
        }


        .form-group input,
        .form-group select,
        .form-group textarea {

            width: 100%;

            border: 1px solid #dce3ee;

            border-radius: 8px;

            padding: 11px 13px;

            outline: none;

            background: #fff;

            color: #26334a;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;

            transition: .2s;
        }


        .form-group input,
        .form-group select {

            height: 44px;
        }


        .form-group textarea {

            min-height: 105px;

            resize: vertical;

            line-height: 1.6;
        }


        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {

            border-color: #2864e6;

            box-shadow:
                0 0 0 3px
                rgba(40, 100, 230, .08);
        }


        .patient-info {

            margin-top: 6px;

            color: #8a95a8;

            font-size: 10px;
        }


        /* =====================================================
           FORM ACTION
        ===================================================== */

        .form-actions {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 25px;

            padding-top: 20px;

            border-top: 1px solid #edf0f5;
        }


        .cancel-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 11px 18px;

            border: 1px solid #dce3ee;

            border-radius: 8px;

            background: #fff;

            color: #526078;

            text-decoration: none;

            font-size: 12px;

            font-weight: 500;
        }


        .cancel-button:hover {

            border-color: #2864e6;

            color: #2864e6;
        }


        .save-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            padding: 11px 20px;

            border: none;

            border-radius: 8px;

            background: #2864e6;

            color: #fff;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;
        }


        .save-button:hover {

            background: #1f55c8;
        }


        /* =====================================================
           TOPBAR PROFILE
        ===================================================== */

        .topbar-right {

            display: flex;

            align-items: center;

            gap: 8px;
        }


        /* SEARCH DIHILANGKAN */

        .search-box {

            display: none !important;
        }


        .profile-wrapper {

            position: relative;
        }


        .profile-button {

            display: flex;

            align-items: center;

            gap: 8px;

            border: none;

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

            width: 30px;

            height: 30px;

            min-width: 30px;

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

            justify-content: center;

            line-height: 1.2;
        }


        .profile-text strong {

            color: #172033;

            font-size: 9px;

            font-weight: 700;

            max-width: 130px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .profile-text small {

            color: #8a95a8;

            font-size: 7px;

            margin-top: 2px;
        }


        .profile-arrow {

            font-size: 8px;

            color: #7b8ba2;

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

            width: 215px;

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


        .dropdown-avatar {

            width: 36px;

            height: 36px;

            min-width: 36px;

            border-radius: 50%;

            background: #2864e6;

            color: #fff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 13px;

            font-weight: 700;
        }


        .profile-info {

            display: flex;

            flex-direction: column;

            min-width: 0;
        }


        .profile-info strong {

            font-size: 10px;

            color: #172033;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .profile-info span {

            font-size: 8px;

            color: #8a95a8;

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

            font-family: 'Poppins', sans-serif;

            font-size: 9px;

            font-weight: 500;

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
           PROFILE MODAL
        ===================================================== */

        .profile-modal {

            position: fixed;

            inset: 0;

            background: rgba(15, 23, 42, .35);

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;

            opacity: 0;

            visibility: hidden;

            transition: .2s;

            z-index: 5000;
        }


        .profile-modal.show {

            opacity: 1;

            visibility: visible;
        }


        .profile-modal-card {

            width: 100%;

            max-width: 360px;

            background: #fff;

            border-radius: 13px;

            box-shadow:
                0 18px 45px
                rgba(0, 0, 0, .16);

            overflow: hidden;
        }


        .profile-modal-top {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 15px 17px;

            border-bottom: 1px solid #edf1f5;
        }


        .profile-modal-top h3 {

            margin: 0;

            font-size: 13px;

            color: #17243a;
        }


        .profile-close {

            width: 28px;

            height: 28px;

            border: 0;

            border-radius: 7px;

            background: #f5f7fb;

            color: #69768c;

            display: flex;

            align-items: center;

            justify-content: center;

            cursor: pointer;
        }


        .profile-modal-body {

            padding: 20px 18px;
        }


        .profile-modal-avatar {

            width: 58px;

            height: 58px;

            margin: 0 auto 12px;

            border-radius: 50%;

            background: #2864e6;

            color: #fff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;

            font-weight: 700;
        }


        .profile-modal-name {

            text-align: center;

            font-size: 15px;

            font-weight: 700;

            color: #17243a;
        }


        .profile-modal-role {

            text-align: center;

            font-size: 9px;

            color: #8a95a8;

            margin: 3px 0 17px;
        }


        .profile-detail {

            border: 1px solid #e6ebf2;

            border-radius: 9px;

            overflow: hidden;
        }


        .profile-detail-row {

            display: flex;

            justify-content: space-between;

            gap: 15px;

            padding: 10px 12px;

            border-bottom: 1px solid #edf1f5;
        }


        .profile-detail-row:last-child {

            border-bottom: 0;
        }


        .profile-detail-label {

            font-size: 8px;

            color: #8a95a8;
        }


        .profile-detail-value {

            font-size: 9px;

            color: #17243a;

            font-weight: 600;

            text-align: right;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 800px) {

            .dashboard-layout .sidebar {

                width: 200px !important;

                min-width: 200px !important;

                max-width: 200px !important;
            }


            .dashboard-layout .main-content {

                margin-left: 200px !important;

                width: calc(100% - 200px) !important;
            }


            .page-content {

                padding: 18px;
            }


            .page-header {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;
            }


            .form-grid {

                grid-template-columns: 1fr;
            }


            .form-group.full-width {

                grid-column: auto;
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
                href="dashboard_petugas.php"
                class="menu-item"
            >

                <i class="bi bi-grid"></i>

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
         MAIN CONTENT
    ===================================================== -->

    <main class="main-content">


        <!-- =================================================
             TOPBAR
        ================================================= -->

        <header class="topbar">


            <h1>
                Tambah Monitoring
            </h1>


            <div class="topbar-right">


                <!-- PROFILE -->

                <div
                    class="profile-wrapper"
                    id="profileWrapper"
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


                        <i
                            class="bi bi-chevron-down profile-arrow"
                        ></i>


                    </button>



                    <!-- PROFILE DROPDOWN -->

                    <div
                        class="profile-dropdown"
                        id="profileDropdown"
                    >


                        <div class="profile-dropdown-header">


                            <div class="dropdown-avatar">

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



        <!-- =================================================
             CONTENT
        ================================================= -->

        <div class="page-content">


            <div class="page-header">


                <div class="page-title">

                    <h2>
                        Tambah Monitoring Pasien
                    </h2>

                    <p>
                        Tambahkan hasil monitoring perkembangan pasien ODGJ
                    </p>

                </div>


                <a
                    href="monitoring_petugas.php"
                    class="back-button"
                >

                    <i class="bi bi-arrow-left"></i>

                    Kembali

                </a>


            </div>



            <!-- =================================================
                 FORM CARD
            ================================================= -->

            <div class="form-card">


                <div class="section-title">


                    <div class="section-icon">

                        <i class="bi bi-clipboard2-pulse"></i>

                    </div>


                    <h3>
                        Data Monitoring
                    </h3>


                </div>



                <!-- ERROR -->

                <?php if (!empty($error)): ?>

                    <div class="alert-error">

                        <i class="bi bi-exclamation-circle"></i>

                        <?= htmlspecialchars($error); ?>

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

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <select
                                name="id_pasien"
                                required
                            >


                                <option value="">

                                    Pilih pasien

                                </option>


                                <?php

                                if ($pasienQuery) {

                                    while (
                                        $pasien =
                                        mysqli_fetch_assoc(
                                            $pasienQuery
                                        )
                                    ):

                                ?>


                                    <option
                                        value="<?= $pasien['id_pasien']; ?>"
                                        <?= (
                                            isset($_POST['id_pasien']) &&
                                            $_POST['id_pasien'] ==
                                            $pasien['id_pasien']
                                        )
                                            ? 'selected'
                                            : '';
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

                                }

                                ?>


                            </select>


                            <span class="patient-info">

                                Pilih pasien yang akan dimonitor.

                            </span>


                        </div>



                        <!-- TANGGAL -->

                        <div class="form-group">


                            <label>

                                Tanggal Monitoring

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <input
                                type="datetime-local"
                                name="tanggal_monitoring"
                                value="<?= htmlspecialchars(
                                    $_POST['tanggal_monitoring']
                                    ?? date('Y-m-d\TH:i')
                                ); ?>"
                                required
                            >


                        </div>



                        <!-- BERAT BADAN -->

                        <div class="form-group">


                            <label>

                                Berat Badan (kg)

                            </label>


                            <input
                                type="number"
                                name="berat_badan"
                                step="0.01"
                                min="0"
                                placeholder="Contoh: 65.50"
                                value="<?= htmlspecialchars(
                                    $_POST['berat_badan'] ?? ''
                                ); ?>"
                            >


                        </div>



                        <!-- KONDISI -->

                        <div class="form-group">


                            <label>

                                Kondisi

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <select
                                name="kondisi"
                                required
                            >


                                <option value="">

                                    Pilih kondisi

                                </option>


                                <option
                                    value="Stabil"
                                    <?= (
                                        ($_POST['kondisi'] ?? '')
                                        === 'Stabil'
                                    )
                                        ? 'selected'
                                        : '';
                                    ?>
                                >

                                    Stabil

                                </option>


                                <option
                                    value="Perlu Pantauan"
                                    <?= (
                                        ($_POST['kondisi'] ?? '')
                                        === 'Perlu Pantauan'
                                    )
                                        ? 'selected'
                                        : '';
                                    ?>
                                >

                                    Perlu Pantauan

                                </option>


                                <option
                                    value="Darurat"
                                    <?= (
                                        ($_POST['kondisi'] ?? '')
                                        === 'Darurat'
                                    )
                                        ? 'selected'
                                        : '';
                                    ?>
                                >

                                    Darurat

                                </option>


                            </select>


                        </div>



                        <!-- AKTIVITAS -->

                        <div class="form-group full-width">


                            <label>

                                Aktivitas Harian

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <textarea
                                name="aktivitas_harian"
                                placeholder="Masukkan aktivitas harian pasien..."
                                required
                            ><?= htmlspecialchars(
                                $_POST['aktivitas_harian'] ?? ''
                            ); ?></textarea>


                        </div>



                        <!-- PERILAKU -->

                        <div class="form-group full-width">


                            <label>

                                Perilaku

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <textarea
                                name="perilaku"
                                placeholder="Masukkan kondisi atau perilaku pasien..."
                                required
                            ><?= htmlspecialchars(
                                $_POST['perilaku'] ?? ''
                            ); ?></textarea>


                        </div>



                        <!-- CATATAN -->

                        <div class="form-group full-width">


                            <label>

                                Catatan

                            </label>


                            <textarea
                                name="catatan"
                                placeholder="Masukkan catatan tambahan..."
                            ><?= htmlspecialchars(
                                $_POST['catatan'] ?? ''
                            ); ?></textarea>


                        </div>


                    </div>



                    <!-- =================================================
                         ACTION
                    ================================================= -->

                    <div class="form-actions">


                        <a
                            href="monitoring_petugas.php"
                            class="cancel-button"
                        >

                            Batal

                        </a>


                        <button
                            type="submit"
                            class="save-button"
                        >

                            <i class="bi bi-check-lg"></i>

                            Simpan Monitoring

                        </button>


                    </div>


                </form>


            </div>


        </div>


    </main>


</div>



<!-- =========================================================
     PROFILE MODAL
========================================================= -->

<div
    class="profile-modal"
    id="profileModal"
>


    <div class="profile-modal-card">


        <div class="profile-modal-top">


            <h3>
                Profil Saya
            </h3>


            <button
                type="button"
                class="profile-close"
                id="closeProfile"
            >

                <i class="bi bi-x-lg"></i>

            </button>


        </div>



        <div class="profile-modal-body">


            <div class="profile-modal-avatar">

                <?= htmlspecialchars($inisial_user); ?>

            </div>


            <div class="profile-modal-name">

                <?= htmlspecialchars($nama_user); ?>

            </div>


            <div class="profile-modal-role">

                Petugas

            </div>



            <div class="profile-detail">


                <div class="profile-detail-row">

                    <span class="profile-detail-label">
                        Nama Lengkap
                    </span>

                    <span class="profile-detail-value">
                        <?= htmlspecialchars($nama_user); ?>
                    </span>

                </div>


                <div class="profile-detail-row">

                    <span class="profile-detail-label">
                        ID Pengguna
                    </span>

                    <span class="profile-detail-value">
                        <?= htmlspecialchars((string)$id_user); ?>
                    </span>

                </div>


                <div class="profile-detail-row">

                    <span class="profile-detail-label">
                        Role
                    </span>

                    <span class="profile-detail-value">
                        Petugas
                    </span>

                </div>


            </div>


        </div>


    </div>


</div>



<!-- =========================================================
     PROFILE SCRIPT
========================================================= -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const profileButton =
            document.getElementById(
                "profileButton"
            );


        const profileDropdown =
            document.getElementById(
                "profileDropdown"
            );


        const profileWrapper =
            document.getElementById(
                "profileWrapper"
            );


        const profileSaya =
            document.getElementById(
                "profileSaya"
            );


        const profileModal =
            document.getElementById(
                "profileModal"
            );


        const closeProfile =
            document.getElementById(
                "closeProfile"
            );



        /* =====================================================
           BUKA / TUTUP DROPDOWN
        ===================================================== */

        profileButton?.addEventListener(
            "click",
            function (e) {

                e.preventDefault();

                e.stopPropagation();

                profileDropdown?.classList.toggle(
                    "show"
                );

                profileButton?.classList.toggle(
                    "active"
                );

            }
        );



        /* =====================================================
           KLIK DI LUAR DROPDOWN
        ===================================================== */

        document.addEventListener(
            "click",
            function (e) {

                if (
                    profileWrapper &&
                    !profileWrapper.contains(
                        e.target
                    )
                ) {

                    profileDropdown?.classList.remove(
                        "show"
                    );

                    profileButton?.classList.remove(
                        "active"
                    );

                }

            }
        );



        /* =====================================================
           PROFIL SAYA
        ===================================================== */

        profileSaya?.addEventListener(
            "click",
            function (e) {

                e.preventDefault();

                e.stopPropagation();


                profileDropdown?.classList.remove(
                    "show"
                );


                profileButton?.classList.remove(
                    "active"
                );


                profileModal?.classList.add(
                    "show"
                );

            }
        );



        /* =====================================================
           TUTUP MODAL
        ===================================================== */

        closeProfile?.addEventListener(
            "click",
            function () {

                profileModal?.classList.remove(
                    "show"
                );

            }
        );



        /* =====================================================
           KLIK BACKDROP MODAL
        ===================================================== */

        profileModal?.addEventListener(
            "click",
            function (e) {

                if (
                    e.target ===
                    profileModal
                ) {

                    profileModal.classList.remove(
                        "show"
                    );

                }

            }
        );



        /* =====================================================
           ESCAPE
        ===================================================== */

        document.addEventListener(
            "keydown",
            function (e) {

                if (e.key === "Escape") {

                    profileModal?.classList.remove(
                        "show"
                    );

                    profileDropdown?.classList.remove(
                        "show"
                    );

                    profileButton?.classList.remove(
                        "active"
                    );

                }

            }
        );


    }
);

</script>


</body>

</html>