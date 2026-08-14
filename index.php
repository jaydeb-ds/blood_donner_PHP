<?php
include "config/database.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BloodConnect | Find Blood Donors Near You</title>

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
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: #fff;
    color: #1f2937;
}

html {
    scroll-behavior: smooth;
}

:root {
    --primary: #e63946;
    --dark-red: #b91c1c;
    --light-red: #fff1f2;
    --dark: #111827;
}


/* ========================================
   NAVBAR
======================================== */

.navbar {
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(15px);
    box-shadow: 0 2px 20px rgba(0,0,0,0.06);
    padding: 15px 0;
}

.navbar-brand {
    font-size: 25px;
    font-weight: 800;
    color: var(--primary) !important;
}

.logo-icon {
    width: 42px;
    height: 42px;
    background: var(--primary);
    color: white;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 8px;
}

.nav-link {
    font-weight: 500;
    color: #374151 !important;
    margin: 0 8px;
}

.nav-link:hover {
    color: var(--primary) !important;
}

.login-btn {
    background: var(--primary);
    color: white !important;
    border-radius: 10px;
    padding: 10px 22px !important;
}

.login-btn:hover {
    background: var(--dark-red);
}


/* ========================================
   HERO
======================================== */

.hero {
    min-height: 680px;

    background:
        radial-gradient(
            circle at 80% 30%,
            rgba(255,255,255,0.15),
            transparent 35%
        ),
        linear-gradient(
            135deg,
            #7f1d1d,
            #dc2626 55%,
            #ef4444
        );

    color: white;

    display: flex;
    align-items: center;

    overflow: hidden;

    position: relative;
}


/* Decorative circles */

.hero::before {
    content: "";
    position: absolute;

    width: 500px;
    height: 500px;

    border-radius: 50%;

    border: 80px solid rgba(255,255,255,0.05);

    right: -180px;
    top: -100px;
}

.hero::after {
    content: "";
    position: absolute;

    width: 350px;
    height: 350px;

    border-radius: 50%;

    background: rgba(255,255,255,0.04);

    left: -150px;
    bottom: -180px;
}


.hero-content {
    position: relative;
    z-index: 2;
}

.hero-badge {

    display: inline-flex;

    align-items: center;

    background: rgba(255,255,255,0.15);

    border: 1px solid rgba(255,255,255,0.25);

    padding: 8px 15px;

    border-radius: 50px;

    font-size: 14px;

    margin-bottom: 25px;

    backdrop-filter: blur(10px);
}

.hero-badge i {
    margin-right: 8px;
}


.hero h1 {

    font-size: clamp(42px, 6vw, 72px);

    line-height: 1.05;

    font-weight: 800;

    letter-spacing: -2px;

    margin-bottom: 25px;
}


.hero h1 span {
    color: #fecaca;
}


.hero p {

    font-size: 19px;

    line-height: 1.7;

    color: #fee2e2;

    max-width: 600px;

    margin-bottom: 35px;
}


/* Hero buttons */

.btn-primary-custom {

    background: white;

    color: var(--primary);

    padding: 14px 25px;

    border-radius: 10px;

    text-decoration: none;

    font-weight: 700;

    display: inline-flex;

    align-items: center;

    gap: 8px;

    transition: 0.3s;
}

.btn-primary-custom:hover {

    transform: translateY(-3px);

    color: var(--dark-red);

    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}


.btn-outline-custom {

    border: 1px solid rgba(255,255,255,0.6);

    color: white;

    padding: 13px 25px;

    border-radius: 10px;

    text-decoration: none;

    font-weight: 600;

    display: inline-flex;

    align-items: center;

    gap: 8px;

    margin-left: 10px;

    transition: 0.3s;
}

.btn-outline-custom:hover {

    background: white;

    color: var(--primary);
}


/* ========================================
   HERO BLOOD DROP
======================================== */

.blood-visual {

    position: relative;

    width: 420px;

    height: 420px;

    margin: auto;

    display: flex;

    align-items: center;

    justify-content: center;
}

.blood-circle {

    width: 330px;

    height: 330px;

    border-radius: 50%;

    background: rgba(255,255,255,0.08);

    border: 1px solid rgba(255,255,255,0.2);

    display: flex;

    align-items: center;

    justify-content: center;

    backdrop-filter: blur(10px);

    animation: float 4s ease-in-out infinite;
}


.blood-drop {

    width: 170px;

    height: 210px;

    background: linear-gradient(
        145deg,
        #fff,
        #fecaca
    );

    border-radius: 55% 55% 60% 60%;

    transform: rotate(45deg);

    position: relative;

    box-shadow:
        0 25px 60px rgba(0,0,0,0.2);
}

.blood-drop::after {

    content: "♥";

    position: absolute;

    transform: rotate(-45deg);

    color: var(--primary);

    font-size: 65px;

    left: 53px;

    top: 60px;
}


@keyframes float {

    0%,100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-18px);
    }
}


/* ========================================
   SEARCH BOX
======================================== */

.search-section {

    margin-top: -65px;

    position: relative;

    z-index: 10;
}

.search-box {

    background: white;

    border-radius: 20px;

    padding: 25px;

    box-shadow: 0 20px 50px rgba(0,0,0,0.12);
}

.search-title {

    font-size: 20px;

    font-weight: 700;

    margin-bottom: 18px;
}

.search-input {

    border: 1px solid #e5e7eb;

    border-radius: 10px;

    padding: 13px 15px;

    width: 100%;

    outline: none;
}

.search-input:focus {

    border-color: var(--primary);

    box-shadow: 0 0 0 3px rgba(230,57,70,0.1);
}

.search-button {

    background: var(--primary);

    color: white;

    border: none;

    border-radius: 10px;

    padding: 13px 25px;

    font-weight: 600;

    width: 100%;
}

.search-button:hover {

    background: var(--dark-red);
}


/* ========================================
   STATS
======================================== */

.stats {

    padding: 80px 0 50px;
}

.stat-card {

    text-align: center;

    padding: 20px;
}

.stat-number {

    font-size: 38px;

    font-weight: 800;

    color: var(--primary);
}

.stat-text {

    color: #6b7280;

    font-size: 15px;
}


/* ========================================
   SECTION TITLE
======================================== */

.section {

    padding: 90px 0;
}

.section-title {

    text-align: center;

    max-width: 650px;

    margin: auto auto 50px;
}

.section-title h2 {

    font-size: 38px;

    font-weight: 800;

    margin-bottom: 12px;
}

.section-title p {

    color: #6b7280;

    line-height: 1.7;
}


/* ========================================
   BLOOD GROUPS
======================================== */

.blood-card {

    background: white;

    border: 1px solid #f1f1f1;

    border-radius: 18px;

    padding: 25px;

    text-align: center;

    transition: 0.3s;

    height: 100%;
}

.blood-card:hover {

    transform: translateY(-8px);

    border-color: #fecaca;

    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
}


.blood-icon {

    width: 70px;

    height: 70px;

    border-radius: 20px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    margin: auto;

    font-size: 24px;

    font-weight: 800;
}

.blood-card h5 {

    margin-top: 18px;

    font-weight: 700;
}

.find-btn {

    color: var(--primary);

    text-decoration: none;

    font-size: 14px;

    font-weight: 600;
}


/* ========================================
   HOW IT WORKS
======================================== */

.how-section {

    background: #fafafa;
}

.step-card {

    background: white;

    padding: 35px 25px;

    border-radius: 18px;

    text-align: center;

    height: 100%;

    border: 1px solid #eee;

    transition: 0.3s;
}

.step-card:hover {

    transform: translateY(-5px);

    box-shadow: 0 15px 30px rgba(0,0,0,0.06);
}

.step-number {

    width: 65px;

    height: 65px;

    border-radius: 18px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    margin: auto;

    font-size: 25px;

    font-weight: 800;
}

.step-card h5 {

    margin-top: 20px;

    font-weight: 700;
}

.step-card p {

    color: #6b7280;

    line-height: 1.7;

    margin-top: 10px;
}


/* ========================================
   EMERGENCY
======================================== */

.emergency {

    padding: 80px 0;

    background:
        linear-gradient(
            135deg,
            #991b1b,
            #dc2626
        );

    color: white;

    position: relative;

    overflow: hidden;
}

.emergency h2 {

    font-size: 40px;

    font-weight: 800;
}

.emergency p {

    color: #fecaca;

    line-height: 1.7;
}

.emergency-btn {

    background: white;

    color: var(--primary);

    padding: 15px 25px;

    border-radius: 10px;

    text-decoration: none;

    font-weight: 700;

    display: inline-block;

    transition: 0.3s;
}

.emergency-btn:hover {

    transform: translateY(-3px);

    color: var(--dark-red);
}


/* ========================================
   FOOTER
======================================== */

footer {

    background: #111827;

    color: #9ca3af;

    padding: 70px 0 20px;
}

.footer-logo {

    color: white;

    font-size: 24px;

    font-weight: 800;

    margin-bottom: 15px;
}

footer h6 {

    color: white;

    font-weight: 700;

    margin-bottom: 20px;
}

footer a {

    color: #9ca3af;

    text-decoration: none;

    display: block;

    margin-bottom: 10px;
}

footer a:hover {

    color: white;
}

.footer-bottom {

    border-top: 1px solid #374151;

    margin-top: 40px;

    padding-top: 20px;

    text-align: center;

    font-size: 14px;
}


/* ========================================
   MOBILE
======================================== */

@media(max-width: 991px) {

    .blood-visual {

        display: none;
    }

}

@media(max-width: 768px) {

    .hero {

        min-height: 620px;

        text-align: center;
    }

    .hero h1 {

        font-size: 45px;
    }

    .hero p {

        font-size: 17px;

        margin-left: auto;

        margin-right: auto;
    }

    .btn-outline-custom {

        margin-left: 0;

        margin-top: 10px;
    }

    .section-title h2 {

        font-size: 30px;
    }

}

</style>

</head>


<body>


<!-- ========================================
     NAVBAR
======================================== -->

<nav class="navbar navbar-expand-lg sticky-top">

<div class="container">

<a class="navbar-brand" href="index.php">

<span class="logo-icon">
<i class="bi bi-heart-pulse-fill"></i>
</span>

BloodConnect

</a>


<button
class="navbar-toggler"
type="button"
data-bs-toggle="collapse"
data-bs-target="#navbarMenu">

<span class="navbar-toggler-icon"></span>

</button>


<div class="collapse navbar-collapse" id="navbarMenu">

<ul class="navbar-nav ms-auto align-items-lg-center">

<li class="nav-item">
<a class="nav-link" href="index.php">
Home
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="search.php">
Find Donor
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="about.php">
About
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="contact.php">
Contact
</a>
</li>

<li class="nav-item">
<a class="nav-link login-btn" href="login.php">
Login
</a>
</li>

</ul>

</div>

</div>

</nav>



<!-- ========================================
     HERO
======================================== -->

<section class="hero">

<div class="container">

<div class="row align-items-center">

<div class="col-lg-7">

<div class="hero-content">

<div class="hero-badge">

<i class="bi bi-heart-fill"></i>

Every Drop Counts

</div>


<h1>

Find Blood.
<br>

<span>Save Lives.</span>

</h1>


<p>

Connect with verified blood donors near your location.
Search by blood group and find the help you need when
every second matters.

</p>


<a href="search.php" class="btn-primary-custom">

<i class="bi bi-search"></i>

Find a Donor

</a>


<a href="register.php" class="btn-outline-custom">

<i class="bi bi-person-plus"></i>

Become a Donor

</a>

</div>

</div>


<!-- Blood visual -->

<div class="col-lg-5">

<div class="blood-visual">

<div class="blood-circle">

<div class="blood-drop"></div>

</div>

</div>

</div>

</div>

</div>

</section>



<!-- ========================================
     SEARCH
======================================== -->

<section class="search-section">

<div class="container">

<div class="search-box">

<div class="search-title">

<i class="bi bi-search text-danger"></i>

Find a Blood Donor Near You

</div>


<form action="search.php" method="GET">

<div class="row g-3">

<div class="col-md-4">

<select
name="blood_group"
class="search-input">

<option value="">Select Blood Group</option>

<option>A+</option>
<option>A-</option>
<option>B+</option>
<option>B-</option>
<option>O+</option>
<option>O-</option>
<option>AB+</option>
<option>AB-</option>

</select>

</div>


<div class="col-md-4">

<input
type="text"
name="location"
class="search-input"
placeholder="Enter city or location">

</div>


<div class="col-md-4">

<button
type="submit"
class="search-button">

<i class="bi bi-search"></i>

Search Donors

</button>

</div>

</div>

</form>

</div>

</div>

</section>



<!-- ========================================
     STATISTICS
======================================== -->

<section class="stats">

<div class="container">

<div class="row">

<div class="col-6 col-md-3">

<div class="stat-card">

<div class="stat-number">
1,250+
</div>

<div class="stat-text">
Registered Donors
</div>

</div>

</div>


<div class="col-6 col-md-3">

<div class="stat-card">

<div class="stat-number">
850+
</div>

<div class="stat-text">
Lives Helped
</div>

</div>

</div>


<div class="col-6 col-md-3">

<div class="stat-card">

<div class="stat-number">
45+
</div>

<div class="stat-text">
Cities Covered
</div>

</div>

</div>


<div class="col-6 col-md-3">

<div class="stat-card">

<div class="stat-number">
24/7
</div>

<div class="stat-text">
Emergency Support
</div>

</div>

</div>

</div>

</div>

</section>



<!-- ========================================
     BLOOD GROUP
======================================== -->

<section class="section">

<div class="container">

<div class="section-title">

<h2>
Find Donors By Blood Group
</h2>

<p>
Choose the required blood group and connect with
available donors near you.
</p>

</div>


<div class="row g-4">


<?php

$bloodGroups = [
    "A+", "A-", "B+", "B-",
    "O+", "O-", "AB+", "AB-"
];

foreach ($bloodGroups as $group):

?>

<div class="col-6 col-md-3">

<div class="blood-card">

<div class="blood-icon">

<?php echo $group; ?>

</div>


<h5>

<?php echo $group; ?>

Blood

</h5>


<a
href="search.php?blood_group=<?php echo urlencode($group); ?>"
class="find-btn">

Find Donors

<i class="bi bi-arrow-right"></i>

</a>

</div>

</div>

<?php endforeach; ?>


</div>

</div>

</section>



<!-- ========================================
     HOW IT WORKS
======================================== -->

<section class="section how-section">

<div class="container">

<div class="section-title">

<h2>
How BloodConnect Works
</h2>

<p>
Finding and connecting with a blood donor takes only
three simple steps.
</p>

</div>


<div class="row g-4">


<div class="col-md-4">

<div class="step-card">

<div class="step-number">
1
</div>

<h5>
Search
</h5>

<p>
Select the blood group you need and search for
available donors near your location.
</p>

</div>

</div>


<div class="col-md-4">

<div class="step-card">

<div class="step-number">
2
</div>

<h5>
Connect
</h5>

<p>
View available donor information and contact a
suitable donor directly.
</p>

</div>

</div>


<div class="col-md-4">

<div class="step-card">

<div class="step-number">
3
</div>

<h5>
Save a Life
</h5>

<p>
Donate or receive blood and help someone during
their time of need.
</p>

</div>

</div>


</div>

</div>

</section>



<!-- ========================================
     EMERGENCY
======================================== -->

<section class="emergency">

<div class="container">

<div class="row align-items-center">

<div class="col-lg-8">

<h2>
Need Blood Urgently?
</h2>

<p class="mb-0">

Create an emergency blood request and reach
nearby donors as quickly as possible.

</p>

</div>


<div class="col-lg-4 text-lg-end mt-4 mt-lg-0">

<a
href="request-blood.php"
class="emergency-btn">

<i class="bi bi-exclamation-triangle-fill"></i>

Request Blood

</a>

</div>

</div>

</div>

</section>



<!-- ========================================
     FOOTER
======================================== -->

<footer>

<div class="container">

<div class="row g-5">


<div class="col-lg-5">

<div class="footer-logo">

<i class="bi bi-heart-pulse-fill text-danger"></i>

BloodConnect

</div>

<p>

A community-driven platform that connects
blood donors with people who need blood.

</p>

</div>


<div class="col-6 col-lg-2">

<h6>
Platform
</h6>

<a href="search.php">
Find Donor
</a>

<a href="register.php">
Become a Donor
</a>

<a href="request-blood.php">
Request Blood
</a>

</div>


<div class="col-6 col-lg-2">

<h6>
Company
</h6>

<a href="about.php">
About Us
</a>

<a href="contact.php">
Contact
</a>

<a href="#">
Privacy
</a>

</div>


<div class="col-lg-3">

<h6>
Contact
</h6>

<p>
<i class="bi bi-envelope"></i>
support@bloodconnect.com
</p>

<p>
<i class="bi bi-telephone"></i>
+91 XXXXX XXXXX
</p>

</div>


</div>


<div class="footer-bottom">

© <?php echo date("Y"); ?> BloodConnect.
All Rights Reserved.

</div>

</div>

</footer>



<!-- Bootstrap JS -->

<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>