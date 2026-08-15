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
// GET DONOR ID
// =====================================================

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {

    header("Location: donors.php");
    exit();

}


$donor_id = (int) $_GET["id"];


// =====================================================
// GET DONOR DETAILS
// =====================================================

$sql = "

    SELECT

        u.id AS user_id,

        u.full_name,

        u.email,

        u.phone,

        u.status AS user_status,

        d.blood_group,

        d.city,

        d.district,

        d.state,

        d.pincode,

        d.availability,

        d.created_at AS donor_created_at

    FROM users u

    INNER JOIN donor_profiles d
        ON u.id = d.user_id

    WHERE u.id = ?

    LIMIT 1

";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $donor_id
);


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


if (
    mysqli_num_rows($result) !== 1
) {

    mysqli_stmt_close($stmt);

    die("Donor not found.");

}


$donor =
    mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


// =====================================================
// GET DONATION / REQUEST HISTORY
// =====================================================

$history_sql = "

    SELECT

        br.id AS request_id,

        br.patient_name,

        br.blood_group,

        br.units_required,

        br.hospital_name,

        br.urgency,

        br.status,

        br.required_date,

        br.created_at,

        brd.status AS donor_response

    FROM blood_request_donors brd

    INNER JOIN blood_requests br
        ON brd.request_id = br.id

    WHERE brd.donor_id = ?

    ORDER BY br.created_at DESC

    LIMIT 10

";


$history_stmt = mysqli_prepare(
    $conn,
    $history_sql
);


mysqli_stmt_bind_param(
    $history_stmt,
    "i",
    $donor_id
);


mysqli_stmt_execute(
    $history_stmt
);


$history_result =
    mysqli_stmt_get_result(
        $history_stmt
    );


// =====================================================
// REQUEST STATISTICS
// =====================================================

function getDonorCount(
    $conn,
    $sql,
    $donor_id
) {

    $stmt =
        mysqli_prepare(
            $conn,
            $sql
        );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $donor_id
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $row =
        mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return (int)$row["total"];
}


$total_requests =
    getDonorCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM blood_request_donors
        WHERE donor_id = ?
        ",
        $donor_id
    );


$accepted_requests =
    getDonorCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM blood_request_donors
        WHERE donor_id = ?
        AND status = 'Accepted'
        ",
        $donor_id
    );


$rejected_requests =
    getDonorCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM blood_request_donors
        WHERE donor_id = ?
        AND status = 'Rejected'
        ",
        $donor_id
    );


$pending_requests =
    getDonorCount(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM blood_request_donors
        WHERE donor_id = ?
        AND status = 'Pending'
        ",
        $donor_id
    );


// =====================================================
// ADMIN
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


// =====================================================
// DONOR INITIAL
// =====================================================

$donor_initial =
    strtoupper(
        substr(
            $donor["full_name"],
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

View Donor |
BloodConnect Admin

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

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;

}


.back-link {

    color: #6b7280;

    text-decoration: none;

    font-size: 10px;

    font-weight: 700;

}


.back-link:hover {

    color: #e63946;

}


.admin-profile {

    display: flex;

    align-items: center;

    gap: 9px;

}


.admin-avatar {

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


.admin-profile strong {

    display: block;

    font-size: 10px;

}


.admin-profile span {

    color: #9ca3af;

    font-size: 8px;

}


/* =====================================================
DONOR HERO
===================================================== */

.donor-hero {

    background: white;

    border: 1px solid #eee;

    border-radius: 18px;

    padding: 24px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 18px;

}


.donor-info {

    display: flex;

    align-items: center;

    gap: 17px;

}


.donor-avatar {

    width: 72px;

    height: 72px;

    border-radius: 18px;

    background: #fff1f2;

    color: #e63946;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

    font-weight: 800;

}


.donor-name {

    font-size: 22px;

    font-weight: 800;

    margin: 0 0 5px;

}


.donor-email {

    color: #6b7280;

    font-size: 10px;

}


.donor-status {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    margin-top: 8px;

    padding: 5px 9px;

    border-radius: 50px;

    font-size: 8px;

    font-weight: 800;

}


.status-active {

    background: #ecfdf5;

    color: #047857;

}


.status-inactive {

    background: #f3f4f6;

    color: #6b7280;

}


.blood-badge-large {

    background: #fff1f2;

    color: #e63946;

    padding: 15px 18px;

    border-radius: 14px;

    font-size: 20px;

    font-weight: 800;

}


/* =====================================================
ACTIONS
===================================================== */

.actions {

    display: flex;

    gap: 8px;

    margin-bottom: 18px;

}


.action {

    padding: 9px 13px;

    border-radius: 8px;

    text-decoration: none;

    font-size: 9px;

    font-weight: 700;

}


.back {

    background: #f3f4f6;

    color: #374151;

}


.toggle {

    background: #ecfdf5;

    color: #047857;

}


.delete {

    background: #fff1f2;

    color: #b91c1c;

}


/* =====================================================
STATISTICS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 13px;

    margin-bottom: 18px;

}


.stat-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 14px;

    padding: 16px;

}


.stat-label {

    color: #6b7280;

    font-size: 8px;

}


.stat-number {

    font-size: 22px;

    font-weight: 800;

    margin-top: 5px;

}


/* =====================================================
CONTENT GRID
===================================================== */

.content-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 17px;

}


/* =====================================================
CARD
===================================================== */

.card {

    background: white;

    border: 1px solid #eee;

    border-radius: 16px;

    padding: 20px;

}


.card-title {

    font-size: 14px;

    font-weight: 800;

    margin-bottom: 18px;

}


/* =====================================================
DETAIL
===================================================== */

.detail {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 12px 0;

    border-bottom: 1px solid #f3f4f6;

}


.detail:last-child {

    border-bottom: none;

}


.detail-icon {

    width: 34px;

    height: 34px;

    border-radius: 9px;

    background: #fff1f2;

    color: #e63946;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 13px;

}


.detail-label {

    color: #9ca3af;

    font-size: 8px;

    margin-bottom: 3px;

}


.detail-value {

    font-size: 10px;

    font-weight: 700;

}


/* =====================================================
HISTORY
===================================================== */

.history-card {

    grid-column: 1 / -1;

}


.table {

    margin: 0;

}


.table th {

    color: #9ca3af;

    font-size: 8px;

    text-transform: uppercase;

    border-bottom: 1px solid #eee;

    padding: 10px;

}


.table td {

    font-size: 9px;

    padding: 12px 10px;

    vertical-align: middle;

    border-bottom: 1px solid #f3f4f6;

}


.table tr:last-child td {

    border-bottom: none;

}


.blood-badge {

    background: #fff1f2;

    color: #e63946;

    padding: 5px 8px;

    border-radius: 6px;

    font-size: 8px;

    font-weight: 800;

}


.response {

    padding: 5px 8px;

    border-radius: 50px;

    font-size: 7px;

    font-weight: 800;

}


.response-pending {

    background: #fff7ed;

    color: #c2410c;

}


.response-accepted {

    background: #ecfdf5;

    color: #047857;

}


.response-rejected {

    background: #fff1f2;

    color: #b91c1c;

}


.request-status {

    padding: 5px 8px;

    border-radius: 50px;

    font-size: 7px;

    font-weight: 800;

}


.request-status-Pending {

    background: #fff7ed;

    color: #c2410c;

}


.request-status-Accepted {

    background: #ecfdf5;

    color: #047857;

}


.request-status-Completed {

    background: #eff6ff;

    color: #1d4ed8;

}


.request-status-Rejected {

    background: #fff1f2;

    color: #b91c1c;

}


/* =====================================================
EMPTY
===================================================== */

.empty {

    text-align: center;

    padding: 35px !important;

    color: #9ca3af;

}


.empty i {

    font-size: 25px;

    display: block;

    margin-bottom: 8px;

}


/* =====================================================
RESPONSIVE
===================================================== */

@media(max-width:1000px) {

    .stats {

        grid-template-columns:
            repeat(2, 1fr);

    }


    .content-grid {

        grid-template-columns: 1fr;

    }


    .history-card {

        grid-column: auto;

    }

}


@media(max-width:800px) {

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
    .menu a span {

        display: none;

    }


    .menu a {

        justify-content: center;

    }


    .main {

        margin-left: 70px;

        padding: 20px;

    }

}


@media(max-width:600px) {

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

        overflow-x: auto;

        gap: 4px;

        margin-top: 10px;

    }


    .menu-title {

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


    .main {

        margin-left: 0;

        padding: 18px 12px;

    }


    .donor-hero {

        align-items: flex-start;

        flex-direction: column;

    }


    .blood-badge-large {

        align-self: flex-start;

    }


    .actions {

        flex-wrap: wrap;

    }


    .stats {

        grid-template-columns: 1fr 1fr;

    }


    .admin-profile {

        display: none;

    }

}


@media(max-width:400px) {

    .stats {

        grid-template-columns: 1fr;

    }


    .donor-info {

        align-items: flex-start;

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


<a href="index.php">

<i class="bi bi-grid-1x2-fill"></i>

<span>
Dashboard
</span>

</a>


<a
href="donors.php"
class="active">

<i class="bi bi-person-heart"></i>

<span>
Donors
</span>

</a>


<a href="users.php">

<i class="bi bi-people-fill"></i>

<span>
Users
</span>

</a>


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
style="margin-top:20px;">

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


</aside>



<!-- =====================================================
MAIN
===================================================== -->

<main class="main">


<!-- TOPBAR -->

<div class="topbar">


<a
href="donors.php"
class="back-link">

<i class="bi bi-arrow-left"></i>

&nbsp;

Back to Donors

</a>


<div class="admin-profile">


<div class="admin-avatar">

<?php

echo htmlspecialchars(
    $admin_initial
);

?>

</div>


<div>

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



<!-- =====================================================
DONOR HERO
===================================================== -->

<div class="donor-hero">


<div class="donor-info">


<div class="donor-avatar">

<?php

echo htmlspecialchars(
    $donor_initial
);

?>

</div>


<div>


<h1 class="donor-name">

<?php

echo htmlspecialchars(
    $donor["full_name"]
);

?>

</h1>


<div class="donor-email">

<i class="bi bi-envelope"></i>

&nbsp;

<?php

echo htmlspecialchars(
    $donor["email"]
);

?>

</div>


<?php if (
    $donor["availability"]
    === "Available"
): ?>


<div class="
donor-status
status-active">

<span>●</span>

Available to Donate

</div>


<?php else: ?>


<div class="
donor-status
status-inactive">

<span>●</span>

Currently Unavailable

</div>


<?php endif; ?>


</div>


</div>


<div class="blood-badge-large">

<i class="bi bi-droplet-fill"></i>

<?php

echo htmlspecialchars(
    $donor["blood_group"]
);

?>

</div>


</div>



<!-- =====================================================
ACTIONS
===================================================== -->

<div class="actions">


<a
href="donors.php"
class="action back">

<i class="bi bi-arrow-left"></i>

&nbsp;

Back

</a>


<a
href="donors.php?toggle=<?php
echo $donor_id;
?>"
class="action toggle"

onclick="
return confirm(
'Change donor availability?'
);
">


<?php

if (
    $donor["availability"]
    === "Available"
):

?>

<i class="bi bi-person-x-fill"></i>

&nbsp;

Mark Unavailable

<?php else: ?>

<i class="bi bi-person-check-fill"></i>

&nbsp;

Mark Available

<?php endif; ?>


</a>


<a
href="donors.php?delete=<?php
echo $donor_id;
?>"
class="action delete"

onclick="
return confirm(
'Are you sure you want to permanently delete this donor?'
);
">


<i class="bi bi-trash3-fill"></i>

&nbsp;

Delete Donor

</a>


</div>



<!-- =====================================================
STATISTICS
===================================================== -->

<div class="stats">


<div class="stat-card">


<div class="stat-label">

Total Requests

</div>


<div class="stat-number">

<?php

echo $total_requests;

?>

</div>


</div>



<div class="stat-card">


<div class="stat-label">

Accepted

</div>


<div
class="stat-number"
style="color:#059669;">

<?php

echo $accepted_requests;

?>

</div>


</div>



<div class="stat-card">


<div class="stat-label">

Pending

</div>


<div
class="stat-number"
style="color:#c2410c;">

<?php

echo $pending_requests;

?>

</div>


</div>



<div class="stat-card">


<div class="stat-label">

Rejected

</div>


<div
class="stat-number"
style="color:#dc2626;">

<?php

echo $rejected_requests;

?>

</div>


</div>


</div>



<!-- =====================================================
CONTENT GRID
===================================================== -->

<div class="content-grid">


<!-- =================================================
PERSONAL INFORMATION
================================================= -->

<div class="card">


<div class="card-title">

<i class="bi bi-person-vcard"></i>

&nbsp;

Personal Information

</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-person-fill"></i>

</div>


<div>

<div class="detail-label">
Full Name
</div>


<div class="detail-value">

<?php

echo htmlspecialchars(
    $donor["full_name"]
);

?>

</div>

</div>


</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-envelope-fill"></i>

</div>


<div>

<div class="detail-label">
Email
</div>


<div class="detail-value">

<?php

echo htmlspecialchars(
    $donor["email"]
);

?>

</div>

</div>


</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-telephone-fill"></i>

</div>


<div>

<div class="detail-label">
Phone
</div>


<div class="detail-value">

<?php

echo htmlspecialchars(
    $donor["phone"]
);

?>

</div>

</div>


</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-droplet-fill"></i>

</div>


<div>

<div class="detail-label">
Blood Group
</div>


<div class="detail-value">

<?php

echo htmlspecialchars(
    $donor["blood_group"]
);

?>

</div>

</div>


</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-calendar-check-fill"></i>

</div>


<div>

<div class="detail-label">
Registered On
</div>


<div class="detail-value">

<?php

echo date(
    "d M Y, h:i A",
    strtotime(
        $donor["donor_created_at"]
    )
);

?>

</div>

</div>


</div>


</div>



<!-- =================================================
LOCATION
================================================= -->

<div class="card">


<div class="card-title">

<i class="bi bi-geo-alt-fill"></i>

&nbsp;

Location Information

</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-building"></i>

</div>


<div>

<div class="detail-label">
City
</div>


<div class="detail-value">

<?php

echo htmlspecialchars(
    $donor["city"]
);

?>

</div>

</div>


</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-geo"></i>

</div>


<div>

<div class="detail-label">
District
</div>


<div class="detail-value">

<?php

echo htmlspecialchars(
    $donor["district"]
);

?>

</div>

</div>


</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-map-fill"></i>

</div>


<div>

<div class="detail-label">
State
</div>


<div class="detail-value">

<?php

echo htmlspecialchars(
    $donor["state"]
);

?>

</div>

</div>


</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-mailbox"></i>

</div>


<div>

<div class="detail-label">
Pincode
</div>


<div class="detail-value">

<?php

echo htmlspecialchars(
    $donor["pincode"]
);

?>

</div>

</div>


</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-circle-fill"></i>

</div>


<div>

<div class="detail-label">
Availability
</div>


<div class="detail-value">


<?php

echo htmlspecialchars(
    $donor["availability"]
);

?>


</div>

</div>


</div>


</div>



<!-- =================================================
REQUEST HISTORY
================================================= -->

<div class="
card
history-card">


<div class="card-title">

<i class="bi bi-clock-history"></i>

&nbsp;

Blood Request History

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
Donor Response
</th>

<th>
Request Status
</th>

<th>
Date
</th>

</tr>

</thead>


<tbody>


<?php if (
    mysqli_num_rows(
        $history_result
    ) > 0
): ?>


<?php while (
    $history =
    mysqli_fetch_assoc(
        $history_result
    )
): ?>


<tr>


<td>

<strong>

<?php

echo htmlspecialchars(
    $history["patient_name"]
);

?>

</strong>

<br>

<span
style="
color:#9ca3af;
font-size:8px;
">

<?php

echo htmlspecialchars(
    $history["units_required"]
);

?>

unit(s)

</span>

</td>



<td>

<span class="blood-badge">

<?php

echo htmlspecialchars(
    $history["blood_group"]
);

?>

</span>

</td>



<td>

<?php

echo htmlspecialchars(
    $history["hospital_name"]
);

?>

</td>



<td>

<?php

echo htmlspecialchars(
    $history["urgency"]
);

?>

</td>



<td>


<?php

$response_class =
    strtolower(
        $history["donor_response"]
    );

?>


<span class="
response
response-<?php
echo $response_class;
?>">

<?php

echo htmlspecialchars(
    $history["donor_response"]
);

?>

</span>


</td>



<td>


<span class="
request-status
request-status-<?php
echo htmlspecialchars(
    $history["status"]
);
?>">

<?php

echo htmlspecialchars(
    $history["status"]
);

?>

</span>


</td>



<td>

<?php

echo date(
    "d M Y",
    strtotime(
        $history["created_at"]
    )
);

?>

</td>


</tr>


<?php endwhile; ?>


<?php else: ?>


<tr>

<td
colspan="7"
class="empty">


<i class="bi bi-inbox"></i>

No blood request history found.

</td>

</tr>


<?php endif; ?>


</tbody>


</table>


</div>


</div>


</div>


</main>


</body>

</html>

<?php

mysqli_stmt_close(
    $history_stmt
);

?>