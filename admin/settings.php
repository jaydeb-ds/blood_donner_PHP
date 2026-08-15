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
// ADMIN INFORMATION
// =====================================================

$admin_name =
    $_SESSION["admin_name"]
    ?? "Administrator";

$admin_email =
    $_SESSION["admin_email"]
    ?? "admin@bloodconnect.com";

$admin_initial =
    strtoupper(
        substr(
            $admin_name,
            0,
            1
        )
    );


// =====================================================
// CHANGE ADMIN PASSWORD
// =====================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["change_password"])
) {

    $current_password =
        $_POST["current_password"]
        ?? "";

    $new_password =
        $_POST["new_password"]
        ?? "";

    $confirm_password =
        $_POST["confirm_password"]
        ?? "";


    if (
        empty($current_password) ||
        empty($new_password) ||
        empty($confirm_password)
    ) {

        $error =
            "Please fill in all password fields.";

    }

    elseif (
        $new_password !== $confirm_password
    ) {

        $error =
            "New password and confirm password do not match.";

    }

    elseif (
        strlen($new_password) < 6
    ) {

        $error =
            "New password must contain at least 6 characters.";

    }

    else {

        /*
         * This project uses the admin login created
         * earlier with a code/password.
         *
         * If you are using a fixed admin password
         * in admin/login.php, change it there too.
         */

        $admin_password =
            $_SESSION["admin_password"]
            ?? "";


        if (
            $admin_password !== "" &&
            !password_verify(
                $current_password,
                $admin_password
            )
        ) {

            $error =
                "Current password is incorrect.";

        }

        else {

            /*
             * Store the new password in the session.
             *
             * If your admin login uses a database,
             * update the database here instead.
             */

            $_SESSION["admin_password"] =
                password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );


            $success =
                "Admin password changed successfully.";

        }

    }

}


// =====================================================
// SAVE APPLICATION SETTINGS
// =====================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["save_settings"])
) {

    $site_name =
        trim(
            $_POST["site_name"] ?? ""
        );

    $site_email =
        trim(
            $_POST["site_email"] ?? ""
        );

    $site_phone =
        trim(
            $_POST["site_phone"] ?? ""
        );

    $default_city =
        trim(
            $_POST["default_city"] ?? ""
        );


    $allow_requests =
        isset(
            $_POST["allow_requests"]
        )
        ? 1
        : 0;


    $allow_registration =
        isset(
            $_POST["allow_registration"]
        )
        ? 1
        : 0;


    $email_notifications =
        isset(
            $_POST["email_notifications"]
        )
        ? 1
        : 0;


    /*
     * Store settings in SESSION for now.
     *
     * This avoids requiring another database table.
     */

    $_SESSION["site_settings"] = [

        "site_name" =>
            $site_name,

        "site_email" =>
            $site_email,

        "site_phone" =>
            $site_phone,

        "default_city" =>
            $default_city,

        "allow_requests" =>
            $allow_requests,

        "allow_registration" =>
            $allow_registration,

        "email_notifications" =>
            $email_notifications

    ];


    $success =
        "Application settings saved successfully.";

}


// =====================================================
// GET SAVED SETTINGS
// =====================================================

$settings =
    $_SESSION["site_settings"]
    ?? [

        "site_name" =>
            "BloodConnect",

        "site_email" =>
            "support@bloodconnect.com",

        "site_phone" =>
            "+91 00000 00000",

        "default_city" =>
            "Agartala",

        "allow_requests" =>
            1,

        "allow_registration" =>
            1,

        "email_notifications" =>
            1

    ];


// =====================================================
// DATABASE INFORMATION
// =====================================================

$db_name =
    "blood";


// Get PHP version

$php_version =
    PHP_VERSION;


// Get MySQL version

$mysql_version =
    mysqli_get_server_info($conn);


// =====================================================
// COUNT DONORS
// =====================================================

$donor_count = 0;

$count_result =
    mysqli_query(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM donor_profiles
        "
    );


if ($count_result) {

    $row =
        mysqli_fetch_assoc(
            $count_result
        );

    $donor_count =
        (int)$row["total"];

}


// =====================================================
// COUNT BLOOD REQUESTS
// =====================================================

$request_count = 0;

$count_result =
    mysqli_query(
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

    $request_count =
        (int)$row["total"];

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
Settings | BloodConnect Admin
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

    margin-bottom: 23px;

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
LAYOUT
===================================================== */

.settings-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 17px;

}


/* =====================================================
CARD
===================================================== */

.card {

    background: white;

    border: 1px solid #eee;

    border-radius: 17px;

    padding: 20px;

}


.card.full {

    grid-column: 1 / -1;

}


.card-title {

    display: flex;

    align-items: center;

    gap: 8px;

    font-size: 14px;

    font-weight: 800;

    margin-bottom: 17px;

}


.card-title i {

    color: #e63946;

}


/* =====================================================
PROFILE
===================================================== */

.profile-header {

    display: flex;

    align-items: center;

    gap: 14px;

    margin-bottom: 18px;

}


.profile-avatar {

    width: 58px;

    height: 58px;

    border-radius: 15px;

    background: #fff1f2;

    color: #e63946;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;

    font-weight: 800;

}


.profile-name {

    font-size: 15px;

    font-weight: 800;

}


.profile-role {

    color: #9ca3af;

    font-size: 8px;

    margin-top: 3px;

}


.info-row {

    display: flex;

    justify-content: space-between;

    gap: 15px;

    padding: 11px 0;

    border-bottom: 1px solid #f3f4f6;

}


.info-row:last-child {

    border-bottom: none;

}


.info-label {

    color: #9ca3af;

    font-size: 9px;

}


.info-value {

    font-size: 9px;

    font-weight: 700;

    text-align: right;

}


/* =====================================================
FORM
===================================================== */

.form-label {

    font-size: 9px;

    font-weight: 700;

    margin-bottom: 6px;

}


.form-control {

    border: 1px solid #e5e7eb;

    border-radius: 8px;

    padding: 9px 11px;

    font-size: 10px;

}


.form-control:focus {

    border-color: #e63946;

    box-shadow:
        0 0 0 3px
        rgba(230,57,70,.08);

}


.save-btn {

    border: none;

    background: #e63946;

    color: white;

    border-radius: 8px;

    padding: 10px 17px;

    font-size: 10px;

    font-weight: 700;

}


.save-btn:hover {

    background: #d62839;

}


/* =====================================================
TOGGLE
===================================================== */

.setting-item {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    padding: 13px 0;

    border-bottom: 1px solid #f3f4f6;

}


.setting-item:last-child {

    border-bottom: none;

}


.setting-name {

    font-size: 10px;

    font-weight: 700;

}


.setting-description {

    color: #9ca3af;

    font-size: 8px;

    margin-top: 4px;

    line-height: 1.5;

}


/* Toggle */

.switch {

    position: relative;

    display: inline-block;

    width: 42px;

    height: 23px;

    flex-shrink: 0;

}


.switch input {

    opacity: 0;

    width: 0;

    height: 0;

}


.slider {

    position: absolute;

    cursor: pointer;

    inset: 0;

    background: #d1d5db;

    border-radius: 50px;

    transition: .2s;

}


.slider:before {

    content: "";

    position: absolute;

    width: 17px;

    height: 17px;

    left: 3px;

    top: 3px;

    background: white;

    border-radius: 50%;

    transition: .2s;

}


.switch input:checked + .slider {

    background: #e63946;

}


.switch input:checked + .slider:before {

    transform: translateX(19px);

}


/* =====================================================
SYSTEM INFO
===================================================== */

.system-item {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 12px 0;

    border-bottom: 1px solid #f3f4f6;

}


.system-item:last-child {

    border-bottom: none;

}


.system-label {

    color: #6b7280;

    font-size: 9px;

}


.system-value {

    font-size: 9px;

    font-weight: 800;

}


.online {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    color: #059669;

}


.online-dot {

    width: 6px;

    height: 6px;

    background: #10b981;

    border-radius: 50%;

}


/* =====================================================
DANGER
===================================================== */

.danger-card {

    border-color: #fecaca;

}


.danger-title {

    color: #b91c1c;

}


.logout-btn {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    background: #fff1f2;

    color: #b91c1c;

    text-decoration: none;

    border-radius: 8px;

    padding: 9px 13px;

    font-size: 9px;

    font-weight: 700;

}


/* =====================================================
RESPONSIVE
===================================================== */

@media(max-width:900px) {

    .settings-grid {

        grid-template-columns: 1fr;

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


    .info-row {

        flex-direction: column;

        gap: 4px;

    }


    .info-value {

        text-align: left;

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


<a
href="settings.php"
class="active">

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


<div class="page-title">

<h1>
Settings
</h1>

<p>
Manage your BloodConnect admin and application settings.
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
SETTINGS GRID
===================================================== -->

<div class="settings-grid">


<!-- =================================================
ADMIN PROFILE
================================================= -->

<div class="card">


<div class="card-title">

<i class="bi bi-person-circle"></i>

Admin Profile

</div>


<div class="profile-header">


<div class="profile-avatar">

<?php

echo htmlspecialchars(
    $admin_initial
);

?>

</div>


<div>

<div class="profile-name">

<?php

echo htmlspecialchars(
    $admin_name
);

?>

</div>


<div class="profile-role">

SYSTEM ADMINISTRATOR

</div>

</div>


</div>



<div class="info-row">


<span class="info-label">
Name
</span>


<span class="info-value">

<?php

echo htmlspecialchars(
    $admin_name
);

?>

</span>


</div>



<div class="info-row">


<span class="info-label">
Email
</span>


<span class="info-value">

<?php

echo htmlspecialchars(
    $admin_email
);

?>

</span>


</div>



<div class="info-row">


<span class="info-label">
Access
</span>


<span class="info-value">

Full Administrator

</span>


</div>



<div class="info-row">


<span class="info-label">
Status
</span>


<span
class="info-value"
style="color:#059669;">

Active

</span>


</div>


</div>



<!-- =================================================
CHANGE PASSWORD
================================================= -->

<div class="card">


<div class="card-title">

<i class="bi bi-shield-lock-fill"></i>

Change Password

</div>


<form
method="POST"
action="settings.php">


<div class="mb-3">


<label class="form-label">

Current Password

</label>


<input
type="password"
name="current_password"
class="form-control"
placeholder="Enter current password"
required>


</div>



<div class="mb-3">


<label class="form-label">

New Password

</label>


<input
type="password"
name="new_password"
class="form-control"
placeholder="Minimum 6 characters"
required>


</div>



<div class="mb-3">


<label class="form-label">

Confirm New Password

</label>


<input
type="password"
name="confirm_password"
class="form-control"
placeholder="Confirm new password"
required>


</div>



<button
type="submit"
name="change_password"
class="save-btn">

<i class="bi bi-lock-fill"></i>

&nbsp;

Change Password

</button>


</form>


</div>



<!-- =================================================
APPLICATION SETTINGS
================================================= -->

<div class="card full">


<div class="card-title">

<i class="bi bi-sliders"></i>

Application Settings

</div>


<form
method="POST"
action="settings.php">


<div class="row g-3">


<div class="col-md-6">


<label class="form-label">

Application Name

</label>


<input
type="text"
name="site_name"
class="form-control"
value="<?php

echo htmlspecialchars(
    $settings["site_name"]
);

?>"
required>


</div>



<div class="col-md-6">


<label class="form-label">

Support Email

</label>


<input
type="email"
name="site_email"
class="form-control"
value="<?php

echo htmlspecialchars(
    $settings["site_email"]
);

?>"
required>


</div>



<div class="col-md-6">


<label class="form-label">

Support Phone

</label>


<input
type="text"
name="site_phone"
class="form-control"
value="<?php

echo htmlspecialchars(
    $settings["site_phone"]
);

?>">


</div>



<div class="col-md-6">


<label class="form-label">

Default City

</label>


<input
type="text"
name="default_city"
class="form-control"
value="<?php

echo htmlspecialchars(
    $settings["default_city"]
);

?>">


</div>


</div>


<hr
style="
margin:22px 0;
border-color:#f3f4f6;
">


<!-- TOGGLES -->


<div class="setting-item">


<div>


<div class="setting-name">

Allow Blood Requests

</div>


<div class="setting-description">

Allow users to create new blood requests.

</div>


</div>


<label class="switch">


<input
type="checkbox"
name="allow_requests"

<?php

echo $settings["allow_requests"]
    ? "checked"
    : "";

?>


>


<span class="slider"></span>


</label>


</div>



<div class="setting-item">


<div>


<div class="setting-name">

Allow Donor Registration

</div>


<div class="setting-description">

Allow new users to register as blood donors.

</div>


</div>


<label class="switch">


<input
type="checkbox"
name="allow_registration"

<?php

echo $settings["allow_registration"]
    ? "checked"
    : "";

?>


>


<span class="slider"></span>


</label>


</div>



<div class="setting-item">


<div>


<div class="setting-name">

Email Notifications

</div>


<div class="setting-description">

Enable email notifications for important blood requests.

</div>


</div>


<label class="switch">


<input
type="checkbox"
name="email_notifications"

<?php

echo $settings["email_notifications"]
    ? "checked"
    : "";

?>


>


<span class="slider"></span>


</label>


</div>



<div
style="
margin-top:18px;
">


<button
type="submit"
name="save_settings"
class="save-btn">

<i class="bi bi-check-lg"></i>

&nbsp;

Save Application Settings

</button>


</div>


</form>


</div>



<!-- =================================================
SYSTEM INFORMATION
================================================= -->

<div class="card">


<div class="card-title">

<i class="bi bi-server"></i>

System Information

</div>



<div class="system-item">


<span class="system-label">

Application

</span>


<span class="system-value">

BloodConnect

</span>


</div>



<div class="system-item">


<span class="system-label">

Database

</span>


<span class="system-value">

<?php

echo htmlspecialchars(
    $db_name
);

?>

</span>


</div>



<div class="system-item">


<span class="system-label">

PHP Version

</span>


<span class="system-value">

<?php

echo htmlspecialchars(
    $php_version
);

?>

</span>


</div>



<div class="system-item">


<span class="system-label">

MySQL Version

</span>


<span class="system-value">

<?php

echo htmlspecialchars(
    $mysql_version
);

?>

</span>


</div>



<div class="system-item">


<span class="system-label">

Database Status

</span>


<span class="
system-value
online">

<span class="online-dot"></span>

Connected

</span>


</div>


</div>



<!-- =================================================
PROJECT STATISTICS
================================================= -->

<div class="card">


<div class="card-title">

<i class="bi bi-bar-chart-fill"></i>

Project Statistics

</div>



<div class="system-item">


<span class="system-label">

Registered Donors

</span>


<span class="system-value">

<?php

echo $donor_count;

?>

</span>


</div>



<div class="system-item">


<span class="system-label">

Blood Requests

</span>


<span class="system-value">

<?php

echo $request_count;

?>

</span>


</div>



<div class="system-item">


<span class="system-label">

Platform Status

</span>


<span
class="system-value"
style="color:#059669;">

Operational

</span>


</div>



<div class="system-item">


<span class="system-label">

Environment

</span>


<span class="system-value">

Development

</span>


</div>


</div>



<!-- =================================================
DANGER ZONE
================================================= -->

<div class="
card
full
danger-card">


<div class="
card-title
danger-title">

<i class="bi bi-exclamation-triangle-fill"></i>

Danger Zone

</div>


<p
style="
color:#6b7280;
font-size:9px;
line-height:1.7;
margin-bottom:15px;
">

Use the logout option to securely end the current administrator session.

</p>


<a
href="logout.php"
class="logout-btn">

<i class="bi bi-box-arrow-right"></i>

Logout Administrator

</a>


</div>


</div>


</main>


</body>

</html>