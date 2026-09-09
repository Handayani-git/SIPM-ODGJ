<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

/* KHUSUS PETUGAS */
if (($_SESSION['role'] ?? '') !== 'petugas') {
    die("Anda tidak memiliki akses ke halaman ini.");
}

require_once "koneksi.php";

$nama_user = $_SESSION['nama_lengkap'] ?? 'Petugas';

/* INISIAL USER LOGIN */
$inisial_user = strtoupper(substr(trim($nama_user), 0, 1));
if ($inisial_user === '') {
    $inisial_user = 'P';
}


/* =====================================================
   HAPUS JADWAL
===================================================== */

if (isset($_GET['hapus'])) {

    $id_jadwal = (int) $_GET['hapus'];

    if ($id_jadwal > 0) {

        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM jadwal_monitoring
             WHERE id_jadwal = ?"
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $id_jadwal
            );

            mysqli_stmt_execute($stmt);

            mysqli_stmt_close($stmt);
        }
    }

    header("Location: jadwal_monitoring.php");
    exit;
}


/* =====================================================
   AMBIL DATA JADWAL MONITORING
   HANYA PASIEN LUAR YAYASAN
===================================================== */

$query = mysqli_query(
    $conn,
    "
    SELECT
        j.id_jadwal,
        j.tanggal_monitoring,
        j.waktu_monitoring,
        j.keterangan,
        j.status,
        p.id_pasien,
        p.nama_pasien,
        p.nomor_registrasi,
        p.status_lokasi
    FROM jadwal_monitoring j

    INNER JOIN pasien p
        ON p.id_pasien = j.id_pasien

    WHERE p.status_lokasi = 'Luar Yayasan'

    ORDER BY
        j.tanggal_monitoring ASC,
        j.waktu_monitoring ASC
    "
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Jadwal Monitoring - SIPM ODGJ</title>


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

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

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
           BUTTON
        ===================================================== */

        .button-primary {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 11px 17px;

            border-radius: 8px;

            background: #2864e6;

            color: #ffffff;

            text-decoration: none;

            font-size: 12px;

            font-weight: 600;

            transition: .2s;
        }


        .button-primary:hover {

            background: #1f55c8;
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
        }


        .card-header h3 {

            margin: 0;

            color: #17243a;

            font-size: 16px;

            font-weight: 600;
        }


        .card-header p {

            margin: 5px 0 0;

            color: #7a8499;

            font-size: 11px;
        }


        /* =====================================================
           TABLE
        ===================================================== */

        .table-wrapper {

            width: 100%;

            overflow-x: auto;
        }


        table {

            width: 100%;

            min-width: 850px;

            border-collapse: collapse;
        }


        th {

            padding: 13px 15px;

            background: #f8faff;

            color: #69768c;

            text-align: left;

            font-size: 10px;

            font-weight: 600;
        }


        td {

            padding: 15px;

            border-bottom: 1px solid #edf0f5;

            color: #40516b;

            font-size: 11px;
        }


        tr:last-child td {

            border-bottom: none;
        }


        .patient-name {

            display: block;

            color: #17243a;

            font-weight: 600;

            margin-bottom: 3px;
        }


        .patient-registration {

            display: block;

            color: #8a95a8;

            font-size: 9px;
        }


        /* =====================================================
           STATUS
        ===================================================== */

        .status {

            display: inline-flex;

            align-items: center;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 9px;

            font-weight: 600;
        }


        .status-terjadwal {

            background: #eaf1ff;

            color: #2864e6;
        }


        .status-selesai {

            background: #e7f5ed;

            color: #198754;
        }


        .status-dibatalkan {

            background: #fff0f0;

            color: #dc3545;
        }


        /* =====================================================
           ACTION
        ===================================================== */

        .actions {

            display: flex;

            align-items: center;

            gap: 6px;
        }


        .action-button {

            width: 31px;

            height: 31px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 7px;

            border: 1px solid #dce3ee;

            background: #ffffff;

            color: #526078;

            text-decoration: none;

            font-size: 13px;
        }


        .action-button:hover {

            color: #2864e6;

            border-color: #2864e6;
        }


        .action-delete:hover {

            color: #dc3545;

            border-color: #dc3545;
        }


        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty-state {

            text-align: center;

            padding: 55px 20px;

            color: #8a95a8;
        }


        .empty-state i {

            display: block;

            margin-bottom: 12px;

            color: #9bb8ef;

            font-size: 38px;
        }


        .empty-state strong {

            display: block;

            margin-bottom: 5px;

            color: #526078;

            font-size: 13px;
        }


        .empty-state span {

            font-size: 11px;
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


            .page-header {

                align-items: flex-start;

                flex-direction: column;
            }

        }



        /* =====================================================
           POPUP KONFIRMASI HAPUS
        ===================================================== */

        .delete-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 9999;
        }

        .delete-modal-overlay.show {
            display: flex;
        }

        .delete-modal {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.18);
            text-align: center;
            animation: deleteModalIn .18s ease-out;
        }

        @keyframes deleteModalIn {
            from {
                opacity: 0;
                transform: translateY(8px) scale(.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .delete-modal-icon {
            width: 54px;
            height: 54px;
            margin: 0 auto 15px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff1f1;
            color: #e5484d;
            font-size: 23px;
        }

        .delete-modal h3 {
            margin: 0 0 8px;
            color: #17243a;
            font-size: 18px;
            font-weight: 700;
        }

        .delete-modal p {
            margin: 0 auto 22px;
            max-width: 330px;
            color: #7a8499;
            font-size: 12px;
            line-height: 1.6;
        }

        .delete-modal-actions {
            display: flex;
            justify-content: center;
            gap: 9px;
        }

        .delete-modal-button {
            min-width: 105px;
            height: 38px;
            padding: 0 16px;
            border-radius: 8px;
            border: 1px solid transparent;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            transition: .2s;
        }

        .delete-modal-cancel {
            background: #f1f4f8;
            color: #4b5563;
            border-color: #e1e6ee;
        }

        .delete-modal-cancel:hover {
            background: #e7ebf1;
        }

        .delete-modal-confirm {
            background: #e5484d;
            color: #ffffff;
        }

        .delete-modal-confirm:hover {
            background: #d6383e;
        }

        @media (max-width: 500px) {
            .delete-modal {
                padding: 24px 20px;
            }

            .delete-modal-actions {
                flex-direction: column-reverse;
            }

            .delete-modal-button {
                width: 100%;
            }
        }

    </style>

</head>


<body>


<!-- =====================================================
     SIDEBAR PETUGAS
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
            Jadwal Monitoring
        </div>


        <div class="user-profile" id="userProfile">

    <button
        type="button"
        class="profile-button"
        id="profileButton"
        aria-expanded="false"
    >

        <span class="profile-top-text">
            <strong><?= htmlspecialchars($nama_user); ?></strong>
            <small>petugas</small>
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


        <!-- HEADER -->

        <div class="page-header">

            <div>

                <h1>
                    Jadwal Monitoring
                </h1>

                <p>
                    Kelola jadwal monitoring pasien luar yayasan.
                </p>

            </div>


            <a
                href="tambah_jadwal_monitoring.php"
                class="button-primary"
            >

                <i class="bi bi-plus-lg"></i>

                Tambah Jadwal Monitoring

            </a>

        </div>


        <!-- CARD -->

        <div class="card">


            <div class="card-header">

                <h3>
                    Daftar Jadwal Monitoring
                </h3>

                <p>
                    Jadwal monitoring pasien yang berada di luar yayasan.
                </p>

            </div>


            <?php if (
                $query &&
                mysqli_num_rows($query) > 0
            ): ?>


                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    No
                                </th>

                                <th>
                                    Pasien
                                </th>

                                <th>
                                    Tanggal
                                </th>

                                <th>
                                    Waktu
                                </th>

                                <th>
                                    Keterangan
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Aksi
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php

                        $no = 1;

                        while (
                            $jadwal =
                            mysqli_fetch_assoc($query)
                        ):

                            $status =
                                $jadwal['status']
                                ?? 'Terjadwal';


                            if ($status === 'Selesai') {

                                $status_class =
                                    'status-selesai';

                            } elseif (
                                $status === 'Dibatalkan'
                            ) {

                                $status_class =
                                    'status-dibatalkan';

                            } else {

                                $status_class =
                                    'status-terjadwal';

                            }

                        ?>


                            <tr>


                                <!-- NO -->

                                <td>
                                    <?= $no++; ?>
                                </td>


                                <!-- PASIEN -->

                                <td>

                                    <span class="patient-name">

                                        <?= htmlspecialchars(
                                            $jadwal['nama_pasien']
                                        ); ?>

                                    </span>


                                    <span
                                        class="patient-registration"
                                    >

                                        <?= htmlspecialchars(
                                            $jadwal['nomor_registrasi']
                                        ); ?>

                                    </span>

                                </td>


                                <!-- TANGGAL -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $jadwal[
                                                'tanggal_monitoring'
                                            ]
                                        )
                                    ) {

                                        echo date(
                                            'd-m-Y',
                                            strtotime(
                                                $jadwal[
                                                    'tanggal_monitoring'
                                                ]
                                            )
                                        );

                                    } else {

                                        echo '-';

                                    }

                                    ?>

                                </td>


                                <!-- WAKTU -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $jadwal[
                                                'waktu_monitoring'
                                            ]
                                        )
                                    ) {

                                        echo date(
                                            'H:i',
                                            strtotime(
                                                $jadwal[
                                                    'waktu_monitoring'
                                                ]
                                            )
                                        );

                                    } else {

                                        echo '-';

                                    }

                                    ?>

                                </td>


                                <!-- KETERANGAN -->

                                <td>

                                    <?= htmlspecialchars(
                                        $jadwal[
                                            'keterangan'
                                        ] ?? '-'
                                    ); ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span
                                        class="status <?= $status_class; ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $status
                                        ); ?>

                                    </span>

                                </td>


                                <!-- AKSI -->

                                <td>

                                    <div class="actions">


                                        <a
                                            href="edit_jadwal_monitoring.php?id=<?= (int)$jadwal['id_jadwal']; ?>"
                                            class="action-button"
                                            title="Edit"
                                        >

                                            <i class="bi bi-pencil"></i>

                                        </a>


                                        <a
                                            href="jadwal_monitoring.php?hapus=<?= (int)$jadwal['id_jadwal']; ?>"
                                            class="action-button action-delete"
                                            title="Hapus"
                                            onclick="openDeleteModal(this.href); return false;"
                                        >

                                            <i class="bi bi-trash"></i>

                                        </a>


                                    </div>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="empty-state">

                    <i class="bi bi-calendar2-check"></i>

                    <strong>
                        Belum Ada Jadwal Monitoring
                    </strong>

                    <span>
                        Belum ada jadwal monitoring pasien luar yayasan.
                    </span>

                </div>


            <?php endif; ?>


        </div>


    </section>


</main>




<!-- =====================================================
     POPUP KONFIRMASI HAPUS
===================================================== -->

<div
    class="delete-modal-overlay"
    id="deleteModal"
    aria-hidden="true"
>

    <div
        class="delete-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="deleteModalTitle"
    >

        <div class="delete-modal-icon">
            <i class="bi bi-trash3"></i>
        </div>

        <h3 id="deleteModalTitle">
            Hapus Jadwal Monitoring?
        </h3>

        <p>
            Yakin ingin menghapus jadwal monitoring ini?
            Data yang sudah dihapus tidak dapat dikembalikan.
        </p>

        <div class="delete-modal-actions">

            <button
                type="button"
                class="delete-modal-button delete-modal-cancel"
                onclick="closeDeleteModal()"
            >
                Batal
            </button>

            <button
                type="button"
                class="delete-modal-button delete-modal-confirm"
                id="confirmDeleteButton"
            >
                <i class="bi bi-trash3"></i>
                Hapus
            </button>

        </div>

    </div>

</div>


<script>

    let deleteUrl = '';


    function openDeleteModal(url) {

        deleteUrl = url;

        const modal = document.getElementById('deleteModal');

        modal.classList.add('show');

        modal.setAttribute('aria-hidden', 'false');

    }


    function closeDeleteModal() {

        const modal = document.getElementById('deleteModal');

        modal.classList.remove('show');

        modal.setAttribute('aria-hidden', 'true');

        deleteUrl = '';

    }


    document
        .getElementById('confirmDeleteButton')
        .addEventListener('click', function () {

            if (deleteUrl !== '') {
                window.location.href = deleteUrl;
            }

        });


    document
        .getElementById('deleteModal')
        .addEventListener('click', function (event) {

            if (event.target === this) {
                closeDeleteModal();
            }

        });


    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {
            closeDeleteModal();
        }

    });

</script>

</body>

</html>