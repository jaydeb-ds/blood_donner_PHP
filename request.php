<?php

session_start();

require_once "./config/database.php";


// =====================================================
// GET DONOR ID
// =====================================================

if (
    !isset($_GET["donor_id"]) ||
    !is_numeric($_GET["donor_id"])
) {

    header("Location: ../blood/search.php");
    exit();

}

$donor_id = (int) $_GET["donor_id"];


// =====================================================
// GET SELECTED DONOR
// =====================================================

$donor_sql = "
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
        d.availability

    FROM users u

    INNER JOIN donor_profiles d
        ON u.id = d.user_id

    WHERE u.id = ?

    AND u.status = 'active'

    LIMIT 1
";


$donor_stmt = mysqli_prepare(
    $conn,
    $donor_sql
);


mysqli_stmt_bind_param(
    $donor_stmt,
    "i",
    $donor_id
);


mysqli_stmt_execute(
    $donor_stmt
);


$donor_result = mysqli_stmt_get_result(
    $donor_stmt
);


if (
    mysqli_num_rows($donor_result) !== 1
) {

    mysqli_stmt_close($donor_stmt);

    die("Donor not found.");

}


$donor = mysqli_fetch_assoc(
    $donor_result
);


mysqli_stmt_close($donor_stmt);


// =====================================================
// VARIABLES
// =====================================================

$error = "";

$success = "";


// =====================================================
// FORM VALUES
// =====================================================

$requester_name =
    trim($_POST["requester_name"] ?? "");

$requester_phone =
    trim($_POST["requester_phone"] ?? "");

$requester_email =
    trim($_POST["requester_email"] ?? "");

$patient_name =
    trim($_POST["patient_name"] ?? "");

$blood_group =
    trim($_POST["blood_group"] ?? "");

$units_required =
    trim($_POST["units_required"] ?? "1");

$hospital_name =
    trim($_POST["hospital_name"] ?? "");

$hospital_address =
    trim($_POST["hospital_address"] ?? "");

$city =
    trim($_POST["city"] ?? "");

$district =
    trim($_POST["district"] ?? "");

$state =
    trim($_POST["state"] ?? "");

$pincode =
    trim($_POST["pincode"] ?? "");

$urgency =
    trim($_POST["urgency"] ?? "Normal");

$required_date =
    trim($_POST["required_date"] ?? "");

$additional_message =
    trim($_POST["additional_message"] ?? "");


// =====================================================
// OPTIONS
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


$urgency_options = [

    "Normal",
    "Urgent",
    "Emergency"

];


// =====================================================
// FORM SUBMISSION
// =====================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {


    // =================================================
    // REQUIRED FIELDS
    // =================================================

    if (
        empty($requester_name) ||
        empty($requester_phone) ||
        empty($patient_name) ||
        empty($blood_group) ||
        empty($units_required) ||
        empty($hospital_name) ||
        empty($hospital_address) ||
        empty($city) ||
        empty($district) ||
        empty($state) ||
        empty($pincode) ||
        empty($required_date)
    ) {

        $error =
            "Please fill in all required fields.";

    }


    // =================================================
    // PHONE
    // =================================================

    elseif (
        !preg_match(
            "/^[0-9]{10}$/",
            $requester_phone
        )
    ) {

        $error =
            "Please enter a valid 10-digit phone number.";

    }


    // =================================================
    // EMAIL
    // =================================================

    elseif (
        !empty($requester_email) &&
        !filter_var(
            $requester_email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";

    }


    // =================================================
    // BLOOD GROUP
    // =================================================

    elseif (
        !in_array(
            $blood_group,
            $blood_groups,
            true
        )
    ) {

        $error =
            "Please select a valid blood group.";

    }


    // =================================================
    // UNITS
    // =================================================

    elseif (
        !is_numeric($units_required) ||
        (int)$units_required < 1 ||
        (int)$units_required > 20
    ) {

        $error =
            "Units required must be between 1 and 20.";

    }


    // =================================================
    // PINCODE
    // =================================================

    elseif (
        !preg_match(
            "/^[0-9]{6}$/",
            $pincode
        )
    ) {

        $error =
            "Please enter a valid 6-digit pincode.";

    }


    // =================================================
    // URGENCY
    // =================================================

    elseif (
        !in_array(
            $urgency,
            $urgency_options,
            true
        )
    ) {

        $error =
            "Please select a valid urgency.";

    }


    // =================================================
    // DATE
    // =================================================

    elseif (
        strtotime($required_date) === false
    ) {

        $error =
            "Please select a valid required date.";

    }

    elseif (
        $required_date < date("Y-m-d")
    ) {

        $error =
            "Required date cannot be in the past.";

    }


    // =================================================
    // DONOR AVAILABILITY
    // =================================================

    elseif (
        $donor["availability"] !== "Available"
    ) {

        $error =
            "This donor is currently unavailable.";

    }


    // =================================================
    // INSERT REQUEST
    // =================================================

    if (empty($error)) {


        mysqli_begin_transaction($conn);


        try {


            // =========================================
            // INSERT BLOOD REQUEST
            // =========================================

            $insert_sql = "

                INSERT INTO blood_requests
                (
                    requester_id,
                    requester_name,
                    requester_phone,
                    requester_email,

                    patient_name,
                    blood_group,
                    units_required,

                    hospital_name,
                    hospital_address,

                    city,
                    district,
                    state,
                    pincode,

                    urgency,
                    required_date,
                    additional_message,

                    status
                )

                VALUES
                (
                    NULL,
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
                    ?,

                    ?,
                    ?,
                    ?,

                    'Pending'
                )

            ";


            $insert_stmt = mysqli_prepare(
                $conn,
                $insert_sql
            );


            $units =
                (int)$units_required;


            /*
             * 16 values
             *
             * s = string
             * i = integer
             */

            mysqli_stmt_bind_param(
                $insert_stmt,
                "sssssiissssssss",
                $requester_name,
                $requester_phone,
                $requester_email,

                $patient_name,
                $blood_group,
                $units,

                $hospital_name,
                $hospital_address,

                $city,
                $district,
                $state,
                $pincode,

                $urgency,
                $required_date,
                $additional_message
            );


            if (
                !mysqli_stmt_execute(
                    $insert_stmt
                )
            ) {

                throw new Exception(
                    mysqli_stmt_error(
                        $insert_stmt
                    )
                );

            }


            $request_id =
                mysqli_insert_id($conn);


            mysqli_stmt_close(
                $insert_stmt
            );


            // =========================================
            // LINK REQUEST TO DONOR
            // =========================================

            $link_sql = "

                INSERT INTO blood_request_donors
                (
                    request_id,
                    donor_id,
                    status
                )

                VALUES
                (
                    ?,
                    ?,
                    'Pending'
                )

            ";


            $link_stmt = mysqli_prepare(
                $conn,
                $link_sql
            );


            mysqli_stmt_bind_param(
                $link_stmt,
                "ii",
                $request_id,
                $donor_id
            );


            if (
                !mysqli_stmt_execute(
                    $link_stmt
                )
            ) {

                throw new Exception(
                    mysqli_stmt_error(
                        $link_stmt
                    )
                );

            }


            mysqli_stmt_close(
                $link_stmt
            );


            // =========================================
            // NOTIFY DONOR
            // =========================================

            $notification_title =
                "New Blood Request";


            $notification_message =
                $requester_name .
                " needs " .
                $blood_group .
                " blood at " .
                $hospital_name .
                ". Please review the blood request.";


            $notification_type =
                "blood_request";


            $notification_sql = "

                INSERT INTO notifications
                (
                    user_id,
                    title,
                    message,
                    type,
                    related_id,
                    is_read
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    0
                )

            ";


            $notification_stmt =
                mysqli_prepare(
                    $conn,
                    $notification_sql
                );


            mysqli_stmt_bind_param(
                $notification_stmt,
                "isssi",
                $donor_id,
                $notification_title,
                $notification_message,
                $notification_type,
                $request_id
            );


            if (
                !mysqli_stmt_execute(
                    $notification_stmt
                )
            ) {

                throw new Exception(
                    mysqli_stmt_error(
                        $notification_stmt
                    )
                );

            }


            mysqli_stmt_close(
                $notification_stmt
            );


            // =========================================
            // COMMIT
            // =========================================

            mysqli_commit($conn);


            $success =
                "Your blood request has been sent successfully to " .
                $donor["full_name"] .
                ".";


            // Clear form

            $requester_name = "";

            $requester_phone = "";

            $requester_email = "";

            $patient_name = "";

            $blood_group = "";

            $units_required = "1";

            $hospital_name = "";

            $hospital_address = "";

            $city = "";

            $district = "";

            $state = "";

            $pincode = "";

            $urgency = "Normal";

            $required_date = "";

            $additional_message = "";


        }
        catch (
            Exception $e
        ) {


            mysqli_rollback($conn);


            $error =
                "Unable to create request: " .
                $e->getMessage();

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

<title>
Request Blood | BloodConnect
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

    font-family: 'Inter', sans-serif;

    background: #f7f8fa;

    color: #1f2937;

}


:root {

    --primary: #e63946;

    --dark-red: #b91c1c;

    --light-red: #fff1f2;

    --gray: #6b7280;

}


/* NAVBAR */

.navbar {

    background: white;

    border-bottom: 1px solid #eee;

    padding: 14px 0;

}


.logo {

    display: flex;

    align-items: center;

    gap: 10px;

    text-decoration: none;

    color: #111827;

    font-size: 21px;

    font-weight: 800;

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

    color: #4b5563;

    font-size: 12px;

    font-weight: 600;

}


.nav-link:hover {

    color: var(--primary);

}


/* PAGE */

.page {

    max-width: 1000px;

    margin: 45px auto;

    padding: 0 20px;

}


/* HEADER */

.header {

    text-align: center;

    margin-bottom: 25px;

}


.header-icon {

    width: 65px;

    height: 65px;

    background: var(--light-red);

    color: var(--primary);

    border-radius: 18px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 28px;

    margin: auto auto 15px;

}


.header h1 {

    font-size: 30px;

    font-weight: 800;

    margin-bottom: 7px;

}


.header p {

    color: var(--gray);

    font-size: 13px;

}


/* DONOR CARD */

.donor-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 17px;

    padding: 18px;

    display: flex;

    align-items: center;

    gap: 15px;

    margin-bottom: 20px;

}


.donor-avatar {

    width: 55px;

    height: 55px;

    background: var(--light-red);

    color: var(--primary);

    border-radius: 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;

    font-weight: 800;

}


.donor-info {

    flex: 1;

}


.donor-info h4 {

    font-size: 16px;

    font-weight: 800;

    margin: 0 0 4px;

}


.donor-location {

    color: var(--gray);

    font-size: 11px;

}


.blood-badge {

    background: var(--light-red);

    color: var(--primary);

    padding: 10px 14px;

    border-radius: 10px;

    font-size: 14px;

    font-weight: 800;

}


/* FORM CARD */

.form-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 20px;

    padding: 30px;

    box-shadow:
        0 10px 35px
        rgba(0,0,0,.05);

}


.section {

    margin-bottom: 30px;

}


.section-title {

    display: flex;

    align-items: center;

    gap: 10px;

    border-bottom: 1px solid #eee;

    padding-bottom: 13px;

    margin-bottom: 20px;

}


.number {

    width: 30px;

    height: 30px;

    border-radius: 9px;

    background: var(--primary);

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 12px;

    font-weight: 800;

}


.section-title h4 {

    margin: 0;

    font-size: 16px;

    font-weight: 800;

}


.section-title small {

    color: var(--gray);

    font-size: 10px;

}


.form-label {

    font-size: 11px;

    font-weight: 700;

    margin-bottom: 7px;

}


.required {

    color: var(--primary);

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
        rgba(230,57,70,.1);

}


textarea {

    min-height: 110px;

    resize: vertical;

}


/* URGENCY */

.urgency {

    position: relative;

}


.urgency input {

    position: absolute;

    opacity: 0;

}


.urgency label {

    display: block;

    border: 1px solid #e5e7eb;

    border-radius: 11px;

    padding: 14px;

    cursor: pointer;

}


.urgency label strong {

    display: block;

    font-size: 12px;

    margin-bottom: 4px;

}


.urgency label small {

    color: var(--gray);

    font-size: 9px;

}


.urgency input:checked + label {

    background: var(--light-red);

    border-color: var(--primary);

}


/* ALERT */

.alert-box {

    padding: 14px 16px;

    border-radius: 11px;

    margin-bottom: 20px;

    font-size: 12px;

}


.success {

    background: #ecfdf5;

    border: 1px solid #a7f3d0;

    color: #047857;

}


.error {

    background: #fff1f2;

    border: 1px solid #fecdd3;

    color: #be123c;

}


/* INFO */

.info {

    background: #eff6ff;

    border: 1px solid #bfdbfe;

    color: #1e40af;

    border-radius: 11px;

    padding: 14px;

    font-size: 11px;

    line-height: 1.7;

}


/* BUTTONS */

.actions {

    display: flex;

    justify-content: flex-end;

    gap: 10px;

    border-top: 1px solid #eee;

    margin-top: 25px;

    padding-top: 25px;

}


.back-btn {

    padding: 11px 18px;

    border-radius: 9px;

    background: #f3f4f6;

    color: #374151;

    text-decoration: none;

    font-size: 12px;

    font-weight: 700;

}


.submit-btn {

    border: none;

    padding: 11px 22px;

    border-radius: 9px;

    background: var(--primary);

    color: white;

    font-size: 12px;

    font-weight: 700;

}


.submit-btn:hover {

    background: var(--dark-red);

}


/* FOOTER */

footer {

    background: #111827;

    color: #9ca3af;

    text-align: center;

    padding: 25px;

    margin-top: 50px;

    font-size: 10px;

}


footer strong {

    color: white;

}


@media(max-width:600px) {

    .page {

        margin-top: 25px;

    }

    .form-card {

        padding: 20px;

    }

    .donor-card {

        align-items: flex-start;

    }

    .actions {

        flex-direction: column;

    }

    .back-btn,
    .submit-btn {

        width: 100%;

        text-align: center;

    }

}

</style>

</head>


<body>


<!-- =====================================================
NAVBAR
===================================================== -->

<nav class="navbar">

<div class="container">


<a
href="index.php"
class="logo">

<div class="logo-icon">

<i class="bi bi-heart-pulse-fill"></i>

</div>

BloodConnect

</a>


<div>

<a
href="../blood/search.php"
class="nav-link me-3">

<i class="bi bi-search"></i>

Find Donors

</a>


<a
href="login.php"
class="nav-link">

Login

</a>

</div>


</div>

</nav>



<!-- =====================================================
PAGE
===================================================== -->

<div class="page">


<!-- HEADER -->

<div class="header">


<div class="header-icon">

<i class="bi bi-droplet-fill"></i>

</div>


<h1>

Request Blood

</h1>


<p>

Send a blood request directly to the
selected donor.

</p>


</div>



<!-- =====================================================
SELECTED DONOR
===================================================== -->

<div class="donor-card">


<div class="donor-avatar">

<?php

echo htmlspecialchars(
    strtoupper(
        substr(
            $donor["full_name"],
            0,
            1
        )
    )
);

?>

</div>


<div class="donor-info">


<h4>

<?php

echo htmlspecialchars(
    $donor["full_name"]
);

?>

</h4>


<div class="donor-location">

<i class="bi bi-geo-alt-fill"></i>

<?php

echo htmlspecialchars(
    $donor["city"]
);

?>,

<?php

echo htmlspecialchars(
    $donor["district"]
);

?>,

<?php

echo htmlspecialchars(
    $donor["state"]
);

?>


<br>

<span
style="
color:#059669;
font-weight:700;
">

● Available to Donate

</span>

</div>


</div>


<div class="blood-badge">

<i class="bi bi-droplet-fill"></i>

<?php

echo htmlspecialchars(
    $donor["blood_group"]
);

?>

</div>


</div>



<!-- ALERTS -->

<?php if (
    !empty($success)
): ?>

<div class="alert-box success">

<i class="bi bi-check-circle-fill"></i>

&nbsp;

<?php

echo htmlspecialchars(
    $success
);

?>


<br><br>


<a
href="../blood/search.php"
style="
color:#047857;
font-weight:700;
text-decoration:none;
">

Find Another Donor

</a>

</div>

<?php endif; ?>


<?php if (
    !empty($error)
): ?>

<div class="alert-box error">

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
FORM
===================================================== -->

<div class="form-card">


<form
method="POST"
action="request.php?donor_id=<?php
echo $donor_id;
?>">


<!-- =================================================
SECTION 1
================================================= -->

<div class="section">


<div class="section-title">


<div class="number">

1

</div>


<div>

<h4>
Your Contact Information
</h4>

<small>
Login is not required.
</small>

</div>


</div>


<div class="row g-3">


<div class="col-md-4">

<label class="form-label">

Your Name

<span class="required">*</span>

</label>


<input
type="text"
name="requester_name"
class="form-control"
placeholder="Your full name"
value="<?php

echo htmlspecialchars(
    $requester_name
);

?>"
required>

</div>


<div class="col-md-4">

<label class="form-label">

Phone Number

<span class="required">*</span>

</label>


<input
type="tel"
name="requester_phone"
class="form-control"
placeholder="10-digit mobile number"
maxlength="10"
value="<?php

echo htmlspecialchars(
    $requester_phone
);

?>"
required>

</div>


<div class="col-md-4">

<label class="form-label">

Email

</label>


<input
type="email"
name="requester_email"
class="form-control"
placeholder="Optional email"
value="<?php

echo htmlspecialchars(
    $requester_email
);

?>">

</div>


</div>

</div>



<!-- =================================================
SECTION 2
================================================= -->

<div class="section">


<div class="section-title">


<div class="number">

2

</div>


<div>

<h4>
Patient Information
</h4>

<small>
Details of the person who needs blood.
</small>

</div>


</div>


<div class="row g-3">


<div class="col-md-8">

<label class="form-label">

Patient Name

<span class="required">*</span>

</label>


<input
type="text"
name="patient_name"
class="form-control"
placeholder="Patient full name"
value="<?php

echo htmlspecialchars(
    $patient_name
);

?>"
required>

</div>


<div class="col-md-4">

<label class="form-label">

Blood Group

<span class="required">*</span>

</label>


<select
name="blood_group"
class="form-select"
required>


<option value="">

Select Blood Group

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


<div class="col-md-4">

<label class="form-label">

Units Required

<span class="required">*</span>

</label>


<input
type="number"
name="units_required"
class="form-control"
min="1"
max="20"
value="<?php

echo htmlspecialchars(
    $units_required
);

?>"
required>

</div>


</div>

</div>



<!-- =================================================
SECTION 3
================================================= -->

<div class="section">


<div class="section-title">


<div class="number">

3

</div>


<div>

<h4>
Hospital Information
</h4>

<small>
Where is the blood required?
</small>

</div>


</div>


<div class="row g-3">


<div class="col-md-6">

<label class="form-label">

Hospital Name

<span class="required">*</span>

</label>


<input
type="text"
name="hospital_name"
class="form-control"
placeholder="Hospital name"
value="<?php

echo htmlspecialchars(
    $hospital_name
);

?>"
required>

</div>


<div class="col-md-6">

<label class="form-label">

Hospital Address

<span class="required">*</span>

</label>


<input
type="text"
name="hospital_address"
class="form-control"
placeholder="Hospital address"
value="<?php

echo htmlspecialchars(
    $hospital_address
);

?>"
required>

</div>


<div class="col-md-4">

<label class="form-label">

City

<span class="required">*</span>

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

?>"
required>

</div>


<div class="col-md-4">

<label class="form-label">

District

<span class="required">*</span>

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

?>"
required>

</div>


<div class="col-md-4">

<label class="form-label">

State

<span class="required">*</span>

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

?>"
required>

</div>


<div class="col-md-4">

<label class="form-label">

Pincode

<span class="required">*</span>

</label>


<input
type="text"
name="pincode"
class="form-control"
maxlength="6"
placeholder="6-digit pincode"
value="<?php

echo htmlspecialchars(
    $pincode
);

?>"
required>

</div>


</div>

</div>



<!-- =================================================
SECTION 4
================================================= -->

<div class="section">


<div class="section-title">


<div class="number">

4

</div>


<div>

<h4>
Urgency
</h4>

<small>
How urgently is the blood needed?
</small>

</div>


</div>


<div class="row g-3">


<div class="col-md-4 urgency">


<input
type="radio"
id="normal"
name="urgency"
value="Normal"
<?php

echo $urgency === "Normal"
    ? "checked"
    : "";

?>>

<label for="normal">

<strong style="color:#047857;">

<i class="bi bi-check-circle"></i>

Normal

</strong>

<small>

Blood needed as planned.

</small>

</label>

</div>


<div class="col-md-4 urgency">


<input
type="radio"
id="urgent"
name="urgency"
value="Urgent"
<?php

echo $urgency === "Urgent"
    ? "checked"
    : "";

?>>

<label for="urgent">

<strong style="color:#c2410c;">

<i class="bi bi-exclamation-circle"></i>

Urgent

</strong>

<small>

Blood needed soon.

</small>

</label>

</div>


<div class="col-md-4 urgency">


<input
type="radio"
id="emergency"
name="urgency"
value="Emergency"
<?php

echo $urgency === "Emergency"
    ? "checked"
    : "";

?>>

<label for="emergency">

<strong style="color:#b91c1c;">

<i class="bi bi-exclamation-triangle-fill"></i>

Emergency

</strong>

<small>

Immediate requirement.

</small>

</label>

</div>


</div>

</div>



<!-- =================================================
SECTION 5
================================================= -->

<div class="section">


<div class="section-title">


<div class="number">

5

</div>


<div>

<h4>
Required Date
</h4>

<small>
When is the blood needed?
</small>

</div>


</div>


<input
type="date"
name="required_date"
class="form-control"
min="<?php
echo date("Y-m-d");
?>"
value="<?php

echo htmlspecialchars(
    $required_date
);

?>"
required>

</div>



<!-- =================================================
SECTION 6
================================================= -->

<div class="section">


<div class="section-title">


<div class="number">

6

</div>


<div>

<h4>
Additional Information
</h4>

<small>
Optional information for the donor.
</small>

</div>


</div>


<textarea
name="additional_message"
class="form-control"
placeholder="Example: Blood is needed for surgery tomorrow morning."><?php

echo htmlspecialchars(
    $additional_message
);

?></textarea>


</div>



<!-- INFO -->

<div class="info">

<i class="bi bi-shield-check"></i>

&nbsp;

<strong>Privacy:</strong>

You don't need an account to request blood.
Your name and contact number will be shared
with the selected donor so they can contact
you about the request.

</div>



<!-- ACTIONS -->

<div class="actions">


<a
href="../blood/search.php"
class="back-btn">

<i class="bi bi-arrow-left"></i>

&nbsp;

Back

</a>


<button
type="submit"
class="submit-btn">

<i class="bi bi-send-fill"></i>

&nbsp;

Send Blood Request

</button>


</div>


</form>


</div>


</div>



<footer>

<strong>
BloodConnect
</strong>

&nbsp; | &nbsp;

Connecting blood donors with people in need.

</footer>


</body>

</html>