<?php
include "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $subject = trim($_POST["subject"]);
    $user_message = trim($_POST["message"]);

    if (
        !empty($name) &&
        !empty($email) &&
        !empty($subject) &&
        !empty($user_message)
    ) {

        /*
        We will connect this form to the
        contact_messages table later.
        */

        $message = "success";

    } else {

        $message = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Contact Us | BloodConnect</title>


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

    min-height: 400px;

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

    text-align: center;

    max-width: 750px;

    margin: auto;
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
CONTACT SECTION
======================================== */

.contact-section {

    padding: 90px 0;
}


/* ========================================
CONTACT INFO
======================================== */

.contact-info {

    padding-right: 30px;
}

.small-title {

    color: var(--primary);

    font-weight: 700;

    text-transform: uppercase;

    font-size: 14px;

    letter-spacing: 1px;

    margin-bottom: 10px;
}

.contact-info h2 {

    font-size: 38px;

    font-weight: 800;

    line-height: 1.2;

    margin-bottom: 20px;
}

.contact-info > p {

    color: var(--gray);

    line-height: 1.8;

    margin-bottom: 30px;
}


/* Contact item */

.contact-item {

    display: flex;

    align-items: flex-start;

    gap: 18px;

    margin-bottom: 25px;
}

.contact-icon {

    min-width: 55px;

    height: 55px;

    background: var(--light-red);

    color: var(--primary);

    border-radius: 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;
}

.contact-item h6 {

    font-weight: 700;

    margin-bottom: 5px;
}

.contact-item p {

    color: var(--gray);

    margin: 0;

    line-height: 1.6;
}


/* ========================================
CONTACT FORM
======================================== */

.contact-form {

    background: white;

    border: 1px solid #eeeeee;

    border-radius: 22px;

    padding: 35px;

    box-shadow: 0 15px 40px rgba(0,0,0,0.07);
}

.contact-form h4 {

    font-weight: 800;

    margin-bottom: 8px;
}

.form-description {

    color: var(--gray);

    font-size: 14px;

    margin-bottom: 25px;
}

.form-label {

    font-weight: 600;

    font-size: 14px;

    margin-bottom: 8px;
}

.form-control,
.form-select {

    border: 1px solid #e5e7eb;

    border-radius: 10px;

    padding: 12px 14px;

    font-size: 14px;
}

.form-control:focus,
.form-select:focus {

    border-color: var(--primary);

    box-shadow: 0 0 0 3px rgba(230,57,70,0.1);
}

textarea.form-control {

    min-height: 140px;

    resize: vertical;
}

.submit-btn {

    background: var(--primary);

    color: white;

    border: none;

    border-radius: 10px;

    padding: 13px 25px;

    font-weight: 700;

    width: 100%;

    transition: 0.3s;
}

.submit-btn:hover {

    background: var(--dark-red);

    transform: translateY(-2px);
}


/* ========================================
ALERT
======================================== */

.success-message {

    background: #ecfdf5;

    color: #047857;

    border: 1px solid #a7f3d0;

    border-radius: 10px;

    padding: 12px 15px;

    margin-bottom: 20px;

    font-size: 14px;
}

.error-message {

    background: #fff1f2;

    color: #be123c;

    border: 1px solid #fecdd3;

    border-radius: 10px;

    padding: 12px 15px;

    margin-bottom: 20px;

    font-size: 14px;
}


/* ========================================
FAQ
======================================== */

.faq-section {

    background: #fafafa;

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

.accordion-item {

    border: 1px solid #eeeeee !important;

    border-radius: 12px !important;

    overflow: hidden;

    margin-bottom: 12px;
}

.accordion-button {

    font-weight: 600;

    padding: 20px;

    box-shadow: none !important;
}

.accordion-button:not(.collapsed) {

    background: var(--light-red);

    color: var(--primary);
}

.accordion-body {

    color: var(--gray);

    line-height: 1.7;

    padding: 20px;
}


/* ========================================
EMERGENCY CTA
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

    text-align: center;
}

.emergency h2 {

    font-size: 40px;

    font-weight: 800;

    margin-bottom: 15px;
}

.emergency p {

    color: #fecaca;

    max-width: 650px;

    margin: auto auto 30px;

    line-height: 1.7;
}

.emergency-btn {

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

.emergency-btn:hover {

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

        min-height: 380px;
    }

    .page-hero h1 {

        font-size: 42px;
    }

    .page-hero p {

        font-size: 16px;
    }

    .contact-section {

        padding: 65px 0;
    }

    .contact-info {

        padding-right: 0;

        margin-bottom: 40px;
    }

    .contact-info h2 {

        font-size: 30px;
    }

    .contact-form {

        padding: 25px;
    }

    .section-title h2 {

        font-size: 30px;
    }

    .emergency h2 {

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

<i class="bi bi-chat-dots-fill"></i>

Get In Touch

</div>


<h1>

We're Here to Help.

</h1>


<p>

Have a question, suggestion, or need assistance?
Our team is here to help you with BloodConnect.

</p>


</div>

</div>

</section>



<!-- ========================================
CONTACT SECTION
======================================== -->

<section class="contact-section">

<div class="container">

<div class="row g-5">


<!-- ========================================
CONTACT INFORMATION
======================================== -->

<div class="col-lg-5">

<div class="contact-info">


<div class="small-title">
Contact Us
</div>


<h2>
Let's Start a Conversation
</h2>


<p>

Whether you have a question about the platform,
need help with your account, or want to share
feedback, we'd love to hear from you.

</p>



<!-- Email -->

<div class="contact-item">

<div class="contact-icon">

<i class="bi bi-envelope-fill"></i>

</div>


<div>

<h6>
Email Us
</h6>

<p>
support@bloodconnect.com
</p>

</div>

</div>



<!-- Phone -->

<div class="contact-item">

<div class="contact-icon">

<i class="bi bi-telephone-fill"></i>

</div>


<div>

<h6>
Call Us
</h6>

<p>
+91 XXXXX XXXXX
</p>

</div>

</div>



<!-- Location -->

<div class="contact-item">

<div class="contact-icon">

<i class="bi bi-geo-alt-fill"></i>

</div>


<div>

<h6>
Our Location
</h6>

<p>
Agartala, Tripura, India
</p>

</div>

</div>



<!-- Working Hours -->

<div class="contact-item">

<div class="contact-icon">

<i class="bi bi-clock-fill"></i>

</div>


<div>

<h6>
Support Hours
</h6>

<p>
Monday - Saturday<br>
9:00 AM - 6:00 PM
</p>

</div>

</div>


</div>

</div>



<!-- ========================================
CONTACT FORM
======================================== -->

<div class="col-lg-7">

<div class="contact-form">


<h4>
Send Us a Message
</h4>


<p class="form-description">

Fill out the form below and our team will
get back to you as soon as possible.

</p>


<?php if ($message == "success"): ?>

<div class="success-message">

<i class="bi bi-check-circle-fill"></i>

Thank you! Your message has been received.

</div>

<?php endif; ?>


<?php if ($message == "error"): ?>

<div class="error-message">

<i class="bi bi-exclamation-circle-fill"></i>

Please fill in all required fields.

</div>

<?php endif; ?>



<form method="POST" action="contact.php">


<!-- Name -->

<div class="mb-3">

<label class="form-label">
Full Name *
</label>

<input
type="text"
name="name"
class="form-control"
placeholder="Enter your full name"
required>

</div>



<div class="row">


<!-- Email -->

<div class="col-md-6">

<div class="mb-3">

<label class="form-label">
Email Address *
</label>

<input
type="email"
name="email"
class="form-control"
placeholder="you@example.com"
required>

</div>

</div>


<!-- Phone -->

<div class="col-md-6">

<div class="mb-3">

<label class="form-label">
Phone Number
</label>

<input
type="tel"
name="phone"
class="form-control"
placeholder="+91 XXXXX XXXXX">

</div>

</div>

</div>



<!-- Subject -->

<div class="mb-3">

<label class="form-label">
Subject *
</label>

<select
name="subject"
class="form-select"
required>

<option value="">
Select a subject
</option>

<option value="General Inquiry">
General Inquiry
</option>

<option value="Donor Support">
Donor Support
</option>

<option value="Blood Request">
Blood Request
</option>

<option value="Technical Issue">
Technical Issue
</option>

<option value="Feedback">
Feedback
</option>

<option value="Report Issue">
Report an Issue
</option>

</select>

</div>



<!-- Message -->

<div class="mb-4">

<label class="form-label">
Your Message *
</label>

<textarea
name="message"
class="form-control"
placeholder="Write your message here..."
required></textarea>

</div>



<!-- Submit -->

<button
type="submit"
class="submit-btn">

<i class="bi bi-send-fill"></i>

&nbsp; Send Message

</button>


</form>

</div>

</div>


</div>

</div>

</section>



<!-- ========================================
FAQ
======================================== -->

<section class="faq-section">

<div class="container">

<div class="section-title">

<h2>
Frequently Asked Questions
</h2>

<p>

Here are some common questions about
BloodConnect.

</p>

</div>


<div class="row justify-content-center">

<div class="col-lg-9">


<div class="accordion" id="faqAccordion">


<!-- FAQ 1 -->

<div class="accordion-item">

<h2 class="accordion-header">

<button
class="accordion-button"
type="button"
data-bs-toggle="collapse"
data-bs-target="#faq1">

How can I become a blood donor?

</button>

</h2>


<div
id="faq1"
class="accordion-collapse collapse show"
data-bs-parent="#faqAccordion">

<div class="accordion-body">

Click on the "Become a Donor" button and
create your account. After completing your
profile, you can set your availability and
become visible to people searching for donors.

</div>

</div>

</div>



<!-- FAQ 2 -->

<div class="accordion-item">

<h2 class="accordion-header">

<button
class="accordion-button collapsed"
type="button"
data-bs-toggle="collapse"
data-bs-target="#faq2">

How can I find a blood donor?

</button>

</h2>


<div
id="faq2"
class="accordion-collapse collapse"
data-bs-parent="#faqAccordion">

<div class="accordion-body">

Go to the "Find Donor" page, select the
required blood group and enter your location.
The system will show available donors matching
your search.

</div>

</div>

</div>



<!-- FAQ 3 -->

<div class="accordion-item">

<h2 class="accordion-header">

<button
class="accordion-button collapsed"
type="button"
data-bs-toggle="collapse"
data-bs-target="#faq3">

Is BloodConnect a blood bank?

</button>

</h2>


<div
id="faq3"
class="accordion-collapse collapse"
data-bs-parent="#faqAccordion">

<div class="accordion-body">

No. BloodConnect is a platform that helps
connect blood donors with people who need
blood. It does not store, collect, or distribute
blood.

</div>

</div>

</div>



<!-- FAQ 4 -->

<div class="accordion-item">

<h2 class="accordion-header">

<button
class="accordion-button collapsed"
type="button"
data-bs-toggle="collapse"
data-bs-target="#faq4">

What should I do during an emergency?

</button>

</h2>


<div
id="faq4"
class="accordion-collapse collapse"
data-bs-parent="#faqAccordion">

<div class="accordion-body">

If you need blood urgently, create a blood
request through the platform and contact
compatible available donors. For a medical
emergency, always contact the appropriate
hospital or emergency medical service directly.

</div>

</div>

</div>



<!-- FAQ 5 -->

<div class="accordion-item">

<h2 class="accordion-header">

<button
class="accordion-button collapsed"
type="button"
data-bs-toggle="collapse"
data-bs-target="#faq5">

Can I update my donor availability?

</button>

</h2>


<div
id="faq5"
class="accordion-collapse collapse"
data-bs-parent="#faqAccordion">

<div class="accordion-body">

Yes. Registered donors will be able to update
their availability from their donor dashboard.
We will implement this functionality in the
donor panel.

</div>

</div>

</div>


</div>

</div>

</div>

</div>

</section>



<!-- ========================================
EMERGENCY CTA
======================================== -->

<section class="emergency">

<div class="container">

<h2>
Need Blood Right Now?
</h2>


<p>

Don't wait. Create a blood request and connect
with potential donors near you.

</p>


<a
href="request-blood.php"
class="emergency-btn">

<i class="bi bi-exclamation-triangle-fill"></i>

Request Blood

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

A community-driven platform connecting
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