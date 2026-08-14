<?php

session_start();

require_once "../config/database.php";


// =====================================================
// CHECK LOGIN
// =====================================================

if (!isset($_SESSION["user_id"])) {

    header("Location: ../login.php");
    exit();

}


// =====================================================
// CHECK DONOR ROLE
// =====================================================

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "donor"
) {

    header("Location: ../login.php");
    exit();

}


$user_id = $_SESSION["user_id"];


// =====================================================
// GET DONOR INFORMATION
// =====================================================

$donor_sql = "
    SELECT
        u.full_name,
        d.blood_group,
        d.city,
        d.district,
        d.state,
        d.availability

    FROM users u

    INNER JOIN donor_profiles d
        ON u.id = d.user_id

    WHERE u.id = ?

    LIMIT 1
";


$donor_stmt = mysqli_prepare(
    $conn,
    $donor_sql
);

mysqli_stmt_bind_param(
    $donor_stmt,
    "i",
    $user_id
);

mysqli_stmt_execute(
    $donor_stmt
);

$donor_result =
    mysqli_stmt_get_result(
        $donor_stmt
    );


if (
    mysqli_num_rows($donor_result) !== 1
) {

    die("Donor profile not found.");

}


$donor = mysqli_fetch_assoc(
    $donor_result
);


$donor_name =
    $donor["full_name"];

$donor_blood_group =
    $donor["blood_group"];

$donor_city =
    $donor["city"];

$donor_district =
    $donor["district"];

$donor_state =
    $donor["state"];

$donor_availability =
    $donor["availability"];


// =====================================================
// FILTER
// =====================================================

$filter_urgency =
    isset($_GET["urgency"])
    ? $_GET["urgency"]
    : "";

$filter_city =
    isset($_GET["city"])
    ? trim($_GET["city"])
    : "";


// =====================================================
// GET BLOOD REQUESTS
// =====================================================
//
// For the first version we show requests having the
// same blood group as the donor.
//
// Later we can implement full blood-group compatibility.
// =====================================================


$sql = "
    SELECT
        br.id,
        br.patient_name,
        br.blood_group,
        br.units_required,
        br.hospital_name,
        br.hospital_address,
        br.city,
        br.district,
        br.state,
        br.pincode,
        br.contact_phone,
        br.urgency,
        br.required_date,
        br.additional_message,
        br.status,
        br.created_at,

        u.full_name AS requester_name

    FROM blood_requests br

    INNER JOIN users u
        ON br.requester_id = u.id

    WHERE br.blood_group = ?
    AND br.status = 'Pending'
    AND br.required_date >= CURDATE()
";


$params = [
    $donor_blood_group
];

$types = "s";


// =====================================================
// URGENCY FILTER
// =====================================================

if (
    !empty($filter_urgency) &&
    in_array(
        $filter_urgency,
        [
            "Normal",
            "Urgent",
            "Emergency"
        ]
    )
) {

    $sql .= "
        AND br.urgency = ?
    ";

    $types .= "s";

    $params[] =
        $filter_urgency;
}


// =====================================================
// CITY FILTER
// =====================================================

if (!empty($filter_city)) {

    $sql .= "
        AND br.city LIKE ?
    ";

    $types .= "s";

    $params[] =
        "%" . $filter_city . "%";
}


// =====================================================
// ORDER
// =====================================================

$sql .= "
    ORDER BY
        CASE br.urgency
            WHEN 'Emergency' THEN 1
            WHEN 'Urgent' THEN 2
            WHEN 'Normal' THEN 3
        END,
        br.required_date ASC,
        br.created_at DESC
";


// =====================================================
// PREPARE REQUEST
// =====================================================

$stmt = mysqli_prepare(
    $conn,
    $sql
);


// =====================================================
// DYNAMIC BIND PARAM
// =====================================================

$bind_params = [];

$bind_params[] = $types;

foreach ($params as $key => $value) {

    $bind_params[] = &$params[$key];

}

call_user_func_array(
    [$stmt, "bind_param"],
    $bind_params
);


// =====================================================
// EXECUTE
// =====================================================

mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


// =====================================================
// COUNT REQUESTS
// =====================================================

$request_count =
    mysqli_num_rows($result);

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Blood Requests | BloodConnect
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

    background: #f8f9fb;

    color: #1f2937;

}


:root {

    --primary: #e63946;

    --dark-red: #b91c1c;

    --light-red: #fff1f2;

    --dark: #111827;

    --gray: #6b7280;

}


/* =====================================================
SIDEBAR
===================================================== */

.sidebar {

    width: 250px;

    height: 100vh;

    position: fixed;

    left: 0;

    top: 0;

    background: #111827;

    color: white;

    padding: 25px 15px;

    z-index: 1000;

}


.logo {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 0 10px;

    margin-bottom: 35px;

    text-decoration: none;

    color: white;

}


.logo-icon {

    width: 40px;

    height: 40px;

    background: var(--primary);

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

}


.logo-text {

    font-size: 21px;

    font-weight: 800;

}


.menu-title {

    color: #6b7280;

    font-size: 11px;

    font-weight: 700;

    text-transform: uppercase;

    padding: 0 12px;

    margin-bottom: 10px;

}


.sidebar-menu {

    list-style: none;

    padding: 0;

    margin: 0;

}


.sidebar-menu li {

    margin-bottom: 5px;

}


.sidebar-menu a {

    display: flex;

    align-items: center;

    gap: 12px;

    color: #d1d5db;

    text-decoration: none;

    padding: 12px;

    border-radius: 10px;

    font-size: 14px;

    transition: 0.2s;

}


.sidebar-menu a:hover,
.sidebar-menu a.active {

    background: #dc2626;

    color: white;

}


.sidebar-menu i {

    font-size: 18px;

}


.logout-link {

    position: absolute;

    bottom: 25px;

    left: 15px;

    right: 15px;

}


/* =====================================================
MAIN
===================================================== */

.main {

    margin-left: 250px;

    min-height: 100vh;

}


/* =====================================================
TOPBAR
===================================================== */

.topbar {

    height: 75px;

    background: white;

    border-bottom: 1px solid #eee;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 35px;

}


.page-title {

    font-size: 20px;

    font-weight: 700;

}


.top-user {

    display: flex;

    align-items: center;

    gap: 12px;

}


.user-avatar {

    width: 40px;

    height: 40px;

    border-radius: 50%;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: 700;

}


/* =====================================================
CONTENT
===================================================== */

.content {

    padding: 35px;

}


/* =====================================================
PAGE HEADER
===================================================== */

.page-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;

}


.page-header h2 {

    font-size: 28px;

    font-weight: 800;

    margin-bottom: 5px;

}


.page-header p {

    color: var(--gray);

    margin: 0;

    font-size: 14px;

}


/* =====================================================
DONOR INFO
===================================================== */

.donor-info {

    background: white;

    border: 1px solid #eee;

    border-radius: 15px;

    padding: 18px 22px;

    margin-bottom: 25px;

    display: flex;

    align-items: center;

    gap: 15px;

}


.blood-icon {

    width: 50px;

    height: 50px;

    border-radius: 14px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

}


.donor-info strong {

    font-size: 15px;

}


.donor-info small {

    display: block;

    color: var(--gray);

    margin-top: 3px;

}


.availability {

    margin-left: auto;

    padding: 7px 12px;

    border-radius: 50px;

    font-size: 12px;

    font-weight: 700;

}


.available {

    background: #ecfdf5;

    color: #047857;

}


.not-available {

    background: #f3f4f6;

    color: #6b7280;

}


/* =====================================================
FILTER
===================================================== */

.filter-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 15px;

    padding: 20px;

    margin-bottom: 25px;

}


.form-label {

    font-size: 12px;

    font-weight: 700;

    margin-bottom: 7px;

}


.form-control,
.form-select {

    border: 1px solid #e5e7eb;

    border-radius: 9px;

    padding: 11px 12px;

    font-size: 13px;

}


.form-control:focus,
.form-select:focus {

    border-color: var(--primary);

    box-shadow:
        0 0 0 3px rgba(230,57,70,0.1);

}


.filter-btn {

    background: var(--primary);

    color: white;

    border: none;

    border-radius: 9px;

    padding: 11px 20px;

    font-weight: 700;

    font-size: 13px;

}


.filter-btn:hover {

    background: var(--dark-red);

}


.clear-btn {

    background: #f3f4f6;

    color: #374151;

    border: none;

    border-radius: 9px;

    padding: 11px 18px;

    font-size: 13px;

    font-weight: 600;

    text-decoration: none;

}


/* =====================================================
REQUEST CARD
===================================================== */

.request-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 18px;

    padding: 24px;

    margin-bottom: 18px;

    transition: 0.25s;

}


.request-card:hover {

    transform: translateY(-3px);

    box-shadow:
        0 12px 30px rgba(0,0,0,0.06);

}


/* =====================================================
REQUEST HEADER
===================================================== */

.request-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 20px;

}


.patient {

    display: flex;

    align-items: center;

    gap: 13px;

}


.patient-icon {

    width: 48px;

    height: 48px;

    border-radius: 13px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;

}


.patient h5 {

    margin: 0;

    font-size: 16px;

    font-weight: 800;

}


.patient small {

    color: var(--gray);

}


/* =====================================================
URGENCY
===================================================== */

.urgency {

    padding: 7px 12px;

    border-radius: 50px;

    font-size: 11px;

    font-weight: 800;

}


.urgency-emergency {

    background: #fee2e2;

    color: #b91c1c;

}


.urgency-urgent {

    background: #ffedd5;

    color: #c2410c;

}


.urgency-normal {

    background: #ecfdf5;

    color: #047857;

}


/* =====================================================
REQUEST DETAILS
===================================================== */

.details-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 12px;

    margin-bottom: 20px;

}


.detail {

    background: #fafafa;

    border-radius: 10px;

    padding: 13px;

}


.detail i {

    color: var(--primary);

    margin-right: 5px;

}


.detail small {

    display: block;

    color: #9ca3af;

    font-size: 10px;

    margin-bottom: 4px;

}


.detail strong {

    font-size: 13px;

}


/* =====================================================
LOCATION
===================================================== */

.location {

    background: #fafafa;

    border-radius: 12px;

    padding: 14px;

    margin-bottom: 18px;

    font-size: 13px;

}


.location i {

    color: var(--primary);

    margin-right: 7px;

}


.location small {

    color: var(--gray);

}


/* =====================================================
MESSAGE
===================================================== */

.request-message {

    border-left: 3px solid #fecaca;

    padding-left: 12px;

    color: #6b7280;

    font-size: 13px;

    line-height: 1.6;

    margin-bottom: 20px;

}


/* =====================================================
BUTTONS
===================================================== */

.request-actions {

    display: flex;

    gap: 10px;

}


.respond-btn {

    background: var(--primary);

    color: white;

    border: none;

    border-radius: 9px;

    padding: 10px 18px;

    font-size: 13px;

    font-weight: 700;

    text-decoration: none;

}


.respond-btn:hover {

    background: var(--dark-red);

    color: white;

}


.call-btn {

    background: #ecfdf5;

    color: #047857;

    border: none;

    border-radius: 9px;

    padding: 10px 18px;

    font-size: 13px;

    font-weight: 700;

    text-decoration: none;

}


.call-btn:hover {

    background: #d1fae5;

    color: #047857;

}


/* =====================================================
EMPTY STATE
===================================================== */

.empty-state {

    background: white;

    border: 1px solid #eee;

    border-radius: 18px;

    padding: 70px 30px;

    text-align: center;

}


.empty-icon {

    width: 75px;

    height: 75px;

    border-radius: 20px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    margin: auto auto 20px;

    font-size: 30px;

}


.empty-state h4 {

    font-weight: 800;

}


.empty-state p {

    color: var(--gray);

    max-width: 500px;

    margin: auto;

}


/* =====================================================
RESPONSIVE
===================================================== */

@media(max-width: 991px) {

    .sidebar {

        width: 70px;

        padding: 20px 10px;

    }


    .logo-text,
    .menu-title,
    .sidebar-menu span {

        display: none;

    }


    .logo {

        justify-content: center;

        padding: 0;

    }


    .sidebar-menu a {

        justify-content: center;

    }


    .logout-link {

        left: 10px;

        right: 10px;

    }


    .main {

        margin-left: 70px;

    }


    .details-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }

}


@media(max-width: 576px) {

    .sidebar {

        display: none;

    }


    .main {

        margin-left: 0;

    }


    .topbar {

        padding: 0 18px;

    }


    .content {

        padding: 20px 15px;

    }


    .page-header {

        display: block;

    }


    .page-header h2 {

        font-size: 24px;

    }


    .donor-info {

        flex-wrap: wrap;

    }


    .availability {

        margin-left: 0;

    }


    .details-grid {

        grid-template-columns: 1fr;

    }


    .request-header {

        align-items: flex-start;

    }


    .request-actions {

        flex-direction: column;

    }


    .respond-btn,
    .call-btn {

        text-align: center;

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
href="dashboard.php"
class="logo">

<div class="logo-icon">

<i class="bi bi-heart-pulse-fill"></i>

</div>

<span class="logo-text">

BloodConnect

</span>

</a>


<div class="menu-title">

Main Menu

</div>


<ul class="sidebar-menu">


<li>

<a href="dashboard.php">

<i class="bi bi-grid-1x2-fill"></i>

<span>
Dashboard
</span>

</a>

</li>


<li>

<a href="profile.php">

<i class="bi bi-person-fill"></i>

<span>
My Profile
</span>

</a>

</li>


<li>

<a
href="blood-requests.php"
class="active">

<i class="bi bi-droplet-fill"></i>

<span>
Blood Requests
</span>

</a>

</li>


<li>

<a href="donation-history.php">

<i class="bi bi-clock-history"></i>

<span>
Donation History
</span>

</a>

</li>


<li>

<a href="notifications.php">

<i class="bi bi-bell-fill"></i>

<span>
Notifications
</span>

</a>

</li>


<!-- <li>

<a href="settings.php">

<i class="bi bi-gear-fill"></i>

<span>
Settings
</span>

</a>

</li> -->


</ul>


<div class="logout-link">

<a
href="../logout.php"
style="
display:flex;
align-items:center;
gap:12px;
color:#d1d5db;
text-decoration:none;
padding:12px;
border-radius:10px;
font-size:14px;
">

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


<!-- =====================================================
TOPBAR
===================================================== -->

<header class="topbar">


<div class="page-title">

Blood Requests

</div>


<div class="top-user">


<div class="user-avatar">

<?php

echo strtoupper(
    substr(
        $donor_name,
        0,
        1
    )
);

?>

</div>


<div class="d-none d-md-block">

<div
style="
font-size:13px;
font-weight:700;
">

<?php

echo htmlspecialchars(
    $donor_name
);

?>

</div>


<div
style="
font-size:11px;
color:#9ca3af;
">

Blood Donor

</div>

</div>


</div>

</header>



<!-- =====================================================
CONTENT
===================================================== -->

<div class="content">


<!-- =====================================================
PAGE HEADER
===================================================== -->

<div class="page-header">


<div>

<h2>

Blood Requests

</h2>


<p>

Find people who need
<?php

echo htmlspecialchars(
    $donor_blood_group
);

?>
blood.

</p>

</div>


<div>

<span
style="
background:#fff1f2;
color:#e63946;
padding:9px 14px;
border-radius:50px;
font-size:12px;
font-weight:700;
">

<i class="bi bi-droplet-fill"></i>

Your Blood Group:

<?php

echo htmlspecialchars(
    $donor_blood_group
);

?>

</span>

</div>


</div>



<!-- =====================================================
DONOR STATUS
===================================================== -->

<div class="donor-info">


<div class="blood-icon">

<i class="bi bi-droplet-fill"></i>

</div>


<div>

<strong>

You are registered as a
<?php

echo htmlspecialchars(
    $donor_blood_group
);

?>
donor.

</strong>


<small>

<i class="bi bi-geo-alt"></i>

<?php

echo htmlspecialchars(
    $donor_city .
    ", " .
    $donor_district
);

?>

</small>

</div>


<div class="availability
<?php

echo $donor_availability === "Available"
    ? "available"
    : "not-available";

?>">

<?php

echo htmlspecialchars(
    $donor_availability
);

?>

</div>


</div>



<!-- =====================================================
FILTER
===================================================== -->

<div class="filter-card">


<form
method="GET"
action="blood-requests.php">


<div class="row g-3 align-items-end">


<!-- URGENCY -->

<div class="col-md-4">

<label class="form-label">

Urgency

</label>


<select
name="urgency"
class="form-select">

<option value="">
All Requests
</option>


<option
value="Emergency"
<?php

echo $filter_urgency === "Emergency"
    ? "selected"
    : "";

?>>

Emergency

</option>


<option
value="Urgent"
<?php

echo $filter_urgency === "Urgent"
    ? "selected"
    : "";

?>>

Urgent

</option>


<option
value="Normal"
<?php

echo $filter_urgency === "Normal"
    ? "selected"
    : "";

?>>

Normal

</option>


</select>

</div>



<!-- CITY -->

<div class="col-md-4">

<label class="form-label">

City

</label>


<input
type="text"
name="city"
class="form-control"
placeholder="Search by city"
value="<?php

echo htmlspecialchars(
    $filter_city
);

?>">

</div>



<!-- BUTTON -->

<div class="col-md-4">


<button
type="submit"
class="filter-btn">

<i class="bi bi-funnel-fill"></i>

&nbsp;

Apply Filter

</button>


<a
href="blood-requests.php"
class="clear-btn">

Clear

</a>


</div>


</div>

</form>


</div>



<!-- =====================================================
REQUEST COUNT
===================================================== -->

<div
style="
font-size:14px;
font-weight:700;
margin-bottom:15px;
">

<?php

echo $request_count;

?>

Request<?php

echo $request_count != 1
    ? "s"
    : "";

?>

Found

</div>



<!-- =====================================================
REQUEST LIST
===================================================== -->

<?php if ($request_count > 0): ?>


<?php while ($request = mysqli_fetch_assoc($result)): ?>


<?php

// Urgency class

if (
    $request["urgency"] ===
    "Emergency"
) {

    $urgency_class =
        "urgency-emergency";

}

elseif (
    $request["urgency"] ===
    "Urgent"
) {

    $urgency_class =
        "urgency-urgent";

}

else {

    $urgency_class =
        "urgency-normal";

}


// Required date

$required_date =
    date(
        "d M Y",
        strtotime(
            $request["required_date"]
        )
    );


// Request date

$request_date =
    date(
        "d M Y",
        strtotime(
            $request["created_at"]
        )
    );

?>


<!-- =================================================
REQUEST CARD
================================================= -->

<div class="request-card">


<!-- HEADER -->

<div class="request-header">


<div class="patient">


<div class="patient-icon">

<i class="bi bi-person-heart"></i>

</div>


<div>

<h5>

<?php

echo htmlspecialchars(
    $request["patient_name"]
);

?>

</h5>


<small>

Requested by
<?php

echo htmlspecialchars(
    $request["requester_name"]
);

?>

</small>

</div>


</div>


<div class="
urgency
<?php

echo $urgency_class;

?>">

<?php

echo htmlspecialchars(
    $request["urgency"]
);

?>

</div>


</div>



<!-- DETAILS -->

<div class="details-grid">


<!-- Blood Group -->

<div class="detail">

<small>

Blood Group

</small>

<strong>

<i class="bi bi-droplet-fill"></i>

<?php

echo htmlspecialchars(
    $request["blood_group"]
);

?>

</strong>

</div>



<!-- Units -->

<div class="detail">

<small>

Units Required

</small>

<strong>

<i class="bi bi-heart-pulse"></i>

<?php

echo htmlspecialchars(
    $request["units_required"]
);

?>

 Unit<?php

echo $request["units_required"] > 1
    ? "s"
    : "";

?>

</strong>

</div>



<!-- Required Date -->

<div class="detail">

<small>

Required Date

</small>

<strong>

<i class="bi bi-calendar-event"></i>

<?php

echo $required_date;

?>

</strong>

</div>



<!-- Posted -->

<div class="detail">

<small>

Request Posted

</small>

<strong>

<i class="bi bi-clock"></i>

<?php

echo $request_date;

?>

</strong>

</div>


</div>



<!-- HOSPITAL -->

<div class="location">

<i class="bi bi-hospital-fill"></i>

<strong>

<?php

echo htmlspecialchars(
    $request["hospital_name"]
);

?>

</strong>


<br>


<small>

<?php

echo htmlspecialchars(
    $request["hospital_address"]
);

?>

<br>

<?php

echo htmlspecialchars(
    $request["city"] .
    ", " .
    $request["district"] .
    ", " .
    $request["state"] .
    " - " .
    $request["pincode"]
);

?>

</small>

</div>



<!-- MESSAGE -->

<?php if (
    !empty(
        $request["additional_message"]
    )
): ?>

<div class="request-message">

<strong>
Additional Information:
</strong>

<br>

<?php

echo nl2br(
    htmlspecialchars(
        $request["additional_message"]
    )
);

?>

</div>

<?php endif; ?>



<!-- ACTIONS -->

<div class="request-actions">


<a
href="respond-request.php?id=<?php
echo $request["id"];
?>"
class="respond-btn">

<i class="bi bi-hand-thumbs-up-fill"></i>

&nbsp;

I Can Donate

</a>


<a
href="tel:<?php
echo htmlspecialchars(
    $request["contact_phone"]
);
?>"
class="call-btn">

<i class="bi bi-telephone-fill"></i>

&nbsp;

Contact Requester

</a>


</div>


</div>


<?php endwhile; ?>


<?php else: ?>


<!-- =================================================
EMPTY STATE
================================================= -->

<div class="empty-state">


<div class="empty-icon">

<i class="bi bi-search"></i>

</div>


<h4>

No Blood Requests Found

</h4>


<p>

There are currently no pending
<?php

echo htmlspecialchars(
    $donor_blood_group
);

?>
blood requests matching your filters.

Please check again later.

</p>


<a
href="blood-requests.php"
class="btn btn-danger mt-4">

View All Matching Requests

</a>


</div>


<?php endif; ?>


</div>

</main>



<!-- Bootstrap -->

<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>