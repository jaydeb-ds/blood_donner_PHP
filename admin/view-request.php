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
// GET REQUEST ID
// =====================================================

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {
    header("Location: blood-requests.php");
    exit();
}

$request_id = (int) $_GET["id"];


// =====================================================
// GET REQUEST DETAILS
// =====================================================

$sql = "
    SELECT
        id,
        requester_name,
        patient_name,
        blood_group,
        units_required,
        hospital_name,
        urgency,
        status,
        required_date,
        created_at
    FROM blood_requests
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $request_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) !== 1) {

    mysqli_stmt_close($stmt);

    die("Blood request not found.");

}

$request = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// =====================================================
// GET DONORS WHO RESPONDED
// =====================================================

$donor_sql = "
    SELECT

        brd.id AS response_id,

        brd.status AS response_status,

        brd.created_at AS response_date,

        u.id AS donor_id,

        u.full_name,

        u.email,

        u.phone,

        d.blood_group,

        d.city,

        d.district,

        d.state,

        d.availability

    FROM blood_request_donors brd

    INNER JOIN users u
        ON brd.donor_id = u.id

    LEFT JOIN donor_profiles d
        ON u.id = d.user_id

    WHERE brd.request_id = ?

    ORDER BY brd.created_at DESC
";

$donor_stmt = mysqli_prepare(
    $conn,
    $donor_sql
);

mysqli_stmt_bind_param(
    $donor_stmt,
    "i",
    $request_id
);

mysqli_stmt_execute($donor_stmt);

$donor_result =
    mysqli_stmt_get_result(
        $donor_stmt
    );


// =====================================================
// DONOR RESPONSE COUNTS
// =====================================================

function getResponseCount(
    $conn,
    $request_id,
    $status = null
) {

    if ($status === null) {

        $sql = "
            SELECT COUNT(*) AS total
            FROM blood_request_donors
            WHERE request_id = ?
        ";

        $stmt = mysqli_prepare(
            $conn,
            $sql
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $request_id
        );

    } else {

        $sql = "
            SELECT COUNT(*) AS total
            FROM blood_request_donors
            WHERE request_id = ?
            AND status = ?
        ";

        $stmt = mysqli_prepare(
            $conn,
            $sql
        );

        mysqli_stmt_bind_param(
            $stmt,
            "is",
            $request_id,
            $status
        );
    }

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $row =
        mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return (int)$row["total"];
}


$total_responses =
    getResponseCount(
        $conn,
        $request_id
    );


$accepted_responses =
    getResponseCount(
        $conn,
        $request_id,
        "Accepted"
    );


$pending_responses =
    getResponseCount(
        $conn,
        $request_id,
        "Pending"
    );


$rejected_responses =
    getResponseCount(
        $conn,
        $request_id,
        "Rejected"
    );


// =====================================================
// ADMIN INFO
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
// REQUEST STATUS CLASS
// =====================================================

$status_class =
    strtolower(
        $request["status"]
    );

$urgency_class =
    strtolower(
        $request["urgency"]
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
View Blood Request |
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

    margin-bottom: 20px;

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
REQUEST HERO
===================================================== */

.request-hero {

    background: white;

    border: 1px solid #eee;

    border-radius: 18px;

    padding: 24px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 17px;

}


.request-title {

    font-size: 22px;

    font-weight: 800;

    margin: 0;

}


.request-id {

    color: #9ca3af;

    font-size: 9px;

    margin-top: 5px;

}


.hero-right {

    display: flex;

    align-items: center;

    gap: 12px;

}


.blood-badge {

    background: #fff1f2;

    color: #e63946;

    padding: 13px 17px;

    border-radius: 13px;

    font-size: 19px;

    font-weight: 800;

}


.status {

    display: inline-block;

    padding: 7px 11px;

    border-radius: 50px;

    font-size: 8px;

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


/* =====================================================
ACTION BAR
===================================================== */

.actions {

    display: flex;

    flex-wrap: wrap;

    gap: 7px;

    margin-bottom: 17px;

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


.accept {

    background: #ecfdf5;

    color: #047857;

}


.complete {

    background: #eff6ff;

    color: #1d4ed8;

}


.reject {

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

    gap: 12px;

    margin-bottom: 17px;

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
CONTENT
===================================================== */

.content-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 17px;

}


.card {

    background: white;

    border: 1px solid #eee;

    border-radius: 16px;

    padding: 20px;

}


.card-title {

    font-size: 14px;

    font-weight: 800;

    margin-bottom: 16px;

}


/* =====================================================
DETAIL
===================================================== */

.detail {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 11px 0;

    border-bottom: 1px solid #f3f4f6;

}


.detail:last-child {

    border-bottom: none;

}


.detail-icon {

    width: 35px;

    height: 35px;

    border-radius: 9px;

    background: #fff1f2;

    color: #e63946;

    display: flex;

    align-items: center;

    justify-content: center;

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
URGENCY
===================================================== */

.urgency {

    display: inline-block;

    padding: 5px 8px;

    border-radius: 50px;

    font-size: 8px;

    font-weight: 800;

}


.urgency-critical {

    background: #fff1f2;

    color: #b91c1c;

}


.urgency-urgent {

    background: #fff7ed;

    color: #c2410c;

}


.urgency-normal {

    background: #eff6ff;

    color: #1d4ed8;

}


/* =====================================================
DONOR CARD
===================================================== */

.donor-history {

    grid-column: 1 / -1;

}


.table {

    margin: 0;

}


.table th {

    color: #9ca3af;

    font-size: 8px;

    text-transform: uppercase;

    font-weight: 800;

    padding: 11px 9px;

    border-bottom: 1px solid #eee;

    white-space: nowrap;

}


.table td {

    font-size: 9px;

    padding: 12px 9px;

    border-bottom: 1px solid #f3f4f6;

    vertical-align: middle;

}


.table tr:last-child td {

    border-bottom: none;

}


.donor-name {

    font-size: 10px;

    font-weight: 800;

}


.donor-email {

    color: #9ca3af;

    font-size: 8px;

    margin-top: 3px;

}


.response {

    display: inline-block;

    padding: 5px 8px;

    border-radius: 50px;

    font-size: 7px;

    font-weight: 800;

}


.response-accepted {

    background: #ecfdf5;

    color: #047857;

}


.response-pending {

    background: #fff7ed;

    color: #c2410c;

}


.response-rejected {

    background: #fff1f2;

    color: #b91c1c;

}


.availability {

    display: inline-block;

    padding: 5px 8px;

    border-radius: 50px;

    font-size: 7px;

    font-weight: 800;

}


.available {

    background: #ecfdf5;

    color: #047857;

}


.unavailable {

    background: #f3f4f6;

    color: #6b7280;

}


/* =====================================================
DESCRIPTION
===================================================== */

.description {

    color: #4b5563;

    font-size: 10px;

    line-height: 1.7;

    background: #f9fafb;

    padding: 14px;

    border-radius: 10px;

}


/* =====================================================
EMPTY
===================================================== */

.empty {

    text-align: center;

    padding: 40px !important;

    color: #9ca3af;

}


.empty i {

    display: block;

    font-size: 28px;

    margin-bottom: 9px;

}


/* =====================================================
RESPONSIVE
===================================================== */

@media(max-width:1000px) {

    .content-grid {

        grid-template-columns: 1fr;

    }


    .donor-history {

        grid-column: auto;

    }


    .stats {

        grid-template-columns:
            repeat(2, 1fr);

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


    .request-hero {

        align-items: flex-start;

        flex-direction: column;

    }


    .hero-right {

        width: 100%;

        justify-content: space-between;

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


<a href="donors.php">

<i class="bi bi-person-heart"></i>

<span>
Donors
</span>

</a>


<a
href="blood-requests.php"
class="active">

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


<!-- =====================================================
TOPBAR
===================================================== -->

<div class="topbar">


<a
href="blood-requests.php"
class="back-link">

<i class="bi bi-arrow-left"></i>

&nbsp;

Back to Blood Requests

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
REQUEST HERO
===================================================== -->

<div class="request-hero">


<div>


<h1 class="request-title">

Blood Request for

<?php

echo htmlspecialchars(
    $request["patient_name"]
);

?>

</h1>


<div class="request-id">

Request ID:

#<?php

echo $request_id;

?>


&nbsp; • &nbsp;


Created:

<?php

echo date(
    "d M Y, h:i A",
    strtotime(
        $request["created_at"]
    )
);

?>

</div>


</div>


<div class="hero-right">


<div class="blood-badge">

<i class="bi bi-droplet-fill"></i>

<?php

echo htmlspecialchars(
    $request["blood_group"]
);

?>

</div>


<span class="
status
status-<?php
echo $status_class;
?>">

<?php

echo htmlspecialchars(
    $request["status"]
);

?>

</span>


</div>


</div>



<!-- =====================================================
ACTIONS
===================================================== -->

<div class="actions">


<a
href="blood-requests.php"
class="action back">

<i class="bi bi-arrow-left"></i>

&nbsp;

Back

</a>


<?php if (
    $request["status"] !== "Accepted"
): ?>

<a
href="blood-requests.php?id=<?php
echo $request_id;
?>&status=Accepted"

class="action accept"

onclick="
return confirm(
'Accept this blood request?'
);
">

<i class="bi bi-check-lg"></i>

&nbsp;

Accept Request

</a>

<?php endif; ?>


<?php if (
    $request["status"] === "Accepted"
): ?>

<a
href="blood-requests.php?id=<?php
echo $request_id;
?>&status=Completed"

class="action complete"

onclick="
return confirm(
'Mark this request as completed?'
);
">

<i class="bi bi-check2-all"></i>

&nbsp;

Mark Completed

</a>

<?php endif; ?>


<?php if (
    $request["status"] !== "Rejected"
): ?>

<a
href="blood-requests.php?id=<?php
echo $request_id;
?>&status=Rejected"

class="action reject"

onclick="
return confirm(
'Reject this blood request?'
);
">

<i class="bi bi-x-lg"></i>

&nbsp;

Reject Request

</a>

<?php endif; ?>


</div>



<!-- =====================================================
RESPONSE STATISTICS
===================================================== -->

<div class="stats">


<div class="stat-card">

<div class="stat-label">

Total Donor Responses

</div>


<div class="stat-number">

<?php

echo $total_responses;

?>

</div>

</div>


<div class="stat-card">

<div class="stat-label">

Accepted Donors

</div>


<div
class="stat-number"
style="color:#059669;">

<?php

echo $accepted_responses;

?>

</div>

</div>


<div class="stat-card">

<div class="stat-label">

Pending Responses

</div>


<div
class="stat-number"
style="color:#c2410c;">

<?php

echo $pending_responses;

?>

</div>

</div>


<div class="stat-card">

<div class="stat-label">

Rejected Responses

</div>


<div
class="stat-number"
style="color:#dc2626;">

<?php

echo $rejected_responses;

?>

</div>

</div>


</div>



<!-- =====================================================
CONTENT
===================================================== -->

<div class="content-grid">


<!-- =================================================
PATIENT INFORMATION
================================================= -->

<div class="card">


<div class="card-title">

<i class="bi bi-person-vcard"></i>

&nbsp;

Patient Information

</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-person-fill"></i>

</div>


<div>

<div class="detail-label">
Patient Name
</div>


<div class="detail-value">

<?php

echo htmlspecialchars(
    $request["patient_name"]
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
    $request["blood_group"]
);

?>

</div>

</div>


</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-stack"></i>

</div>


<div>

<div class="detail-label">
Units Required
</div>


<div class="detail-value">

<?php

echo htmlspecialchars(
    $request["units_required"]
);

?>

 Unit(s)

</div>

</div>


</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-exclamation-triangle-fill"></i>

</div>


<div>

<div class="detail-label">
Urgency
</div>


<div class="detail-value">


<span class="
urgency
urgency-<?php
echo $urgency_class;
?>">

<?php

echo htmlspecialchars(
    $request["urgency"]
);

?>

</span>


</div>

</div>


</div>


</div>



<!-- =================================================
REQUEST INFORMATION
================================================= -->

<div class="card">


<div class="card-title">

<i class="bi bi-file-earmark-text-fill"></i>

&nbsp;

Request Information

</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-person-badge-fill"></i>

</div>


<div>

<div class="detail-label">
Requested By
</div>


<div class="detail-value">

<?php

echo htmlspecialchars(
    $request["requester_name"]
);

?>

</div>

</div>


</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-hospital-fill"></i>

</div>


<div>

<div class="detail-label">
Hospital
</div>


<div class="detail-value">

<?php

echo htmlspecialchars(
    $request["hospital_name"]
);

?>

</div>

</div>


</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-calendar-event-fill"></i>

</div>


<div>

<div class="detail-label">
Required Date
</div>


<div class="detail-value">

<?php

if (
    !empty(
        $request["required_date"]
    )
) {

    echo date(
        "d M Y",
        strtotime(
            $request["required_date"]
        )
    );

} else {

    echo "Not specified";

}

?>

</div>

</div>


</div>



<div class="detail">


<div class="detail-icon">

<i class="bi bi-clock-fill"></i>

</div>


<div>

<div class="detail-label">
Request Created
</div>


<div class="detail-value">

<?php

echo date(
    "d M Y, h:i A",
    strtotime(
        $request["created_at"]
    )
);

?>

</div>

</div>


</div>


</div>



<!-- =================================================
DONOR RESPONSES
================================================= -->

<div class="
card
donor-history">


<div class="card-title">

<i class="bi bi-people-fill"></i>

&nbsp;

Donors Who Responded

</div>


<div class="table-responsive">


<table class="table">


<thead>

<tr>

<th>
Donor
</th>

<th>
Blood Group
</th>

<th>
Phone
</th>

<th>
Location
</th>

<th>
Availability
</th>

<th>
Response
</th>

<th>
Date
</th>

<th>
Action
</th>

</tr>

</thead>


<tbody>


<?php if (
    mysqli_num_rows(
        $donor_result
    ) > 0
): ?>


<?php while (
    $donor =
    mysqli_fetch_assoc(
        $donor_result
    )
): ?>


<?php

$response_class =
    strtolower(
        $donor["response_status"]
    );


$availability_class =
    strtolower(
        $donor["availability"]
    );

?>


<tr>


<!-- DONOR -->

<td>


<div class="donor-name">

<?php

echo htmlspecialchars(
    $donor["full_name"]
);

?>

</div>


<div class="donor-email">

<?php

echo htmlspecialchars(
    $donor["email"]
);

?>

</div>


</td>



<!-- BLOOD -->

<td>

<span class="blood-badge">

<?php

echo htmlspecialchars(
    $donor["blood_group"]
);

?>

</span>

</td>



<!-- PHONE -->

<td>

<?php

echo htmlspecialchars(
    $donor["phone"]
);

?>

</td>



<!-- LOCATION -->

<td>

<?php

echo htmlspecialchars(
    $donor["city"]
);

?>


<br>

<span
style="
color:#9ca3af;
font-size:8px;
">

<?php

echo htmlspecialchars(
    $donor["district"]
);

?>

</span>

</td>



<!-- AVAILABILITY -->

<td>


<span class="
availability
<?php
echo $availability_class;
?>">

<?php

echo htmlspecialchars(
    $donor["availability"]
);

?>

</span>


</td>



<!-- RESPONSE -->

<td>


<span class="
response
response-<?php
echo $response_class;
?>">

<?php

echo htmlspecialchars(
    $donor["response_status"]
);

?>

</span>


</td>



<!-- DATE -->

<td>

<?php

echo date(
    "d M Y",
    strtotime(
        $donor["response_date"]
    )
);

?>

</td>



<!-- ACTION -->

<td>

<a
href="view-donor.php?id=<?php
echo (int)$donor["donor_id"];
?>"

style="
display:inline-flex;
align-items:center;
justify-content:center;
width:29px;
height:29px;
border-radius:7px;
background:#eff6ff;
color:#2563eb;
text-decoration:none;
"

title="View Donor">

<i class="bi bi-eye-fill"></i>

</a>

</td>


</tr>


<?php endwhile; ?>


<?php else: ?>


<tr>

<td
colspan="8"
class="empty">


<i class="bi bi-person-x"></i>

No donors have responded to this request yet.


</td>

</tr>


<?php endif; ?>


</tbody>

</table>


</div>


</div>


<!-- =================================================
REQUEST STATUS
================================================= -->

<div class="card">


<div class="card-title">

<i class="bi bi-info-circle-fill"></i>

&nbsp;

Current Request Status

</div>


<div
style="
text-align:center;
padding:15px 0;
">


<span class="
status
status-<?php
echo $status_class;
?>"
style="
font-size:10px;
padding:8px 14px;
">

<?php

echo htmlspecialchars(
    $request["status"]
);

?>

</span>


<p
style="
color:#9ca3af;
font-size:9px;
margin-top:12px;
margin-bottom:0;
">

Request #<?php
echo $request_id;
?>

is currently

<strong>

<?php

echo htmlspecialchars(
    $request["status"]
);

?>

</strong>

.

</p>


</div>


</div>


</div>


</main>


</body>

</html>

<?php

mysqli_stmt_close(
    $donor_stmt
);

?>