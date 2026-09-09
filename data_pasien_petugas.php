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
$id_user   = $_SESSION['id_user'] ?? '-';

$query_pasien = mysqli_query(
    $conn,
    "SELECT
        id_pasien,
        nama_pasien,
        jenis_kelamin,
        status_lokasi
     FROM pasien
     ORDER BY id_pasien DESC"
);

$error_pasien = '';

if (!$query_pasien) {
    $error_pasien = mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pasien - Petugas | SIPM ODGJ</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

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
            background: #f5f7fb;
            color: #14213d;
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 218px;
            min-width: 218px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            min-width: 0;
        }

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
        }

        .page-header p {
            margin: 6px 0 0;
            color: #7a8499;
            font-size: 13px;
        }

        .content-card {
            background: #fff;
            border: 1px solid #e1e7f0;
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 20px 22px;
            border-bottom: 1px solid #edf0f5;
        }

        .card-header h3 {
            margin: 0;
            font-size: 16px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f8faff;
            padding: 14px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #69768c;
            white-space: nowrap;
        }

        .data-table td {
            padding: 14px;
            border-bottom: 1px solid #edf0f5;
            font-size: 12px;
            vertical-align: middle;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .data-table tbody tr:hover {
            background: #fafcff;
        }

        .data-table td .badge-dalam,
        .data-table .badge-dalam {
            display: inline-block !important;
            padding: 6px 12px !important;
            border-radius: 20px !important;
            font-size: 10px !important;
            font-weight: 500 !important;
            white-space: nowrap !important;
            background: #cffafe !important;
            color: #159db7 !important;
            border: 1px solid #78e4f4 !important;
        }

        .data-table td .badge-luar,
        .data-table .badge-luar {
            display: inline-block !important;
            padding: 6px 12px !important;
            border-radius: 20px !important;
            font-size: 10px !important;
            font-weight: 500 !important;
            white-space: nowrap !important;
            background: #fce7f3 !important;
            color: #e34b91 !important;
            border: 1px solid #ffb3d3 !important;
        }

        .btn-detail {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 8px 13px;
            background: #2864e6;
            color: #fff;
            text-decoration: none;
            border-radius: 7px;
            font-size: 11px;
            border: none;
        }

        .btn-detail:hover {
            background: #1e55ca;
            color: #fff;
        }

        .empty-data {
            padding: 40px !important;
            text-align: center;
            color: #8a95a8;
        }

        .error-data {
            margin: 20px 22px;
            padding: 14px 16px;
            border-radius: 8px;
            background: #fff0f0;
            border: 1px solid #ffd0d0;
            color: #b42318;
            font-size: 12px;
        }

        .topbar {
            position: relative !important;
            z-index: 1000 !important;
        }

        .topbar-right {
            position: relative !important;
            z-index: 1001 !important;
        }

        .user-profile {
            position: relative !important;
            display: flex !important;
            align-items: center !important;
            z-index: 1002 !important;
        }

        .profile-button {
            position: relative !important;
            display: flex !important;
            align-items: center !important;
            gap: 7px !important;
            padding: 7px 9px !important;
            border: none !important;
            background: transparent !important;
            color: #17243a !important;
            font-family: 'Poppins', sans-serif !important;
            font-size: 12px !important;
            font-weight: 500 !important;
            cursor: pointer !important;
            border-radius: 8px !important;
            transition: background 0.2s ease !important;
            z-index: 1003 !important;
        }

        .profile-button:hover {
            background: #f5f7fb !important;
        }

        .profile-top-info {
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-end !important;
            line-height: 1.2 !important;
        }

        .profile-top-info strong {
            font-size: 12px !important;
            font-weight: 600 !important;
            color: #17243a !important;
        }

        .profile-top-info span {
            margin-top: 2px !important;
            font-size: 9px !important;
            color: #8993a6 !important;
        }

        .profile-top-avatar {
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 50% !important;
            background: #2864e6 !important;
            color: #fff !important;
            font-size: 16px !important;
            font-weight: 600 !important;
        }

        .profile-arrow {
            font-size: 10px !important;
            color: #7a8499 !important;
            transition: transform 0.2s ease !important;
        }

        .profile-button.active .profile-arrow {
            transform: rotate(180deg);
        }

        .profile-dropdown {
            position: absolute !important;
            top: calc(100% + 10px) !important;
            right: 0 !important;
            width: 230px !important;
            background: #fff !important;
            border: 1px solid #e5eaf2 !important;
            border-radius: 12px !important;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.10) !important;
            overflow: hidden !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transform: translateY(-6px) !important;
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease !important;
            z-index: 9999 !important;
        }

        .profile-dropdown.show {
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0) !important;
        }

        .profile-dropdown-header {
            display: flex !important;
            align-items: center !important;
            gap: 11px !important;
            padding: 16px !important;
        }

        .profile-avatar {
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 50% !important;
            background: #eef4ff !important;
            color: #2864e6 !important;
            font-size: 18px !important;
        }

        .profile-info {
            display: flex !important;
            flex-direction: column !important;
            min-width: 0 !important;
        }

        .profile-info strong {
            color: #17243a !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        .profile-info span {
            margin-top: 2px !important;
            color: #8a95a8 !important;
            font-size: 10px !important;
        }

        .profile-divider {
            height: 1px !important;
            background: #edf0f5 !important;
        }

        .profile-menu-item {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            width: 100% !important;
            padding: 12px 16px !important;
            color: #374151 !important;
            text-decoration: none !important;
            font-size: 11px !important;
            border: none !important;
            background: transparent !important;
            font-family: 'Poppins', sans-serif !important;
            text-align: left !important;
            cursor: pointer !important;
            transition: background 0.2s ease, color 0.2s ease !important;
        }

        .profile-menu-item:hover {
            background: #f8fafc !important;
            color: #2864e6 !important;
        }

        .profile-menu-item i {
            width: 18px !important;
            text-align: center !important;
            font-size: 15px !important;
        }

        .profile-menu-item.logout-item {
            color: #dc3545 !important;
        }

        .profile-menu-item.logout-item:hover {
            background: #fff5f5 !important;
            color: #dc3545 !important;
        }

        .profile-modal {
            position: fixed !important;
            inset: 0 !important;
            background: rgba(15, 23, 42, 0.35) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 20px !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transition: opacity 0.2s ease, visibility 0.2s ease !important;
            z-index: 20000 !important;
        }

        .profile-modal.show {
            opacity: 1 !important;
            visibility: visible !important;
        }

        .profile-modal-card {
            width: 100% !important;
            max-width: 390px !important;
            background: #fff !important;
            border-radius: 14px !important;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15) !important;
            overflow: hidden !important;
            transform: translateY(-10px) !important;
            transition: transform 0.2s ease !important;
        }

        .profile-modal.show .profile-modal-card {
            transform: translateY(0) !important;
        }

        .profile-modal-top {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 18px 20px !important;
            border-bottom: 1px solid #edf0f5 !important;
        }

        .profile-modal-top h3 {
            margin: 0 !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            color: #17243a !important;
        }

        .profile-close {
            width: 30px !important;
            height: 30px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border: none !important;
            background: #f5f7fb !important;
            color: #69768c !important;
            border-radius: 7px !important;
            cursor: pointer !important;
            font-size: 16px !important;
        }

        .profile-close:hover {
            background: #edf1f7 !important;
            color: #17243a !important;
        }

        .profile-modal-body {
            padding: 24px 20px !important;
        }

        .profile-modal-avatar {
            width: 68px !important;
            height: 68px !important;
            margin: 0 auto 15px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 50% !important;
            background: #eef4ff !important;
            color: #2864e6 !important;
            font-size: 30px !important;
        }

        .profile-modal-name {
            text-align: center !important;
            font-size: 17px !important;
            font-weight: 600 !important;
            color: #17243a !important;
            margin-bottom: 4px !important;
        }

        .profile-modal-role {
            text-align: center !important;
            font-size: 11px !important;
            color: #8a95a8 !important;
            margin-bottom: 22px !important;
        }

        .profile-detail {
            border: 1px solid #e8edf4 !important;
            border-radius: 9px !important;
            overflow: hidden !important;
        }

        .profile-detail-row {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 20px !important;
            padding: 12px 14px !important;
            border-bottom: 1px solid #edf0f5 !important;
        }

        .profile-detail-row:last-child {
            border-bottom: none !important;
        }

        .profile-detail-label {
            font-size: 10px !important;
            color: #8a95a8 !important;
        }

        .profile-detail-value {
            font-size: 11px !important;
            color: #17243a !important;
            font-weight: 500 !important;
            text-align: right !important;
        }

        @media (max-width: 700px) {
            .sidebar {
                width: 180px;
                min-width: 180px;
            }

            .page-content {
                padding: 18px;
            }

            .card-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
<div class="dashboard-layout">

    <aside class="sidebar">
        <div class="sidebar-brand">
            <img
                src="/SIPM-ODGJ/assets/img/logo YCKA.png"
                alt="Logo Yayasan"
                class="sidebar-logo"
            >

            <h2>SIPM ODGJ</h2>

            <p>Yayasan Cahaya Kasih Amanah</p>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard_petugas.php" class="menu-item">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>

            <a href="data_pasien_petugas.php" class="menu-item active">
                <i class="bi bi-people"></i>
                <span>Data Pasien</span>
            </a>

            <a href="monitoring_petugas.php" class="menu-item">
                <i class="bi bi-clipboard2-pulse"></i>
                <span>Monitoring</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <a href="logout.php" class="logout-button">
                <i class="bi bi-box-arrow-left"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <h1>Data Pasien</h1>

            <div class="topbar-right">
                <div class="user-profile" id="userProfile">

                    <button
                        type="button"
                        class="profile-button"
                        id="profileButton"
                    >
                        <div class="profile-top-info">
                            <strong><?= htmlspecialchars($nama_user); ?></strong>
                            <span>petugas</span>
                        </div>

                        <div class="profile-top-avatar">
                            <?= htmlspecialchars(strtoupper(substr(trim($nama_user), 0, 1))); ?>
                        </div>

                        <i class="bi bi-chevron-down profile-arrow"></i>
                    </button>

                    <div
                        class="profile-dropdown"
                        id="profileDropdown"
                    >
                        <div class="profile-dropdown-header">

                            <div class="profile-avatar">
                                <i class="bi bi-person-fill"></i>
                            </div>

                            <div class="profile-info">
                                <strong>
                                    <?= htmlspecialchars($nama_user); ?>
                                </strong>

                                <span>Petugas</span>
                            </div>

                        </div>

                        <div class="profile-divider"></div>

                        <button
                            type="button"
                            class="profile-menu-item"
                            id="profileSaya"
                        >
                            <i class="bi bi-person"></i>
                            <span>Profil Saya</span>
                        </button>

                        <a
                            href="logout.php"
                            class="profile-menu-item logout-item"
                        >
                            <i class="bi bi-box-arrow-left"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="page-content">

            <div class="page-header">
                <h2>Data Pasien</h2>
                <p>Daftar pasien yang terdaftar dalam sistem.</p>
            </div>

            <div class="content-card">

                <div class="card-header">
                    <h3>
                        <i class="bi bi-people"></i>
                        Daftar Pasien
                    </h3>

                </div>

                <?php if ($error_pasien !== ''): ?>
                    <div class="error-data">
                        Gagal mengambil data pasien:
                        <?= htmlspecialchars($error_pasien); ?>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="data-table" id="tabelPasien">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Pasien</th>
                                <th>Jenis Kelamin</th>
                                <th>Status Lokasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php
                        $no = 1;

                        if (
                            $query_pasien &&
                            mysqli_num_rows($query_pasien) > 0
                        ):
                        ?>

                            <?php while ($pasien = mysqli_fetch_assoc($query_pasien)): ?>

                                <tr>
                                    <td><?= $no++; ?></td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars(
                                                $pasien['nama_pasien'] ?? '-'
                                            ); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $pasien['jenis_kelamin'] ?? '-'
                                        ); ?>
                                    </td>

                                    <td>
                                        <?php
                                        $status_lokasi =
                                            $pasien['status_lokasi'] ?? '';
                                        ?>

                                        <?php if ($status_lokasi === 'Dalam Yayasan'): ?>

                                            <span class="badge-dalam">
                                                Dalam Yayasan
                                            </span>

                                        <?php else: ?>

                                            <span class="badge-luar">
                                                <?= htmlspecialchars(
                                                    $status_lokasi !== ''
                                                        ? $status_lokasi
                                                        : 'Luar Yayasan'
                                                ); ?>
                                            </span>

                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <a
                                            href="detail_pasien_petugas.php?id=<?= (int)$pasien['id_pasien']; ?>"
                                            class="btn-detail"
                                        >
                                            <i class="bi bi-eye"></i>
                                            Detail
                                        </a>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="5" class="empty-data">
                                    Belum ada data pasien.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </main>
</div>

<div class="profile-modal" id="profileModal">

    <div class="profile-modal-card">

        <div class="profile-modal-top">
            <h3>Profil Saya</h3>

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
                <i class="bi bi-person-fill"></i>
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

<script>
document.addEventListener("DOMContentLoaded", function () {

    const profileButton =
        document.getElementById("profileButton");

    const profileDropdown =
        document.getElementById("profileDropdown");

    const profileSaya =
        document.getElementById("profileSaya");

    const profileModal =
        document.getElementById("profileModal");

    const closeProfile =
        document.getElementById("closeProfile");

    const userProfile =
        document.getElementById("userProfile");

    if (profileButton && profileDropdown) {

        profileButton.addEventListener("click", function (event) {

            event.preventDefault();
            event.stopPropagation();

            const isOpen =
                profileDropdown.classList.contains("show");

            if (isOpen) {
                profileDropdown.classList.remove("show");
                profileButton.classList.remove("active");
            } else {
                profileDropdown.classList.add("show");
                profileButton.classList.add("active");
            }
        });
    }

    document.addEventListener("click", function (event) {

        if (
            userProfile &&
            !userProfile.contains(event.target)
        ) {
            if (profileDropdown) {
                profileDropdown.classList.remove("show");
            }

            if (profileButton) {
                profileButton.classList.remove("active");
            }
        }
    });

    if (profileSaya && profileModal) {

        profileSaya.addEventListener("click", function (event) {

            event.preventDefault();
            event.stopPropagation();

            if (profileDropdown) {
                profileDropdown.classList.remove("show");
            }

            if (profileButton) {
                profileButton.classList.remove("active");
            }

            profileModal.classList.add("show");
        });
    }

    if (closeProfile && profileModal) {

        closeProfile.addEventListener("click", function (event) {

            event.preventDefault();

            profileModal.classList.remove("show");
        });
    }

    if (profileModal) {

        profileModal.addEventListener("click", function (event) {

            if (event.target === profileModal) {
                profileModal.classList.remove("show");
            }
        });
    }

    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {

            if (profileModal) {
                profileModal.classList.remove("show");
            }

            if (profileDropdown) {
                profileDropdown.classList.remove("show");
            }

            if (profileButton) {
                profileButton.classList.remove("active");
            }
        }
    });

    const searchInput =
        document.getElementById("searchPasien");

    const tabel =
        document.getElementById("tabelPasien");

    if (searchInput && tabel) {

        searchInput.addEventListener("keyup", function () {

            const keyword =
                this.value.toLowerCase().trim();

            const tbody =
                tabel.querySelector("tbody");

            if (!tbody) {
                return;
            }

            const rows =
                tbody.getElementsByTagName("tr");

            for (let i = 0; i < rows.length; i++) {

                if (!rows[i].cells[1]) {
                    continue;
                }

                const nama =
                    rows[i]
                        .cells[1]
                        .textContent
                        .toLowerCase();

                rows[i].style.display =
                    nama.includes(keyword)
                        ? ""
                        : "none";
            }
        });
    }
});
</script>

</body>
</html>
