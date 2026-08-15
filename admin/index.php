<?php

session_start();

require_once "../config/database.php";


// =====================================================
// ADMIN AUTHENTICATION
// =====================================================

if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {

    header("Location: login.php");

    exit();

}


// =====================================================
// HELPER FUNCTION
// =====================================================

function getCount($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);

    return (int)$row["total"];
}


// =====================================================
// DASHBOARD STATISTICS
// =====================================================

// Total registered users

$total_users = getCount(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM users
    "
);


// Total donors

$total_donors = getCount(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM donor_profiles
    "
);


// Available donors

$available_donors = getCount(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM donor_profiles
    WHERE availability = 'Available'
    "
);


// Total blood requests

$total_requests = getCount(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM blood_requests
    "
);


// Pending requests

$pending_requests = getCount(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM blood_requests
    WHERE status = 'Pending'
    "
);


// Accepted requests

$accepted_requests = getCount(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM blood_requests
    WHERE status = 'Accepted'
    "
);


// Completed requests

$completed_requests = getCount(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM blood_requests
    WHERE status = 'Completed'
    "
);


// Rejected requests

$rejected_requests = getCount(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM blood_requests
    WHERE status = 'Rejected'
    "
);


// =====================================================
// RECENT BLOOD REQUESTS
// =====================================================

$recent_sql = "

    SELECT

        id,

        COALESCE(
            requester_name,
            'Registered User'
        ) AS requester_name,

        patient_name,

        blood_group,

        units_required,

        hospital_name,

        urgency,

        status,

        created_at

    FROM blood_requests

    ORDER BY id DESC

    LIMIT 8

";


$recent_result = mysqli_query(
    $conn,
    $recent_sql
);


// =====================================================
// ADMIN NAME
// =====================================================

$admin_name =
    $_SESSION["admin_name"]
    ?? "Administrator";


$admin_initial =
    strtoupper(
        substr(
            $admin_name,
            0,
            1
        )
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Admin Dashboard | BloodConnect
</title>


<!-- Bootstrap -->

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">


<!-- Bootstrap Icons -->

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<!-- Google Font -->

<link
href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
rel="stylesheet">


<style>

/* =====================================================
GLOBAL
===================================================== */

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family: 'Inter', sans-serif;

    background: #f6f7f9;

    color: #111827;

}


/* =====================================================
SIDEBAR
===================================================== */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    bottom: 0;

    width: 245px;

    background: #111827;

    color: white;

    padding: 22px 15px;

    z-index: 1000;

}


/* LOGO */

.logo {

    display: flex;

    align-items: center;

    gap: 11px;

    padding: 5px 10px 23px;

    border-bottom: 1px solid #273244;

    color: white;

    text-decoration: none;

}


.logo-icon {

    width: 40px;

    height: 40px;

    border-radius: 11px;

    background: #e63946;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 18px;

}


.logo-text {

    font-size: 17px;

    font-weight: 800;

}


.logo-text span {

    display: block;

    color: #9ca3af;

    font-size: 8px;

    font-weight: 500;

    margin-top: 2px;

}


/* MENU */

.menu {

    margin-top: 25px;

}


.menu-title {

    color: #6b7280;

    font-size: 8px;

    text-transform: uppercase;

    letter-spacing: 1px;

    font-weight: 800;

    padding: 0 12px;

    margin-bottom: 8px;

}


.menu a {

    display: flex;

    align-items: center;

    gap: 12px;

    color: #9ca3af;

    text-decoration: none;

    font-size: 11px;

    font-weight: 600;

    padding: 11px 12px;

    border-radius: 9px;

    margin-bottom: 4px;

    transition: .2s;

}


.menu a i {

    font-size: 15px;

    width: 18px;

    text-align: center;

}


.menu a:hover {

    background: #1f2937;

    color: white;

}


.menu a.active {

    background: #2a1d20;

    color: #ff5c68;

}


/* SIDEBAR BOTTOM */

.sidebar-bottom {

    position: absolute;

    left: 15px;

    right: 15px;

    bottom: 20px;

}


.admin-mini {

    border-top: 1px solid #273244;

    padding-top: 15px;

    display: flex;

    align-items: center;

    gap: 10px;

}


.admin-mini-avatar {

    width: 35px;

    height: 35px;

    border-radius: 10px;

    background: #fff1f2;

    color: #e63946;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 12px;

    font-weight: 800;

}


.admin-mini-info {

    min-width: 0;

}


.admin-mini-info strong {

    display: block;

    color: white;

    font-size: 10px;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;

}


.admin-mini-info span {

    color: #6b7280;

    font-size: 8px;

}


/* =====================================================
MAIN
===================================================== */

.main {

    margin-left: 245px;

    min-height: 100vh;

    padding: 25px 30px;

}


/* =====================================================
TOPBAR
===================================================== */

.topbar {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 27px;

}


.page-title h1 {

    margin: 0;

    font-size: 25px;

    font-weight: 800;

}


.page-title p {

    margin: 6px 0 0;

    color: #6b7280;

    font-size: 11px;

}


.top-right {

    display: flex;

    align-items: center;

    gap: 15px;

}


.notification-btn {

    width: 38px;

    height: 38px;

    border-radius: 10px;

    background: white;

    border: 1px solid #eee;

    color: #6b7280;

    display: flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    position: relative;

}


.notification-dot {

    position: absolute;

    width: 7px;

    height: 7px;

    background: #e63946;

    border-radius: 50%;

    top: 8px;

    right: 8px;

}


.profile {

    display: flex;

    align-items: center;

    gap: 9px;

}


.profile-avatar {

    width: 39px;

    height: 39px;

    border-radius: 11px;

    background: #fff1f2;

    color: #e63946;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 12px;

    font-weight: 800;

}


.profile-name strong {

    display: block;

    font-size: 10px;

}


.profile-name span {

    color: #9ca3af;

    font-size: 8px;

}


/* =====================================================
WELCOME
===================================================== */

.welcome {

    background:
        linear-gradient(
            120deg,
            #e63946,
            #c81e2a
        );

    color: white;

    border-radius: 17px;

    padding: 24px 27px;

    margin-bottom: 22px;

    position: relative;

    overflow: hidden;

}


.welcome::after {

    content: "";

    position: absolute;

    width: 180px;

    height: 180px;

    border-radius: 50%;

    border: 25px solid
        rgba(255,255,255,.08);

    right: 40px;

    top: -80px;

}


.welcome h2 {

    margin: 0 0 6px;

    font-size: 19px;

    font-weight: 800;

}


.welcome p {

    margin: 0;

    color: #ffe4e6;

    font-size: 10px;

}


/* =====================================================
SECTION TITLE
===================================================== */

.section-title {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 12px;

}


.section-title h3 {

    font-size: 14px;

    font-weight: 800;

    margin: 0;

}


.section-title a {

    color: #e63946;

    text-decoration: none;

    font-size: 9px;

    font-weight: 700;

}


/* =====================================================
STAT CARDS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 13px;

    margin-bottom: 25px;

}


.stat-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 14px;

    padding: 17px;

    transition: .2s;

}


.stat-card:hover {

    transform: translateY(-2px);

    box-shadow:
        0 10px 25px
        rgba(0,0,0,.05);

}


.stat-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

}


.stat-icon {

    width: 37px;

    height: 37px;

    border-radius: 10px;

    background: #fff1f2;

    color: #e63946;

    display: flex;

    align-items: center;

    justify-content: center;

}


.stat-number {

    font-size: 24px;

    font-weight: 800;

    margin-top: 15px;

}


.stat-label {

    color: #6b7280;

    font-size: 9px;

    margin-top: 2px;

}


/* =====================================================
STATUS CARDS
===================================================== */

.status-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 13px;

    margin-bottom: 25px;

}


.status-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 13px;

    padding: 14px 16px;

}


.status-card-top {

    display: flex;

    justify-content: space-between;

    align-items: center;

}


.status-card span {

    color: #6b7280;

    font-size: 9px;

}


.status-card strong {

    display: block;

    font-size: 19px;

    margin-top: 5px;

}


.status-icon {

    width: 31px;

    height: 31px;

    border-radius: 8px;

    display: flex;

    align-items: center;

    justify-content: center;

}


.pending-icon {

    background: #fff7ed;

    color: #ea580c;

}


.accepted-icon {

    background: #ecfdf5;

    color: #059669;

}


.completed-icon {

    background: #eff6ff;

    color: #2563eb;

}


.rejected-icon {

    background: #fff1f2;

    color: #dc2626;

}


/* =====================================================
CONTENT GRID
===================================================== */

.content-grid {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        260px;

    gap: 17px;

}


/* =====================================================
TABLE CARD
===================================================== */

.card {

    background: white;

    border: 1px solid #eee;

    border-radius: 16px;

    padding: 19px;

}


.table {

    margin: 0;

}


.table th {

    color: #9ca3af;

    text-transform: uppercase;

    font-size: 8px;

    font-weight: 800;

    border-bottom: 1px solid #eee;

    padding: 10px;

}


.table td {

    border-bottom: 1px solid #f3f4f6;

    padding: 12px 10px;

    font-size: 9px;

    vertical-align: middle;

}


.table tr:last-child td {

    border-bottom: none;

}


.patient {

    font-weight: 700;

    color: #111827;

}


.requester {

    color: #9ca3af;

    font-size: 8px;

    margin-top: 2px;

}


.blood-badge {

    display: inline-flex;

    align-items: center;

    gap: 4px;

    background: #fff1f2;

    color: #e63946;

    border-radius: 7px;

    padding: 5px 8px;

    font-weight: 800;

    font-size: 9px;

}


.status-badge {

    display: inline-block;

    border-radius: 50px;

    padding: 5px 8px;

    font-size: 7px;

    font-weight: 800;

}


.status-pending {

    background: #fff7ed;

    color: #c2410c;

}


.status-accepted {

    background: #ecfdf5;

    color: #047857;

}


.status-completed {

    background: #eff6ff;

    color: #1d4ed8;

}


.status-rejected {

    background: #fff1f2;

    color: #b91c1c;

}


.view-link {

    color: #e63946;

    text-decoration: none;

    font-size: 9px;

    font-weight: 700;

}


/* =====================================================
QUICK ACTIONS
===================================================== */

.quick-actions {

    display: grid;

    gap: 9px;

}


.quick-action {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 11px;

    border-radius: 10px;

    background: #f9fafb;

    color: #374151;

    text-decoration: none;

    border: 1px solid #f0f0f0;

    transition: .2s;

}


.quick-action:hover {

    background: #fff1f2;

    border-color: #fecdd3;

    color: #e63946;

}


.quick-action-icon {

    width: 32px;

    height: 32px;

    border-radius: 8px;

    background: white;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #e63946;

    font-size: 13px;

}


.quick-action-text strong {

    display: block;

    font-size: 9px;

}


.quick-action-text span {

    color: #9ca3af;

    font-size: 7px;

}


/* =====================================================
DONOR SUMMARY
===================================================== */

.donor-summary {

    margin-top: 17px;

}


.summary-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 10px 0;

    border-bottom: 1px solid #f3f4f6;

}


.summary-row:last-child {

    border-bottom: none;

}


.summary-row span {

    color: #6b7280;

    font-size: 9px;

}


.summary-row strong {

    font-size: 11px;

}


/* =====================================================
RESPONSIVE
===================================================== */

@media(max-width: 1100px) {

    .stats,
    .status-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }

}


@media(max-width: 850px) {

    .sidebar {

        width: 70px;

        padding: 15px 8px;

    }


    .logo {

        justify-content: center;

        padding-left: 0;

        padding-right: 0;

    }


    .logo-text,
    .logo-text span,
    .menu-title,
    .menu a span,
    .admin-mini-info {

        display: none;

    }


    .menu a {

        justify-content: center;

    }


    .sidebar-bottom {

        left: 8px;

        right: 8px;

    }


    .admin-mini {

        justify-content: center;

    }


    .main {

        margin-left: 70px;

    }


    .content-grid {

        grid-template-columns: 1fr;

    }

}


@media(max-width:600px) {

    .main {

        margin-left: 0;

        padding: 18px 12px;

    }


    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

        padding: 10px;

    }


    .logo {

        justify-content: flex-start;

        padding: 5px 10px 12px;

    }


    .logo-text {

        display: block;

    }


    .logo-text span {

        display: block;

    }


    .menu {

        display: flex;

        gap: 4px;

        overflow-x: auto;

        margin-top: 10px;

    }


    .menu-title,
    .sidebar-bottom {

        display: none;

    }


    .menu a {

        white-space: nowrap;

        justify-content: flex-start;

        margin: 0;

    }


    .menu a span {

        display: inline;

    }


    .topbar {

        align-items: flex-start;

    }


    .profile-name {

        display: none;

    }


    .stats,
    .status-grid {

        grid-template-columns: 1fr 1fr;

    }


    .welcome {

        padding: 20px;

    }


}


@media(max-width:400px) {

    .stats,
    .status-grid {

        grid-template-columns: 1fr;

    }

}

</style>

</head>


<body>


<!-- =====================================================
SIDEBAR
===================================================== -->

<aside class="sidebar">


<a
href="index.php"
class="logo">


<div class="logo-icon">

<i class="bi bi-heart-pulse-fill"></i>

</div>


<div class="logo-text">

BloodConnect

<span>
ADMIN PANEL
</span>

</div>


</a>



<div class="menu">


<div class="menu-title">

Main Menu

</div>


<a
href="index.php"
class="active">

<i class="bi bi-grid-1x2-fill"></i>

<span>
Dashboard
</span>

</a>


<a href="donors.php">

<i class="bi bi-person-heart"></i>

<span>
Donors
</span>

</a>


<!-- <a href="users.php">

<i class="bi bi-people-fill"></i>

<span>
Users
</span>

</a> -->


<a href="blood-requests.php">

<i class="bi bi-droplet-fill"></i>

<span>
Blood Requests
</span>

</a>


<a href="notifications.php">

<i class="bi bi-bell-fill"></i>

<span>
Notifications
</span>

</a>



<div
class="menu-title"
style="margin-top:22px;">

System

</div>


<a href="settings.php">

<i class="bi bi-gear-fill"></i>

<span>
Settings
</span>

</a>


<a href="logout.php">

<i class="bi bi-box-arrow-right"></i>

<span>
Logout
</span>

</a>


</div>



<div class="sidebar-bottom">


<div class="admin-mini">


<div class="admin-mini-avatar">

<?php

echo htmlspecialchars(
    $admin_initial
);

?>

</div>


<div class="admin-mini-info">


<strong>

<?php

echo htmlspecialchars(
    $admin_name
);

?>

</strong>


<span>
Administrator
</span>


</div>


</div>


</div>


</aside>



<!-- =====================================================
MAIN
===================================================== -->

<main class="main">


<!-- =====================================================
TOPBAR
===================================================== -->

<div class="topbar">


<div class="page-title">

<h1>
Dashboard
</h1>

<p>
Welcome back, <?php

echo htmlspecialchars(
    $admin_name
);

?>. Here's what's happening today.

</p>

</div>


<div class="top-right">


<a
href="notifications.php"
class="notification-btn">

<i class="bi bi-bell"></i>

<span class="notification-dot"></span>

</a>


<div class="profile">


<div class="profile-avatar">

<?php

echo htmlspecialchars(
    $admin_initial
);

?>

</div>


<div class="profile-name">

<strong>

<?php

echo htmlspecialchars(
    $admin_name
);

?>

</strong>

<span>
Administrator
</span>

</div>


</div>


</div>


</div>



<!-- =====================================================
WELCOME BANNER
===================================================== -->

<div class="welcome">


<h2>

<i class="bi bi-heart-pulse-fill"></i>

&nbsp;

BloodConnect Admin

</h2>


<p>

Manage donors, users and blood requests
from one place.

</p>


</div>



<!-- =====================================================
MAIN STATISTICS
===================================================== -->

<div class="section-title">


<h3>
Overview
</h3>


</div>


<div class="stats">


<!-- USERS -->

<div class="stat-card">


<div class="stat-header">


<div class="stat-icon">

<i class="bi bi-people-fill"></i>

</div>


</div>


<div class="stat-number">

<?php

echo $total_users;

?>

</div>


<div class="stat-label">

Total Users

</div>


</div>



<!-- DONORS -->

<div class="stat-card">


<div class="stat-header">


<div class="stat-icon">

<i class="bi bi-person-heart"></i>

</div>


</div>


<div class="stat-number">

<?php

echo $total_donors;

?>

</div>


<div class="stat-label">

Registered Donors

</div>


</div>



<!-- AVAILABLE DONORS -->

<div class="stat-card">


<div class="stat-header">


<div class="stat-icon">

<i class="bi bi-heart-pulse-fill"></i>

</div>


</div>


<div class="stat-number">

<?php

echo $available_donors;

?>

</div>


<div class="stat-label">

Available Donors

</div>


</div>



<!-- REQUESTS -->

<div class="stat-card">


<div class="stat-header">


<div class="stat-icon">

<i class="bi bi-droplet-fill"></i>

</div>


</div>


<div class="stat-number">

<?php

echo $total_requests;

?>

</div>


<div class="stat-label">

Total Blood Requests

</div>


</div>


</div>



<!-- =====================================================
REQUEST STATUS
===================================================== -->

<div class="section-title">


<h3>
Request Status
</h3>


<a href="blood-requests.php">

Manage Requests

<i class="bi bi-arrow-right"></i>

</a>


</div>


<div class="status-grid">


<!-- PENDING -->

<div class="status-card">


<div class="status-card-top">


<div>

<span>
Pending
</span>


<strong>

<?php

echo $pending_requests;

?>

</strong>

</div>


<div class="
status-icon
pending-icon">

<i class="bi bi-hourglass-split"></i>

</div>


</div>


</div>



<!-- ACCEPTED -->

<div class="status-card">


<div class="status-card-top">


<div>

<span>
Accepted
</span>


<strong>

<?php

echo $accepted_requests;

?>

</strong>

</div>


<div class="
status-icon
accepted-icon">

<i class="bi bi-check-circle-fill"></i>

</div>


</div>


</div>



<!-- COMPLETED -->

<div class="status-card">


<div class="status-card-top">


<div>

<span>
Completed
</span>


<strong>

<?php

echo $completed_requests;

?>

</strong>

</div>


<div class="
status-icon
completed-icon">

<i class="bi bi-check2-all"></i>

</div>


</div>


</div>



<!-- REJECTED -->

<div class="status-card">


<div class="status-card-top">


<div>

<span>
Rejected
</span>


<strong>

<?php

echo $rejected_requests;

?>

</strong>

</div>


<div class="
status-icon
rejected-icon">

<i class="bi bi-x-circle-fill"></i>

</div>


</div>


</div>


</div>



<!-- =====================================================
CONTENT
===================================================== -->

<div class="content-grid">


<!-- =================================================
RECENT REQUESTS
================================================= -->

<div class="card">


<div class="section-title">


<h3>
Recent Blood Requests
</h3>


<a href="blood-requests.php">

View All

<i class="bi bi-arrow-right"></i>

</a>


</div>


<div class="table-responsive">


<table class="table">


<thead>

<tr>

<th>
Patient
</th>

<th>
Blood
</th>

<th>
Hospital
</th>

<th>
Urgency
</th>

<th>
Status
</th>

<th>
Action
</th>

</tr>

</thead>


<tbody>


<?php

if (
    $recent_result &&
    mysqli_num_rows(
        $recent_result
    ) > 0
):

?>


<?php while (
    $row =
    mysqli_fetch_assoc(
        $recent_result
    )
):

?>


<tr>


<td>


<div class="patient">

<?php

echo htmlspecialchars(
    $row["patient_name"]
);

?>

</div>


<div class="requester">

By:

<?php

echo htmlspecialchars(
    $row["requester_name"]
);

?>

</div>


</td>


<td>


<span class="blood-badge">

<i class="bi bi-droplet-fill"></i>

<?php

echo htmlspecialchars(
    $row["blood_group"]
);

?>

</span>


</td>


<td>

<?php

echo htmlspecialchars(
    $row["hospital_name"]
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row["urgency"]
);

?>

</td>


<td>


<?php

$status_class =
    strtolower(
        $row["status"]
    );

?>


<span class="
status-badge
status-<?php
echo $status_class;
?>">

<?php

echo htmlspecialchars(
    $row["status"]
);

?>

</span>


</td>


<td>

<a
href="view-request.php?id=<?php
echo (int)$row["id"];
?>"
class="view-link">

View

<i class="bi bi-arrow-right"></i>

</a>

</td>


</tr>


<?php endwhile; ?>


<?php else: ?>


<tr>

<td
colspan="6"
style="
text-align:center;
color:#9ca3af;
padding:30px;
">

<i
class="bi bi-inbox"
style="
font-size:25px;
display:block;
margin-bottom:8px;
">

</i>

No blood requests found.

</td>

</tr>


<?php endif; ?>


</tbody>


</table>


</div>


</div>



<!-- =================================================
RIGHT SIDE
================================================= -->

<div>


<!-- QUICK ACTIONS -->

<div class="card">


<div class="section-title">

<h3>
Quick Actions
</h3>

</div>


<div class="quick-actions">


<a
href="blood-requests.php"
class="quick-action">


<div class="quick-action-icon">

<i class="bi bi-droplet-fill"></i>

</div>


<div class="quick-action-text">

<strong>
Manage Requests
</strong>

<span>
View all blood requests
</span>

</div>


</a>



<a
href="donors.php"
class="quick-action">


<div class="quick-action-icon">

<i class="bi bi-person-heart"></i>

</div>


<div class="quick-action-text">

<strong>
Manage Donors
</strong>

<span>
View registered donors
</span>

</div>


</a>

<a
href="notifications.php"
class="quick-action">


<div class="quick-action-icon">

<i class="bi bi-bell-fill"></i>

</div>


<div class="quick-action-text">

<strong>
Notifications
</strong>

<span>
Manage notifications
</span>

</div>


</a>


</div>


</div>



<!-- DONOR SUMMARY -->

<div class="card donor-summary">


<div class="section-title">

<h3>
Donor Summary
</h3>

</div>


<div class="summary-row">

<span>
Total Donors
</span>

<strong>

<?php

echo $total_donors;

?>

</strong>

</div>


<div class="summary-row">

<span>
Available Now
</span>

<strong
style="color:#059669;">

<?php

echo $available_donors;

?>

</strong>

</div>


<div class="summary-row">

<span>
Unavailable
</span>

<strong
style="color:#dc2626;">

<?php

echo max(
    0,
    $total_donors -
    $available_donors
);

?>

</strong>

</div>


</div>


</div>


</div>


</main>


</body>

</html>