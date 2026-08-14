<?php

include "config/database.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ==============================
    // GET FORM DATA
    // ==============================

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);

    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    $blood_group = $_POST["blood_group"];
    $date_of_birth = $_POST["date_of_birth"];
    $gender = $_POST["gender"];
    $weight = $_POST["weight"];

    $address = trim($_POST["address"]);
    $city = trim($_POST["city"]);
    $district = trim($_POST["district"]);
    $state = trim($_POST["state"]);
    $pincode = trim($_POST["pincode"]);

    $last_donation_date =
        !empty($_POST["last_donation_date"])
        ? $_POST["last_donation_date"]
        : NULL;

    $availability = $_POST["availability"];


    // ==============================
    // BASIC VALIDATION
    // ==============================

    if (
        empty($full_name) ||
        empty($email) ||
        empty($phone) ||
        empty($password) ||
        empty($confirm_password) ||
        empty($blood_group) ||
        empty($date_of_birth) ||
        empty($gender) ||
        empty($weight) ||
        empty($address) ||
        empty($city) ||
        empty($district) ||
        empty($state) ||
        empty($pincode)
    ) {

        $message = "Please fill in all required fields.";
        $message_type = "error";

    }

    // ==============================
    // EMAIL VALIDATION
    // ==============================

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    }

    // ==============================
    // PASSWORD VALIDATION
    // ==============================

    elseif (strlen($password) < 6) {

        $message = "Password must contain at least 6 characters.";
        $message_type = "error";

    }

    elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";
        $message_type = "error";

    }

    // ==============================
    // PHONE VALIDATION
    // ==============================

    elseif (!preg_match('/^[0-9]{10}$/', $phone)) {

        $message = "Please enter a valid 10-digit phone number.";
        $message_type = "error";

    }

    // ==============================
    // PINCODE VALIDATION
    // ==============================

    elseif (!preg_match('/^[0-9]{6}$/', $pincode)) {

        $message = "Please enter a valid 6-digit pincode.";
        $message_type = "error";

    }

    else {

        // ==============================
        // CHECK DUPLICATE EMAIL / PHONE
        // ==============================

        $check_sql = "
            SELECT id
            FROM users
            WHERE email = ? OR phone = ?
        ";

        $check_stmt = mysqli_prepare($conn, $check_sql);

        mysqli_stmt_bind_param(
            $check_stmt,
            "ss",
            $email,
            $phone
        );

        mysqli_stmt_execute($check_stmt);

        $check_result = mysqli_stmt_get_result($check_stmt);


        if (mysqli_num_rows($check_result) > 0) {

            $message = "Email or phone number is already registered.";
            $message_type = "error";

        }

        else {

            // ==============================
            // HASH PASSWORD
            // ==============================

            $hashed_password =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            // ==============================
            // START TRANSACTION
            // ==============================

            mysqli_begin_transaction($conn);


            try {

                // ==============================
                // INSERT USER
                // ==============================

                $user_sql = "
                    INSERT INTO users
                    (
                        full_name,
                        email,
                        phone,
                        password,
                        role,
                        status
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        'donor',
                        'active'
                    )
                ";

                $user_stmt =
                    mysqli_prepare(
                        $conn,
                        $user_sql
                    );

                mysqli_stmt_bind_param(
                    $user_stmt,
                    "ssss",
                    $full_name,
                    $email,
                    $phone,
                    $hashed_password
                );

                if (!mysqli_stmt_execute($user_stmt)) {

                    throw new Exception(
                        "Unable to create account."
                    );
                }


                // ==============================
                // GET USER ID
                // ==============================

                $user_id =
                    mysqli_insert_id($conn);


                // ==============================
                // INSERT DONOR PROFILE
                // ==============================

                $donor_sql = "
                    INSERT INTO donor_profiles
                    (
                        user_id,
                        blood_group,
                        date_of_birth,
                        gender,
                        weight,
                        address,
                        city,
                        district,
                        state,
                        pincode,
                        availability,
                        last_donation_date
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ";

                $donor_stmt =
                    mysqli_prepare(
                        $conn,
                        $donor_sql
                    );

                mysqli_stmt_bind_param(
                    $donor_stmt,
                    "isssdsssssss",
                    $user_id,
                    $blood_group,
                    $date_of_birth,
                    $gender,
                    $weight,
                    $address,
                    $city,
                    $district,
                    $state,
                    $pincode,
                    $availability,
                    $last_donation_date
                );


                if (!mysqli_stmt_execute($donor_stmt)) {

                    throw new Exception(
                        "Unable to create donor profile."
                    );
                }


                // ==============================
                // COMMIT
                // ==============================

                mysqli_commit($conn);


                $message =
                    "Registration successful! You can now login.";

                $message_type = "success";


            } catch (Exception $e) {

                // ==============================
                // ROLLBACK
                // ==============================

                mysqli_rollback($conn);

                $message =
                    "Registration failed. Please try again.";

                $message_type = "error";
            }
        }
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

<title>Become a Donor | BloodConnect</title>


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

    font-family: 'Inter', sans-serif;

    background:
        linear-gradient(
            135deg,
            #fff1f2,
            #ffffff
        );

    color: #1f2937;

}

:root {

    --primary: #e63946;

    --dark-red: #b91c1c;

    --light-red: #fff1f2;

}


/* ========================================
NAVBAR
======================================== */

.navbar {

    background: rgba(255,255,255,0.96);

    backdrop-filter: blur(15px);

    box-shadow:
        0 2px 20px rgba(0,0,0,0.06);

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


/* ========================================
REGISTER SECTION
======================================== */

.register-section {

    padding: 70px 0 100px;

}


/* ========================================
REGISTER HEADER
======================================== */

.register-header {

    text-align: center;

    max-width: 700px;

    margin: auto auto 40px;

}

.register-icon {

    width: 75px;

    height: 75px;

    background: var(--primary);

    color: white;

    border-radius: 22px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: auto auto 20px;

    font-size: 32px;

    box-shadow:
        0 12px 30px rgba(230,57,70,0.25);

}

.register-header h1 {

    font-size: 42px;

    font-weight: 800;

    margin-bottom: 12px;

}

.register-header p {

    color: #6b7280;

    line-height: 1.7;

}


/* ========================================
FORM CARD
======================================== */

.form-card {

    background: white;

    border-radius: 24px;

    padding: 40px;

    box-shadow:
        0 20px 60px rgba(0,0,0,0.08);

    border: 1px solid #f1f1f1;

}


/* ========================================
FORM SECTION
======================================== */

.form-section {

    margin-bottom: 35px;

}

.form-section-title {

    display: flex;

    align-items: center;

    gap: 12px;

    font-size: 19px;

    font-weight: 800;

    margin-bottom: 25px;

    padding-bottom: 12px;

    border-bottom: 1px solid #eee;

}

.form-section-title i {

    width: 40px;

    height: 40px;

    border-radius: 10px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

}


/* ========================================
FORM
======================================== */

.form-label {

    font-size: 14px;

    font-weight: 600;

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

    box-shadow:
        0 0 0 3px rgba(230,57,70,0.1);

}


/* ========================================
PASSWORD
======================================== */

.password-wrapper {

    position: relative;

}

.password-wrapper .form-control {

    padding-right: 45px;

}

.password-toggle {

    position: absolute;

    right: 14px;

    top: 50%;

    transform: translateY(-50%);

    border: none;

    background: transparent;

    color: #6b7280;

}


/* ========================================
BLOOD GROUP
======================================== */

.blood-group-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 10px;

}

.blood-option input {

    display: none;

}

.blood-option label {

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 13px;

    border: 1px solid #e5e7eb;

    border-radius: 10px;

    font-weight: 700;

    color: #374151;

    cursor: pointer;

    transition: 0.2s;

}

.blood-option input:checked + label {

    background: var(--primary);

    color: white;

    border-color: var(--primary);

}

.blood-option label:hover {

    border-color: var(--primary);

    color: var(--primary);

}


/* ========================================
CHECKBOX
======================================== */

.terms {

    background: #fafafa;

    border-radius: 12px;

    padding: 15px;

    font-size: 13px;

    color: #6b7280;

}

.terms input {

    accent-color: var(--primary);

}


/* ========================================
SUBMIT
======================================== */

.register-btn {

    width: 100%;

    border: none;

    background: var(--primary);

    color: white;

    padding: 14px;

    border-radius: 10px;

    font-weight: 700;

    font-size: 16px;

    transition: 0.3s;

}

.register-btn:hover {

    background: var(--dark-red);

    transform: translateY(-2px);

    box-shadow:
        0 10px 25px rgba(230,57,70,0.2);

}


/* ========================================
ALERT
======================================== */

.alert-custom {

    border-radius: 12px;

    padding: 14px 18px;

    margin-bottom: 25px;

    font-size: 14px;

}

.alert-success-custom {

    background: #ecfdf5;

    border: 1px solid #a7f3d0;

    color: #047857;

}

.alert-error-custom {

    background: #fff1f2;

    border: 1px solid #fecdd3;

    color: #be123c;

}


/* ========================================
LOGIN LINK
======================================== */

.login-text {

    text-align: center;

    margin-top: 25px;

    color: #6b7280;

    font-size: 14px;

}

.login-text a {

    color: var(--primary);

    font-weight: 700;

    text-decoration: none;

}


/* ========================================
MOBILE
======================================== */

@media(max-width:768px) {

    .register-section {

        padding: 50px 15px 70px;

    }

    .register-header h1 {

        font-size: 34px;

    }

    .form-card {

        padding: 25px 20px;

    }

    .blood-group-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }

}

</style>

</head>


<body>


<!-- ========================================
NAVBAR
======================================== -->

<nav class="navbar navbar-expand-lg">

<div class="container">


<a
class="navbar-brand"
href="index.php">

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


<div
class="collapse navbar-collapse"
id="navbarMenu">

<ul class="navbar-nav ms-auto">


<li class="nav-item">

<a
class="nav-link"
href="index.php">

Home

</a>

</li>


<li class="nav-item">

<a
class="nav-link"
href="search.php">

Find Donor

</a>

</li>


<li class="nav-item">

<a
class="nav-link"
href="about.php">

About

</a>

</li>


<li class="nav-item">

<a
class="nav-link"
href="contact.php">

Contact

</a>

</li>


</ul>

</div>

</div>

</nav>



<!-- ========================================
REGISTER
======================================== -->

<section class="register-section">

<div class="container">


<!-- HEADER -->

<div class="register-header">

<div class="register-icon">

<i class="bi bi-heart-fill"></i>

</div>


<h1>
Become a Blood Donor
</h1>


<p>

Your one donation can make a difference.
Register as a donor and help people find
the blood they need.

</p>

</div>



<!-- FORM -->

<div class="row justify-content-center">

<div class="col-xl-9 col-lg-10">


<div class="form-card">


<!-- ALERT -->

<?php if ($message_type == "success"): ?>

<div class="alert-custom alert-success-custom">

<i class="bi bi-check-circle-fill"></i>

<?php echo $message; ?>

&nbsp;

<a
href="login.php"
style="color:#047857;font-weight:700;">

Login Now

</a>

</div>

<?php endif; ?>


<?php if ($message_type == "error"): ?>

<div class="alert-custom alert-error-custom">

<i class="bi bi-exclamation-circle-fill"></i>

<?php echo $message; ?>

</div>

<?php endif; ?>



<form
method="POST"
action="register.php"
id="registerForm">


<!-- ========================================
ACCOUNT INFORMATION
======================================== -->

<div class="form-section">


<div class="form-section-title">

<i class="bi bi-person"></i>

Account Information

</div>


<div class="row g-3">


<!-- NAME -->

<div class="col-md-6">

<label class="form-label">
Full Name *
</label>

<input
type="text"
name="full_name"
class="form-control"
placeholder="Enter your full name"
required>

</div>


<!-- EMAIL -->

<div class="col-md-6">

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


<!-- PHONE -->

<div class="col-md-6">

<label class="form-label">
Phone Number *
</label>

<input
type="tel"
name="phone"
class="form-control"
placeholder="10 digit mobile number"
maxlength="10"
required>

</div>


<!-- PASSWORD -->

<div class="col-md-6">

<label class="form-label">
Password *
</label>

<div class="password-wrapper">

<input
type="password"
name="password"
id="password"
class="form-control"
placeholder="Minimum 6 characters"
required>

<button
type="button"
class="password-toggle"
onclick="togglePassword('password', this)">

<i class="bi bi-eye"></i>

</button>

</div>

</div>


<!-- CONFIRM PASSWORD -->

<div class="col-md-6">

<label class="form-label">
Confirm Password *
</label>

<div class="password-wrapper">

<input
type="password"
name="confirm_password"
id="confirm_password"
class="form-control"
placeholder="Confirm your password"
required>

<button
type="button"
class="password-toggle"
onclick="togglePassword('confirm_password', this)">

<i class="bi bi-eye"></i>

</button>

</div>

</div>


</div>

</div>



<!-- ========================================
DONOR INFORMATION
======================================== -->

<div class="form-section">


<div class="form-section-title">

<i class="bi bi-droplet-fill"></i>

Donor Information

</div>


<!-- BLOOD GROUP -->

<div class="mb-4">

<label class="form-label">

Blood Group *

</label>


<div class="blood-group-grid">


<div class="blood-option">

<input
type="radio"
name="blood_group"
id="a_positive"
value="A+"
required>

<label for="a_positive">
A+
</label>

</div>


<div class="blood-option">

<input
type="radio"
name="blood_group"
id="a_negative"
value="A-">

<label for="a_negative">
A-
</label>

</div>


<div class="blood-option">

<input
type="radio"
name="blood_group"
id="b_positive"
value="B+">

<label for="b_positive">
B+
</label>

</div>


<div class="blood-option">

<input
type="radio"
name="blood_group"
id="b_negative"
value="B-">

<label for="b_negative">
B-
</label>

</div>


<div class="blood-option">

<input
type="radio"
name="blood_group"
id="o_positive"
value="O+">

<label for="o_positive">
O+
</label>

</div>


<div class="blood-option">

<input
type="radio"
name="blood_group"
id="o_negative"
value="O-">

<label for="o_negative">
O-
</label>

</div>


<div class="blood-option">

<input
type="radio"
name="blood_group"
id="ab_positive"
value="AB+">

<label for="ab_positive">
AB+
</label>

</div>


<div class="blood-option">

<input
type="radio"
name="blood_group"
id="ab_negative"
value="AB-">

<label for="ab_negative">
AB-
</label>

</div>


</div>

</div>



<div class="row g-3">


<!-- DOB -->

<div class="col-md-4">

<label class="form-label">

Date of Birth *

</label>

<input
type="date"
name="date_of_birth"
class="form-control"
required>

</div>


<!-- GENDER -->

<div class="col-md-4">

<label class="form-label">

Gender *

</label>

<select
name="gender"
class="form-select"
required>

<option value="">
Select Gender
</option>

<option value="Male">
Male
</option>

<option value="Female">
Female
</option>

<option value="Other">
Other
</option>

</select>

</div>


<!-- WEIGHT -->

<div class="col-md-4">

<label class="form-label">

Weight (kg) *

</label>

<input
type="number"
name="weight"
class="form-control"
placeholder="e.g. 60"
min="1"
max="300"
step="0.1"
required>

</div>


<!-- LAST DONATION -->

<div class="col-md-6">

<label class="form-label">

Last Donation Date

</label>

<input
type="date"
name="last_donation_date"
class="form-control">

<small class="text-muted">

Leave blank if you have never donated.

</small>

</div>


<!-- AVAILABILITY -->

<div class="col-md-6">

<label class="form-label">

Current Availability *

</label>

<select
name="availability"
class="form-select"
required>

<option value="Available">
Available - I can donate
</option>

<option value="Not Available">
Not Available
</option>

</select>

</div>


</div>

</div>



<!-- ========================================
LOCATION
======================================== -->

<div class="form-section">


<div class="form-section-title">

<i class="bi bi-geo-alt-fill"></i>

Location Information

</div>


<div class="row g-3">


<!-- ADDRESS -->

<div class="col-12">

<label class="form-label">

Address *

</label>

<textarea
name="address"
class="form-control"
rows="3"
placeholder="Enter your complete address"
required></textarea>

</div>


<!-- CITY -->

<div class="col-md-6">

<label class="form-label">

City *

</label>

<input
type="text"
name="city"
class="form-control"
placeholder="Enter city"
required>

</div>


<!-- DISTRICT -->

<div class="col-md-6">

<label class="form-label">

District *

</label>

<input
type="text"
name="district"
class="form-control"
placeholder="Enter district"
required>

</div>


<!-- STATE -->

<div class="col-md-6">

<label class="form-label">

State *

</label>

<select
name="state"
class="form-select"
required>

<option value="">
Select State
</option>

<option value="Andhra Pradesh">
Andhra Pradesh
</option>

<option value="Arunachal Pradesh">
Arunachal Pradesh
</option>

<option value="Assam">
Assam
</option>

<option value="Bihar">
Bihar
</option>

<option value="Chhattisgarh">
Chhattisgarh
</option>

<option value="Delhi">
Delhi
</option>

<option value="Goa">
Goa
</option>

<option value="Gujarat">
Gujarat
</option>

<option value="Haryana">
Haryana
</option>

<option value="Himachal Pradesh">
Himachal Pradesh
</option>

<option value="Jharkhand">
Jharkhand
</option>

<option value="Karnataka">
Karnataka
</option>

<option value="Kerala">
Kerala
</option>

<option value="Madhya Pradesh">
Madhya Pradesh
</option>

<option value="Maharashtra">
Maharashtra
</option>

<option value="Manipur">
Manipur
</option>

<option value="Meghalaya">
Meghalaya
</option>

<option value="Mizoram">
Mizoram
</option>

<option value="Nagaland">
Nagaland
</option>

<option value="Odisha">
Odisha
</option>

<option value="Punjab">
Punjab
</option>

<option value="Rajasthan">
Rajasthan
</option>

<option value="Sikkim">
Sikkim
</option>

<option value="Tamil Nadu">
Tamil Nadu
</option>

<option value="Telangana">
Telangana
</option>

<option value="Tripura">
Tripura
</option>

<option value="Uttar Pradesh">
Uttar Pradesh
</option>

<option value="Uttarakhand">
Uttarakhand
</option>

<option value="West Bengal">
West Bengal
</option>

</select>

</div>


<!-- PINCODE -->

<div class="col-md-6">

<label class="form-label">

Pincode *

</label>

<input
type="text"
name="pincode"
class="form-control"
placeholder="6 digit pincode"
maxlength="6"
required>

</div>


</div>

</div>



<!-- ========================================
TERMS
======================================== -->

<div class="terms mb-4">

<div class="form-check">

<input
class="form-check-input"
type="checkbox"
id="terms"
required>

<label
class="form-check-label"
for="terms">

I confirm that the information provided by me
is correct and I am voluntarily registering as
a blood donor.

</label>

</div>

</div>



<!-- SUBMIT -->

<button
type="submit"
class="register-btn">

<i class="bi bi-heart-fill"></i>

&nbsp; Register as a Blood Donor

</button>


</form>



<div class="login-text">

Already have an account?

<a href="login.php">
Login here
</a>

</div>


</div>

</div>

</div>

</div>

</section>



<!-- ========================================
JAVASCRIPT
======================================== -->

<script>

function togglePassword(inputId, button) {

    const input =
        document.getElementById(inputId);

    const icon =
        button.querySelector("i");


    if (input.type === "password") {

        input.type = "text";

        icon.classList.remove("bi-eye");

        icon.classList.add("bi-eye-slash");

    } else {

        input.type = "password";

        icon.classList.remove("bi-eye-slash");

        icon.classList.add("bi-eye");

    }

}


document
.getElementById("registerForm")
.addEventListener("submit", function(event) {

    const password =
        document.getElementById("password").value;

    const confirmPassword =
        document.getElementById("confirm_password").value;


    if (password !== confirmPassword) {

        event.preventDefault();

        alert("Passwords do not match.");

    }

});

</script>


<!-- Bootstrap JS -->

<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>