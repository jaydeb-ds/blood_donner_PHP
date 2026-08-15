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
// VARIABLES
// =====================================================

$success = "";
$error = "";


// =====================================================
// CHANGE REQUEST STATUS
// =====================================================

if (
    isset($_GET["status"]) &&
    isset($_GET["id"]) &&
    is_numeric($_GET["id"])
) {

    $request_id = (int) $_GET["id"];

    $new_status = trim(
        $_GET["status"]
    );


    $allowed_statuses = [
        "Pending",
        "Accepted",
        "Completed",
        "Rejected"
    ];


    if (
        !in_array(
            $new_status,
            $allowed_statuses,
            true
        )
    ) {

        $error =
            "Invalid request status.";

    } else {

        $sql = "
            UPDATE blood_requests
            SET status = ?
            WHERE id = ?
        ";


        $stmt = mysqli_prepare(
            $conn,
            $sql
        );


        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $new_status,
            $request_id
        );


        if (
            mysqli_stmt_execute($stmt)
        ) {

            $success =
                "Blood request status updated successfully.";

        } else {

            $error =
                "Unable to update request status.";

        }


        mysqli_stmt_close($stmt);

    }

}


// =====================================================
// DELETE REQUEST
// =====================================================

if (
    isset($_GET["delete"]) &&
    is_numeric($_GET["delete"])
) {

    $delete_id =
        (int) $_GET["delete"];


    mysqli_begin_transaction($conn);


    try {

        /*
        Delete donor responses first.

        If your database has a foreign key,
        this prevents constraint errors.
        */

        $sql = "
            DELETE FROM blood_request_donors
            WHERE request_id = ?
        ";


        $stmt = mysqli_prepare(
            $conn,
            $sql
        );


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $delete_id
        );


        mysqli_stmt_execute($stmt);


        mysqli_stmt_close($stmt);


        // Delete request

        $sql = "
            DELETE FROM blood_requests
            WHERE id = ?
        ";


        $stmt = mysqli_prepare(
            $conn,
            $sql
        );


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $delete_id
        );


        if (
            !mysqli_stmt_execute($stmt)
        ) {

            throw new Exception(
                "Unable to delete blood request."
            );

        }


        mysqli_stmt_close($stmt);


        mysqli_commit($conn);


        $success =
            "Blood request deleted successfully.";

    }
    catch (Exception $e) {

        mysqli_rollback($conn);


        $error =
            $e->getMessage();

    }

}


// =====================================================
// SEARCH & FILTER
// =====================================================

$search =
    trim(
        $_GET["search"] ?? ""
    );


$blood_filter =
    trim(
        $_GET["blood_group"] ?? ""
    );


$urgency_filter =
    trim(
        $_GET["urgency"] ?? ""
    );


$status_filter =
    trim(
        $_GET["request_status"] ?? ""
    );


// =====================================================
// MAIN QUERY
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

    WHERE 1 = 1

";


$params = [];

$types = "";


// =====================================================
// SEARCH
// =====================================================

if (
    !empty($search)
) {

    $sql .= "

        AND (

            patient_name LIKE ?

            OR requester_name LIKE ?

            OR hospital_name LIKE ?

        )

    ";


    $search_value =
        "%" . $search . "%";


    $params[] =
        $search_value;

    $params[] =
        $search_value;

    $params[] =
        $search_value;


    $types .= "sss";

}


// =====================================================
// BLOOD GROUP FILTER
// =====================================================

if (
    !empty($blood_filter)
) {

    $sql .= "
        AND blood_group = ?
    ";


    $params[] =
        $blood_filter;


    $types .= "s";

}


// =====================================================
// URGENCY FILTER
// =====================================================

if (
    !empty($urgency_filter)
) {

    $sql .= "
        AND urgency = ?
    ";


    $params[] =
        $urgency_filter;


    $types .= "s";

}


// =====================================================
// STATUS FILTER
// =====================================================

if (
    !empty($status_filter)
) {

    $sql .= "
        AND status = ?
    ";


    $params[] =
        $status_filter;


    $types .= "s";

}


// =====================================================
// ORDER
// =====================================================

$sql .= "

    ORDER BY
        created_at DESC

";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


// =====================================================
// BIND PARAMETERS
// =====================================================

if (
    !empty($params)
) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );

}


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


// =====================================================
// STATISTICS
// =====================================================

// Total

$total_requests = 0;

$count_result = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM blood_requests
    "
);


if ($count_result) {

    $row =
        mysqli_fetch_assoc(
            $count_result
        );

    $total_requests =
        (int) $row["total"];

}


// Pending

$pending_requests = 0;

$count_result = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM blood_requests
    WHERE status = 'Pending'
    "
);


if ($count_result) {

    $row =
        mysqli_fetch_assoc(
            $count_result
        );

    $pending_requests =
        (int) $row["total"];

}


// Accepted

$accepted_requests = 0;

$count_result = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM blood_requests
    WHERE status = 'Accepted'
    "
);


if ($count_result) {

    $row =
        mysqli_fetch_assoc(
            $count_result
        );

    $accepted_requests =
        (int) $row["total"];

}


// Completed

$completed_requests = 0;

$count_result = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM blood_requests
    WHERE status = 'Completed'
    "
);


if ($count_result) {

    $row =
        mysqli_fetch_assoc(
            $count_result
        );

    $completed_requests =
        (int) $row["total"];

}


// Rejected

$rejected_requests = 0;

$count_result = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM blood_requests
    WHERE status = 'Rejected'
    "
);


if ($count_result) {

    $row =
        mysqli_fetch_assoc(
            $count_result
        );

    $rejected_requests =
        (int) $row["total"];

}


// =====================================================
// ADMIN INFORMATION
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
Blood Requests | BloodConnect Admin
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

    margin-bottom: 25px;

}


.page-title h1 {

    margin: 0;

    font-size: 24px;

    font-weight: 800;

}


.page-title p {

    margin: 6px 0 0;

    color: #6b7280;

    font-size: 11px;

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
STATISTICS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(5, 1fr);

    gap: 12px;

    margin-bottom: 20px;

}


.stat-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 14px;

    padding: 15px;

}


.stat-label {

    color: #6b7280;

    font-size: 8px;

}


.stat-number {

    font-size: 22px;

    font-weight: 800;

    margin-top: 6px;

}


/* =====================================================
ALERT
===================================================== */

.alert-box {

    padding: 12px 15px;

    border-radius: 10px;

    margin-bottom: 18px;

    font-size: 11px;

}


.alert-success {

    background: #ecfdf5;

    color: #047857;

    border: 1px solid #a7f3d0;

}


.alert-error {

    background: #fff1f2;

    color: #be123c;

    border: 1px solid #fecdd3;

}


/* =====================================================
FILTER
===================================================== */

.filter-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 16px;

    padding: 17px;

    margin-bottom: 17px;

}


.filter-title {

    font-size: 12px;

    font-weight: 800;

    margin-bottom: 13px;

}


.form-label {

    font-size: 9px;

    font-weight: 700;

    margin-bottom: 6px;

}


.form-control,
.form-select {

    border: 1px solid #e5e7eb;

    border-radius: 8px;

    padding: 9px 11px;

    font-size: 10px;

}


.form-control:focus,
.form-select:focus {

    border-color: #e63946;

    box-shadow:
        0 0 0 3px
        rgba(230,57,70,.08);

}


.filter-btn {

    border: none;

    background: #e63946;

    color: white;

    border-radius: 8px;

    padding: 9px 17px;

    font-size: 10px;

    font-weight: 700;

}


.reset-btn {

    display: inline-block;

    background: #f3f4f6;

    color: #374151;

    text-decoration: none;

    border-radius: 8px;

    padding: 9px 15px;

    font-size: 10px;

    font-weight: 700;

}


/* =====================================================
TABLE
===================================================== */

.table-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 17px;

    padding: 20px;

}


.table-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 17px;

}


.table-header h3 {

    font-size: 14px;

    font-weight: 800;

    margin: 0;

}


.result-count {

    color: #9ca3af;

    font-size: 9px;

    margin-top: 3px;

}


.table {

    margin: 0;

}


.table th {

    color: #9ca3af;

    font-size: 8px;

    text-transform: uppercase;

    font-weight: 800;

    border-bottom: 1px solid #eee;

    padding: 11px 9px;

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


/* =====================================================
PATIENT
===================================================== */

.patient-name {

    font-size: 10px;

    font-weight: 800;

}


.requester {

    color: #9ca3af;

    font-size: 8px;

    margin-top: 3px;

}


/* =====================================================
BLOOD BADGE
===================================================== */

.blood-badge {

    display: inline-flex;

    align-items: center;

    gap: 4px;

    background: #fff1f2;

    color: #e63946;

    padding: 5px 8px;

    border-radius: 7px;

    font-size: 9px;

    font-weight: 800;

}


/* =====================================================
URGENCY
===================================================== */

.urgency {

    display: inline-block;

    padding: 5px 8px;

    border-radius: 50px;

    font-size: 7px;

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
STATUS
===================================================== */

.status {

    display: inline-block;

    padding: 5px 8px;

    border-radius: 50px;

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


/* =====================================================
ACTION BUTTONS
===================================================== */

.action-group {

    display: flex;

    align-items: center;

    gap: 5px;

}


.action-btn {

    width: 29px;

    height: 29px;

    border-radius: 7px;

    display: flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    font-size: 11px;

    border: none;

}


.view-btn {

    background: #eff6ff;

    color: #2563eb;

}


.accept-btn {

    background: #ecfdf5;

    color: #059669;

}


.complete-btn {

    background: #eff6ff;

    color: #2563eb;

}


.reject-btn {

    background: #fff1f2;

    color: #dc2626;

}


.delete-btn {

    background: #fef2f2;

    color: #991b1b;

}


.action-btn:hover {

    opacity: .75;

}


/* =====================================================
EMPTY
===================================================== */

.empty {

    text-align: center;

    padding: 50px 20px !important;

    color: #9ca3af;

}


.empty i {

    display: block;

    font-size: 30px;

    margin-bottom: 10px;

}


/* =====================================================
RESPONSIVE
===================================================== */

@media(max-width:1200px) {

    .stats {

        grid-template-columns:
            repeat(3, 1fr);

    }

}


@media(max-width:900px) {

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


    .admin-profile {

        display: none;

    }


    .stats {

        grid-template-columns: 1fr 1fr;

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


<!-- TOPBAR -->

<div class="topbar">


<div class="page-title">

<h1>
Blood Requests
</h1>

<p>
Manage and monitor all blood donation requests.
</p>

</div>


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
ALERTS
===================================================== -->

<?php if (
    !empty($success)
): ?>

<div class="
alert-box
alert-success">

<i class="bi bi-check-circle-fill"></i>

&nbsp;

<?php

echo htmlspecialchars(
    $success
);

?>

</div>

<?php endif; ?>


<?php if (
    !empty($error)
): ?>

<div class="
alert-box
alert-error">

<i class="bi bi-exclamation-circle-fill"></i>

&nbsp;

<?php

echo htmlspecialchars(
    $error
);

?>

</div>

<?php endif; ?>



<!-- =====================================================
STATISTICS
===================================================== -->

<div class="stats">


<div class="stat-card">

<div class="stat-label">
Total Requests
</div>

<div class="stat-number">
<?php echo $total_requests; ?>
</div>

</div>


<div class="stat-card">

<div class="stat-label">
Pending
</div>

<div
class="stat-number"
style="color:#c2410c;">

<?php echo $pending_requests; ?>

</div>

</div>


<div class="stat-card">

<div class="stat-label">
Accepted
</div>

<div
class="stat-number"
style="color:#059669;">

<?php echo $accepted_requests; ?>

</div>

</div>


<div class="stat-card">

<div class="stat-label">
Completed
</div>

<div
class="stat-number"
style="color:#2563eb;">

<?php echo $completed_requests; ?>

</div>

</div>


<div class="stat-card">

<div class="stat-label">
Rejected
</div>

<div
class="stat-number"
style="color:#dc2626;">

<?php echo $rejected_requests; ?>

</div>

</div>


</div>



<!-- =====================================================
FILTER
===================================================== -->

<div class="filter-card">


<div class="filter-title">

<i class="bi bi-funnel-fill"></i>

&nbsp;

Search & Filter Requests

</div>


<form
method="GET"
action="blood-requests.php">


<div class="row g-2 align-items-end">


<!-- SEARCH -->

<div class="col-lg-4 col-md-6">

<label class="form-label">
Search
</label>

<input
type="text"
name="search"
class="form-control"
placeholder="Patient, requester or hospital..."
value="<?php

echo htmlspecialchars(
    $search
);

?>">

</div>



<!-- BLOOD GROUP -->

<div class="col-lg-2 col-md-6">

<label class="form-label">
Blood Group
</label>

<select
name="blood_group"
class="form-select">

<option value="">
All Groups
</option>


<?php

$blood_groups = [

    "A+",
    "A-",
    "B+",
    "B-",
    "O+",
    "O-",
    "AB+",
    "AB-"

];

?>


<?php foreach (
    $blood_groups
    as $group
): ?>

<option
value="<?php
echo $group;
?>"

<?php

echo $blood_filter === $group
    ? "selected"
    : "";

?>>

<?php
echo $group;
?>

</option>

<?php endforeach; ?>


</select>

</div>



<!-- URGENCY -->

<div class="col-lg-2 col-md-4">

<label class="form-label">
Urgency
</label>

<select
name="urgency"
class="form-select">

<option value="">
All
</option>


<option
value="Critical"

<?php

echo $urgency_filter === "Critical"
    ? "selected"
    : "";

?>>

Critical

</option>


<option
value="Urgent"

<?php

echo $urgency_filter === "Urgent"
    ? "selected"
    : "";

?>>

Urgent

</option>


<option
value="Normal"

<?php

echo $urgency_filter === "Normal"
    ? "selected"
    : "";

?>>

Normal

</option>


</select>

</div>



<!-- STATUS -->

<div class="col-lg-2 col-md-4">

<label class="form-label">
Status
</label>

<select
name="request_status"
class="form-select">

<option value="">
All Status
</option>


<option
value="Pending"

<?php

echo $status_filter === "Pending"
    ? "selected"
    : "";

?>>

Pending

</option>


<option
value="Accepted"

<?php

echo $status_filter === "Accepted"
    ? "selected"
    : "";

?>>

Accepted

</option>


<option
value="Completed"

<?php

echo $status_filter === "Completed"
    ? "selected"
    : "";

?>>

Completed

</option>


<option
value="Rejected"

<?php

echo $status_filter === "Rejected"
    ? "selected"
    : "";

?>>

Rejected

</option>


</select>

</div>



<!-- BUTTON -->

<div class="col-lg-2 col-md-4">

<button
type="submit"
class="filter-btn">

<i class="bi bi-search"></i>

Search

</button>


<a
href="blood-requests.php"
class="reset-btn ms-1">

Reset

</a>

</div>


</div>

</form>

</div>



<!-- =====================================================
REQUEST TABLE
===================================================== -->

<div class="table-card">


<div class="table-header">


<div>

<h3>
All Blood Requests
</h3>


<div class="result-count">

Latest requests are shown first.

</div>

</div>


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
Units
</th>

<th>
Hospital
</th>

<th>
Urgency
</th>

<th>
Required Date
</th>

<th>
Status
</th>

<th>
Actions
</th>

</tr>

</thead>


<tbody>


<?php if (
    mysqli_num_rows($result) > 0
): ?>


<?php while (
    $request =
    mysqli_fetch_assoc($result)
): ?>


<?php

$urgency_class =
    strtolower(
        $request["urgency"]
    );


$status_class =
    strtolower(
        $request["status"]
    );

?>


<tr>


<!-- PATIENT -->

<td>


<div class="patient-name">

<?php

echo htmlspecialchars(
    $request["patient_name"]
);

?>

</div>


<div class="requester">

Requested by:

<?php

echo htmlspecialchars(
    $request["requester_name"]
);

?>

</div>


</td>



<!-- BLOOD -->

<td>

<span class="blood-badge">

<i class="bi bi-droplet-fill"></i>

<?php

echo htmlspecialchars(
    $request["blood_group"]
);

?>

</span>

</td>



<!-- UNITS -->

<td>

<strong>

<?php

echo htmlspecialchars(
    $request["units_required"]
);

?>

</strong>

<span
style="
color:#9ca3af;
font-size:8px;
">

unit(s)

</span>

</td>



<!-- HOSPITAL -->

<td>

<?php

echo htmlspecialchars(
    $request["hospital_name"]
);

?>

</td>



<!-- URGENCY -->

<td>

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

</td>



<!-- REQUIRED DATE -->

<td>

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

</td>



<!-- STATUS -->

<td>

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

</td>



<!-- ACTIONS -->

<td>


<div class="action-group">


<!-- VIEW -->

<a
href="view-request.php?id=<?php
echo (int)$request["id"];
?>"

class="
action-btn
view-btn"

title="View Request">

<i class="bi bi-eye-fill"></i>

</a>



<!-- ACCEPT -->

<?php if (
    $request["status"]
    !== "Accepted"
): ?>

<a
href="blood-requests.php?id=<?php
echo (int)$request["id"];
?>&status=Accepted"

class="
action-btn
accept-btn"

title="Accept Request"

onclick="
return confirm(
'Accept this blood request?'
);
">

<i class="bi bi-check-lg"></i>

</a>

<?php endif; ?>



<!-- COMPLETE -->

<?php if (
    $request["status"]
    === "Accepted"
): ?>

<a
href="blood-requests.php?id=<?php
echo (int)$request["id"];
?>&status=Completed"

class="
action-btn
complete-btn"

title="Mark Completed"

onclick="
return confirm(
'Mark this request as completed?'
);
">

<i class="bi bi-check2-all"></i>

</a>

<?php endif; ?>



<!-- REJECT -->

<?php if (
    $request["status"]
    !== "Rejected"
): ?>

<a
href="blood-requests.php?id=<?php
echo (int)$request["id"];
?>&status=Rejected"

class="
action-btn
reject-btn"

title="Reject Request"

onclick="
return confirm(
'Reject this blood request?'
);
">

<i class="bi bi-x-lg"></i>

</a>

<?php endif; ?>



<!-- DELETE -->

<a
href="blood-requests.php?delete=<?php
echo (int)$request["id"];
?>"

class="
action-btn
delete-btn"

title="Delete Request"

onclick="
return confirm(
'Are you sure you want to permanently delete this blood request?'
);
">

<i class="bi bi-trash3-fill"></i>

</a>


</div>


</td>


</tr>


<?php endwhile; ?>


<?php else: ?>


<tr>

<td
colspan="8"
class="empty">

<i class="bi bi-droplet-half"></i>

No blood requests found.

</td>

</tr>


<?php endif; ?>


</tbody>


</table>


</div>


</div>


</main>


</body>

</html>

<?php

mysqli_stmt_close($stmt);

?>