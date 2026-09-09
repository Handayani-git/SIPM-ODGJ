<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "koneksi.php";


/* =========================
   CEK ID MONITORING
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: monitoring.php");
    exit;
}

$id_monitoring = (int) $_GET['id'];


/* =========================
   PROSES UPDATE
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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


    /* =========================
       VALIDASI
    ========================= */

    if (
        empty($tanggal_monitoring) ||
        empty($kondisi) ||
        empty($aktivitas_harian) ||
        empty($perilaku)
    ) {

        $error = "Data wajib belum lengkap.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE monitoring SET
                tanggal_monitoring = ?,
                berat_badan = ?,
                kondisi = ?,
                aktivitas_harian = ?,
                perilaku = ?,
                catatan = ?
             WHERE id_monitoring = ?"
        );


        if (!$stmt) {

            $error = "Query gagal dibuat: " . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "sdssssi",
                $tanggal_monitoring,
                $berat_badan,
                $kondisi,
                $aktivitas_harian,
                $perilaku,
                $catatan,
                $id_monitoring
            );


            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                header(
                    "Location: detail_monitoring.php?id="
                    . $id_monitoring
                    . "&status=updated"
                );

                exit;

            } else {

                $error =
                    "Data monitoring gagal diperbarui: "
                    . mysqli_stmt_error($stmt);

                mysqli_stmt_close($stmt);
            }

        }

    }

}


/* =========================
   AMBIL DATA MONITORING
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        m.*,
        p.nama_pasien,
        p.nomor_registrasi
     FROM monitoring m
     LEFT JOIN pasien p
        ON m.id_pasien = p.id_pasien
     WHERE m.id_monitoring = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_monitoring
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$monitoring = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================
   CEK DATA
========================= */

if (!$monitoring) {
    die("Data monitoring tidak ditemukan.");
}


/* =========================
   FORMAT TANGGAL UNTUK INPUT
========================= */

$tanggal_input = !empty($monitoring['tanggal_monitoring'])
    ? date(
        'Y-m-d\TH:i',
        strtotime($monitoring['tanggal_monitoring'])
    )
    : '';

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
        Edit Monitoring - Sistem Informasi ODGJ
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

        }


        .patient-box {

            padding: 16px 18px;

            background: #f6f8fc;

            border: 1px solid #e1e7f0;

            border-radius: 9px;

            margin-bottom: 24px;

        }


        .patient-label {

            display: block;

            color: #8a95a8;

            font-size: 11px;

            margin-bottom: 5px;

        }


        .patient-name {

            display: block;

            color: #17243a;

            font-size: 14px;

            font-weight: 600;

        }


        .patient-registration {

            display: block;

            color: #7a8499;

            font-size: 11px;

            margin-top: 3px;

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
        .form-group select,
        .form-group textarea {

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
        .form-group select:focus,
        .form-group textarea:focus {

            border-color: #2864e6;

        }


        .form-group textarea {

            min-height: 105px;

            resize: vertical;

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

            min-width: 58px !important;

            min-height: 58px !important;

            object-fit: contain !important;

            margin: 0 auto 7px !important;

        }


        .dashboard-layout .sidebar-logo h2 {

            margin: 0 !important;

            font-size: 17px !important;

            line-height: 1.3 !important;

        }


        .dashboard-layout .sidebar-logo p {

            margin: 3px 0 0 !important;

            font-size: 9px !important;

            line-height: 1.3 !important;

        }


        .dashboard-layout .main-content {

            margin-left: 218px !important;

            width: calc(100% - 218px) !important;

            min-width: 0 !important;

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
                class="menu-item"
            >
                <i class="bi bi-person-gear"></i>
                <span>Data User</span>
            </a>


            <a
                href="monitoring.php"
                class="menu-item active"
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
                Edit Monitoring
            </h1>


            <div class="topbar-right">

                <div class="search-box">

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        placeholder="Search..."
                    >

                </div>


                <i
                    class="bi bi-bell"
                    style="font-size:20px;color:#26334a;"
                ></i>


                <div class="profile">

                    <strong>
                        Selamat Datang, Admin
                    </strong>

                    <small>
                        Admin Profile
                    </small>

                </div>


                <div class="profile-avatar">
                    A
                </div>

            </div>

        </header>


        <!-- CONTENT -->

        <div class="page-content">


            <div class="page-header">

                <div class="page-title">

                    <h2>
                        Edit Data Monitoring
                    </h2>

                    <p>
                        Perbarui data hasil monitoring pasien
                    </p>

                </div>


                <a
                    href="detail_monitoring.php?id=<?= $id_monitoring; ?>"
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

                        <i class="bi bi-clipboard2-pulse"></i>

                    </div>

                    <h3>
                        Data Monitoring
                    </h3>

                </div>


                <!-- DATA PASIEN -->

                <div class="patient-box">

                    <span class="patient-label">
                        Pasien
                    </span>

                    <span class="patient-name">

                        <?= htmlspecialchars(
                            $monitoring['nama_pasien'] ?? '-'
                        ); ?>

                    </span>

                    <span class="patient-registration">

                        No. Registrasi:
                        <?= htmlspecialchars(
                            $monitoring['nomor_registrasi'] ?? '-'
                        ); ?>

                    </span>

                </div>


                <form
                    method="POST"
                    action=""
                >


                    <div class="form-grid">


                        <!-- TANGGAL -->

                        <div class="form-group">

                            <label>
                                Tanggal Monitoring
                                <span class="required">*</span>
                            </label>

                            <input
                                type="datetime-local"
                                name="tanggal_monitoring"
                                value="<?= htmlspecialchars($tanggal_input); ?>"
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
                                value="<?= htmlspecialchars(
                                    $monitoring['berat_badan'] ?? ''
                                ); ?>"
                                placeholder="Contoh: 65.50"
                            >

                        </div>


                        <!-- KONDISI -->

                        <div class="form-group">

                            <label>
                                Kondisi
                                <span class="required">*</span>
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
                                    <?= $monitoring['kondisi'] === 'Stabil'
                                        ? 'selected'
                                        : ''; ?>
                                >
                                    Stabil
                                </option>

                                <option
                                    value="Perlu Pantauan"
                                    <?= $monitoring['kondisi'] === 'Perlu Pantauan'
                                        ? 'selected'
                                        : ''; ?>
                                >
                                    Perlu Pantauan
                                </option>

                                <option
                                    value="Darurat"
                                    <?= $monitoring['kondisi'] === 'Darurat'
                                        ? 'selected'
                                        : ''; ?>
                                >
                                    Darurat
                                </option>

                            </select>

                        </div>


                        <!-- AKTIVITAS -->

                        <div class="form-group full-width">

                            <label>
                                Aktivitas Harian
                                <span class="required">*</span>
                            </label>

                            <textarea
                                name="aktivitas_harian"
                                required
                                placeholder="Masukkan aktivitas harian pasien"
                            ><?= htmlspecialchars(
                                $monitoring['aktivitas_harian'] ?? ''
                            ); ?></textarea>

                        </div>


                        <!-- PERILAKU -->

                        <div class="form-group full-width">

                            <label>
                                Perilaku
                                <span class="required">*</span>
                            </label>

                            <textarea
                                name="perilaku"
                                required
                                placeholder="Masukkan kondisi atau perilaku pasien"
                            ><?= htmlspecialchars(
                                $monitoring['perilaku'] ?? ''
                            ); ?></textarea>

                        </div>


                        <!-- CATATAN -->

                        <div class="form-group full-width">

                            <label>
                                Catatan
                            </label>

                            <textarea
                                name="catatan"
                                placeholder="Masukkan catatan tambahan"
                            ><?= htmlspecialchars(
                                $monitoring['catatan'] ?? ''
                            ); ?></textarea>

                        </div>


                    </div>


                    <!-- BUTTON -->

                    <div class="form-actions">

                        <a
                            href="detail_monitoring.php?id=<?= $id_monitoring; ?>"
                            class="cancel-button"
                        >
                            Batal
                        </a>


                        <button
                            type="submit"
                            class="save-button"
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


</body>

</html>