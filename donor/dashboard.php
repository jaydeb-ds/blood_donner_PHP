<?php

session_start();

require_once "../config/database.php";


// ========================================
// CHECK LOGIN
// ========================================

if (!isset($_SESSION["user_id"])) {

    header("Location: ../login.php");

    exit();

}


// ========================================
// CHECK DONOR ROLE
// ========================================

if ($_SESSION["role"] !== "donor") {

    header("Location: ../login.php");

    exit();

}


$user_id = $_SESSION["user_id"];


// ========================================
// GET DONOR INFORMATION
// ========================================

$sql = "
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.phone,
        u.status,

        d.blood_group,
        d.date_of_birth,
        d.gender,
        d.weight,
        d.address,
        d.city,
        d.district,
        d.state,
        d.pincode,
        d.availability,
        d.last_donation_date,
        d.profile_image

    FROM users u

    INNER JOIN donor_profiles d
        ON u.id = d.user_id

    WHERE u.id = ?

    LIMIT 1
";


$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


if (mysqli_num_rows($result) !== 1) {

    die("Donor profile not found.");

}


$donor = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// ========================================
// DONOR VARIABLES
// ========================================

$full_name = $donor["full_name"];

$email = $donor["email"];

$phone = $donor["phone"];

$blood_group = $donor["blood_group"];

$availability = $donor["availability"];

$city = $donor["city"];

$district = $donor["district"];

$state = $donor["state"];

$last_donation = $donor["last_donation_date"];

$profile_image = $donor["profile_image"];


// ========================================
// PROFILE COMPLETION
// ========================================

$profile_fields = [

    $full_name,
    $email,
    $phone,
    $blood_group,
    $donor["date_of_birth"],
    $donor["gender"],
    $donor["weight"],
    $donor["address"],
    $city,
    $district,
    $state,
    $donor["pincode"]

];


$completed_fields = 0;

$total_fields = count($profile_fields);


foreach ($profile_fields as $field) {

    if (!empty($field)) {

        $completed_fields++;

    }

}


$profile_completion =
    round(
        ($completed_fields / $total_fields) * 100
    );


// ========================================
// AVAILABILITY BADGE
// ========================================

if ($availability === "Available") {

    $availability_class = "available";

} else {

    $availability_class = "unavailable";

}


// ========================================
// LAST DONATION
// ========================================

if (!empty($last_donation)) {

    $last_donation_display =
        date(
            "d M Y",
            strtotime($last_donation)
        );

} else {

    $last_donation_display =
        "No donation recorded";

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Donor Dashboard | BloodConnect
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

/* ========================================
GLOBAL
======================================== */

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


/* ========================================
SIDEBAR
======================================== */

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


/* Logout */

.logout-link {

    position: absolute;

    bottom: 25px;

    left: 15px;

    right: 15px;

}


/* ========================================
MAIN
======================================== */

.main {

    margin-left: 250px;

    min-height: 100vh;

}


/* ========================================
TOPBAR
======================================== */

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


/* ========================================
CONTENT
======================================== */

.content {

    padding: 35px;

}


/* ========================================
WELCOME
======================================== */

.welcome-card {

    background:

        linear-gradient(
            135deg,
            #991b1b,
            #e63946
        );

    color: white;

    border-radius: 20px;

    padding: 30px;

    position: relative;

    overflow: hidden;

    margin-bottom: 25px;

}


.welcome-card::after {

    content: "";

    width: 250px;

    height: 250px;

    border-radius: 50%;

    border: 45px solid rgba(255,255,255,0.06);

    position: absolute;

    right: -80px;

    top: -80px;

}


.welcome-card h2 {

    font-size: 28px;

    font-weight: 800;

    margin-bottom: 8px;

}


.welcome-card p {

    color: #fecaca;

    margin: 0;

}


/* ========================================
STAT CARDS
======================================== */

.stat-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 16px;

    padding: 22px;

    height: 100%;

    display: flex;

    align-items: center;

    gap: 15px;

    transition: 0.3s;

}


.stat-card:hover {

    transform: translateY(-4px);

    box-shadow:
        0 10px 25px rgba(0,0,0,0.06);

}


.stat-icon {

    min-width: 52px;

    height: 52px;

    border-radius: 14px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;

}


.stat-card h4 {

    font-size: 20px;

    font-weight: 800;

    margin: 0;

}


.stat-card p {

    color: var(--gray);

    font-size: 12px;

    margin: 4px 0 0;

}


/* ========================================
SECTION CARD
======================================== */

.dashboard-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 18px;

    padding: 25px;

    height: 100%;

}


.card-header-custom {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 25px;

}


.card-header-custom h5 {

    font-weight: 800;

    margin: 0;

}


.view-link {

    color: var(--primary);

    font-size: 13px;

    font-weight: 600;

    text-decoration: none;

}


/* ========================================
BLOOD GROUP CARD
======================================== */

.blood-display {

    text-align: center;

    padding: 25px;

    background: var(--light-red);

    border-radius: 15px;

}


.blood-group {

    width: 90px;

    height: 90px;

    border-radius: 25px;

    background: var(--primary);

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: auto;

    font-size: 28px;

    font-weight: 800;

    box-shadow:
        0 10px 25px rgba(230,57,70,0.2);

}


.blood-display h5 {

    margin-top: 15px;

    font-weight: 800;

}


.blood-display p {

    color: var(--gray);

    font-size: 13px;

    margin-bottom: 0;

}


/* ========================================
AVAILABILITY
======================================== */

.availability {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 7px 12px;

    border-radius: 50px;

    font-size: 12px;

    font-weight: 700;

}


.availability::before {

    content: "";

    width: 7px;

    height: 7px;

    border-radius: 50%;

}


.availability.available {

    background: #ecfdf5;

    color: #047857;

}


.availability.available::before {

    background: #10b981;

}


.availability.unavailable {

    background: #f3f4f6;

    color: #6b7280;

}


.availability.unavailable::before {

    background: #9ca3af;

}


/* ========================================
PROFILE
======================================== */

.profile-item {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 12px 0;

    border-bottom: 1px solid #f1f1f1;

}


.profile-item:last-child {

    border-bottom: none;

}


.profile-item i {

    width: 35px;

    height: 35px;

    background: var(--light-red);

    color: var(--primary);

    border-radius: 9px;

    display: flex;

    align-items: center;

    justify-content: center;

}


.profile-item small {

    display: block;

    color: #9ca3af;

    font-size: 11px;

}


.profile-item span {

    font-size: 13px;

    font-weight: 600;

}


/* ========================================
PROFILE COMPLETION
======================================== */

.progress {

    height: 8px;

    border-radius: 20px;

    background: #f1f1f1;

}


.progress-bar {

    background: var(--primary);

    border-radius: 20px;

}


/* ========================================
QUICK ACTIONS
======================================== */

.action-btn {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 15px;

    border: 1px solid #eee;

    border-radius: 12px;

    color: #374151;

    text-decoration: none;

    margin-bottom: 10px;

    transition: 0.2s;

}


.action-btn:hover {

    border-color: #fecaca;

    background: var(--light-red);

    color: var(--primary);

}


.action-btn i {

    font-size: 20px;

    color: var(--primary);

}


/* ========================================
RESPONSIVE
======================================== */

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


    .welcome-card {

        padding: 25px 20px;

    }


    .welcome-card h2 {

        font-size: 23px;

    }

}

</style>

</head>


<body>


<!-- ========================================
SIDEBAR
======================================== -->

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

<a
href="dashboard.php"
class="active">

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


<li>

<a href="search.php" >
<i class="bi bi-search"></i>
<span>
Find Donors
</span>
</a>
</li>


</ul>


<div class="logout-link">

<a
href="../logout.php"
class="sidebar-menu a">

</a>


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



<!-- ========================================
MAIN
======================================== -->

<main class="main">


<!-- ========================================
TOPBAR
======================================== -->

<header class="topbar">


<div class="page-title">

Donor Dashboard

</div>


<div class="top-user">

<div class="user-avatar">

<?php

echo strtoupper(
    substr($full_name, 0, 1)
);

?>

</div>


<div class="d-none d-md-block">

<div
style="
font-size:13px;
font-weight:700;
">

<?php echo htmlspecialchars($full_name); ?>

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



<!-- ========================================
CONTENT
======================================== -->

<div class="content">


<!-- ========================================
WELCOME
======================================== -->

<div class="welcome-card">


<h2>

Welcome back,
<?php

echo htmlspecialchars($full_name);

?>

👋

</h2>


<p>

Thank you for being a blood donor.
Your contribution can help save lives.

</p>


</div>



<!-- ========================================
STATISTICS
======================================== -->

<div class="row g-3 mb-4">


<!-- Blood Group -->

<div class="col-sm-6 col-xl-3">

<div class="stat-card">

<div class="stat-icon">

<i class="bi bi-droplet-fill"></i>

</div>


<div>

<h4>

<?php

echo htmlspecialchars(
    $blood_group
);

?>

</h4>

<p>
Blood Group
</p>

</div>

</div>

</div>



<!-- Availability -->

<div class="col-sm-6 col-xl-3">

<div class="stat-card">

<div class="stat-icon">

<i class="bi bi-heart-pulse-fill"></i>

</div>


<div>

<h4>

<?php

echo htmlspecialchars(
    $availability
);

?>

</h4>

<p>
Availability
</p>

</div>

</div>

</div>



<!-- Location -->

<div class="col-sm-6 col-xl-3">

<div class="stat-card">

<div class="stat-icon">

<i class="bi bi-geo-alt-fill"></i>

</div>


<div>

<h4>

<?php

echo htmlspecialchars(
    $city
);

?>

</h4>

<p>
Current City
</p>

</div>

</div>

</div>



<!-- Last Donation -->

<div class="col-sm-6 col-xl-3">

<div class="stat-card">

<div class="stat-icon">

<i class="bi bi-calendar-heart"></i>

</div>


<div>

<h4 style="font-size:15px;">

<?php

echo htmlspecialchars(
    $last_donation_display
);

?>

</h4>

<p>
Last Donation
</p>

</div>

</div>

</div>


</div>



<!-- ========================================
MAIN DASHBOARD ROW
======================================== -->

<div class="row g-4">


<!-- ========================================
BLOOD CARD
======================================== -->

<div class="col-lg-4">

<div class="dashboard-card">


<div class="card-header-custom">

<h5>
Your Donor Status
</h5>

<span
class="availability <?php echo $availability_class; ?>">

<?php

echo htmlspecialchars(
    $availability
);

?>

</span>

</div>


<div class="blood-display">


<div class="blood-group">

<?php

echo htmlspecialchars(
    $blood_group
);

?>

</div>


<h5>

<?php

echo htmlspecialchars(
    $blood_group
);

?>

Blood Donor

</h5>


<p>

Your blood group is registered
with BloodConnect.

</p>

</div>


</div>

</div>



<!-- ========================================
PROFILE INFORMATION
======================================== -->

<div class="col-lg-8">

<div class="dashboard-card">


<div class="card-header-custom">

<h5>
Profile Information
</h5>


<a
href="profile.php"
class="view-link">

Edit Profile

<i class="bi bi-arrow-right"></i>

</a>

</div>



<div class="row">


<div class="col-md-6">


<div class="profile-item">

<i class="bi bi-person"></i>

<div>

<small>
Full Name
</small>

<span>

<?php

echo htmlspecialchars(
    $full_name
);

?>

</span>

</div>

</div>



<div class="profile-item">

<i class="bi bi-envelope"></i>

<div>

<small>
Email
</small>

<span>

<?php

echo htmlspecialchars(
    $email
);

?>

</span>

</div>

</div>



<div class="profile-item">

<i class="bi bi-telephone"></i>

<div>

<small>
Phone
</small>

<span>

<?php

echo htmlspecialchars(
    $phone
);

?>

</span>

</div>

</div>


</div>


<div class="col-md-6">


<div class="profile-item">

<i class="bi bi-geo-alt"></i>

<div>

<small>
Location
</small>

<span>

<?php

echo htmlspecialchars(
    $city . ", " . $district
);

?>

</span>

</div>

</div>



<div class="profile-item">

<i class="bi bi-map"></i>

<div>

<small>
State
</small>

<span>

<?php

echo htmlspecialchars(
    $state
);

?>

</span>

</div>

</div>



<div class="profile-item">

<i class="bi bi-calendar-heart"></i>

<div>

<small>
Last Donation
</small>

<span>

<?php

echo htmlspecialchars(
    $last_donation_display
);

?>

</span>

</div>

</div>


</div>


</div>


</div>

</div>



<!-- ========================================
BOTTOM ROW
======================================== -->

<div class="row g-4 mt-1">


<!-- PROFILE COMPLETION -->

<div class="col-lg-6">

<div class="dashboard-card">


<div class="card-header-custom">

<h5>
Profile Completion
</h5>

<strong style="color:#e63946;">

<?php

echo $profile_completion;

?>%

</strong>

</div>


<div class="progress mb-3">

<div
class="progress-bar"
style="
width: <?php echo $profile_completion; ?>%;
">

</div>

</div>


<p
style="
font-size:13px;
color:#6b7280;
margin:0;
">

Complete your profile to help recipients
find the information they need.

</p>


<?php if ($profile_completion < 100): ?>

<a
href="profile.php"
class="btn btn-sm btn-danger mt-3">

Complete Profile

</a>

<?php endif; ?>


</div>

</div>



<!-- QUICK ACTIONS -->

<div class="col-lg-6">

<div class="dashboard-card">


<div class="card-header-custom">

<h5>
Quick Actions
</h5>

</div>


<a
href="profile.php"
class="action-btn">

<i class="bi bi-person-fill"></i>

<span>
Update My Profile
</span>

</a>


<a
href="blood-requests.php"
class="action-btn">

<i class="bi bi-droplet-fill"></i>

<span>
View Blood Requests
</span>

</a>


<a
href="donation-history.php"
class="action-btn">

<i class="bi bi-clock-history"></i>

<span>
View Donation History
</span>

</a>


</div>

</div>


</div>


</div>

</main>



<!-- Bootstrap -->

<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>