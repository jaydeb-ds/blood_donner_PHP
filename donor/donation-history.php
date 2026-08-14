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


$user_id = (int) $_SESSION["user_id"];


// =====================================================
// GET DONOR INFORMATION
// =====================================================

$donor_sql = "
    SELECT
        u.full_name,
        d.blood_group,
        d.city,
        d.last_donation_date

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


$donor =
    mysqli_fetch_assoc(
        $donor_result
    );


mysqli_stmt_close(
    $donor_stmt
);


$donor_name =
    $donor["full_name"];

$blood_group =
    $donor["blood_group"];


// =====================================================
// FILTERS
// =====================================================

$status_filter =
    isset($_GET["status"])
    ? trim($_GET["status"])
    : "";


$year_filter =
    isset($_GET["year"])
    ? trim($_GET["year"])
    : "";


// Validate status

$allowed_statuses = [
    "Completed",
    "Pending",
    "Cancelled"
];


if (
    !empty($status_filter) &&
    !in_array(
        $status_filter,
        $allowed_statuses,
        true
    )
) {

    $status_filter = "";
}


// Validate year

if (
    !empty($year_filter) &&
    !preg_match(
        '/^[0-9]{4}$/',
        $year_filter
    )
) {

    $year_filter = "";
}


// =====================================================
// GET TOTAL COMPLETED DONATIONS
// =====================================================

$count_sql = "
    SELECT COUNT(*) AS total
    FROM donations
    WHERE donor_id = ?
    AND status = 'Completed'
";


$count_stmt =
    mysqli_prepare(
        $conn,
        $count_sql
    );


mysqli_stmt_bind_param(
    $count_stmt,
    "i",
    $user_id
);


mysqli_stmt_execute(
    $count_stmt
);


$count_result =
    mysqli_stmt_get_result(
        $count_stmt
    );


$count_row =
    mysqli_fetch_assoc(
        $count_result
    );


$total_donations =
    (int) $count_row["total"];


mysqli_stmt_close(
    $count_stmt
);


// =====================================================
// TOTAL UNITS DONATED
// =====================================================

$units_sql = "
    SELECT
        COALESCE(
            SUM(units_donated),
            0
        ) AS total_units

    FROM donations

    WHERE donor_id = ?
    AND status = 'Completed'
";


$units_stmt =
    mysqli_prepare(
        $conn,
        $units_sql
    );


mysqli_stmt_bind_param(
    $units_stmt,
    "i",
    $user_id
);


mysqli_stmt_execute(
    $units_stmt
);


$units_result =
    mysqli_stmt_get_result(
        $units_stmt
    );


$units_row =
    mysqli_fetch_assoc(
        $units_result
    );


$total_units =
    $units_row["total_units"];


mysqli_stmt_close(
    $units_stmt
);


// =====================================================
// GET LAST COMPLETED DONATION
// =====================================================

$last_sql = "
    SELECT donation_date

    FROM donations

    WHERE donor_id = ?
    AND status = 'Completed'

    ORDER BY donation_date DESC

    LIMIT 1
";


$last_stmt =
    mysqli_prepare(
        $conn,
        $last_sql
    );


mysqli_stmt_bind_param(
    $last_stmt,
    "i",
    $user_id
);


mysqli_stmt_execute(
    $last_stmt
);


$last_result =
    mysqli_stmt_get_result(
        $last_stmt
    );


if (
    mysqli_num_rows($last_result) === 1
) {

    $last_row =
        mysqli_fetch_assoc(
            $last_result
        );

    $last_donation =
        date(
            "d M Y",
            strtotime(
                $last_row["donation_date"]
            )
        );

} elseif (
    !empty(
        $donor["last_donation_date"]
    )
) {

    $last_donation =
        date(
            "d M Y",
            strtotime(
                $donor["last_donation_date"]
            )
        );

} else {

    $last_donation =
        "No donation yet";
}


mysqli_stmt_close(
    $last_stmt
);


// =====================================================
// GET DONATION HISTORY
// =====================================================

$sql = "
    SELECT
        id,
        request_id,
        patient_name,
        blood_group,
        hospital_name,
        hospital_address,
        donation_date,
        units_donated,
        notes,
        status,
        created_at

    FROM donations

    WHERE donor_id = ?
";


$params = [
    $user_id
];

$types = "i";


// Status filter

if (!empty($status_filter)) {

    $sql .= "
        AND status = ?
    ";

    $types .= "s";

    $params[] =
        $status_filter;
}


// Year filter

if (!empty($year_filter)) {

    $sql .= "
        AND YEAR(donation_date) = ?
    ";

    $types .= "i";

    $params[] =
        (int) $year_filter;
}


$sql .= "
    ORDER BY
        donation_date DESC,
        id DESC
";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


// =====================================================
// DYNAMIC BIND PARAM
// =====================================================

$bind_params = [];

$bind_params[] =
    $types;


foreach (
    $params as $key => $value
) {

    $bind_params[] =
        &$params[$key];
}


call_user_func_array(
    [
        $stmt,
        "bind_param"
    ],
    $bind_params
);


mysqli_stmt_execute(
    $stmt
);


$result =
    mysqli_stmt_get_result(
        $stmt
    );


$history_count =
    mysqli_num_rows(
        $result
    );


// =====================================================
// GET AVAILABLE YEARS
// =====================================================

$years_sql = "
    SELECT DISTINCT
        YEAR(donation_date) AS donation_year

    FROM donations

    WHERE donor_id = ?

    ORDER BY donation_year DESC
";


$years_stmt =
    mysqli_prepare(
        $conn,
        $years_sql
    );


mysqli_stmt_bind_param(
    $years_stmt,
    "i",
    $user_id
);


mysqli_stmt_execute(
    $years_stmt
);


$years_result =
    mysqli_stmt_get_result(
        $years_stmt
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
Donation History | BloodConnect
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

    margin-bottom: 25px;
}


.page-header h2 {

    font-size: 28px;

    font-weight: 800;

    margin-bottom: 6px;
}


.page-header p {

    color: var(--gray);

    margin: 0;

    font-size: 14px;
}


/* =====================================================
STAT CARDS
===================================================== */

.stat-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 16px;

    padding: 22px;

    height: 100%;

    display: flex;

    align-items: center;

    gap: 15px;
}


.stat-icon {

    width: 52px;

    height: 52px;

    flex-shrink: 0;

    border-radius: 14px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;
}


.stat-content h3 {

    font-size: 20px;

    font-weight: 800;

    margin: 0;
}


.stat-content p {

    color: var(--gray);

    font-size: 12px;

    margin: 4px 0 0;
}


/* =====================================================
FILTER
===================================================== */

.filter-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 16px;

    padding: 20px;

    margin: 25px 0;
}


.form-label {

    font-size: 12px;

    font-weight: 700;

    margin-bottom: 7px;
}


.form-select {

    border: 1px solid #e5e7eb;

    border-radius: 9px;

    padding: 11px 12px;

    font-size: 13px;
}


.form-select:focus {

    border-color: var(--primary);

    box-shadow:
        0 0 0 3px rgba(230,57,70,0.1);
}


.filter-btn {

    border: none;

    background: var(--primary);

    color: white;

    border-radius: 9px;

    padding: 11px 20px;

    font-size: 13px;

    font-weight: 700;
}


.filter-btn:hover {

    background: var(--dark-red);
}


.clear-btn {

    background: #f3f4f6;

    color: #374151;

    border-radius: 9px;

    padding: 11px 18px;

    font-size: 13px;

    font-weight: 600;

    text-decoration: none;
}


/* =====================================================
HISTORY CARD
===================================================== */

.history-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 18px;

    overflow: hidden;
}


.history-header {

    padding: 22px 25px;

    border-bottom: 1px solid #eee;

    display: flex;

    align-items: center;

    justify-content: space-between;
}


.history-header h5 {

    margin: 0;

    font-weight: 800;
}


.history-header span {

    color: var(--gray);

    font-size: 12px;
}


/* =====================================================
DONATION ITEM
===================================================== */

.donation-item {

    padding: 22px 25px;

    border-bottom: 1px solid #f1f1f1;

    display: flex;

    align-items: center;

    gap: 18px;
}


.donation-item:last-child {

    border-bottom: none;
}


.donation-icon {

    width: 55px;

    height: 55px;

    border-radius: 15px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 23px;

    flex-shrink: 0;
}


.donation-main {

    flex: 1;
}


.donation-main h5 {

    font-size: 15px;

    font-weight: 800;

    margin: 0 0 5px;
}


.donation-main p {

    color: var(--gray);

    font-size: 12px;

    margin: 0;
}


.donation-main p i {

    color: var(--primary);
}


/* =====================================================
DONATION DETAILS
===================================================== */

.donation-details {

    display: flex;

    gap: 25px;

    align-items: center;
}


.donation-detail {

    min-width: 90px;
}


.donation-detail small {

    display: block;

    color: #9ca3af;

    font-size: 10px;

    margin-bottom: 4px;
}


.donation-detail strong {

    font-size: 12px;
}


/* =====================================================
STATUS
===================================================== */

.status {

    display: inline-block;

    padding: 7px 11px;

    border-radius: 50px;

    font-size: 10px;

    font-weight: 800;
}


.status-completed {

    background: #ecfdf5;

    color: #047857;
}


.status-pending {

    background: #fff7ed;

    color: #c2410c;
}


.status-cancelled {

    background: #f3f4f6;

    color: #6b7280;
}


/* =====================================================
EMPTY STATE
===================================================== */

.empty-state {

    text-align: center;

    padding: 70px 25px;
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

    margin: auto auto 20px;

    font-size: 14px;
}


/* =====================================================
NOTE
===================================================== */

.note-box {

    background: #fffbeb;

    border: 1px solid #fde68a;

    color: #92400e;

    padding: 14px 17px;

    border-radius: 12px;

    font-size: 12px;

    margin-top: 20px;
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


    .donation-details {

        gap: 12px;
    }

}


@media(max-width: 768px) {

    .donation-item {

        align-items: flex-start;

        flex-wrap: wrap;
    }


    .donation-main {

        min-width: calc(100% - 80px);
    }


    .donation-details {

        width: 100%;

        padding-left: 73px;

        flex-wrap: wrap;
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


    .page-header h2 {

        font-size: 24px;
    }


    .donation-details {

        padding-left: 0;

        display: grid;

        grid-template-columns:
            repeat(2, 1fr);
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

<a href="blood-requests.php">

<i class="bi bi-droplet-fill"></i>

<span>
Blood Requests
</span>

</a>

</li>


<li>

<a
href="donation-history.php"
class="active">

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

Donation History

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

<h2>

Your Donation Journey

</h2>


<p>

View your blood donation history and
the lives you have helped.

</p>

</div>



<!-- =====================================================
STATISTICS
===================================================== -->

<div class="row g-3">


<!-- TOTAL DONATIONS -->

<div class="col-md-4">

<div class="stat-card">


<div class="stat-icon">

<i class="bi bi-heart-fill"></i>

</div>


<div class="stat-content">

<h3>

<?php

echo $total_donations;

?>

</h3>

<p>
Completed Donations
</p>

</div>


</div>

</div>



<!-- TOTAL UNITS -->

<div class="col-md-4">

<div class="stat-card">


<div class="stat-icon">

<i class="bi bi-droplet-fill"></i>

</div>


<div class="stat-content">

<h3>

<?php

echo htmlspecialchars(
    $total_units
);

?>

</h3>

<p>
Total Units Donated
</p>

</div>


</div>

</div>



<!-- LAST DONATION -->

<div class="col-md-4">

<div class="stat-card">


<div class="stat-icon">

<i class="bi bi-calendar-heart"></i>

</div>


<div class="stat-content">

<h3
style="
font-size:15px;
">

<?php

echo htmlspecialchars(
    $last_donation
);

?>

</h3>

<p>
Last Donation
</p>

</div>


</div>

</div>


</div>



<!-- =====================================================
FILTERS
===================================================== -->

<div class="filter-card">


<form
method="GET"
action="donation-history.php">


<div class="row g-3 align-items-end">


<!-- STATUS -->

<div class="col-md-4">

<label class="form-label">

Donation Status

</label>


<select
name="status"
class="form-select">


<option value="">

All Statuses

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
value="Pending"
<?php

echo $status_filter === "Pending"
    ? "selected"
    : "";

?>>

Pending

</option>


<option
value="Cancelled"
<?php

echo $status_filter === "Cancelled"
    ? "selected"
    : "";

?>>

Cancelled

</option>


</select>

</div>



<!-- YEAR -->

<div class="col-md-4">

<label class="form-label">

Year

</label>


<select
name="year"
class="form-select">


<option value="">

All Years

</option>


<?php while (
    $year =
        mysqli_fetch_assoc(
            $years_result
        )
): ?>


<?php

if (
    empty(
        $year["donation_year"]
    )
) {

    continue;
}

?>


<option
value="<?php

echo (int)
    $year["donation_year"];

?>"

<?php

echo $year_filter ==
    $year["donation_year"]
    ? "selected"
    : "";

?>>

<?php

echo (int)
    $year["donation_year"];

?>

</option>


<?php endwhile; ?>


</select>

</div>



<!-- BUTTONS -->

<div class="col-md-4">


<button
type="submit"
class="filter-btn">

<i class="bi bi-funnel-fill"></i>

Filter

</button>


<a
href="donation-history.php"
class="clear-btn">

Clear

</a>


</div>


</div>


</form>

</div>



<!-- =====================================================
HISTORY
===================================================== -->

<div class="history-card">


<div class="history-header">


<h5>

Donation History

</h5>


<span>

<?php

echo $history_count;

?>

record<?php

echo $history_count !== 1
    ? "s"
    : "";

?>

</span>


</div>



<?php if ($history_count > 0): ?>


<?php while (
    $donation =
        mysqli_fetch_assoc(
            $result
        )
): ?>


<?php

$donation_date =
    date(
        "d M Y",
        strtotime(
            $donation["donation_date"]
        )
    );


$status_class =
    "status-completed";


if (
    $donation["status"] ===
    "Pending"
) {

    $status_class =
        "status-pending";

}


if (
    $donation["status"] ===
    "Cancelled"
) {

    $status_class =
        "status-cancelled";

}

?>


<div class="donation-item">


<!-- ICON -->

<div class="donation-icon">

<i class="bi bi-droplet-fill"></i>

</div>



<!-- MAIN INFO -->

<div class="donation-main">


<h5>

<?php

echo htmlspecialchars(
    $donation["patient_name"]
);

?>

</h5>


<p>

<i class="bi bi-hospital"></i>

<?php

echo htmlspecialchars(
    $donation["hospital_name"]
);

?>

</p>


<?php if (
    !empty(
        $donation["hospital_address"]
    )
): ?>

<p>

<i class="bi bi-geo-alt"></i>

<?php

echo htmlspecialchars(
    $donation["hospital_address"]
);

?>

</p>

<?php endif; ?>


<?php if (
    !empty(
        $donation["notes"]
    )
): ?>

<p class="mt-1">

<i class="bi bi-chat-left-text"></i>

<?php

echo htmlspecialchars(
    $donation["notes"]
);

?>

</p>

<?php endif; ?>


</div>



<!-- DETAILS -->

<div class="donation-details">


<div class="donation-detail">

<small>
Blood Group
</small>

<strong>

<?php

echo htmlspecialchars(
    $donation["blood_group"]
);

?>

</strong>

</div>



<div class="donation-detail">

<small>
Units
</small>

<strong>

<?php

echo htmlspecialchars(
    $donation["units_donated"]
);

?>

</strong>

</div>



<div class="donation-detail">

<small>
Donation Date
</small>

<strong>

<?php

echo $donation_date;

?>

</strong>

</div>



<div class="donation-detail">

<small>
Status
</small>

<span
class="
status
<?php

echo $status_class;

?>
">

<?php

echo htmlspecialchars(
    $donation["status"]
);

?>

</span>

</div>


</div>


</div>


<?php endwhile; ?>


<?php else: ?>


<!-- =====================================================
EMPTY STATE
===================================================== -->

<div class="empty-state">


<div class="empty-icon">

<i class="bi bi-heart"></i>

</div>


<h4>

No Donation History Yet

</h4>


<p>

Your completed blood donations will
appear here. Browse blood requests and
help someone when you are eligible
and available to donate.

</p>


<a
href="blood-requests.php"
class="btn btn-danger">

<i class="bi bi-droplet-fill"></i>

View Blood Requests

</a>


</div>


<?php endif; ?>


</div>



<div class="note-box">

<i class="bi bi-info-circle-fill"></i>

<strong>Important:</strong>

Donation records should only be marked
as completed after the donation has
actually taken place. BloodConnect does
not replace medical screening or advice
from qualified healthcare professionals.

</div>


</div>

</main>



<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>