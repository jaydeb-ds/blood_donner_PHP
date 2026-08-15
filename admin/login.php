<?php

session_start();


// =====================================================
// ADMIN LOGIN CREDENTIALS
// =====================================================

$admin_email = "admin@gmail.com";

$admin_password = "admin123";


// =====================================================
// IF ALREADY LOGGED IN
// =====================================================

if (
    isset($_SESSION["admin_logged_in"]) &&
    $_SESSION["admin_logged_in"] === true
) {

    header("Location: dashboard.php");

    exit();

}


// =====================================================
// VARIABLES
// =====================================================

$error = "";

$email = "";


// =====================================================
// LOGIN FORM
// =====================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $email =
        trim(
            $_POST["email"] ?? ""
        );


    $password =
        $_POST["password"] ?? "";


    // ================================================
    // CHECK LOGIN
    // ================================================

    if (
        $email === $admin_email &&
        $password === $admin_password
    ) {


        // ============================================
        // CREATE ADMIN SESSION
        // ============================================

        session_regenerate_id(true);


        $_SESSION["admin_logged_in"] =
            true;


        $_SESSION["admin_email"] =
            $admin_email;


        $_SESSION["admin_name"] =
            "BloodConnect Admin";


        // ============================================
        // REDIRECT
        // ============================================

        header(
            "Location: index.php"
        );

        exit();

    }

    else {

        $error =
            "Invalid admin email or password.";

    }

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
Admin Login | BloodConnect
</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">


<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<link
href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
rel="stylesheet">


<style>

* {

    box-sizing: border-box;

}


body {

    margin: 0;

    min-height: 100vh;

    display: flex;

    align-items: center;

    justify-content: center;

    font-family: 'Inter', sans-serif;

    background:
        linear-gradient(
            135deg,
            #fff1f2,
            #f8fafc
        );

}


/* =====================================================
LOGIN CARD
===================================================== */

.login-card {

    width: 420px;

    max-width: calc(100% - 30px);

    background: white;

    border: 1px solid #eee;

    border-radius: 22px;

    padding: 35px;

    box-shadow:
        0 25px 70px
        rgba(0,0,0,.09);

}


/* =====================================================
LOGO
===================================================== */

.logo {

    width: 65px;

    height: 65px;

    margin: 0 auto 18px;

    border-radius: 19px;

    background: #e63946;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 28px;

    box-shadow:
        0 10px 25px
        rgba(230,57,70,.25);

}


/* =====================================================
TITLE
===================================================== */

h1 {

    text-align: center;

    font-size: 25px;

    font-weight: 800;

    margin: 0 0 6px;

}


.subtitle {

    text-align: center;

    color: #6b7280;

    font-size: 11px;

    margin-bottom: 28px;

}


/* =====================================================
ADMIN BADGE
===================================================== */

.admin-badge {

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

    background: #fff1f2;

    color: #e63946;

    padding: 8px;

    border-radius: 9px;

    font-size: 10px;

    font-weight: 700;

    margin-bottom: 20px;

}


/* =====================================================
FORM
===================================================== */

.form-label {

    font-size: 11px;

    font-weight: 700;

    margin-bottom: 7px;

}


.form-control {

    padding: 12px 13px;

    border-radius: 10px;

    border: 1px solid #e5e7eb;

    font-size: 13px;

}


.form-control:focus {

    border-color: #e63946;

    box-shadow:
        0 0 0 3px
        rgba(230,57,70,.1);

}


/* =====================================================
LOGIN BUTTON
===================================================== */

.login-btn {

    width: 100%;

    border: none;

    border-radius: 10px;

    padding: 13px;

    background: #e63946;

    color: white;

    font-size: 12px;

    font-weight: 800;

    margin-top: 8px;

    transition: .2s;

}


.login-btn:hover {

    background: #b91c1c;

    transform: translateY(-1px);

}


/* =====================================================
ERROR
===================================================== */

.error {

    background: #fff1f2;

    border: 1px solid #fecdd3;

    color: #be123c;

    padding: 12px;

    border-radius: 10px;

    font-size: 11px;

    margin-bottom: 18px;

}


/* =====================================================
BACK
===================================================== */

.back {

    text-align: center;

    margin-top: 22px;

}


.back a {

    color: #6b7280;

    font-size: 11px;

    text-decoration: none;

}


.back a:hover {

    color: #e63946;

}


/* =====================================================
FOOTER
===================================================== */

.security-note {

    text-align: center;

    color: #9ca3af;

    font-size: 9px;

    margin-top: 18px;

}


</style>

</head>


<body>


<div class="login-card">


<!-- LOGO -->

<div class="logo">

<i class="bi bi-shield-lock-fill"></i>

</div>


<h1>

Admin Login

</h1>


<div class="subtitle">

BloodConnect Administration Panel

</div>


<div class="admin-badge">

<i class="bi bi-shield-check"></i>

Administrator Access

</div>


<!-- ERROR -->

<?php if (
    !empty($error)
): ?>

<div class="error">

<i class="bi bi-exclamation-circle-fill"></i>

&nbsp;

<?php

echo htmlspecialchars(
    $error
);

?>

</div>

<?php endif; ?>


<!-- FORM -->

<form
method="POST"
action="login.php">


<!-- EMAIL -->

<div class="mb-3">


<label class="form-label">

Admin Email

</label>


<input
type="email"
name="email"
class="form-control"
placeholder="admin@bloodconnect.com"
value="<?php

echo htmlspecialchars(
    $email
);

?>"
required>


</div>



<!-- PASSWORD -->

<div class="mb-3">


<label class="form-label">

Password

</label>


<input
type="password"
name="password"
class="form-control"
placeholder="Enter admin password"
required>


</div>



<!-- BUTTON -->

<button
type="submit"
class="login-btn">

<i class="bi bi-box-arrow-in-right"></i>

&nbsp;

Login to Admin Panel

</button>


</form>


<!-- BACK -->

<div class="back">

<a href="../index.php">

<i class="bi bi-arrow-left"></i>

&nbsp;

Back to BloodConnect

</a>

</div>


<div class="security-note">

<i class="bi bi-lock-fill"></i>

&nbsp;

Authorized administrators only

</div>


</div>


</body>

</html>