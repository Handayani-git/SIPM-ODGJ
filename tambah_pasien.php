<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "koneksi.php";

$role = $_SESSION['role'] ?? '';
$nama_user = $_SESSION['nama_lengkap'] ?? ($role === 'admin' ? 'Admin' : 'Petugas');
$role_label = ($role === 'admin') ? 'Admin' : 'Petugas';
$inisial_user = strtoupper(substr(trim($nama_user), 0, 1));


// ===============================
// PROSES SIMPAN DATA PASIEN
// ===============================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nomor_registrasi = trim($_POST['nomor_registrasi']);
    $nama_pasien      = trim($_POST['nama_pasien']);
    $nik              = trim($_POST['nik']);
    $jenis_kelamin    = $_POST['jenis_kelamin'];
    $tempat_lahir     = trim($_POST['tempat_lahir']);
    $tanggal_lahir    = $_POST['tanggal_lahir'];
    $alamat           = trim($_POST['alamat']);
    $status_lokasi    = $_POST['status_lokasi'];
    $kondisi          = $_POST['kondisi'];
    $tanggal_masuk    = $_POST['tanggal_masuk'];
    $status_pasien    = $_POST['status_pasien'];


    // Cek nomor registrasi sudah digunakan atau belum
    $cek = mysqli_prepare(
        $conn,
        "SELECT id_pasien FROM pasien WHERE nomor_registrasi = ?"
    );

    mysqli_stmt_bind_param(
        $cek,
        "s",
        $nomor_registrasi
    );

    mysqli_stmt_execute($cek);

    $hasil_cek = mysqli_stmt_get_result($cek);


    if (mysqli_num_rows($hasil_cek) > 0) {

        $error = "Nomor registrasi sudah digunakan.";

    } else {

        // INSERT DATA
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO pasien
            (
                nomor_registrasi,
                nama_pasien,
                nik,
                jenis_kelamin,
                tempat_lahir,
                tanggal_lahir,
                alamat,
                status_lokasi,
                kondisi,
                tanggal_masuk,
                status_pasien
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );


        mysqli_stmt_bind_param(
            $stmt,
            "sssssssssss",
            $nomor_registrasi,
            $nama_pasien,
            $nik,
            $jenis_kelamin,
            $tempat_lahir,
            $tanggal_lahir,
            $alamat,
            $status_lokasi,
            $kondisi,
            $tanggal_masuk,
            $status_pasien
        );


        if (mysqli_stmt_execute($stmt)) {

            header("Location: data_pasien.php?status=success");
            exit;

        } else {

            $error = "Data pasien gagal disimpan: " . mysqli_error($conn);

        }

    }

}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Tambah Pasien - Sistem Informasi ODGJ</title>


    <!-- Google Font -->

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
          rel="stylesheet">


    <!-- Bootstrap Icons -->

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


    <!-- Dashboard CSS -->

    <link rel="stylesheet"
          href="/SIPM-ODGJ/assets/css/dashboard.css">


    <style>

        /* ===============================
           PAGE
        =============================== */

        .page-content {
            padding: 28px;
        }


        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
        }


        .page-title h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
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

            padding: 10px 16px;

            border: 1px solid #dce3ee;
            border-radius: 8px;

            background: white;
            color: #526078;

            text-decoration: none;

            font-size: 12px;
            font-weight: 500;
        }


        .back-button:hover {
            background: #f7f9fc;
        }


        /* ===============================
           FORM CARD
        =============================== */

        .form-card {
            background: white;

            border: 1px solid #e1e7f0;

            border-radius: 12px;

            padding: 28px;
        }


        .form-section {
            margin-bottom: 30px;
        }


        .form-section:last-child {
            margin-bottom: 0;
        }


        .form-section-title {
            display: flex;
            align-items: center;
            gap: 10px;

            margin-bottom: 20px;

            padding-bottom: 12px;

            border-bottom: 1px solid #edf0f5;
        }


        .form-section-title i {
            width: 30px;
            height: 30px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 7px;

            background: #e8f0ff;
            color: #2864e6;

            font-size: 14px;
        }


        .form-section-title h3 {
            margin: 0;

            font-size: 15px;
            font-weight: 600;
        }


        /* ===============================
           FORM GRID
        =============================== */

        .form-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 18px 22px;
        }


        .form-group {
            display: flex;
            flex-direction: column;
        }


        .form-group.full {
            grid-column: 1 / -1;
        }


        .form-group label {
            margin-bottom: 7px;

            font-size: 12px;
            font-weight: 500;

            color: #344054;
        }


        .required {
            color: #e53935;
        }


        .form-group input,
        .form-group select,
        .form-group textarea {

            width: 100%;

            box-sizing: border-box;

            padding: 11px 13px;

            border: 1px solid #dce3ee;

            border-radius: 8px;

            outline: none;

            background: white;

            color: #26334a;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;

            transition: .2s;
        }


        .form-group textarea {
            min-height: 90px;
            resize: vertical;
        }


        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {

            border-color: #2864e6;

            box-shadow:
                0 0 0 3px rgba(40, 100, 230, .08);
        }


        .form-help {
            margin-top: 5px;

            color: #929cad;

            font-size: 10px;
        }


        /* ===============================
           ERROR
        =============================== */

        .error-message {

            display: flex;
            align-items: center;
            gap: 9px;

            margin-bottom: 20px;

            padding: 12px 15px;

            border: 1px solid #ffd1d1;

            border-radius: 8px;

            background: #fff5f5;

            color: #d93636;

            font-size: 12px;
        }


        /* ===============================
           BUTTON
        =============================== */

        .form-actions {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 30px;

            padding-top: 20px;

            border-top: 1px solid #edf0f5;
        }


        .cancel-button,
        .save-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 11px 20px;

            border-radius: 8px;

            font-family: 'Poppins', sans-serif;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;

            text-decoration: none;
        }


        .cancel-button {

            border: 1px solid #dce3ee;

            background: white;

            color: #667085;
        }


        .cancel-button:hover {
            background: #f8f9fb;
        }


        .save-button {

            border: none;

            background: #2864e6;

            color: white;
        }


        .save-button:hover {
            background: #1f56ca;
        }


        /* ===============================
           RESPONSIVE
        =============================== */

        @media (max-width: 800px) {

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
            }

        }

        /* =========================
   PROFILE TOPBAR
========================= */

.profile-wrapper {
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
    text-transform: lowercase;
}

.profile-avatar {
    width: 40px;
    height: 40px;
    min-width: 40px;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #2864e6;
    color: #ffffff;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    font-weight: 600;
    box-sizing: border-box;
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
   PROFILE DROPDOWN
========================= */

.profile-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 210px;
    background: #fff;
    border: 1px solid #e1e7f0;
    border-radius: 12px;
    box-shadow: 0 12px 30px rgba(31, 48, 84, 0.12);
    padding: 8px;

    opacity: 0;
    visibility: hidden;
    transform: translateY(-6px);

    transition: 0.2s ease;
    z-index: 1200;
}

.topbar-profile.open .profile-dropdown {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.profile-dropdown-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-bottom: 1px solid #edf0f5;
    margin-bottom: 5px;
}

.profile-dropdown-name {
    color: #17243a;
    font-size: 12px;
    font-weight: 600;
    margin: 0;
}

.profile-dropdown-role {
    color: #8a95a8;
    font-size: 10px;
    margin: 2px 0 0;
}

.profile-dropdown-item {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 9px;

    padding: 10px;

    border: 0;
    border-radius: 8px;

    background: transparent;
    color: #526078;

    text-decoration: none;

    font-family: 'Poppins', sans-serif;
    font-size: 11px;
    font-weight: 500;

    cursor: pointer;
    text-align: left;
}

.profile-dropdown-item:hover {
    background: #f4f7fc;
    color: #2864e6;
}

.profile-dropdown-item.logout {
    color: #dc4545;
}

.profile-dropdown-item.logout:hover {
    background: #fff3f3;
    color: #dc4545;
}


/* =========================
   PROFILE MODAL
========================= */

.profile-modal {
    position: fixed;
    inset: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 20px;

    background: rgba(23, 36, 58, 0.28);

    opacity: 0;
    visibility: hidden;

    transition: 0.2s ease;

    z-index: 2000;
}

.profile-modal.show {
    opacity: 1;
    visibility: visible;
}

.profile-modal-card {
    width: 100%;
    max-width: 370px;

    background: #fff;

    border-radius: 14px;

    box-shadow:
        0 18px 45px rgba(31, 48, 84, 0.18);

    padding: 22px;
}

.profile-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 15px;

    margin-bottom: 18px;
}

.profile-modal-title {
    margin: 0;

    color: #17243a;

    font-size: 16px;
    font-weight: 700;
}

.profile-modal-close {
    width: 32px;
    height: 32px;

    border: 1px solid #e1e7f0;
    border-radius: 8px;

    background: #fff;
    color: #667085;

    cursor: pointer;
}

.profile-modal-user {
    display: flex;
    align-items: center;

    gap: 12px;

    padding: 13px;
    margin-bottom: 16px;

    border-radius: 10px;

    background: #f6f8fc;
}

.profile-modal-user .profile-avatar {
    width: 42px;
    height: 42px;
    min-width: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: #e8f0ff;
    color: #2864e6;

    font-size: 16px;
    font-weight: 700;
}

.profile-modal-user-name {
    color: #17243a;

    font-size: 13px;
    font-weight: 600;

    margin: 0;
}

.profile-modal-user-role {
    color: #7a8499;

    font-size: 10px;

    margin-top: 2px;
}

.profile-info {
    display: grid;
    gap: 10px;
}

.profile-info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 15px;

    padding-bottom: 10px;

    border-bottom: 1px solid #edf0f5;
}

.profile-info-row:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}

.profile-info-label {
    color: #8a95a8;
    font-size: 10px;
}

.profile-info-value {
    color: #26334a;

    font-size: 11px;
    font-weight: 600;

    text-align: right;
}

    </style>

</head>


<body>


<div class="dashboard-layout">


    <!-- ===============================
         SIDEBAR
    =============================== -->

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


            <a href="dashboard.php"
               class="menu-item">

                <i class="bi bi-grid-1x2"></i>

                <span>Dashboard</span>

            </a>


            <a href="data_pasien.php"
               class="menu-item active">

                <i class="bi bi-people"></i>

                <span>Data Pasien</span>

            </a>


            <a href="data_user.php"
               class="menu-item">

                <i class="bi bi-person-gear"></i>

                <span>Data User</span>

            </a>


            <a href="monitoring.php"
               class="menu-item">

                <i class="bi bi-clipboard2-pulse"></i>

                <span>Monitoring</span>

            </a>


            <a href="laporan.php"
               class="menu-item">

                <i class="bi bi-file-earmark-bar-graph"></i>

                <span>Laporan</span>

            </a>


        </nav>


        <div class="sidebar-bottom">

            <a href="logout.php"
               class="logout-button">

                <i class="bi bi-box-arrow-left"></i>

                <span>Logout</span>

            </a>

        </div>


    </aside>



    <!-- ===============================
         MAIN CONTENT
    =============================== -->

    <main class="main-content">


        <!-- HEADER -->

        <header class="topbar">

    <h1>
        Tambah Pasien
    </h1>

    <div class="topbar-right">

        <!-- PROFILE -->
        <div class="profile-wrapper" id="topbarProfile">

            <button
                type="button"
                class="profile-trigger"
                id="profileTrigger"
                aria-label="Menu Profil"
                aria-expanded="false"
            >

                <div class="profile-info">
                    <strong><?= htmlspecialchars($nama_user); ?></strong>
                    <span><?= htmlspecialchars(strtolower($role_label)); ?></span>
                </div>

                <div class="profile-avatar">
                    <?= htmlspecialchars($inisial_user); ?>
                </div>

                <i class="bi bi-chevron-down profile-arrow"></i>

            </button>

            <!-- PROFILE DROPDOWN -->
            <div
                class="profile-dropdown"
                id="profileDropdown"
            >

                <div class="profile-dropdown-header">
                    <div>
                        <p class="profile-dropdown-name">
                            <?= htmlspecialchars($nama_user); ?>
                        </p>
                        <p class="profile-dropdown-role">
                            <?= htmlspecialchars($role_label); ?>
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    class="profile-dropdown-item"
                    id="profileDetailButton"
                >
                    <i class="bi bi-person"></i>
                    <span>Profil Saya</span>
                </button>

                <a
                    href="logout.php"
                    class="profile-dropdown-item logout"
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
                        Tambah Pasien
                    </h2>

                    <p>
                        Tambahkan data pasien ODGJ baru ke dalam sistem
                    </p>

                </div>


                <a
                    href="data_pasien.php"
                    class="back-button"
                >

                    <i class="bi bi-arrow-left"></i>

                    Kembali

                </a>


            </div>



            <!-- FORM CARD -->

            <div class="form-card">


                <?php if (isset($error)): ?>

                    <div class="error-message">

                        <i class="bi bi-exclamation-circle"></i>

                        <?= htmlspecialchars($error); ?>

                    </div>

                <?php endif; ?>



                <form
                    action=""
                    method="POST"
                >


                    <!-- ===============================
                         IDENTITAS
                    =============================== -->

                    <div class="form-section">


                        <div class="form-section-title">

                            <i class="bi bi-person"></i>

                            <h3>
                                Identitas Pasien
                            </h3>

                        </div>


                        <div class="form-grid">


                            <!-- NOMOR REGISTRASI -->

                            <div class="form-group">

                                <label for="nomor_registrasi">

                                    Nomor Registrasi

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    id="nomor_registrasi"
                                    name="nomor_registrasi"
                                    placeholder="Contoh: ODGJ-0004"
                                    required
                                >

                            </div>



                            <!-- NIK -->

                            <div class="form-group">

                                <label for="nik">

                                    NIK

                                </label>

                                <input
                                    type="text"
                                    id="nik"
                                    name="nik"
                                    maxlength="20"
                                    placeholder="Masukkan NIK"
                                >

                            </div>



                            <!-- NAMA -->

                            <div class="form-group">

                                <label for="nama_pasien">

                                    Nama Pasien

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    id="nama_pasien"
                                    name="nama_pasien"
                                    placeholder="Masukkan nama lengkap pasien"
                                    required
                                >

                            </div>



                            <!-- JENIS KELAMIN -->

                            <div class="form-group">

                                <label for="jenis_kelamin">

                                    Jenis Kelamin

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <select
                                    id="jenis_kelamin"
                                    name="jenis_kelamin"
                                    required
                                >

                                    <option value="">
                                        Pilih jenis kelamin
                                    </option>

                                    <option value="Laki-laki">
                                        Laki-laki
                                    </option>

                                    <option value="Perempuan">
                                        Perempuan
                                    </option>

                                </select>

                            </div>



                            <!-- TEMPAT LAHIR -->

                            <div class="form-group">

                                <label for="tempat_lahir">

                                    Tempat Lahir

                                </label>

                                <input
                                    type="text"
                                    id="tempat_lahir"
                                    name="tempat_lahir"
                                    placeholder="Contoh: Bandung"
                                >

                            </div>



                            <!-- TANGGAL LAHIR -->

                            <div class="form-group">

                                <label for="tanggal_lahir">

                                    Tanggal Lahir

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="date"
                                    id="tanggal_lahir"
                                    name="tanggal_lahir"
                                    required
                                >

                            </div>



                            <!-- ALAMAT -->

                            <div class="form-group full">

                                <label for="alamat">

                                    Alamat

                                </label>

                                <textarea
                                    id="alamat"
                                    name="alamat"
                                    placeholder="Masukkan alamat lengkap pasien"
                                ></textarea>

                            </div>


                        </div>

                    </div>



                    <!-- ===============================
                         STATUS PERAWATAN
                    =============================== -->

                    <div class="form-section">


                        <div class="form-section-title">

                            <i class="bi bi-hospital"></i>

                            <h3>
                                Status Perawatan
                            </h3>

                        </div>


                        <div class="form-grid">


                            <!-- LOKASI -->

                            <div class="form-group">

                                <label for="status_lokasi">

                                    Lokasi Pasien

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <select
                                    id="status_lokasi"
                                    name="status_lokasi"
                                    required
                                >

                                    <option value="">
                                        Pilih lokasi pasien
                                    </option>

                                    <option value="Dalam Yayasan">
                                        Dalam Yayasan
                                    </option>

                                    <option value="Luar Yayasan">
                                        Luar Yayasan
                                    </option>

                                </select>

                            </div>



                            <!-- KONDISI -->

                            <div class="form-group">

                                <label for="kondisi">

                                    Kondisi Pasien

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <select
                                    id="kondisi"
                                    name="kondisi"
                                    required
                                >

                                    <option value="">
                                        Pilih kondisi pasien
                                    </option>

                                    <option value="Stabil">
                                        Stabil
                                    </option>

                                    <option value="Perlu Pantauan">
                                        Perlu Pantauan
                                    </option>

                                    <option value="Darurat">
                                        Darurat
                                    </option>

                                </select>

                            </div>



                            <!-- TANGGAL MASUK -->

                            <div class="form-group">

                                <label for="tanggal_masuk">

                                    Tanggal Masuk

                                </label>

                                <input
                                    type="date"
                                    id="tanggal_masuk"
                                    name="tanggal_masuk"
                                >

                            </div>



                            <!-- STATUS -->

                            <div class="form-group">

                                <label for="status_pasien">

                                    Status Pasien

                                    <span class="required">
                                        *
                                    </span>

                                </label>

                                <select
                                    id="status_pasien"
                                    name="status_pasien"
                                    required
                                >

                                    <option value="Aktif">
                                        Aktif
                                    </option>

                                    <option value="Tidak Aktif">
                                        Tidak Aktif
                                    </option>

                                </select>

                            </div>


                        </div>

                    </div>



                    <!-- ===============================
                         BUTTON
                    =============================== -->

                    <div class="form-actions">


                        <a
                            href="data_pasien.php"
                            class="cancel-button"
                        >

                            Batal

                        </a>


                        <button
                            type="submit"
                            class="save-button"
                        >

                            <i class="bi bi-check-lg"></i>

                            Simpan Pasien

                        </button>


                    </div>


                </form>


            </div>


        </div>


    </main>


</div>



<!-- =========================
     PROFILE MODAL
========================= -->
<div class="profile-modal" id="profileModal" aria-hidden="true">
    <div class="profile-modal-card" role="dialog" aria-modal="true" aria-labelledby="profileModalTitle">

        <div class="profile-modal-head">
            <h3 class="profile-modal-title" id="profileModalTitle">
                Profil Saya
            </h3>

            <button
                type="button"
                class="profile-modal-close"
                id="profileModalClose"
                aria-label="Tutup"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="profile-modal-user">
            <span class="profile-avatar" aria-hidden="true">
                <?= htmlspecialchars(strtoupper(substr($nama_user, 0, 1))); ?>
            </span>

            <div>
                <p class="profile-modal-user-name">
                    <?= htmlspecialchars($nama_user); ?>
                </p>

                <p class="profile-modal-user-role">
                    <?= htmlspecialchars($role_label); ?>
                </p>
            </div>
        </div>

        <div class="profile-info">
            <div class="profile-info-row">
                <span class="profile-info-label">Nama</span>
                <span class="profile-info-value">
                    <?= htmlspecialchars($nama_user); ?>
                </span>
            </div>

            <div class="profile-info-row">
                <span class="profile-info-label">ID Pengguna</span>
                <span class="profile-info-value">
                    <?= (int) $_SESSION['id_user']; ?>
                </span>
            </div>

            <div class="profile-info-row">
                <span class="profile-info-label">Role</span>
                <span class="profile-info-value">
                    <?= htmlspecialchars($role_label); ?>
                </span>
            </div>
        </div>

    </div>
</div>

<script>
/* =========================
   PROFILE TOPBAR
========================= */

const topbarProfile = document.getElementById('topbarProfile');
const profileTrigger = document.getElementById('profileTrigger');
const profileDetailButton = document.getElementById('profileDetailButton');
const profileModal = document.getElementById('profileModal');
const profileModalClose = document.getElementById('profileModalClose');

if (topbarProfile && profileTrigger) {

    profileTrigger.addEventListener('click', function (event) {
        event.stopPropagation();

        const isOpen = topbarProfile.classList.toggle('open');

        profileTrigger.classList.toggle('active', isOpen);

        profileTrigger.setAttribute(
            'aria-expanded',
            isOpen ? 'true' : 'false'
        );
    });

    document.addEventListener('click', function (event) {
        if (!topbarProfile.contains(event.target)) {
            topbarProfile.classList.remove('open');
            profileTrigger.classList.remove('active');

            profileTrigger.setAttribute(
                'aria-expanded',
                'false'
            );
        }
    });
}

/* =========================
   PROFIL SAYA
========================= */

function openProfileModal() {
    if (!profileModal) return;

    if (topbarProfile) {
        topbarProfile.classList.remove('open');
        profileTrigger.classList.remove('active');
    }

    if (profileTrigger) {
        profileTrigger.setAttribute('aria-expanded', 'false');
    }

    profileModal.classList.add('show');
    profileModal.setAttribute('aria-hidden', 'false');
}

function closeProfileModal() {
    if (!profileModal) return;

    profileModal.classList.remove('show');
    profileModal.setAttribute('aria-hidden', 'true');
}

if (profileDetailButton) {
    profileDetailButton.addEventListener('click', function () {
        openProfileModal();
    });
}

if (profileModalClose) {
    profileModalClose.addEventListener('click', function () {
        closeProfileModal();
    });
}

if (profileModal) {
    profileModal.addEventListener('click', function (event) {
        if (event.target === profileModal) {
            closeProfileModal();
        }
    });
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        if (topbarProfile) {
            topbarProfile.classList.remove('open');
        }

        if (profileTrigger) {
            profileTrigger.setAttribute('aria-expanded', 'false');
        }

        closeProfileModal();
    }
});
</script>

</body>

</html>