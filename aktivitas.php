<?php

session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'] ?? '';
$nama_user = $_SESSION['nama_lengkap'] ?? ($role === 'admin' ? 'Admin' : 'Petugas');
$role_label = ($role === 'admin') ? 'Admin' : 'Petugas';

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
        Aktivitas - Sistem Informasi ODGJ
    </title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

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

        .page-content { padding: 30px; }
        .page-header { display:flex; justify-content:space-between; align-items:flex-start; gap:20px; margin-bottom:22px; }
        .page-title h2 { margin:0; font-size:24px; font-weight:700; color:#17243a; }
        .page-title p { margin:5px 0 0; color:#7a8499; font-size:13px; }
        .back-button { display:inline-flex; align-items:center; gap:8px; padding:10px 15px; border:1px solid #dce3ee; border-radius:8px; background:#fff; color:#526078; text-decoration:none; font-size:13px; font-weight:500; transition:.2s ease; }
        .back-button:hover { border-color:#2864e6; color:#2864e6; background:#f8fbff; }
        .activity-card { background:#fff; border:1px solid #e1e7f0; border-radius:12px; padding:0; overflow:hidden; }
        .activity-card-header { display:flex; align-items:center; gap:12px; padding:20px 24px; border-bottom:1px solid #edf0f5; }
        .activity-card-header-icon { width:40px; height:40px; min-width:40px; border-radius:10px; background:#eaf1ff; color:#2864e6; display:flex; align-items:center; justify-content:center; font-size:17px; }
        .activity-card-header h3 { margin:0; font-size:15px; font-weight:600; color:#17243a; }
        .activity-card-header p { margin:3px 0 0; color:#8993a6; font-size:11px; }
        .activity-list { position:relative; padding:4px 24px 6px; }
        .activity-list::before { content:""; position:absolute; left:29px; top:20px; bottom:20px; width:1px; background:#dfe5ee; }
        .activity-item { position:relative; display:flex; gap:18px; padding:18px 0; border-bottom:1px solid #edf0f5; }
        .activity-item:last-child { border-bottom:none; }
        .activity-point { position:relative; z-index:2; width:10px; height:10px; min-width:10px; border-radius:50%; margin:5px 0 0 0; box-shadow:0 0 0 4px #fff; }
        .blue-point { background:#2864e6; } .gray-point { background:#9aa5b7; } .green-point { background:#28a866; }
        .activity-content { flex:1; min-width:0; }
        .activity-time { display:block; margin-bottom:4px; color:#8993a6; font-size:10px; }
        .activity-content strong { display:block; margin-bottom:4px; color:#17243a; font-size:13px; font-weight:600; }
        .activity-content p { margin:0; color:#65738a; font-size:11px; line-height:1.55; }

        /* PROFILE TOPBAR - SAMA DENGAN DASHBOARD PETUGAS */
        .topbar-profile { position:relative; display:flex; align-items:center; }
        .profile-trigger {
            position:relative;
            display:flex;
            align-items:center;
            justify-content:flex-end;
            gap:10px;
            padding:5px 8px;
            border:none;
            background:transparent;
            color:#17243a;
            font-family:'Poppins',sans-serif;
            cursor:pointer;
            border-radius:12px;
            transition:background .2s ease;
        }
        .profile-trigger:hover,
        .profile-trigger[aria-expanded="true"] { background:#f4f7fc; }
        .profile-trigger-name-wrap {
            display:flex;
            flex-direction:column;
            align-items:flex-end;
            justify-content:center;
            line-height:1.15;
            min-width:80px;
        }
        .profile-trigger-name-wrap strong {
            margin:0;
            color:#17243a;
            font-size:14px;
            font-weight:600;
            white-space:nowrap;
        }
        .profile-trigger-name-wrap small {
            margin-top:3px;
            color:#8a95a8;
            font-size:10px;
            font-weight:400;
            text-transform:lowercase;
        }
        .profile-trigger-avatar {
            width:48px;
            height:48px;
            min-width:48px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:50%;
            background:#2864e6;
            color:#fff;
            font-size:18px;
            font-weight:600;
            line-height:1;
            flex-shrink:0;
        }
        .profile-trigger-arrow {
            color:#7a8499;
            font-size:11px;
            transition:transform .2s ease;
            margin-left:1px;
        }
        .topbar-profile.open .profile-trigger-arrow { transform:rotate(180deg); }
        .profile-dropdown { position:absolute; top:calc(100% + 10px); right:0; width:210px; background:#fff; border:1px solid #e1e7f0; border-radius:12px; box-shadow:0 12px 30px rgba(31,48,84,.12); padding:8px; opacity:0; visibility:hidden; transform:translateY(-6px); transition:.2s ease; z-index:1200; }
        .topbar-profile.open .profile-dropdown { opacity:1; visibility:visible; transform:translateY(0); }
        .profile-dropdown-header { display:flex; align-items:center; gap:10px; padding:10px; border-bottom:1px solid #edf0f5; margin-bottom:5px; }
        .profile-dropdown-name { color:#17243a; font-size:12px; font-weight:600; margin:0; }
        .profile-dropdown-role { color:#8a95a8; font-size:10px; margin:2px 0 0; }
        .profile-dropdown-item { width:100%; display:flex; align-items:center; gap:9px; padding:10px; border:0; border-radius:8px; background:transparent; color:#526078; text-decoration:none; font-family:'Poppins',sans-serif; font-size:11px; font-weight:500; cursor:pointer; text-align:left; }
        .profile-dropdown-item:hover { background:#f4f7fc; color:#2864e6; }
        .profile-dropdown-item.logout { color:#dc4545; }
        .profile-dropdown-item.logout:hover { background:#fff3f3; color:#dc4545; }
        .profile-modal { position:fixed; inset:0; display:flex; align-items:center; justify-content:center; padding:20px; background:rgba(23,36,58,.28); opacity:0; visibility:hidden; transition:.2s ease; z-index:2000; }
        .profile-modal.show { opacity:1; visibility:visible; }
        .profile-modal-card { width:100%; max-width:370px; background:#fff; border-radius:14px; box-shadow:0 18px 45px rgba(31,48,84,.18); padding:22px; }
        .profile-modal-head { display:flex; align-items:center; justify-content:space-between; gap:15px; margin-bottom:18px; }
        .profile-modal-title { margin:0; color:#17243a; font-size:16px; font-weight:700; }
        .profile-modal-close { width:32px; height:32px; border:1px solid #e1e7f0; border-radius:8px; background:#fff; color:#667085; cursor:pointer; }
        .profile-modal-user { display:flex; align-items:center; gap:12px; padding:13px; margin-bottom:16px; border-radius:10px; background:#f6f8fc; }
        .profile-modal-user .profile-avatar { width:42px; height:42px; min-width:42px; display:flex; align-items:center; justify-content:center; border-radius:50%; background:#e8f0ff; color:#2864e6; font-size:16px; font-weight:700; }
        .profile-modal-user-name { color:#17243a; font-size:13px; font-weight:600; margin:0; }
        .profile-modal-user-role { color:#7a8499; font-size:10px; margin-top:2px; }
        .profile-info { display:grid; gap:10px; }
        .profile-info-row { display:flex; align-items:center; justify-content:space-between; gap:15px; padding-bottom:10px; border-bottom:1px solid #edf0f5; }
        .profile-info-row:last-child { border-bottom:0; padding-bottom:0; }
        .profile-info-label { color:#8a95a8; font-size:10px; }
        .profile-info-value { color:#26334a; font-size:11px; font-weight:600; text-align:right; }
        @media (max-width:768px) { .page-content{padding:20px;} .activity-card-header,.activity-list{padding-left:18px;padding-right:18px;} .activity-list::before{left:23px;} .page-header{align-items:flex-start;} }
    </style>

</head>


<body>


<div class="dashboard-layout">


    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar">


        <div class="sidebar-brand">

            <img
                src="/SIPM-ODGJ/assets/img/logo YCKA.png"
                alt="Logo Yayasan Cahaya Kasih Amanah"
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
                href="dashboard.php"
                class="menu-item active"
            >

                <i class="bi bi-grid-1x2"></i>

                <span>
                    Dashboard
                </span>

            </a>


            <a
                href="data_pasien.php"
                class="menu-item"
            >

                <i class="bi bi-people"></i>

                <span>
                    Data Pasien
                </span>

            </a>


            <a
                href="data_user.php"
                class="menu-item"
            >

                <i class="bi bi-person-gear"></i>

                <span>
                    Data User
                </span>

            </a>


            <a
                href="monitoring.php"
                class="menu-item"
            >

                <i class="bi bi-clipboard2-pulse"></i>

                <span>
                    Monitoring
                </span>

            </a>


            <a
                href="laporan.php"
                class="menu-item"
            >

                <i class="bi bi-file-earmark-bar-graph"></i>

                <span>
                    Laporan
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


    <!-- ================= MAIN ================= -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">


            <h1>
                Aktivitas
            </h1>


            <div class="topbar-right">

                <div class="topbar-profile" id="topbarProfile">
                    <button type="button" class="profile-trigger" id="profileTrigger" aria-expanded="false">
                        <span class="profile-trigger-name-wrap">
                            <strong><?= htmlspecialchars($nama_user); ?></strong>
                            <small><?= htmlspecialchars(strtolower($role_label)); ?></small>
                        </span>
                        <span class="profile-trigger-avatar">
                            <?= htmlspecialchars(strtoupper(substr(trim($nama_user), 0, 1))); ?>
                        </span>
                        <i class="bi bi-chevron-down profile-trigger-arrow"></i>
                    </button>

                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="profile-dropdown-header">
                            <div>
                                <p class="profile-dropdown-name"><?= htmlspecialchars($nama_user); ?></p>
                                <p class="profile-dropdown-role"><?= htmlspecialchars($role_label); ?></p>
                            </div>
                        </div>

                        <button type="button" class="profile-dropdown-item" id="profileDetailButton">
                            <i class="bi bi-person"></i>
                            <span>Profil Saya</span>
                        </button>

                        <a href="logout.php" class="profile-dropdown-item logout">
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
                        Aktivitas Sistem
                    </h2>

                    <p>
                        Riwayat aktivitas terbaru pada sistem
                    </p>

                </div>


                <a
                    href="dashboard.php"
                    class="back-button"
                >

                    <i class="bi bi-arrow-left"></i>

                    Kembali

                </a>


            </div>


            <!-- ACTIVITY CARD -->

            <div class="activity-card">


                <div class="activity-card-header">


                    <div class="activity-card-header-icon">

                        <i class="bi bi-clock-history"></i>

                    </div>


                    <div>

                        <h3>
                            Riwayat Aktivitas
                        </h3>

                        <p>
                            Aktivitas yang terjadi pada sistem
                        </p>

                    </div>


                </div>


                <div class="activity-list">


                    <!-- AKTIVITAS 1 -->

                    <div class="activity-item">

                        <div class="activity-point blue-point"></div>

                        <div class="activity-content">

                            <span class="activity-time">
                                Baru Saja
                            </span>

                            <strong>
                                Monitoring pasien ditambahkan
                            </strong>

                            <p>
                                Oleh Sarah untuk pasien Ahmad Ridwan.
                            </p>

                        </div>

                    </div>


                    <!-- AKTIVITAS 2 -->

                    <div class="activity-item">

                        <div class="activity-point gray-point"></div>

                        <div class="activity-content">

                            <span class="activity-time">
                                2 Jam Lalu
                            </span>

                            <strong>
                                Data pasien diperbarui
                            </strong>

                            <p>
                                Status Siti Aminah diubah menjadi
                                "Perlu Pantauan".
                            </p>

                        </div>

                    </div>


                    <!-- AKTIVITAS 3 -->

                    <div class="activity-item">

                        <div class="activity-point green-point"></div>

                        <div class="activity-content">

                            <span class="activity-time">
                                Kemarin, 14:30
                            </span>

                            <strong>
                                Laporan bulanan diekspor
                            </strong>

                            <p>
                                Admin mengunduh laporan aktivitas
                                bulan September.
                            </p>

                        </div>

                    </div>


                    <!-- AKTIVITAS 4 -->

                    <div class="activity-item">

                        <div class="activity-point gray-point"></div>

                        <div class="activity-content">

                            <span class="activity-time">
                                10 Okt, 09:00
                            </span>

                            <strong>
                                Pasien baru didaftarkan
                            </strong>

                            <p>
                                Budi Santoso ditambahkan ke sistem.
                            </p>

                        </div>

                    </div>


                </div>


            </div>


        </div>


    </main>


</div>



    <div class="profile-modal" id="profileModal" aria-hidden="true">
        <div class="profile-modal-card">
            <div class="profile-modal-head">
                <h3 class="profile-modal-title">Profil Saya</h3>
                <button type="button" class="profile-modal-close" id="profileModalClose" aria-label="Tutup">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="profile-modal-user">
                <div class="profile-avatar"><?= strtoupper(substr($nama_user, 0, 1)); ?></div>
                <div>
                    <p class="profile-modal-user-name"><?= htmlspecialchars($nama_user); ?></p>
                    <div class="profile-modal-user-role"><?= htmlspecialchars($role_label); ?></div>
                </div>
            </div>

            <div class="profile-info">
                <div class="profile-info-row">
                    <span class="profile-info-label">Nama</span>
                    <span class="profile-info-value"><?= htmlspecialchars($nama_user); ?></span>
                </div>
                <div class="profile-info-row">
                    <span class="profile-info-label">Role</span>
                    <span class="profile-info-value"><?= htmlspecialchars($role_label); ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        const topbarProfile = document.getElementById('topbarProfile');
        const profileTrigger = document.getElementById('profileTrigger');
        const profileDetailButton = document.getElementById('profileDetailButton');
        const profileModal = document.getElementById('profileModal');
        const profileModalClose = document.getElementById('profileModalClose');

        profileTrigger.addEventListener('click', function (event) {
            event.stopPropagation();
            const isOpen = topbarProfile.classList.toggle('open');
            this.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function (event) {
            if (!topbarProfile.contains(event.target)) {
                topbarProfile.classList.remove('open');
                profileTrigger.setAttribute('aria-expanded', 'false');
            }
        });

        profileDetailButton.addEventListener('click', function () {
            topbarProfile.classList.remove('open');
            profileTrigger.setAttribute('aria-expanded', 'false');
            profileModal.classList.add('show');
            profileModal.setAttribute('aria-hidden', 'false');
        });

        function closeProfileModal() {
            profileModal.classList.remove('show');
            profileModal.setAttribute('aria-hidden', 'true');
        }

        profileModalClose.addEventListener('click', closeProfileModal);
        profileModal.addEventListener('click', function (event) {
            if (event.target === profileModal) closeProfileModal();
        });
    </script>

</body>

</html>