<?php

session_start();

require_once "./config/database.php";


// =====================================================
// GET SEARCH VALUES
// =====================================================

$blood_group =
    isset($_GET["blood_group"])
    ? trim($_GET["blood_group"])
    : "";

$city =
    isset($_GET["city"])
    ? trim($_GET["city"])
    : "";

$district =
    isset($_GET["district"])
    ? trim($_GET["district"])
    : "";

$state =
    isset($_GET["state"])
    ? trim($_GET["state"])
    : "";

$availability =
    isset($_GET["availability"])
    ? trim($_GET["availability"])
    : "";

$donor_name =
    isset($_GET["donor_name"])
    ? trim($_GET["donor_name"])
    : "";


// =====================================================
// ALLOWED VALUES
// =====================================================

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


$availability_options = [
    "Available",
    "Not Available"
];


// =====================================================
// BUILD QUERY
// =====================================================

$sql = "
    SELECT
        u.id AS user_id,
        u.full_name,
        u.email,
        u.phone,
        u.status,

        d.blood_group,
        d.city,
        d.district,
        d.state,
        d.pincode,
        d.availability,
        d.last_donation_date,
        d.latitude,
        d.longitude

    FROM users u

    INNER JOIN donor_profiles d
        ON u.id = d.user_id

    WHERE u.status = 'active'
";


$params = [];

$types = "";


// =====================================================
// BLOOD GROUP FILTER
// =====================================================

if (
    !empty($blood_group) &&
    in_array(
        $blood_group,
        $blood_groups,
        true
    )
) {

    $sql .= "
        AND d.blood_group = ?
    ";

    $types .= "s";

    $params[] =
        $blood_group;
}


// =====================================================
// CITY FILTER
// =====================================================

if (!empty($city)) {

    $sql .= "
        AND d.city LIKE ?
    ";

    $types .= "s";

    $params[] =
        "%" . $city . "%";
}


// =====================================================
// DISTRICT FILTER
// =====================================================

if (!empty($district)) {

    $sql .= "
        AND d.district LIKE ?
    ";

    $types .= "s";

    $params[] =
        "%" . $district . "%";
}


// =====================================================
// STATE FILTER
// =====================================================

if (!empty($state)) {

    $sql .= "
        AND d.state LIKE ?
    ";

    $types .= "s";

    $params[] =
        "%" . $state . "%";
}


// =====================================================
// AVAILABILITY FILTER
// =====================================================

if (
    !empty($availability) &&
    in_array(
        $availability,
        $availability_options,
        true
    )
) {

    $sql .= "
        AND d.availability = ?
    ";

    $types .= "s";

    $params[] =
        $availability;
}


// =====================================================
// DONOR NAME SEARCH
// =====================================================

if (!empty($donor_name)) {

    $sql .= "
        AND u.full_name LIKE ?
    ";

    $types .= "s";

    $params[] =
        "%" . $donor_name . "%";
}


// =====================================================
// ORDER
// =====================================================

$sql .= "
    ORDER BY
        CASE
            WHEN d.availability = 'Available'
            THEN 1
            ELSE 2
        END,

        u.full_name ASC
";


// =====================================================
// PREPARE
// =====================================================

$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


// =====================================================
// BIND PARAMETERS
// =====================================================

if (!empty($params)) {

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
}


// =====================================================
// EXECUTE
// =====================================================

mysqli_stmt_execute(
    $stmt
);


$result =
    mysqli_stmt_get_result(
        $stmt
);


$total_donors =
    mysqli_num_rows(
        $result
    );


// =====================================================
// SEARCH PERFORMED?
// =====================================================

$search_performed =
    !empty($blood_group) ||
    !empty($city) ||
    !empty($district) ||
    !empty($state) ||
    !empty($availability) ||
    !empty($donor_name);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Find Blood Donor | BloodConnect
</title>


<!-- Bootstrap -->

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">


<!-- Bootstrap Icons -->

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<!-- Google Fonts -->

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
NAVBAR
===================================================== */

.navbar {

    background: white;

    border-bottom: 1px solid #eee;

    padding: 14px 0;

}


.navbar-brand {

    display: flex;

    align-items: center;

    gap: 10px;

    font-size: 21px;

    font-weight: 800;

    color: var(--dark);

    text-decoration: none;

}


.logo-icon {

    width: 40px;

    height: 40px;

    background: var(--primary);

    color: white;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

}


.nav-link {

    font-size: 13px;

    font-weight: 600;

    color: #4b5563;

    margin-left: 15px;

}


.nav-link:hover {

    color: var(--primary);

}


.login-btn {

    background: var(--primary);

    color: white !important;

    padding: 9px 16px !important;

    border-radius: 9px;

}


.login-btn:hover {

    background: var(--dark-red);

}


/* =====================================================
HERO
===================================================== */

.hero {

    background:
        linear-gradient(
            135deg,
            #fff1f2,
            #ffffff
        );

    padding: 65px 20px 90px;

    text-align: center;

    border-bottom: 1px solid #f1f1f1;

}


.hero-badge {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    background: #ffe4e6;

    color: #be123c;

    padding: 8px 14px;

    border-radius: 50px;

    font-size: 11px;

    font-weight: 800;

    margin-bottom: 18px;

}


.hero h1 {

    font-size: 42px;

    font-weight: 800;

    margin-bottom: 12px;

    color: #111827;

}


.hero h1 span {

    color: var(--primary);

}


.hero p {

    color: var(--gray);

    max-width: 650px;

    margin: auto;

    font-size: 15px;

    line-height: 1.7;

}


/* =====================================================
SEARCH CONTAINER
===================================================== */

.search-wrapper {

    max-width: 1050px;

    margin: -45px auto 50px;

    position: relative;

    padding: 0 20px;

}


.search-card {

    background: white;

    border-radius: 20px;

    padding: 28px;

    box-shadow:
        0 15px 45px
        rgba(0,0,0,0.08);

    border: 1px solid #eee;

}


.search-title {

    display: flex;

    align-items: center;

    gap: 12px;

    margin-bottom: 23px;

}


.search-title-icon {

    width: 45px;

    height: 45px;

    border-radius: 12px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;

}


.search-title h4 {

    margin: 0;

    font-size: 17px;

    font-weight: 800;

}


.search-title p {

    margin: 3px 0 0;

    font-size: 11px;

    color: var(--gray);

}


/* =====================================================
FORM
===================================================== */

.form-label {

    font-size: 11px;

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
        0 0 0 3px
        rgba(230,57,70,0.1);

}


.search-btn {

    width: 100%;

    border: none;

    background: var(--primary);

    color: white;

    padding: 11px;

    border-radius: 9px;

    font-size: 13px;

    font-weight: 700;

}


.search-btn:hover {

    background: var(--dark-red);

}


.clear-btn {

    width: 100%;

    display: block;

    text-align: center;

    margin-top: 9px;

    color: #6b7280;

    font-size: 11px;

    text-decoration: none;

}


.clear-btn:hover {

    color: var(--primary);

}


/* =====================================================
RESULT AREA
===================================================== */

.results-section {

    max-width: 1100px;

    margin: auto;

    padding: 0 20px 60px;

}


.results-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 20px;

}


.results-header h3 {

    font-size: 20px;

    font-weight: 800;

    margin: 0;

}


.result-count {

    background: var(--light-red);

    color: var(--primary);

    padding: 7px 12px;

    border-radius: 50px;

    font-size: 11px;

    font-weight: 800;

}


/* =====================================================
DONOR CARD
===================================================== */

.donor-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 17px;

    padding: 22px;

    margin-bottom: 15px;

    transition: 0.25s;

}


.donor-card:hover {

    transform: translateY(-3px);

    box-shadow:
        0 12px 35px
        rgba(0,0,0,0.07);

}


.donor-main {

    display: flex;

    align-items: center;

    gap: 15px;

}


.donor-avatar {

    width: 58px;

    height: 58px;

    border-radius: 16px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

    font-weight: 800;

    flex-shrink: 0;

}


.donor-name {

    font-size: 16px;

    font-weight: 800;

    margin-bottom: 4px;

}


.donor-location {

    color: var(--gray);

    font-size: 12px;

}


.donor-location i {

    color: var(--primary);

}


.blood-badge {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-width: 53px;

    height: 40px;

    padding: 0 10px;

    background: var(--light-red);

    color: var(--primary);

    border-radius: 10px;

    font-size: 14px;

    font-weight: 800;

}


.available-badge {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 6px 10px;

    border-radius: 50px;

    font-size: 10px;

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


.donor-info-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 10px;

    margin-top: 20px;

}


.info-box {

    background: #fafafa;

    border-radius: 10px;

    padding: 12px;

}


.info-box small {

    display: block;

    color: #9ca3af;

    font-size: 9px;

    margin-bottom: 4px;

}


.info-box strong {

    font-size: 11px;

}


.donor-actions {

    display: flex;

    gap: 9px;

    margin-top: 18px;

}


.call-btn {

    flex: 1;

    text-align: center;

    background: #ecfdf5;

    color: #047857;

    border-radius: 9px;

    padding: 10px;

    font-size: 12px;

    font-weight: 700;

    text-decoration: none;

}


.call-btn:hover {

    background: #d1fae5;

    color: #047857;

}


.request-btn {

    flex: 1;

    text-align: center;

    background: var(--primary);

    color: white;

    border-radius: 9px;

    padding: 10px;

    font-size: 12px;

    font-weight: 700;

    text-decoration: none;

}


.request-btn:hover {

    background: var(--dark-red);

    color: white;

}


/* =====================================================
EMPTY STATE
===================================================== */

.empty-state {

    background: white;

    border: 1px solid #eee;

    border-radius: 18px;

    padding: 65px 25px;

    text-align: center;

}


.empty-icon {

    width: 75px;

    height: 75px;

    background: var(--light-red);

    color: var(--primary);

    border-radius: 20px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: auto auto 18px;

    font-size: 29px;

}


.empty-state h4 {

    font-weight: 800;

    font-size: 19px;

}


.empty-state p {

    max-width: 520px;

    margin: auto;

    color: var(--gray);

    font-size: 13px;

    line-height: 1.7;

}


/* =====================================================
INFO BOX
===================================================== */

.info-section {

    max-width: 1100px;

    margin: 0 auto 60px;

    padding: 0 20px;

}


.info-card {

    background: #111827;

    color: white;

    border-radius: 18px;

    padding: 25px;

}


.info-card h5 {

    font-weight: 800;

    margin-bottom: 8px;

}


.info-card p {

    color: #d1d5db;

    font-size: 12px;

    line-height: 1.7;

    margin: 0;

}


/* =====================================================
FOOTER
===================================================== */

footer {

    background: #111827;

    color: #9ca3af;

    padding: 25px 20px;

    text-align: center;

    font-size: 11px;

}


footer strong {

    color: white;

}


/* =====================================================
RESPONSIVE
===================================================== */

@media(max-width: 768px) {

    .hero {

        padding: 50px 20px 75px;

    }


    .hero h1 {

        font-size: 32px;

    }


    .search-card {

        padding: 20px;

    }


    .donor-info-grid {

        grid-template-columns:
            1fr;

    }


    .results-header {

        align-items: flex-start;

        gap: 10px;

    }

}


@media(max-width: 576px) {

    .hero h1 {

        font-size: 28px;

    }


    .hero p {

        font-size: 13px;

    }


    .donor-main {

        align-items: flex-start;

    }


    .donor-actions {

        flex-direction: column;

    }


    .navbar .nav-link {

        margin-left: 0;

        margin-top: 5px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
NAVBAR
===================================================== -->

<nav class="navbar navbar-expand-lg">


<div class="container">


<a
class="navbar-brand"
href="../index.php">


<div class="logo-icon">

<i class="bi bi-heart-pulse-fill"></i>

</div>


BloodConnect

</a>


<button
class="navbar-toggler"
type="button"
data-bs-toggle="collapse"
data-bs-target="#navbarMenu">

<span class="navbar-toggler-icon"></span>

</button>


<div
class="collapse navbar-collapse"
id="navbarMenu">


<ul
class="navbar-nav ms-auto align-items-lg-center">


<li class="nav-item">

<a
class="nav-link"
href="../index.php">

Home

</a>

</li>


<li class="nav-item">

<a
class="nav-link"
href="../about.php">

About Us

</a>

</li>


<li class="nav-item">

<a
class="nav-link"
href="search.php"
style="color:#e63946;">

Find Donor

</a>

</li>


<li class="nav-item">

<a
class="nav-link"
href="../contact.php">

Contact

</a>

</li>


<li class="nav-item">

<a
class="nav-link login-btn"
href="../login.php">

Login

</a>

</li>


</ul>

</div>

</div>

</nav>



<!-- =====================================================
HERO
===================================================== -->

<section class="hero">


<div class="hero-badge">

<i class="bi bi-search"></i>

Find Blood Donor

</div>


<h1>

Find a <span>Blood Donor</span> Near You

</h1>


<p>

Search our donor network by blood group
and location. Every search could help
connect someone with the blood they need.

</p>


</section>



<!-- =====================================================
SEARCH FORM
===================================================== -->

<div class="search-wrapper">


<div class="search-card">


<div class="search-title">


<div class="search-title-icon">

<i class="bi bi-search"></i>

</div>


<div>

<h4>

Search Blood Donors

</h4>


<p>

Enter one or more details to find
matching donors.

</p>

</div>


</div>


<form
method="GET"
action="search.php">


<div class="row g-3">


<!-- BLOOD GROUP -->

<div class="col-md-3">


<label class="form-label">

Blood Group

</label>


<select
name="blood_group"
class="form-select">


<option value="">

Any Blood Group

</option>


<?php foreach (
    $blood_groups as $group
): ?>


<option
value="<?php
echo htmlspecialchars($group);
?>"

<?php

echo $blood_group === $group
    ? "selected"
    : "";

?>>

<?php

echo htmlspecialchars($group);

?>

</option>


<?php endforeach; ?>


</select>


</div>



<!-- CITY -->

<div class="col-md-3">


<label class="form-label">

City

</label>


<input
type="text"
name="city"
class="form-control"
placeholder="e.g. Agartala"
value="<?php

echo htmlspecialchars(
    $city
);

?>">


</div>



<!-- DISTRICT -->

<div class="col-md-3">


<label class="form-label">

District

</label>


<input
type="text"
name="district"
class="form-control"
placeholder="e.g. West Tripura"
value="<?php

echo htmlspecialchars(
    $district
);

?>">


</div>



<!-- STATE -->

<div class="col-md-3">


<label class="form-label">

State

</label>


<input
type="text"
name="state"
class="form-control"
placeholder="e.g. Tripura"
value="<?php

echo htmlspecialchars(
    $state
);

?>">


</div>



<!-- DONOR NAME -->

<div class="col-md-4">


<label class="form-label">

Donor Name

</label>


<input
type="text"
name="donor_name"
class="form-control"
placeholder="Search donor name"
value="<?php

echo htmlspecialchars(
    $donor_name
);

?>">


</div>



<!-- AVAILABILITY -->

<div class="col-md-4">


<label class="form-label">

Availability

</label>


<select
name="availability"
class="form-select">


<option value="">

Any Availability

</option>


<?php foreach (
    $availability_options as $option
): ?>


<option
value="<?php
echo htmlspecialchars($option);
?>"

<?php

echo $availability === $option
    ? "selected"
    : "";

?>>

<?php

echo htmlspecialchars($option);

?>

</option>


<?php endforeach; ?>


</select>


</div>



<!-- SEARCH -->

<div class="col-md-4">


<label class="form-label">

&nbsp;

</label>


<button
type="submit"
class="search-btn">


<i class="bi bi-search"></i>

&nbsp;

Find Blood Donors


</button>


<a
href="search.php"
class="clear-btn">

Clear Search

</a>


</div>


</div>


</form>


</div>

</div>



<!-- =====================================================
RESULTS
===================================================== -->

<section class="results-section">


<?php if ($search_performed): ?>


<div class="results-header">


<h3>

Available Donors

</h3>


<div class="result-count">

<?php

echo $total_donors;

?>

Donor<?php

echo $total_donors !== 1
    ? "s"
    : "";

?>

Found

</div>


</div>


<?php endif; ?>



<?php if (
    $search_performed &&
    $total_donors > 0
): ?>


<?php while (
    $donor =
        mysqli_fetch_assoc(
            $result
        )
): ?>


<?php

$initial =
    strtoupper(
        substr(
            $donor["full_name"],
            0,
            1
        )
    );


$is_available =
    $donor["availability"] ===
    "Available";

?>


<!-- =================================================
DONOR CARD
================================================= -->

<div class="donor-card">


<div class="row align-items-center">


<div class="col-lg-7">


<div class="donor-main">


<div class="donor-avatar">

<?php

echo htmlspecialchars(
    $initial
);

?>

</div>


<div>

<div class="donor-name">

<?php

echo htmlspecialchars(
    $donor["full_name"]
);

?>

</div>


<div class="donor-location">

<i class="bi bi-geo-alt-fill"></i>

<?php

echo htmlspecialchars(
    $donor["city"]
);

?>


<?php if (
    !empty(
        $donor["district"]
    )
): ?>

,

<?php

echo htmlspecialchars(
    $donor["district"]
);

?>

<?php endif; ?>


<?php if (
    !empty(
        $donor["state"]
    )
): ?>

,

<?php

echo htmlspecialchars(
    $donor["state"]
);

?>

<?php endif; ?>

</div>


<div class="mt-2">


<span class="available-badge
<?php

echo $is_available
    ? "available"
    : "unavailable";

?>">


<i class="bi bi-circle-fill"
style="font-size:6px;">
</i>


<?php

echo $is_available
    ? "Available to Donate"
    : "Currently Unavailable";

?>

</span>


</div>


</div>


</div>


</div>



<!-- BLOOD GROUP -->

<div class="col-lg-2 text-lg-center mt-3 mt-lg-0">


<div class="blood-badge mx-lg-auto">

<i class="bi bi-droplet-fill"></i>

&nbsp;

<?php

echo htmlspecialchars(
    $donor["blood_group"]
);

?>

</div>


</div>



<!-- ACTION -->

<div class="col-lg-3 mt-3 mt-lg-0">


<?php if (
    $is_available
): ?>


<a
href="tel:<?php
echo htmlspecialchars(
    $donor["phone"]
);
?>"
class="call-btn">

<i class="bi bi-telephone-fill"></i>

&nbsp;

Call Donor

</a>


<?php else: ?>


<div
style="
text-align:center;
color:#9ca3af;
font-size:11px;
padding:10px;
">

Currently unavailable

</div>


<?php endif; ?>


</div>


</div>



<!-- =================================================
DONOR INFORMATION
================================================= -->

<div class="donor-info-grid">


<div class="info-box">


<small>

Location

</small>


<strong>

<?php

echo htmlspecialchars(
    $donor["city"]
);

?>

<?php if (
    !empty(
        $donor["pincode"]
    )
): ?>

-

<?php

echo htmlspecialchars(
    $donor["pincode"]
);

?>

<?php endif; ?>


</strong>


</div>


<div class="info-box">


<small>

Last Donation

</small>


<strong>


<?php if (
    !empty(
        $donor["last_donation_date"]
    )
): ?>


<?php

echo date(
    "d M Y",
    strtotime(
        $donor[
            "last_donation_date"
        ]
    )
);

?>


<?php else: ?>

Not available

<?php endif; ?>


</strong>


</div>


<div class="info-box">


<small>

Donor Status

</small>


<strong>

<?php

echo htmlspecialchars(
    ucfirst(
        $donor["status"]
    )
);

?>

</strong>


</div>


</div>



<!-- ACTIONS -->

<div class="donor-actions">


<?php if (
    $is_available
): ?>


<a
href="tel:<?php
echo htmlspecialchars(
    $donor["phone"]
);
?>"
class="call-btn">

<i class="bi bi-telephone-fill"></i>

Call Donor

</a>


<a
href="/blood/login.php"
class="request-btn">

<i class="bi bi-droplet-fill"></i>

Create Blood Request

</a>


<?php endif; ?>


</div>


</div>


<?php endwhile; ?>


<?php elseif (
    $search_performed
): ?>


<!-- =================================================
NO RESULTS
================================================= -->

<div class="empty-state">


<div class="empty-icon">

<i class="bi bi-search"></i>

</div>


<h4>

No Matching Donors Found

</h4>


<p>

We couldn't find a donor matching your
search criteria. Try selecting a different
blood group or expanding your location.

</p>


<a
href="search.php"
class="btn btn-danger mt-4">

<i class="bi bi-arrow-clockwise"></i>

Start New Search

</a>


</div>


<?php else: ?>


<!-- =================================================
INITIAL STATE
================================================= -->

<div class="empty-state">


<div class="empty-icon">

<i class="bi bi-droplet-half"></i>

</div>


<h4>

Search for a Blood Donor

</h4>


<p>

Select a blood group and enter your
location above to find registered donors
who may be able to help.

</p>


</div>


<?php endif; ?>


</section>



<!-- =====================================================
INFORMATION
===================================================== -->

<section class="info-section">


<div class="info-card">


<h5>

<i class="bi bi-shield-check"></i>

&nbsp;

Need Blood Urgently?

</h5>


<p>

If this is an emergency, please contact
your nearest hospital or blood bank
immediately. BloodConnect helps connect
donors and recipients but does not replace
professional medical services.

</p>


</div>


</section>



<!-- =====================================================
FOOTER
===================================================== -->

<footer>

<strong>
BloodConnect
</strong>

&nbsp; | &nbsp;

Connecting blood donors with people in need.

<br>

© 2026 BloodConnect. All rights reserved.

</footer>



<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>