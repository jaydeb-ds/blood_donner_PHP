<?php
include "config/database.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>About Us | BloodConnect</title>

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

html {
    scroll-behavior: smooth;
}

body {
    font-family: 'Inter', sans-serif;
    background: #ffffff;
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
   NAVBAR
======================================== */

.navbar {
    background: rgba(255,255,255,0.96);
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
   PAGE HERO
======================================== */

.page-hero {

    min-height: 430px;

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

    position: relative;

    overflow: hidden;
}


/* Decorative circles */

.page-hero::before {

    content: "";

    position: absolute;

    width: 450px;
    height: 450px;

    border-radius: 50%;

    border: 70px solid rgba(255,255,255,0.05);

    right: -160px;
    top: -180px;
}

.page-hero::after {

    content: "";

    position: absolute;

    width: 300px;
    height: 300px;

    border-radius: 50%;

    background: rgba(255,255,255,0.04);

    left: -130px;
    bottom: -180px;
}


.page-hero-content {

    position: relative;

    z-index: 2;

    max-width: 750px;

    margin: auto;

    text-align: center;
}

.page-badge {

    display: inline-flex;

    align-items: center;

    background: rgba(255,255,255,0.15);

    border: 1px solid rgba(255,255,255,0.25);

    padding: 8px 17px;

    border-radius: 50px;

    font-size: 14px;

    margin-bottom: 20px;

    backdrop-filter: blur(10px);
}

.page-badge i {
    margin-right: 8px;
}

.page-hero h1 {

    font-size: clamp(42px, 6vw, 62px);

    font-weight: 800;

    letter-spacing: -1.5px;

    margin-bottom: 20px;
}

.page-hero p {

    font-size: 18px;

    line-height: 1.7;

    color: #fee2e2;

    max-width: 650px;

    margin: auto;
}


/* ========================================
   COMMON SECTION
======================================== */

.section {

    padding: 90px 0;
}

.section-title {

    max-width: 700px;

    margin: auto auto 50px;

    text-align: center;
}

.section-title h2 {

    font-size: 38px;

    font-weight: 800;

    margin-bottom: 15px;
}

.section-title p {

    color: var(--gray);

    line-height: 1.7;
}


/* ========================================
   OUR STORY
======================================== */

.story-image {

    min-height: 430px;

    border-radius: 25px;

    background:
        linear-gradient(
            rgba(127,29,29,0.78),
            rgba(220,38,38,0.78)
        ),
        url('assets/images/blood-donation.jpg');

    background-size: cover;

    background-position: center;

    display: flex;

    align-items: center;

    justify-content: center;

    color: white;

    overflow: hidden;
}

.story-icon {

    width: 120px;

    height: 120px;

    border-radius: 35px;

    background: rgba(255,255,255,0.15);

    border: 1px solid rgba(255,255,255,0.3);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 55px;

    backdrop-filter: blur(10px);
}


.story-content {

    padding-left: 30px;
}

.story-content .small-title {

    color: var(--primary);

    font-weight: 700;

    text-transform: uppercase;

    font-size: 14px;

    letter-spacing: 1px;

    margin-bottom: 10px;
}

.story-content h2 {

    font-size: 38px;

    font-weight: 800;

    line-height: 1.2;

    margin-bottom: 20px;
}

.story-content p {

    color: var(--gray);

    line-height: 1.8;

    margin-bottom: 15px;
}


/* ========================================
   MISSION / VISION
======================================== */

.mission-section {

    background: #fafafa;
}

.info-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 20px;

    padding: 35px;

    height: 100%;

    transition: 0.3s;
}

.info-card:hover {

    transform: translateY(-7px);

    box-shadow: 0 15px 35px rgba(0,0,0,0.07);

    border-color: #fecaca;
}

.info-icon {

    width: 65px;

    height: 65px;

    border-radius: 18px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 27px;

    margin-bottom: 22px;
}

.info-card h4 {

    font-weight: 800;

    margin-bottom: 15px;
}

.info-card p {

    color: var(--gray);

    line-height: 1.8;

    margin-bottom: 0;
}


/* ========================================
   VALUES
======================================== */

.value-card {

    text-align: center;

    padding: 30px 20px;
}

.value-icon {

    width: 75px;

    height: 75px;

    border-radius: 22px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    margin: auto;

    font-size: 30px;
}

.value-card h5 {

    margin-top: 20px;

    font-weight: 800;
}

.value-card p {

    color: var(--gray);

    line-height: 1.7;

    margin-top: 10px;
}


/* ========================================
   HOW IT WORKS
======================================== */

.how-section {

    background: #fafafa;
}

.step-card {

    background: white;

    border-radius: 20px;

    padding: 35px 25px;

    text-align: center;

    border: 1px solid #eee;

    height: 100%;

    transition: 0.3s;
}

.step-card:hover {

    transform: translateY(-6px);

    box-shadow: 0 15px 35px rgba(0,0,0,0.07);
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

    font-size: 24px;

    font-weight: 800;
}

.step-card h5 {

    margin-top: 20px;

    font-weight: 800;
}

.step-card p {

    color: var(--gray);

    line-height: 1.7;

    margin-top: 10px;
}


/* ========================================
   CTA
======================================== */

.cta {

    padding: 85px 0;

    background:
        linear-gradient(
            135deg,
            #991b1b,
            #dc2626
        );

    color: white;

    text-align: center;

    position: relative;

    overflow: hidden;
}

.cta h2 {

    font-size: 40px;

    font-weight: 800;

    margin-bottom: 15px;
}

.cta p {

    color: #fecaca;

    max-width: 650px;

    margin: auto auto 30px;

    line-height: 1.7;
}

.cta-btn {

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

.cta-btn:hover {

    transform: translateY(-3px);

    color: var(--dark-red);

    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
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

@media(max-width: 768px) {

    .page-hero {

        min-height: 400px;

    }

    .page-hero h1 {

        font-size: 42px;

    }

    .page-hero p {

        font-size: 16px;

    }

    .section {

        padding: 65px 0;

    }

    .section-title h2 {

        font-size: 30px;

    }

    .story-content {

        padding-left: 0;

        margin-top: 35px;

    }

    .story-content h2 {

        font-size: 30px;

    }

    .story-image {

        min-height: 300px;

    }

    .cta h2 {

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
     PAGE HERO
======================================== -->

<section class="page-hero">

<div class="container">

<div class="page-hero-content">

<div class="page-badge">

<i class="bi bi-heart-fill"></i>

About BloodConnect

</div>


<h1>
Connecting People.
<br>
Saving Lives.
</h1>


<p>

BloodConnect is a community-driven platform designed
to make finding and donating blood faster, easier,
and more accessible.

</p>

</div>

</div>

</section>



<!-- ========================================
     OUR STORY
======================================== -->

<section class="section">

<div class="container">

<div class="row align-items-center">


<div class="col-lg-6">

<div class="story-image">

<div class="story-icon">

<i class="bi bi-heart-pulse-fill"></i>

</div>

</div>

</div>


<div class="col-lg-6">

<div class="story-content">

<div class="small-title">
Our Story
</div>


<h2>
Technology That Connects
People When It Matters Most
</h2>


<p>

Finding the right blood donor during an emergency
can be difficult. People often depend on social media,
phone calls, or personal contacts to find compatible
blood donors.

</p>


<p>

BloodConnect was created to simplify this process.
Our platform connects people who need blood with
available donors based on blood group and location.

</p>


<p>

Our goal is simple: use technology to make blood
donation more accessible and help communities respond
faster during emergencies.

</p>

</div>

</div>


</div>

</div>

</section>



<!-- ========================================
     MISSION & VISION
======================================== -->

<section class="section mission-section">

<div class="container">

<div class="section-title">

<h2>
Our Mission & Vision
</h2>

<p>

We want to create a connected community where
finding a blood donor is simple, fast, and reliable.

</p>

</div>


<div class="row g-4">


<!-- Mission -->

<div class="col-md-6">

<div class="info-card">

<div class="info-icon">

<i class="bi bi-bullseye"></i>

</div>


<h4>
Our Mission
</h4>


<p>

Our mission is to connect blood donors and recipients
through a simple digital platform that makes it easier
to find compatible blood donors when they are needed.

</p>

</div>

</div>


<!-- Vision -->

<div class="col-md-6">

<div class="info-card">

<div class="info-icon">

<i class="bi bi-eye"></i>

</div>


<h4>
Our Vision
</h4>


<p>

Our vision is to build a strong and connected blood
donation community where no one struggles to find a
blood donor during a medical emergency.

</p>

</div>

</div>


</div>

</div>

</section>



<!-- ========================================
     OUR VALUES
======================================== -->

<section class="section">

<div class="container">

<div class="section-title">

<h2>
What We Believe In
</h2>

<p>

The principles that guide BloodConnect and our
community of donors.

</p>

</div>


<div class="row g-4">


<div class="col-md-3">

<div class="value-card">

<div class="value-icon">

<i class="bi bi-heart-fill"></i>

</div>

<h5>
Compassion
</h5>

<p>

We believe every act of kindness can make
a meaningful difference.

</p>

</div>

</div>


<div class="col-md-3">

<div class="value-card">

<div class="value-icon">

<i class="bi bi-shield-check"></i>

</div>

<h5>
Trust
</h5>

<p>

We aim to create a safe and trustworthy
platform for our users.

</p>

</div>

</div>


<div class="col-md-3">

<div class="value-card">

<div class="value-icon">

<i class="bi bi-lightning-charge-fill"></i>

</div>

<h5>
Speed
</h5>

<p>

During emergencies, every second matters.
We focus on quick connections.

</p>

</div>

</div>


<div class="col-md-3">

<div class="value-card">

<div class="value-icon">

<i class="bi bi-people-fill"></i>

</div>

<h5>
Community
</h5>

<p>

Together, donors and recipients can build
a stronger community.

</p>

</div>

</div>


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
How BloodConnect Helps
</h2>

<p>

Our platform makes the process of finding and
connecting with blood donors simple.

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

Search for available donors based on blood
group and location.

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

View suitable donors and contact them directly
when blood is needed.

</p>

</div>

</div>


<div class="col-md-4">

<div class="step-card">

<div class="step-number">
3
</div>

<h5>
Make a Difference
</h5>

<p>

Donate blood and become part of a community
that helps save lives.

</p>

</div>

</div>


</div>

</div>

</section>



<!-- ========================================
     CTA
======================================== -->

<section class="cta">

<div class="container">

<h2>
Be Someone's Reason to Hope
</h2>


<p>

Your decision to donate blood could make a life-saving
difference for someone and their family.

</p>


<a
href="register.php"
class="cta-btn">

<i class="bi bi-heart-fill"></i>

Become a Blood Donor

</a>

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

A community-driven platform connecting blood
donors with people who need blood.

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
Privacy Policy
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