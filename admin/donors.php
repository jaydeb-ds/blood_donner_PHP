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
// MESSAGE VARIABLES
// =====================================================

$success = "";
$error = "";


// =====================================================
// DELETE DONOR
// =====================================================

if (
    isset($_GET["delete"]) &&
    is_numeric($_GET["delete"])
) {

    $delete_id = (int) $_GET["delete"];


    mysqli_begin_transaction($conn);

    try {

        // Delete donor profile

        $delete_profile_sql = "
            DELETE FROM donor_profiles
            WHERE user_id = ?
        ";

        $stmt = mysqli_prepare(
            $conn,
            $delete_profile_sql
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $delete_id
        );

        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(
                "Unable to delete donor profile."
            );

        }

        mysqli_stmt_close($stmt);


        // Delete user account

        $delete_user_sql = "
            DELETE FROM users
            WHERE id = ?
        ";

        $stmt = mysqli_prepare(
            $conn,
            $delete_user_sql
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $delete_id
        );

        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(
                "Unable to delete donor account."
            );

        }

        mysqli_stmt_close($stmt);


        mysqli_commit($conn);

        $success =
            "Donor has been deleted successfully.";

    }
    catch (Exception $e) {

        mysqli_rollback($conn);

        $error =
            $e->getMessage();

    }

}


// =====================================================
// CHANGE DONOR AVAILABILITY
// =====================================================

if (
    isset($_GET["toggle"]) &&
    is_numeric($_GET["toggle"])
) {

    $toggle_id = (int) $_GET["toggle"];


    // Get current availability

    $check_sql = "
        SELECT availability
        FROM donor_profiles
        WHERE user_id = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare(
        $conn,
        $check_sql
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $toggle_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $donor = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);


    if ($donor) {

        $new_status =
            $donor["availability"] === "Available"
            ? "Unavailable"
            : "Available";


        $update_sql = "
            UPDATE donor_profiles
            SET availability = ?
            WHERE user_id = ?
        ";

        $stmt = mysqli_prepare(
            $conn,
            $update_sql
        );

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $new_status,
            $toggle_id
        );

        if (
            mysqli_stmt_execute($stmt)
        ) {

            $success =
                "Donor availability updated.";

        }
        else {

            $error =
                "Unable to update donor availability.";

        }

        mysqli_stmt_close($stmt);

    }

}


// =====================================================
// SEARCH AND FILTER
// =====================================================

$search =
    trim(
        $_GET["search"] ?? ""
    );


$blood_filter =
    trim(
        $_GET["blood_group"] ?? ""
    );


$availability_filter =
    trim(
        $_GET["availability"] ?? ""
    );


// =====================================================
// BUILD QUERY
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

        d.created_at

    FROM users u

    INNER JOIN donor_profiles d
        ON u.id = d.user_id

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
            u.full_name LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
            OR d.city LIKE ?
            OR d.district LIKE ?
        )

    ";


    $search_value =
        "%" . $search . "%";


    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;


    $types .= "sssss";

}


// =====================================================
// BLOOD GROUP FILTER
// =====================================================

if (
    !empty($blood_filter)
) {

    $sql .= "
        AND d.blood_group = ?
    ";

    $params[] =
        $blood_filter;

    $types .= "s";

}


// =====================================================
// AVAILABILITY FILTER
// =====================================================

if (
    !empty($availability_filter)
) {

    $sql .= "
        AND d.availability = ?
    ";

    $params[] =
        $availability_filter;

    $types .= "s";

}


// =====================================================
// ORDER
// =====================================================

$sql .= "

    ORDER BY
        d.created_at DESC

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
// TOTAL DONORS
// =====================================================

$total_donors = 0;

$count_result = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM donor_profiles
    "
);

if ($count_result) {

    $count_row =
        mysqli_fetch_assoc(
            $count_result
        );

    $total_donors =
        (int)$count_row["total"];

}


// Available donors

$available_donors = 0;

$count_result = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM donor_profiles
    WHERE availability = 'Available'
    "
);

if ($count_result) {

    $count_row =
        mysqli_fetch_assoc(
            $count_result
        );

    $available_donors =
        (int)$count_row["total"];

}


// Unavailable donors

$unavailable_donors =
    max(
        0,
        $total_donors -
        $available_donors
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

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Donor Management | BloodConnect Admin
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
STAT CARDS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 13px;

    margin-bottom: 20px;

}


.stat-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 14px;

    padding: 17px;

}


.stat-content {

    display: flex;

    justify-content: space-between;

    align-items: center;

}


.stat-label {

    color: #6b7280;

    font-size: 9px;

}


.stat-number {

    font-size: 23px;

    font-weight: 800;

    margin-top: 5px;

}


.stat-icon {

    width: 40px;

    height: 40px;

    border-radius: 11px;

    background: #fff1f2;

    color: #e63946;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 16px;

}


/* =====================================================
ALERTS
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
FILTER CARD
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


.filter-btn:hover {

    background: #b91c1c;

}


.reset-btn {

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
DONOR
===================================================== */

.donor-name {

    font-size: 10px;

    font-weight: 800;

}


.donor-email {

    color: #9ca3af;

    font-size: 8px;

    margin-top: 3px;

}


.blood-badge {

    display: inline-flex;

    align-items: center;

    gap: 3px;

    padding: 5px 8px;

    background: #fff1f2;

    color: #e63946;

    border-radius: 7px;

    font-size: 9px;

    font-weight: 800;

}


.availability {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 5px 8px;

    border-radius: 50px;

    font-size: 8px;

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


.dot {

    width: 5px;

    height: 5px;

    border-radius: 50%;

    background: currentColor;

}


/* =====================================================
ACTION BUTTONS
===================================================== */

.action-group {

    display: flex;

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

    border: none;

    font-size: 11px;

}


.view-btn {

    background: #eff6ff;

    color: #2563eb;

}


.toggle-btn {

    background: #ecfdf5;

    color: #059669;

}


.delete-btn {

    background: #fff1f2;

    color: #dc2626;

}


.action-btn:hover {

    opacity: .75;

}


/* =====================================================
EMPTY
===================================================== */

.empty {

    text-align: center;

    padding: 45px 20px !important;

    color: #9ca3af;

}


.empty i {

    font-size: 30px;

    display: block;

    margin-bottom: 10px;

}


/* =====================================================
RESPONSIVE
===================================================== */

@media(max-width:1000px) {

    .stats {

        grid-template-columns:
            1fr 1fr;

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

        margin-top: 10px;

        gap: 4px;

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


    .stats {

        grid-template-columns: 1fr;

    }


    .admin-profile {

        display: none;

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
Donor Management
</h1>

<p>
View and manage all registered blood donors.
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
ALERT
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


<div class="stat-content">


<div>

<div class="stat-label">

Total Donors

</div>


<div class="stat-number">

<?php

echo $total_donors;

?>

</div>

</div>


<div class="stat-icon">

<i class="bi bi-people-fill"></i>

</div>


</div>


</div>



<div class="stat-card">


<div class="stat-content">


<div>

<div class="stat-label">

Available Donors

</div>


<div class="stat-number"
style="color:#059669;">

<?php

echo $available_donors;

?>

</div>

</div>


<div class="stat-icon"
style="
background:#ecfdf5;
color:#059669;
">

<i class="bi bi-heart-pulse-fill"></i>

</div>


</div>


</div>



<div class="stat-card">


<div class="stat-content">


<div>

<div class="stat-label">

Unavailable Donors

</div>


<div class="stat-number"
style="color:#6b7280;">

<?php

echo $unavailable_donors;

?>

</div>

</div>


<div class="stat-icon"
style="
background:#f3f4f6;
color:#6b7280;
">

<i class="bi bi-person-x-fill"></i>

</div>


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

Search & Filter Donors

</div>


<form
method="GET"
action="donors.php">


<div class="row g-2 align-items-end">


<div class="col-lg-5 col-md-5">


<label class="form-label">

Search

</label>


<input
type="text"
name="search"
class="form-control"
placeholder="Name, phone, email, city..."
value="<?php

echo htmlspecialchars(
    $search
);

?>">


</div>



<div class="col-lg-2 col-md-2">


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

$groups = [

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
    $groups as $group
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



<div class="col-lg-2 col-md-2">


<label class="form-label">

Availability

</label>


<select
name="availability"
class="form-select">


<option value="">
All
</option>


<option
value="Available"

<?php

echo $availability_filter === "Available"
    ? "selected"
    : "";

?>>

Available

</option>


<option
value="Unavailable"

<?php

echo $availability_filter === "Unavailable"
    ? "selected"
    : "";

?>>

Unavailable

</option>


</select>


</div>



<div class="col-lg-3 col-md-3">


<button
type="submit"
class="filter-btn">

<i class="bi bi-search"></i>

Search

</button>


<a
href="donors.php"
class="reset-btn ms-1">

Reset

</a>


</div>


</div>


</form>


</div>



<!-- =====================================================
DONOR TABLE
===================================================== -->

<div class="table-card">


<div class="table-header">


<div>


<h3>

Registered Donors

</h3>


<div class="result-count">

Showing filtered donor results

</div>


</div>


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
Joined
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
    $row =
    mysqli_fetch_assoc($result)
): ?>


<tr>


<!-- DONOR -->

<td>


<div class="donor-name">

<?php

echo htmlspecialchars(
    $row["full_name"]
);

?>

</div>


<div class="donor-email">

<?php

echo htmlspecialchars(
    $row["email"]
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
    $row["blood_group"]
);

?>

</span>


</td>



<!-- PHONE -->

<td>

<?php

echo htmlspecialchars(
    $row["phone"]
);

?>

</td>



<!-- LOCATION -->

<td>


<?php

echo htmlspecialchars(
    $row["city"]
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
    $row["district"]
);

?>,

<?php

echo htmlspecialchars(
    $row["state"]
);

?>

</span>


</td>



<!-- AVAILABILITY -->

<td>


<?php

if (
    $row["availability"]
    === "Available"
):

?>


<span class="
availability
available">

<span class="dot"></span>

Available

</span>


<?php else: ?>


<span class="
availability
unavailable">

<span class="dot"></span>

Unavailable

</span>


<?php endif; ?>


</td>



<!-- JOINED -->

<td>

<?php

echo date(
    "d M Y",
    strtotime(
        $row["created_at"]
    )
);

?>

</td>



<!-- ACTIONS -->

<td>


<div class="action-group">


<!-- VIEW -->

<a
href="view-donor.php?id=<?php
echo (int)$row["user_id"];
?>"
class="
action-btn
view-btn"
title="View Donor">


<i class="bi bi-eye-fill"></i>

</a>



<!-- TOGGLE -->

<a
href="donors.php?toggle=<?php
echo (int)$row["user_id"];
?>"

class="
action-btn
toggle-btn"

title="Change Availability"

onclick="
return confirm(
'Change this donor availability?'
);
">


<?php

if (
    $row["availability"]
    === "Available"
):

?>

<i class="bi bi-person-x-fill"></i>

<?php else: ?>

<i class="bi bi-person-check-fill"></i>

<?php endif; ?>


</a>



<!-- DELETE -->

<a
href="donors.php?delete=<?php
echo (int)$row["user_id"];
?>"

class="
action-btn
delete-btn"

title="Delete Donor"

onclick="
return confirm(
'Are you sure you want to permanently delete this donor?'
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
colspan="7"
class="empty">


<i class="bi bi-person-x"></i>

No donors found.

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