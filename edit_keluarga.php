<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "koneksi.php";


/* =========================
   CEK ID KELUARGA
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$id_keluarga = (int) $_GET['id'];


/* =========================
   AMBIL DATA KELUARGA
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT 
        k.id_keluarga,
        k.id_pasien,
        k.nama_keluarga,
        k.hubungan,
        k.nomor_telepon,
        k.alamat,
        p.nama_pasien,
        p.nomor_registrasi
     FROM keluarga k
     INNER JOIN pasien p 
        ON k.id_pasien = p.id_pasien
     WHERE k.id_keluarga = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_keluarga
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$keluarga = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================
   CEK DATA
========================= */

if (!$keluarga) {
    die("Data keluarga tidak ditemukan.");
}


$id_pasien = $keluarga['id_pasien'];


/* =========================
   PROSES UPDATE
========================= */

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_keluarga = trim($_POST['nama_keluarga'] ?? '');
    $hubungan = trim($_POST['hubungan'] ?? '');
    $nomor_telepon = trim($_POST['nomor_telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');


    /* VALIDASI */

    if ($nama_keluarga === '') {

        $error = "Nama keluarga wajib diisi.";

    } elseif ($hubungan === '') {

        $error = "Hubungan dengan pasien wajib diisi.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE keluarga
             SET
                nama_keluarga = ?,
                hubungan = ?,
                nomor_telepon = ?,
                alamat = ?
             WHERE id_keluarga = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssssi",
            $nama_keluarga,
            $hubungan,
            $nomor_telepon,
            $alamat,
            $id_keluarga
        );


        if (mysqli_stmt_execute($stmt)) {

            mysqli_stmt_close($stmt);

            header(
                "Location: detail_pasien.php?id=" .
                $id_pasien .
                "&status=family_updated"
            );

            exit;

        } else {

            $error = "Data keluarga gagal diperbarui: " .
                     mysqli_error($conn);

            mysqli_stmt_close($stmt);
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
        Edit Keluarga - Sistem Informasi ODGJ
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

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: #f6f8fc;
            color: #17243a;
        }


        /* =========================
           LAYOUT
        ========================= */

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }


        .main-content {
            margin-left: 220px;
            width: calc(100% - 220px);
        }


        /* =========================
           PAGE CONTENT
        ========================= */

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
            font-size: 26px;
            font-weight: 700;
        }


        .page-title p {
            margin: 6px 0 0;
            color: #7a8499;
            font-size: 14px;
        }


        /* =========================
           BACK BUTTON
        ========================= */

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

            transition: 0.2s;
        }


        .back-button:hover {
            border-color: #2864e6;
            color: #2864e6;
        }


        /* =========================
           FORM CARD
        ========================= */

        .form-card {
            background: white;

            border: 1px solid #e1e7f0;

            border-radius: 12px;

            padding: 28px;

            max-width: 100%;
        }


        /* =========================
           SECTION TITLE
        ========================= */

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;

            margin-bottom: 22px;

            padding-bottom: 16px;

            border-bottom: 1px solid #edf0f5;
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


        /* =========================
           PATIENT INFO
        ========================= */

        .patient-info {
            background: #f6f8fc;

            border: 1px solid #e1e7f0;

            border-radius: 10px;

            padding: 16px;

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

            font-size: 15px;

            font-weight: 600;
        }


        .patient-registration {
            display: block;

            color: #8a95a8;

            font-size: 11px;

            margin-top: 3px;
        }


        /* =========================
           FORM GRID
        ========================= */

        .form-grid {
            display: grid;

            grid-template-columns: repeat(2, 1fr);

            gap: 20px 25px;
        }


        .form-group {
            display: flex;

            flex-direction: column;

            gap: 7px;
        }


        .form-group.full-width {
            grid-column: 1 / -1;
        }


        .form-group label {
            font-size: 12px;

            font-weight: 500;

            color: #26334a;
        }


        .required {
            color: #e53935;
        }


        .form-group input,
        .form-group select,
        .form-group textarea {

            width: 100%;

            padding: 13px 14px;

            border: 1px solid #dce3ee;

            border-radius: 8px;

            background: white;

            color: #26334a;

            font-family: 'Poppins', sans-serif;

            font-size: 13px;

            outline: none;

            transition: 0.2s;
        }


        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {

            border-color: #2864e6;

            box-shadow: 0 0 0 3px rgba(40, 100, 230, 0.08);
        }


        .form-group textarea {

            min-height: 120px;

            resize: vertical;
        }


        /* =========================
           ERROR
        ========================= */

        .error-message {

            background: #ffe8e8;

            border: 1px solid #ffcaca;

            color: #d83b3b;

            padding: 12px 14px;

            border-radius: 8px;

            font-size: 12px;

            margin-bottom: 20px;
        }


        /* =========================
           BUTTON
        ========================= */

        .form-actions {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 28px;

            padding-top: 20px;

            border-top: 1px solid #edf0f5;
        }


        .button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 12px 20px;

            border-radius: 8px;

            font-family: 'Poppins', sans-serif;

            font-size: 13px;

            font-weight: 500;

            text-decoration: none;

            cursor: pointer;

            border: none;

            transition: 0.2s;
        }


        .button-cancel {

            background: white;

            border: 1px solid #dce3ee;

            color: #526078;
        }


        .button-cancel:hover {

            border-color: #2864e6;

            color: #2864e6;
        }


        .button-save {

            background: #2864e6;

            color: white;
        }


        .button-save:hover {

            background: #1f55c7;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 800px) {

            .main-content {

                margin-left: 190px;

                width: calc(100% - 190px);
            }


            .page-content {

                padding: 20px;
            }


            .form-grid {

                grid-template-columns: 1fr;
            }


            .form-group.full-width {

                grid-column: auto;
            }


            .page-header {

                align-items: flex-start;

                gap: 15px;

                flex-direction: column;
            }

        }

    </style>

</head>


<body>


<div class="dashboard-layout">


    <!-- =========================
         SIDEBAR
    ========================= -->

    <aside class="sidebar">

        <div class="sidebar-logo">

    <img
        src="assets/img/logo YCKA.png"
        alt="Logo Yayasan Cahaya Kasih Amanah"
        style="
            width: 58px !important;
            height: 58px !important;
            max-width: 58px !important;
            max-height: 58px !important;
            min-width: 58px !important;
            min-height: 58px !important;
            object-fit: contain !important;
            display: block !important;
            margin: 0 auto 7px auto !important;
            position: static !important;
        "
    >

    <h2>SIPM ODGJ</h2>

            <p>
                Yayasan Cahaya Kasih Amanah
            </p>

        </div>


        <nav class="sidebar-menu">

            <a href="dashboard.php">
                <i class="bi bi-grid"></i>
                <span>Dashboard</span>
            </a>


            <a href="data_pasien.php">
                <i class="bi bi-people"></i>
                <span>Data Pasien</span>
            </a>


            <a href="#">
                <i class="bi bi-person-gear"></i>
                <span>Data User</span>
            </a>


            <a href="#">
                <i class="bi bi-clipboard2-pulse"></i>
                <span>Monitoring</span>
            </a>


            <a href="#">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Laporan</span>
            </a>

        </nav>


        <div class="sidebar-bottom">

            <a href="logout.php" class="logout-button">

                <i class="bi bi-box-arrow-left"></i>

                <span>Logout</span>

            </a>

        </div>

    </aside>


    <!-- =========================
         MAIN
    ========================= -->

    <main class="main-content">


        <!-- =========================
             TOPBAR
        ========================= -->

        <header class="topbar">

            <div></div>

            <div class="topbar-right">

                <div class="search-box">

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        placeholder="Search..."
                    >

                </div>


                <i class="bi bi-bell notification-icon"></i>


                <div class="user-info">

                    <strong>
                        Selamat Datang, Admin
                    </strong>

                    <small>
                        Admin Profile
                    </small>

                </div>


                <div class="user-avatar">
                    A
                </div>

            </div>

        </header>


        <!-- =========================
             CONTENT
        ========================= -->

        <div class="page-content">


            <div class="page-header">

                <div class="page-title">

                    <h2>
                        Edit Data Keluarga
                    </h2>

                    <p>
                        Perbarui data keluarga atau wali pasien
                    </p>

                </div>


                <a
                    href="detail_pasien.php?id=<?= $id_pasien; ?>"
                    class="back-button"
                >

                    <i class="bi bi-arrow-left"></i>

                    Kembali

                </a>

            </div>


            <div class="form-card">


                <div class="section-title">

                    <div class="section-icon">

                        <i class="bi bi-people"></i>

                    </div>

                    <h3>
                        Data Keluarga / Wali
                    </h3>

                </div>


                <?php if ($error !== ''): ?>

                    <div class="error-message">

                        <i class="bi bi-exclamation-circle"></i>

                        <?= htmlspecialchars($error); ?>

                    </div>

                <?php endif; ?>


                <!-- INFO PASIEN -->

                <div class="patient-info">

                    <span class="patient-label">
                        Pasien
                    </span>

                    <span class="patient-name">
                        <?= htmlspecialchars($keluarga['nama_pasien']); ?>
                    </span>

                    <span class="patient-registration">
                        No. Registrasi:
                        <?= htmlspecialchars($keluarga['nomor_registrasi']); ?>
                    </span>

                </div>


                <!-- FORM -->

                <form method="POST">


                    <div class="form-grid">


                        <!-- NAMA -->

                        <div class="form-group">

                            <label for="nama_keluarga">

                                Nama Keluarga

                                <span class="required">*</span>

                            </label>

                            <input
                                type="text"
                                id="nama_keluarga"
                                name="nama_keluarga"
                                value="<?= htmlspecialchars($keluarga['nama_keluarga']); ?>"
                                placeholder="Masukkan nama keluarga"
                                required
                            >

                        </div>


                        <!-- HUBUNGAN -->

                        <div class="form-group">

                            <label for="hubungan">

                                Hubungan dengan Pasien

                                <span class="required">*</span>

                            </label>

                            <select
                                id="hubungan"
                                name="hubungan"
                                required
                            >

                                <option value="">
                                    Pilih hubungan
                                </option>

                                <option
                                    value="Ayah"
                                    <?= $keluarga['hubungan'] === 'Ayah' ? 'selected' : ''; ?>
                                >
                                    Ayah
                                </option>

                                <option
                                    value="Ibu"
                                    <?= $keluarga['hubungan'] === 'Ibu' ? 'selected' : ''; ?>
                                >
                                    Ibu
                                </option>

                                <option
                                    value="Suami"
                                    <?= $keluarga['hubungan'] === 'Suami' ? 'selected' : ''; ?>
                                >
                                    Suami
                                </option>

                                <option
                                    value="Istri"
                                    <?= $keluarga['hubungan'] === 'Istri' ? 'selected' : ''; ?>
                                >
                                    Istri
                                </option>

                                <option
                                    value="Anak"
                                    <?= $keluarga['hubungan'] === 'Anak' ? 'selected' : ''; ?>
                                >
                                    Anak
                                </option>

                                <option
                                    value="Saudara"
                                    <?= $keluarga['hubungan'] === 'Saudara' ? 'selected' : ''; ?>
                                >
                                    Saudara
                                </option>

                                <option
                                    value="Wali"
                                    <?= $keluarga['hubungan'] === 'Wali' ? 'selected' : ''; ?>
                                >
                                    Wali
                                </option>

                                <option
                                    value="Lainnya"
                                    <?= $keluarga['hubungan'] === 'Lainnya' ? 'selected' : ''; ?>
                                >
                                    Lainnya
                                </option>

                            </select>

                        </div>


                        <!-- TELEPON -->

                        <div class="form-group">

                            <label for="nomor_telepon">
                                Nomor Telepon
                            </label>

                            <input
                                type="text"
                                id="nomor_telepon"
                                name="nomor_telepon"
                                value="<?= htmlspecialchars($keluarga['nomor_telepon'] ?? ''); ?>"
                                placeholder="Contoh: 081234567890"
                            >

                        </div>


                        <!-- ALAMAT -->

                        <div class="form-group full-width">

                            <label for="alamat">
                                Alamat
                            </label>

                            <textarea
                                id="alamat"
                                name="alamat"
                                placeholder="Masukkan alamat lengkap keluarga atau wali"
                            ><?= htmlspecialchars($keluarga['alamat'] ?? ''); ?></textarea>

                        </div>


                    </div>


                    <!-- BUTTON -->

                    <div class="form-actions">

                        <a
                            href="detail_pasien.php?id=<?= $id_pasien; ?>"
                            class="button button-cancel"
                        >

                            <i class="bi bi-x-lg"></i>

                            Batal

                        </a>


                        <button
                            type="submit"
                            class="button button-save"
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