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

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "donor") {

    header("Location: ../login.php");
    exit();

}


$user_id = $_SESSION["user_id"];

$message = "";
$message_type = "";


// =====================================================
// UPDATE PROFILE
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // -------------------------------------------------
    // GET FORM DATA
    // -------------------------------------------------

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);

    $blood_group = $_POST["blood_group"];
    $date_of_birth = $_POST["date_of_birth"];
    $gender = $_POST["gender"];
    $weight = $_POST["weight"];

    $address = trim($_POST["address"]);
    $city = trim($_POST["city"]);
    $district = trim($_POST["district"]);
    $state = trim($_POST["state"]);
    $pincode = trim($_POST["pincode"]);

    $availability = $_POST["availability"];

    $last_donation_date =
        !empty($_POST["last_donation_date"])
        ? $_POST["last_donation_date"]
        : NULL;


    // -------------------------------------------------
    // VALIDATION
    // -------------------------------------------------

    if (
        empty($full_name) ||
        empty($email) ||
        empty($phone) ||
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

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    }

    elseif (!preg_match('/^[0-9]{10}$/', $phone)) {

        $message = "Please enter a valid 10-digit phone number.";
        $message_type = "error";

    }

    elseif (!preg_match('/^[0-9]{6}$/', $pincode)) {

        $message = "Please enter a valid 6-digit pincode.";
        $message_type = "error";

    }

    else {

        // -------------------------------------------------
        // CHECK EMAIL / PHONE DUPLICATE
        // -------------------------------------------------

        $check_sql = "
            SELECT id
            FROM users
            WHERE (email = ? OR phone = ?)
            AND id != ?
            LIMIT 1
        ";

        $check_stmt = mysqli_prepare($conn, $check_sql);

        mysqli_stmt_bind_param(
            $check_stmt,
            "ssi",
            $email,
            $phone,
            $user_id
        );

        mysqli_stmt_execute($check_stmt);

        $check_result = mysqli_stmt_get_result($check_stmt);


        if (mysqli_num_rows($check_result) > 0) {

            $message =
                "This email or phone number is already used by another account.";

            $message_type = "error";

        }

        else {

            // -------------------------------------------------
            // START TRANSACTION
            // -------------------------------------------------

            mysqli_begin_transaction($conn);


            try {

                // =================================================
                // UPDATE USERS TABLE
                // =================================================

                $user_sql = "
                    UPDATE users

                    SET
                        full_name = ?,
                        email = ?,
                        phone = ?

                    WHERE id = ?
                ";

                $user_stmt =
                    mysqli_prepare(
                        $conn,
                        $user_sql
                    );

                mysqli_stmt_bind_param(
                    $user_stmt,
                    "sssi",
                    $full_name,
                    $email,
                    $phone,
                    $user_id
                );


                if (!mysqli_stmt_execute($user_stmt)) {

                    throw new Exception(
                        "Unable to update account information."
                    );

                }


                // =================================================
                // UPDATE DONOR PROFILE
                // =================================================

                $donor_sql = "
                    UPDATE donor_profiles

                    SET
                        blood_group = ?,
                        date_of_birth = ?,
                        gender = ?,
                        weight = ?,
                        address = ?,
                        city = ?,
                        district = ?,
                        state = ?,
                        pincode = ?,
                        availability = ?,
                        last_donation_date = ?

                    WHERE user_id = ?
                ";


                $donor_stmt =
                    mysqli_prepare(
                        $conn,
                        $donor_sql
                    );


                // mysqli_stmt_bind_param(
                //     $donor_stmt,
                //     "sssdsssssssi",
                //     $blood_group,
                //     $date_of_birth,
                //     $gender,
                //     $weight,
                //     $address,
                //     $city,
                //     $district,
                //     $state,
                //     $pincode,
                //     $availability,
                //     $last_donation_date,
                //     $user_id
                // );


                /*
                 * Remove the space in the type string.
                 *
                 * Correct type string:
                 * sss dsssssssi
                 *
                 * becomes:
                 * sssdsssssssi
                 */

                // Re-bind correctly

                mysqli_stmt_bind_param(
                    $donor_stmt,
                    "sssdsssssssi",
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
                    $last_donation_date,
                    $user_id
                );


                if (!mysqli_stmt_execute($donor_stmt)) {

                    throw new Exception(
                        "Unable to update donor profile."
                    );

                }


                // =================================================
                // PROFILE IMAGE UPLOAD
                // =================================================

                if (
                    isset($_FILES["profile_image"]) &&
                    $_FILES["profile_image"]["error"] === UPLOAD_ERR_OK
                ) {

                    $file = $_FILES["profile_image"];

                    $file_name = $file["name"];
                    $file_tmp = $file["tmp_name"];
                    $file_size = $file["size"];

                    $file_extension =
                        strtolower(
                            pathinfo(
                                $file_name,
                                PATHINFO_EXTENSION
                            )
                        );


                    // Allowed extensions

                    $allowed_extensions = [
                        "jpg",
                        "jpeg",
                        "png",
                        "webp"
                    ];


                    if (
                        !in_array(
                            $file_extension,
                            $allowed_extensions
                        )
                    ) {

                        throw new Exception(
                            "Only JPG, JPEG, PNG and WEBP images are allowed."
                        );

                    }


                    // Maximum 2 MB

                    if ($file_size > 2 * 1024 * 1024) {

                        throw new Exception(
                            "Profile image must be less than 2 MB."
                        );

                    }


                    // Create upload folder if it doesn't exist

                    $upload_directory =
                        "../assets/uploads/";


                    if (
                        !is_dir($upload_directory)
                    ) {

                        mkdir(
                            $upload_directory,
                            0755,
                            true
                        );

                    }


                    // Generate unique filename

                    $new_file_name =
                        "donor_" .
                        $user_id .
                        "_" .
                        time() .
                        "." .
                        $file_extension;


                    $upload_path =
                        $upload_directory .
                        $new_file_name;


                    // Move file

                    if (
                        !move_uploaded_file(
                            $file_tmp,
                            $upload_path
                        )
                    ) {

                        throw new Exception(
                            "Unable to upload profile image."
                        );

                    }


                    // Save filename to database

                    $image_sql = "
                        UPDATE donor_profiles

                        SET profile_image = ?

                        WHERE user_id = ?
                    ";


                    $image_stmt =
                        mysqli_prepare(
                            $conn,
                            $image_sql
                        );


                    mysqli_stmt_bind_param(
                        $image_stmt,
                        "si",
                        $new_file_name,
                        $user_id
                    );


                    if (
                        !mysqli_stmt_execute(
                            $image_stmt
                        )
                    ) {

                        throw new Exception(
                            "Unable to save profile image."
                        );

                    }

                }


                // =================================================
                // COMMIT
                // =================================================

                mysqli_commit($conn);


                // Update session name

                $_SESSION["full_name"] =
                    $full_name;

                $_SESSION["email"] =
                    $email;

                $_SESSION["phone"] =
                    $phone;


                $message =
                    "Your profile has been updated successfully.";

                $message_type = "success";


            } catch (Exception $e) {

                mysqli_rollback($conn);

                $message =
                    $e->getMessage();

                $message_type = "error";

            }

        }

    }

}


// =====================================================
// GET CURRENT DONOR DATA
// =====================================================

$sql = "
    SELECT

        u.full_name,
        u.email,
        u.phone,

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

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
My Profile | BloodConnect
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

/* =================================================
GLOBAL
================================================= */

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


/* =================================================
SIDEBAR
================================================= */

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


/* =================================================
MAIN
================================================= */

.main {

    margin-left: 250px;

    min-height: 100vh;

}


/* =================================================
TOPBAR
================================================= */

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


/* =================================================
CONTENT
================================================= */

.content {

    padding: 35px;

}


/* =================================================
PROFILE HEADER
================================================= */

.profile-header {

    background:
        linear-gradient(
            135deg,
            #991b1b,
            #e63946
        );

    border-radius: 20px;

    padding: 30px;

    color: white;

    display: flex;

    align-items: center;

    gap: 25px;

    margin-bottom: 25px;

}


.profile-picture {

    width: 95px;

    height: 95px;

    border-radius: 25px;

    background: rgba(255,255,255,0.15);

    border: 2px solid rgba(255,255,255,0.4);

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;

    flex-shrink: 0;

}


.profile-picture img {

    width: 100%;

    height: 100%;

    object-fit: cover;

}


.profile-picture i {

    font-size: 40px;

}


.profile-header h2 {

    font-size: 26px;

    font-weight: 800;

    margin-bottom: 5px;

}


.profile-header p {

    color: #fecaca;

    margin: 0;

}


/* =================================================
FORM CARD
================================================= */

.form-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 18px;

    padding: 30px;

}


.form-section {

    margin-bottom: 35px;

}


.section-title {

    display: flex;

    align-items: center;

    gap: 12px;

    font-size: 18px;

    font-weight: 800;

    padding-bottom: 15px;

    border-bottom: 1px solid #eee;

    margin-bottom: 25px;

}


.section-title i {

    width: 40px;

    height: 40px;

    border-radius: 10px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

}


/* =================================================
FORM
================================================= */

.form-label {

    font-size: 13px;

    font-weight: 600;

    margin-bottom: 7px;

}


.form-control,
.form-select {

    border: 1px solid #e5e7eb;

    border-radius: 10px;

    padding: 12px 13px;

    font-size: 14px;

}


.form-control:focus,
.form-select:focus {

    border-color: var(--primary);

    box-shadow:
        0 0 0 3px rgba(230,57,70,0.1);

}


/* =================================================
BLOOD GROUP
================================================= */

.blood-group-grid {

    display: grid;

    grid-template-columns:
        repeat(8, 1fr);

    gap: 8px;

}


.blood-option input {

    display: none;

}


.blood-option label {

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 11px 5px;

    border: 1px solid #e5e7eb;

    border-radius: 9px;

    font-weight: 700;

    font-size: 13px;

    cursor: pointer;

}


.blood-option input:checked + label {

    background: var(--primary);

    border-color: var(--primary);

    color: white;

}


.blood-option label:hover {

    border-color: var(--primary);

}


/* =================================================
BUTTON
================================================= */

.save-btn {

    background: var(--primary);

    border: none;

    color: white;

    border-radius: 10px;

    padding: 13px 25px;

    font-weight: 700;

    transition: 0.3s;

}


.save-btn:hover {

    background: var(--dark-red);

    transform: translateY(-2px);

}


/* =================================================
ALERT
================================================= */

.alert-custom {

    border-radius: 10px;

    padding: 13px 16px;

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


/* =================================================
UPLOAD
================================================= */

.image-upload {

    border: 1px dashed #d1d5db;

    border-radius: 12px;

    padding: 15px;

    background: #fafafa;

}


.image-upload small {

    color: #9ca3af;

}


/* =================================================
MOBILE
================================================= */

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


    .blood-group-grid {

        grid-template-columns:
            repeat(4, 1fr);

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


    .profile-header {

        padding: 25px 20px;

    }


    .profile-header h2 {

        font-size: 21px;

    }


    .form-card {

        padding: 20px;

    }


    .blood-group-grid {

        grid-template-columns:
            repeat(4, 1fr);

    }

}

</style>

</head>


<body>


<!-- =================================================
SIDEBAR
================================================= -->

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

<a
href="profile.php"
class="active">

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



<!-- =================================================
MAIN
================================================= -->

<main class="main">


<!-- =================================================
TOPBAR
================================================= -->

<header class="topbar">


<div class="page-title">

My Profile

</div>


<div class="top-user">


<div class="user-avatar">

<?php

echo strtoupper(
    substr(
        $donor["full_name"],
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
    $donor["full_name"]
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



<!-- =================================================
CONTENT
================================================= -->

<div class="content">


<!-- =================================================
PROFILE HEADER
================================================= -->

<div class="profile-header">


<div class="profile-picture">


<?php if (!empty($donor["profile_image"])): ?>

<img
src="../assets/uploads/<?php
echo htmlspecialchars(
    $donor["profile_image"]
);
?>"
alt="Profile Picture">

<?php else: ?>

<i class="bi bi-person-fill"></i>

<?php endif; ?>


</div>


<div>

<h2>

<?php

echo htmlspecialchars(
    $donor["full_name"]
);

?>

</h2>


<p>

<i class="bi bi-droplet-fill"></i>

<?php

echo htmlspecialchars(
    $donor["blood_group"]
);

?>

&nbsp; • &nbsp;

<?php

echo htmlspecialchars(
    $donor["city"]
);

?>

</p>

</div>


</div>



<!-- =================================================
FORM CARD
================================================= -->

<div class="form-card">


<!-- ALERT -->

<?php if ($message_type === "success"): ?>

<div class="alert-custom alert-success-custom">

<i class="bi bi-check-circle-fill"></i>

&nbsp;

<?php

echo htmlspecialchars(
    $message
);

?>

</div>

<?php endif; ?>


<?php if ($message_type === "error"): ?>

<div class="alert-custom alert-error-custom">

<i class="bi bi-exclamation-circle-fill"></i>

&nbsp;

<?php

echo htmlspecialchars(
    $message
);

?>

</div>

<?php endif; ?>



<form
method="POST"
action="profile.php"
enctype="multipart/form-data">


<!-- =================================================
PERSONAL INFORMATION
================================================= -->

<div class="form-section">


<div class="section-title">

<i class="bi bi-person-fill"></i>

Personal Information

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
value="<?php
echo htmlspecialchars(
    $donor["full_name"]
);
?>"
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
value="<?php
echo htmlspecialchars(
    $donor["email"]
);
?>"
required>

</div>


<!-- PHONE -->

<div class="col-md-6">

<label class="form-label">

Phone Number *

</label>

<input
type="text"
name="phone"
class="form-control"
maxlength="10"
value="<?php
echo htmlspecialchars(
    $donor["phone"]
);
?>"
required>

</div>


<!-- DATE OF BIRTH -->

<div class="col-md-6">

<label class="form-label">

Date of Birth *

</label>

<input
type="date"
name="date_of_birth"
class="form-control"
value="<?php
echo htmlspecialchars(
    $donor["date_of_birth"]
);
?>"
required>

</div>


<!-- GENDER -->

<div class="col-md-6">

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

<option
value="Male"
<?php
echo $donor["gender"] === "Male"
    ? "selected"
    : "";
?>>

Male

</option>

<option
value="Female"
<?php
echo $donor["gender"] === "Female"
    ? "selected"
    : "";
?>>

Female

</option>

<option
value="Other"
<?php
echo $donor["gender"] === "Other"
    ? "selected"
    : "";
?>>

Other

</option>

</select>

</div>


<!-- WEIGHT -->

<div class="col-md-6">

<label class="form-label">

Weight (kg) *

</label>

<input
type="number"
name="weight"
class="form-control"
step="0.1"
min="1"
max="300"
value="<?php
echo htmlspecialchars(
    $donor["weight"]
);
?>"
required>

</div>


</div>

</div>



<!-- =================================================
DONOR INFORMATION
================================================= -->

<div class="form-section">


<div class="section-title">

<i class="bi bi-droplet-fill"></i>

Donor Information

</div>


<!-- BLOOD GROUP -->

<div class="mb-4">

<label class="form-label">

Blood Group *

</label>


<div class="blood-group-grid">


<?php

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

foreach ($blood_groups as $group):

?>


<div class="blood-option">

<input
type="radio"
name="blood_group"
id="blood_<?php
echo str_replace(
    ['+', '-'],
    ['plus', 'minus'],
    $group
);
?>"
value="<?php
echo $group;
?>"
<?php

echo $donor["blood_group"] === $group
    ? "checked"
    : "";

?>>

<label
for="blood_<?php
echo str_replace(
    ['+', '-'],
    ['plus', 'minus'],
    $group
);
?>">

<?php

echo $group;

?>

</label>

</div>


<?php endforeach; ?>


</div>

</div>



<div class="row g-3">


<!-- LAST DONATION -->

<div class="col-md-6">

<label class="form-label">

Last Donation Date

</label>

<input
type="date"
name="last_donation_date"
class="form-control"
value="<?php
echo htmlspecialchars(
    $donor["last_donation_date"] ?? ""
);
?>">

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

<option
value="Available"
<?php
echo $donor["availability"] === "Available"
    ? "selected"
    : "";
?>>

Available - I can donate

</option>

<option
value="Not Available"
<?php
echo $donor["availability"] === "Not Available"
    ? "selected"
    : "";
?>>

Not Available

</option>

</select>

</div>


</div>

</div>



<!-- =================================================
LOCATION
================================================= -->

<div class="form-section">


<div class="section-title">

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
required><?php

echo htmlspecialchars(
    $donor["address"]
);

?></textarea>

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
value="<?php
echo htmlspecialchars(
    $donor["city"]
);
?>"
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
value="<?php
echo htmlspecialchars(
    $donor["district"]
);
?>"
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


<?php

$states = [

    "Andhra Pradesh",
    "Arunachal Pradesh",
    "Assam",
    "Bihar",
    "Chhattisgarh",
    "Delhi",
    "Goa",
    "Gujarat",
    "Haryana",
    "Himachal Pradesh",
    "Jharkhand",
    "Karnataka",
    "Kerala",
    "Madhya Pradesh",
    "Maharashtra",
    "Manipur",
    "Meghalaya",
    "Mizoram",
    "Nagaland",
    "Odisha",
    "Punjab",
    "Rajasthan",
    "Sikkim",
    "Tamil Nadu",
    "Telangana",
    "Tripura",
    "Uttar Pradesh",
    "Uttarakhand",
    "West Bengal"

];


foreach ($states as $state):

?>


<option
value="<?php
echo htmlspecialchars($state);
?>"
<?php

echo $donor["state"] === $state
    ? "selected"
    : "";

?>>

<?php

echo htmlspecialchars($state);

?>

</option>


<?php endforeach; ?>


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
maxlength="6"
value="<?php
echo htmlspecialchars(
    $donor["pincode"]
);
?>"
required>

</div>


</div>

</div>



<!-- =================================================
PROFILE IMAGE
================================================= -->

<div class="form-section">


<div class="section-title">

<i class="bi bi-camera-fill"></i>

Profile Picture

</div>


<div class="image-upload">


<label class="form-label">

Upload New Profile Picture

</label>


<input
type="file"
name="profile_image"
class="form-control"
accept=".jpg,.jpeg,.png,.webp">


<small>

Allowed formats: JPG, JPEG, PNG, WEBP.
Maximum size: 2 MB.

</small>

</div>

</div>



<!-- =================================================
SAVE
================================================= -->

<div class="d-flex justify-content-end gap-2">


<a
href="dashboard.php"
class="btn btn-light">

Cancel

</a>


<button
type="submit"
class="save-btn">

<i class="bi bi-check-lg"></i>

&nbsp;

Save Changes

</button>


</div>


</form>

</div>

</div>

</main>



<!-- Bootstrap -->

<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>