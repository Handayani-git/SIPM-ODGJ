<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'petugas') {
    die('Anda tidak memiliki akses ke halaman ini.');
}

require_once 'koneksi.php';

$nama_user = $_SESSION['nama_lengkap'] ?? 'Petugas';
$id_user = $_SESSION['id_user'] ?? '-';

$query = "
    SELECT m.id_monitoring, m.id_pasien, m.id_user, m.tanggal_monitoring,
           m.berat_badan, m.kondisi, m.aktivitas_harian, m.perilaku,
           m.catatan, m.created_at, p.nama_pasien, p.nomor_registrasi,
           p.status_lokasi
    FROM monitoring m
    LEFT JOIN pasien p ON m.id_pasien = p.id_pasien
    ORDER BY m.tanggal_monitoring DESC
";
$result = mysqli_query($conn, $query);

$query_jadwal = "
    SELECT j.id_jadwal, j.id_pasien, j.id_user, j.tanggal_monitoring,
           j.waktu_monitoring, j.keterangan, j.status, j.created_at,
           p.nama_pasien, p.nomor_registrasi
    FROM jadwal_monitoring j
    INNER JOIN pasien p ON j.id_pasien = p.id_pasien
    WHERE p.status_lokasi = 'Luar Yayasan'
    ORDER BY j.tanggal_monitoring ASC, j.waktu_monitoring ASC
";
$hasil_jadwal = mysqli_query($conn, $query_jadwal);

function kondisiClass($kondisi) {
    if ($kondisi === 'Stabil') return 'badge-stabil';
    if ($kondisi === 'Perlu Pantauan') return 'badge-pantauan';
    if ($kondisi === 'Darurat') return 'badge-darurat';
    return 'badge-default';
}

$inisial = strtoupper(substr(trim($nama_user), 0, 1));
if ($inisial === '') $inisial = 'P';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Monitoring Pasien - SIPM ODGJ</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="/SIPM-ODGJ/assets/css/dashboard.css">
<style>
html,body{margin:0;padding:0;font-family:'Poppins',sans-serif;}
.dashboard-layout{display:flex;min-height:100vh}.sidebar{width:218px;min-width:218px;min-height:100vh;display:flex;flex-direction:column}.main-content{flex:1;min-width:0}.page-content{padding:28px}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-title h2{margin:0;font-size:24px}.page-title p{margin:6px 0 0;color:#7a8499;font-size:13px}.header-actions{display:flex;align-items:center;margin-left:auto}
.btn-tambah-monitoring{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:42px;padding:0 18px;background:#2f66d0;color:#fff;border:none;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;cursor:pointer;box-sizing:border-box}.btn-tambah-monitoring:hover{background:#2458bd;color:#fff}
/* PROFIL ATAS - SAMA GAYA AVATAR BIRU */
.topbar{position:relative!important;z-index:1000!important}.topbar-right{position:relative!important;z-index:1001!important}.user-profile{position:relative!important;display:flex!important;align-items:center!important;z-index:1002!important}
.profile-button{position:relative!important;display:flex!important;align-items:center!important;gap:10px!important;padding:6px 8px!important;border:none!important;background:transparent!important;color:#17243a!important;font-family:'Poppins',sans-serif!important;cursor:pointer!important;border-radius:10px!important;z-index:1003!important}.profile-button:hover{background:#f5f7fb!important}
.profile-top-info{display:flex;flex-direction:column;align-items:flex-end;line-height:1.2}.profile-top-info strong{font-size:12px;font-weight:600;color:#17243a}.profile-top-info span{font-size:9px;color:#8993a6;margin-top:2px}.profile-top-avatar{width:40px;height:40px;min-width:40px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#2864e6;color:#fff;font-size:16px;font-weight:600}.profile-arrow{font-size:10px!important;color:#8993a6!important;transition:transform .2s ease!important}.profile-button.active .profile-arrow{transform:rotate(180deg)}
.profile-dropdown{position:absolute!important;top:calc(100% + 10px)!important;right:0!important;width:230px!important;background:#fff!important;border:1px solid #e5eaf2!important;border-radius:12px!important;box-shadow:0 8px 25px rgba(0,0,0,.10)!important;overflow:hidden!important;opacity:0!important;visibility:hidden!important;transform:translateY(-6px)!important;transition:opacity .2s ease,transform .2s ease,visibility .2s ease!important;z-index:9999!important}.profile-dropdown.show{opacity:1!important;visibility:visible!important;transform:translateY(0)!important}
.profile-dropdown-header{display:flex!important;align-items:center!important;gap:11px!important;padding:16px!important}.profile-avatar{width:40px!important;height:40px!important;min-width:40px!important;display:flex!important;align-items:center!important;justify-content:center!important;border-radius:50%!important;background:#eef4ff!important;color:#2864e6!important;font-size:18px!important}.profile-info{display:flex!important;flex-direction:column!important;min-width:0!important}.profile-info strong{color:#17243a!important;font-size:12px!important;font-weight:600!important}.profile-info span{margin-top:2px!important;color:#8a95a8!important;font-size:10px!important}.profile-divider{height:1px!important;background:#edf0f5!important}.profile-menu-item{display:flex!important;align-items:center!important;gap:10px!important;width:100%!important;padding:12px 16px!important;color:#374151!important;text-decoration:none!important;font-size:11px!important;border:none!important;background:transparent!important;font-family:'Poppins',sans-serif!important;text-align:left!important;cursor:pointer!important}.profile-menu-item:hover{background:#f8fafc!important;color:#2864e6!important}.profile-menu-item i{width:18px!important;text-align:center!important;font-size:15px!important}.profile-menu-item.logout-item{color:#dc3545!important}.profile-menu-item.logout-item:hover{background:#fff5f5!important;color:#dc3545!important}
.profile-modal{position:fixed!important;inset:0!important;background:rgba(15,23,42,.35)!important;display:flex!important;align-items:center!important;justify-content:center!important;padding:20px!important;opacity:0!important;visibility:hidden!important;transition:opacity .2s ease,visibility .2s ease!important;z-index:20000!important}.profile-modal.show{opacity:1!important;visibility:visible!important}.profile-modal-card{width:100%!important;max-width:390px!important;background:#fff!important;border-radius:14px!important;box-shadow:0 15px 40px rgba(0,0,0,.15)!important;overflow:hidden!important}.profile-modal-top{display:flex!important;align-items:center!important;justify-content:space-between!important;padding:18px 20px!important;border-bottom:1px solid #edf0f5!important}.profile-modal-top h3{margin:0!important;font-size:15px!important;color:#17243a!important}.profile-close{width:30px!important;height:30px!important;display:flex!important;align-items:center!important;justify-content:center!important;border:none!important;background:#f5f7fb!important;color:#69768c!important;border-radius:7px!important;cursor:pointer!important}.profile-modal-body{padding:24px 20px!important}.profile-modal-avatar{width:68px!important;height:68px!important;margin:0 auto 15px!important;display:flex!important;align-items:center!important;justify-content:center!important;border-radius:50%!important;background:#eef4ff!important;color:#2864e6!important;font-size:30px!important}.profile-modal-name{text-align:center!important;font-size:17px!important;font-weight:600!important;color:#17243a!important;margin-bottom:4px!important}.profile-modal-role{text-align:center!important;font-size:11px!important;color:#8a95a8!important;margin-bottom:22px!important}.profile-detail{border:1px solid #e8edf4!important;border-radius:9px!important;overflow:hidden!important}.profile-detail-row{display:flex!important;align-items:center!important;justify-content:space-between!important;gap:20px!important;padding:12px 14px!important;border-bottom:1px solid #edf0f5!important}.profile-detail-row:last-child{border-bottom:none!important}.profile-detail-label{font-size:10px!important;color:#8a95a8!important}.profile-detail-value{font-size:11px!important;color:#17243a!important;font-weight:500!important;text-align:right!important}
.monitoring-card,.jadwal-card{background:#fff;border:1px solid #e1e7f0;border-radius:12px;overflow:hidden}.monitoring-card-header,.jadwal-card-header{display:flex;justify-content:space-between;align-items:center;gap:15px;padding:20px 22px;border-bottom:1px solid #edf0f5}.monitoring-card-header h3,.jadwal-card-header h3{margin:0;font-size:16px}.search-box-monitoring{position:relative;width:280px}.search-box-monitoring i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#9aa5b8}.search-box-monitoring input{width:100%;height:42px;padding-left:38px;border:1px solid #dce3ee;border-radius:8px;outline:none;font-family:Poppins;box-sizing:border-box}.table-wrapper{width:100%;overflow-x:auto}.monitoring-table{width:100%;border-collapse:collapse;min-width:1050px}.monitoring-table th,.jadwal-table th{padding:14px 16px;text-align:left;background:#f8faff;color:#69768c;font-size:10px}.monitoring-table td{padding:16px;border-bottom:1px solid #edf0f5;font-size:11px}.patient-name{font-weight:600}.patient-registration{display:block;margin-top:3px;color:#8a95a8;font-size:9px}.badge{display:inline-flex;padding:6px 11px;border-radius:20px;font-size:9px}.badge-stabil{background:#dcf8e8;color:#159447}.badge-pantauan{background:#fff0c9;color:#b57b00}.badge-darurat{background:#ffe1e1;color:#d83b3b}.badge-default{background:#eef1f6;color:#68758b}.action-button{width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #dce3ee;border-radius:7px;color:#526078;text-decoration:none}.empty-monitoring{padding:50px;text-align:center;color:#8a95a8}.jadwal-card{margin-top:22px}.jadwal-card-header p{margin:5px 0 0;color:#7a8499;font-size:12px}.jadwal-table{width:100%;border-collapse:collapse}.jadwal-table td{padding:14px 16px;border-bottom:1px solid #edf0f5;font-size:11px}.jadwal-status{display:inline-flex;padding:6px 10px;border-radius:20px;background:#eef4ff;color:#2f66d0;font-size:9px;font-weight:600}.jadwal-kosong{padding:35px 20px;text-align:center;color:#8a95a8;font-size:12px}
@media(max-width:700px){.page-content{padding:18px}.page-header{align-items:flex-start;flex-direction:column;gap:15px}.header-actions{width:100%}.btn-tambah-monitoring{width:100%}.monitoring-card-header,.jadwal-card-header{align-items:flex-start;flex-direction:column;gap:15px}.search-box-monitoring{width:100%}}
</style>
</head>
<body>
<div class="dashboard-layout">
<aside class="sidebar">
<div class="sidebar-brand"><img src="/SIPM-ODGJ/assets/img/logo YCKA.png" alt="Logo" class="sidebar-logo"><h2>SIPM ODGJ</h2><p>Yayasan Cahaya Kasih Amanah</p></div>
<nav class="sidebar-menu">
<a href="dashboard_petugas.php" class="menu-item"><i class="bi bi-grid"></i><span>Dashboard</span></a>
<a href="data_pasien_petugas.php" class="menu-item"><i class="bi bi-people"></i><span>Data Pasien</span></a>
<a href="monitoring_petugas.php" class="menu-item active"><i class="bi bi-clipboard2-pulse"></i><span>Monitoring</span></a>
</nav>
<div class="sidebar-bottom"><a href="logout.php" class="logout-button"><i class="bi bi-box-arrow-left"></i><span>Logout</span></a></div>
</aside>
<main class="main-content">
<header class="topbar"><h1>Monitoring</h1><div class="topbar-right"><div class="user-profile" id="userProfile">
<button type="button" class="profile-button" id="profileButton" aria-expanded="false"><div class="profile-top-info"><strong><?= htmlspecialchars($nama_user); ?></strong><span>petugas</span></div><div class="profile-top-avatar"><?= htmlspecialchars($inisial); ?></div><i class="bi bi-chevron-down profile-arrow"></i></button>
<div class="profile-dropdown" id="profileDropdown"><div class="profile-dropdown-header"><div class="profile-avatar"><?= htmlspecialchars($inisial); ?></div><div class="profile-info"><strong><?= htmlspecialchars($nama_user); ?></strong><span>Petugas</span></div></div><div class="profile-divider"></div><button type="button" class="profile-menu-item" id="profileSaya"><i class="bi bi-person"></i><span>Profil Saya</span></button><a href="logout.php" class="profile-menu-item logout-item"><i class="bi bi-box-arrow-left"></i><span>Logout</span></a></div>
</div></div></header>
<div class="page-content">
<div class="page-header"><div class="page-title"><h2>Monitoring Pasien</h2><p>Kelola data monitoring perkembangan pasien ODGJ</p></div><div class="header-actions"><a href="tambah_monitoring.php" class="btn-tambah-monitoring"><i class="bi bi-plus-lg"></i><span>Tambah Monitoring</span></a></div></div>
<div class="monitoring-card"><div class="monitoring-card-header"><h3>Daftar Monitoring</h3><div class="search-box-monitoring"><i class="bi bi-search"></i><input type="text" id="searchMonitoring" placeholder="Cari monitoring..."></div></div><div class="table-wrapper">
<?php if ($result && mysqli_num_rows($result)>0): ?>
<table class="monitoring-table" id="monitoringTable"><thead><tr><th>No</th><th>Pasien</th><th>Tanggal Monitoring</th><th>Berat Badan</th><th>Kondisi</th><th>Aktivitas Harian</th><th>Perilaku</th><th>Catatan</th><th>Detail</th></tr></thead><tbody>
<?php $no=1; while($monitoring=mysqli_fetch_assoc($result)): ?><tr><td><?= $no++; ?></td><td><span class="patient-name"><?= htmlspecialchars($monitoring['nama_pasien']??'Pasien'); ?></span><span class="patient-registration"><?= htmlspecialchars($monitoring['nomor_registrasi']??'-'); ?></span></td><td><?= !empty($monitoring['tanggal_monitoring']) ? date('d-m-Y H:i',strtotime($monitoring['tanggal_monitoring'])) : '-'; ?></td><td><?= $monitoring['berat_badan']!==null ? htmlspecialchars($monitoring['berat_badan']).' kg' : '-'; ?></td><td><span class="badge <?= kondisiClass($monitoring['kondisi']); ?>"><?= htmlspecialchars($monitoring['kondisi']??'-'); ?></span></td><td><?= htmlspecialchars($monitoring['aktivitas_harian']??'-'); ?></td><td><?= htmlspecialchars($monitoring['perilaku']??'-'); ?></td><td><?= htmlspecialchars($monitoring['catatan']??'-'); ?></td><td><a href="detail_monitoring.php?id=<?= (int)$monitoring['id_monitoring']; ?>" class="action-button" title="Lihat"><i class="bi bi-eye"></i></a></td></tr><?php endwhile; ?></tbody></table>
<?php else: ?><div class="empty-monitoring"><i class="bi bi-clipboard2-pulse"></i><h3>Belum Ada Data Monitoring</h3><p>Data monitoring pasien belum tersedia.</p></div><?php endif; ?></div></div>
<div class="jadwal-card"><div class="jadwal-card-header"><div><h3>Jadwal Monitoring Pasien Luar Yayasan</h3><p>Jadwal monitoring yang akan dilakukan oleh petugas kepada pasien di luar yayasan.</p></div><a href="tambah_jadwal_monitoring.php" class="btn-tambah-monitoring"><i class="bi bi-plus-lg"></i><span>Tambah Jadwal</span></a></div>
<?php if($hasil_jadwal && mysqli_num_rows($hasil_jadwal)>0): ?><div class="table-wrapper"><table class="jadwal-table"><thead><tr><th>No</th><th>Pasien</th><th>Tanggal</th><th>Waktu</th><th>Keterangan</th><th>Status</th></tr></thead><tbody><?php $no_jadwal=1; while($jadwal=mysqli_fetch_assoc($hasil_jadwal)): ?><tr><td><?= $no_jadwal++; ?></td><td><strong><?= htmlspecialchars($jadwal['nama_pasien']??'Pasien'); ?></strong><span class="patient-registration"><?= htmlspecialchars($jadwal['nomor_registrasi']??'-'); ?></span></td><td><?= !empty($jadwal['tanggal_monitoring']) ? date('d-m-Y',strtotime($jadwal['tanggal_monitoring'])) : '-'; ?></td><td><?= !empty($jadwal['waktu_monitoring']) ? date('H:i',strtotime($jadwal['waktu_monitoring'])) : '-'; ?></td><td><?= htmlspecialchars($jadwal['keterangan']??'-'); ?></td><td><span class="jadwal-status"><?= htmlspecialchars($jadwal['status']??'Terjadwal'); ?></span></td></tr><?php endwhile; ?></tbody></table></div><?php else: ?><div class="jadwal-kosong"><i class="bi bi-calendar-check"></i><p>Belum ada jadwal monitoring pasien luar yayasan.</p></div><?php endif; ?></div>
</div></main></div>
<div class="profile-modal" id="profileModal"><div class="profile-modal-card"><div class="profile-modal-top"><h3>Profil Saya</h3><button type="button" class="profile-close" id="closeProfile"><i class="bi bi-x-lg"></i></button></div><div class="profile-modal-body"><div class="profile-modal-avatar"><?= htmlspecialchars($inisial); ?></div><div class="profile-modal-name"><?= htmlspecialchars($nama_user); ?></div><div class="profile-modal-role">Petugas</div><div class="profile-detail"><div class="profile-detail-row"><span class="profile-detail-label">Nama Lengkap</span><span class="profile-detail-value"><?= htmlspecialchars($nama_user); ?></span></div><div class="profile-detail-row"><span class="profile-detail-label">ID Pengguna</span><span class="profile-detail-value"><?= htmlspecialchars((string)$id_user); ?></span></div><div class="profile-detail-row"><span class="profile-detail-label">Role</span><span class="profile-detail-value">Petugas</span></div></div></div></div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
const b=document.getElementById('profileButton'),d=document.getElementById('profileDropdown'),u=document.getElementById('userProfile'),s=document.getElementById('profileSaya'),m=document.getElementById('profileModal'),c=document.getElementById('closeProfile');
if(b&&d)b.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();d.classList.toggle('show');b.classList.toggle('active');});
document.addEventListener('click',function(e){if(u&&!u.contains(e.target)){d&&d.classList.remove('show');b&&b.classList.remove('active');}});
if(s&&m)s.addEventListener('click',function(e){e.preventDefault();d.classList.remove('show');b.classList.remove('active');m.classList.add('show');});
if(c&&m)c.addEventListener('click',function(){m.classList.remove('show');});
if(m)m.addEventListener('click',function(e){if(e.target===m)m.classList.remove('show');});
document.addEventListener('keydown',function(e){if(e.key==='Escape'){m&&m.classList.remove('show');d&&d.classList.remove('show');b&&b.classList.remove('active');}});
const input=document.getElementById('searchMonitoring'),table=document.getElementById('monitoringTable');
if(input&&table)input.addEventListener('keyup',function(){const k=this.value.toLowerCase().trim();table.querySelectorAll('tbody tr').forEach(function(r){r.style.display=r.innerText.toLowerCase().includes(k)?'':'none';});});
});
</script>
</body></html>
