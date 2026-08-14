<?php

session_start();

require_once "../config/database.php";


// =====================================================
// CHECK LOGIN
// =====================================================

if (!isset($_SESSION["user_id"])) {

    $_SESSION["redirect_after_login"] =
        "donor/request.php?donor_id=" .
        ($_GET["donor_id"] ?? "");

    header("Location: ../login.php");
    exit();
}


$requester_id = (int) $_SESSION["user_id"];


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
// DON'T ALLOW REQUEST TO SELF
// =====================================================

if ($donor_id === $requester_id) {

    header("Location: ../blood/search.php");
    exit();
}


// =====================================================
// GET REQUESTER INFORMATION
// =====================================================

$requester_sql = "
    SELECT
        id,
        full_name,
        email,
        phone

    FROM users

    WHERE id = ?

    LIMIT 1
";


$requester_stmt =
    mysqli_prepare(
        $conn,
        $requester_sql
    );


mysqli_stmt_bind_param(
    $requester_stmt,
    "i",
    $requester_id
);


mysqli_stmt_execute(
    $requester_stmt
);


$requester_result =
    mysqli_stmt_get_result(
        $requester_stmt
    );


if (
    mysqli_num_rows($requester_result) !== 1
) {

    die("Requester account not found.");

}


$requester =
    mysqli_fetch_assoc(
        $requester_result
    );


mysqli_stmt_close(
    $requester_stmt
);


// =====================================================
// GET SELECTED DONOR
// =====================================================

$donor_sql = "
    SELECT

        u.id AS user_id,

        u.full_name,
        u.phone,
        u.email,
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


$donor_stmt =
    mysqli_prepare(
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


$donor_result =
    mysqli_stmt_get_result(
        $donor_stmt
    );


if (
    mysqli_num_rows($donor_result) !== 1
) {

    mysqli_stmt_close(
        $donor_stmt
    );

    die("The selected donor was not found or is no longer active.");

}


$donor =
    mysqli_fetch_assoc(
        $donor_result
    );


mysqli_stmt_close(
    $donor_stmt
);


// =====================================================
// VARIABLES
// =====================================================

$error = "";

$success = "";


// =====================================================
// FORM VALUES
// =====================================================

$patient_name =
    trim(
        $_POST["patient_name"] ?? ""
    );


$blood_group =
    trim(
        $_POST["blood_group"] ?? ""
    );


$units_required =
    trim(
        $_POST["units_required"] ?? "1"
    );


$hospital_name =
    trim(
        $_POST["hospital_name"] ?? ""
    );


$hospital_address =
    trim(
        $_POST["hospital_address"] ?? ""
    );


$city =
    trim(
        $_POST["city"] ?? ""
    );


$district =
    trim(
        $_POST["district"] ?? ""
    );


$state =
    trim(
        $_POST["state"] ?? ""
    );


$pincode =
    trim(
        $_POST["pincode"] ?? ""
    );


$contact_phone =
    trim(
        $_POST["contact_phone"]
        ?? $requester["phone"]
    );


$urgency =
    trim(
        $_POST["urgency"] ?? "Normal"
    );


$required_date =
    trim(
        $_POST["required_date"] ?? ""
    );


$additional_message =
    trim(
        $_POST["additional_message"] ?? ""
    );


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


$urgency_options = [

    "Normal",
    "Urgent",
    "Emergency"

];


// =====================================================
// SUBMIT REQUEST
// =====================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {


    // =================================================
    // VALIDATE REQUIRED FIELDS
    // =================================================

    if (
        empty($patient_name) ||
        empty($blood_group) ||
        empty($units_required) ||
        empty($hospital_name) ||
        empty($hospital_address) ||
        empty($city) ||
        empty($district) ||
        empty($state) ||
        empty($pincode) ||
        empty($contact_phone) ||
        empty($required_date)
    ) {

        $error =
            "Please fill in all required fields.";

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
    // PHONE
    // =================================================

    elseif (
        !preg_match(
            "/^[0-9]{10}$/",
            $contact_phone
        )
    ) {

        $error =
            "Please enter a valid 10-digit phone number.";

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
            "Please select a valid urgency level.";

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
    // CHECK DONOR BLOOD GROUP
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


        mysqli_begin_transaction(
            $conn
        );


        try {


            // =========================================
            // INSERT BLOOD REQUEST
            // =========================================

            $insert_sql = "

                INSERT INTO blood_requests
                (
                    requester_id,
                    patient_name,
                    blood_group,
                    units_required,
                    hospital_name,
                    hospital_address,
                    city,
                    district,
                    state,
                    pincode,
                    contact_phone,
                    urgency,
                    required_date,
                    additional_message,
                    status
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
                    ?,
                    ?,
                    ?,
                    'Pending'
                )

            ";


            $insert_stmt =
                mysqli_prepare(
                    $conn,
                    $insert_sql
                );


            $units =
                (int)$units_required;


            mysqli_stmt_bind_param(
                $insert_stmt,
                "ississsssssss",
                $requester_id,
                $patient_name,
                $blood_group,
                $units,
                $hospital_name,
                $hospital_address,
                $city,
                $district,
                $state,
                $pincode,
                $contact_phone,
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
                    "Unable to create blood request."
                );

            }


            $request_id =
                mysqli_insert_id(
                    $conn
                );


            mysqli_stmt_close(
                $insert_stmt
            );


            // =========================================
            // CONNECT REQUEST WITH DONOR
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


            $link_stmt =
                mysqli_prepare(
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
                    "Unable to send request to donor."
                );

            }


            mysqli_stmt_close(
                $link_stmt
            );


            // =========================================
            // NOTIFICATION TO DONOR
            // =========================================

            $notification_title =
                "New Blood Request";


            $notification_message =
                $requester["full_name"] .
                " needs " .
                $blood_group .
                " blood at " .
                $hospital_name .
                ". You have received a blood donation request.";


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
                    "Unable to send donor notification."
                );

            }


            mysqli_stmt_close(
                $notification_stmt
            );


            // =========================================
            // COMMIT
            // =========================================

            mysqli_commit(
                $conn
            );


            $success =
                "Your blood request has been sent to " .
                $donor["full_name"] .
                " successfully.";


            // Clear form

            $patient_name = "";

            $blood_group = "";

            $units_required = "1";

            $hospital_name = "";

            $hospital_address = "";

            $city = "";

            $district = "";

            $state = "";

            $pincode = "";

            $contact_phone =
                $requester["phone"];

            $urgency = "Normal";

            $required_date = "";

            $additional_message = "";


        }

        catch (
            Exception $e
        ) {


            mysqli_rollback(
                $conn
            );


            $error =
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
Request Blood From Donor | BloodConnect
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

    background: #f8f9fb;

    color: #1f2937;

}


:root {

    --primary: #e63946;

    --dark-red: #b91c1c;

    --light-red: #fff1f2;

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

    color: #111827;

    text-decoration: none;

    font-size: 21px;

    font-weight: 800;

}


.logo-icon {

    width: 40px;

    height: 40px;

    border-radius: 12px;

    background: var(--primary);

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

}


.nav-link {

    color: #4b5563;

    font-size: 13px;

    font-weight: 600;

    margin-left: 15px;

}


.nav-link:hover {

    color: var(--primary);

}


/* =====================================================
PAGE
===================================================== */

.page-wrapper {

    max-width: 1000px;

    margin: 40px auto;

    padding: 0 20px;

}


/* =====================================================
SELECTED DONOR
===================================================== */

.selected-donor {

    background: white;

    border: 1px solid #eee;

    border-radius: 18px;

    padding: 20px;

    margin-bottom: 20px;

    display: flex;

    align-items: center;

    gap: 15px;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,0.04);

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


.donor-details {

    flex: 1;

}


.donor-details h4 {

    font-size: 17px;

    font-weight: 800;

    margin: 0 0 5px;

}


.donor-location {

    color: var(--gray);

    font-size: 11px;

}


.blood-badge {

    background: var(--light-red);

    color: var(--primary);

    padding: 9px 14px;

    border-radius: 10px;

    font-size: 14px;

    font-weight: 800;

}


/* =====================================================
HEADER
===================================================== */

.page-header {

    text-align: center;

    margin-bottom: 25px;

}


.page-header h1 {

    font-size: 30px;

    font-weight: 800;

    margin-bottom: 7px;

}


.page-header p {

    color: var(--gray);

    font-size: 13px;

}


/* =====================================================
ALERT
===================================================== */

.alert-custom {

    padding: 14px 16px;

    border-radius: 11px;

    font-size: 12px;

    margin-bottom: 20px;

}


.success-alert {

    background: #ecfdf5;

    color: #047857;

    border: 1px solid #a7f3d0;

}


.error-alert {

    background: #fff1f2;

    color: #be123c;

    border: 1px solid #fecdd3;

}


/* =====================================================
FORM CARD
===================================================== */

.form-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 20px;

    padding: 30px;

    box-shadow:
        0 10px 35px
        rgba(0,0,0,0.05);

}


.section {

    margin-bottom: 30px;

}


.section-title {

    display: flex;

    align-items: center;

    gap: 10px;

    border-bottom: 1px solid #eee;

    padding-bottom: 14px;

    margin-bottom: 20px;

}


.section-number {

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

    display: block;

    color: var(--gray);

    font-size: 10px;

    margin-top: 2px;

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


textarea.form-control {

    min-height: 110px;

    resize: vertical;

}


/* =====================================================
URGENCY
===================================================== */

.urgency {

    position: relative;

}


.urgency input {

    position: absolute;

    opacity: 0;

}


.urgency label {

    display: block;

    padding: 14px;

    border: 1px solid #e5e7eb;

    border-radius: 11px;

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

    border-color: var(--primary);

    background: var(--light-red);

}


.normal strong {

    color: #047857;

}


.urgent strong {

    color: #c2410c;

}


.emergency strong {

    color: #b91c1c;

}


/* =====================================================
INFO
===================================================== */

.info-box {

    background: #eff6ff;

    color: #1e40af;

    border: 1px solid #bfdbfe;

    border-radius: 11px;

    padding: 13px 15px;

    font-size: 11px;

    line-height: 1.7;

    margin-top: 20px;

}


/* =====================================================
BUTTONS
===================================================== */

.actions {

    display: flex;

    justify-content: flex-end;

    gap: 10px;

    border-top: 1px solid #eee;

    padding-top: 25px;

    margin-top: 25px;

}


.back-btn {

    background: #f3f4f6;

    color: #374151;

    text-decoration: none;

    padding: 11px 18px;

    border-radius: 9px;

    font-size: 12px;

    font-weight: 700;

}


.submit-btn {

    border: none;

    background: var(--primary);

    color: white;

    padding: 11px 22px;

    border-radius: 9px;

    font-size: 12px;

    font-weight: 700;

}


.submit-btn:hover {

    background: var(--dark-red);

}


/* =====================================================
RESPONSIVE
===================================================== */

@media(max-width: 576px) {

    .page-wrapper {

        margin-top: 25px;

    }


    .form-card {

        padding: 20px;

    }


    .selected-donor {

        align-items: flex-start;

    }


    .blood-badge {

        margin-left: auto;

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
href="../index.php"
class="navbar-brand">


<div class="logo-icon">

<i class="bi bi-heart-pulse-fill"></i>

</div>


BloodConnect

</a>


<div>

<a
href="../blood/search.php"
class="nav-link d-inline-block">

<i class="bi bi-search"></i>

Find Donors

</a>


<a
href="../donor/dashboard.php"
class="nav-link d-inline-block">

Dashboard

</a>

</div>


</div>

</nav>



<!-- =====================================================
PAGE
===================================================== -->

<div class="page-wrapper">


<!-- =====================================================
SELECTED DONOR
===================================================== -->

<div class="selected-donor">


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


<div class="donor-details">


<h4>

Request Blood From
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


<br>

<i class="bi bi-circle-fill"
style="
font-size:6px;
color:#059669;
"></i>

Available Donor

</div>


</div>


<div class="blood-badge">

<i class="bi bi-droplet-fill"></i>

&nbsp;

<?php

echo htmlspecialchars(
    $donor["blood_group"]
);

?>

</div>


</div>



<!-- =====================================================
HEADER
===================================================== -->

<div class="page-header">


<h1>

Create Blood Request

</h1>


<p>

Your request will be sent directly to
the selected donor.

</p>


</div>



<!-- =====================================================
ALERTS
===================================================== -->

<?php if (
    !empty($success)
): ?>

<div class="
alert-custom
success-alert">


<i class="bi bi-check-circle-fill"></i>

&nbsp;

<?php

echo htmlspecialchars(
    $success
);

?>


<div class="mt-2">


<a
href="../donor/notifications.php"
style="
color:#047857;
font-weight:700;
text-decoration:none;
font-size:11px;
">

View Notifications

</a>


&nbsp; | &nbsp;


<a
href="../blood/search.php"
style="
color:#047857;
font-weight:700;
text-decoration:none;
font-size:11px;
">

Find Another Donor

</a>


</div>


</div>

<?php endif; ?>



<?php if (
    !empty($error)
): ?>

<div class="
alert-custom
error-alert">


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
PATIENT INFORMATION
================================================= -->

<div class="section">


<div class="section-title">


<div class="section-number">

1

</div>


<div>

<h4>
Patient Information
</h4>

<small>
Details about the person who needs blood.
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
placeholder="Enter patient's full name"
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

echo htmlspecialchars(
    $group
);

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
HOSPITAL
================================================= -->

<div class="section">


<div class="section-title">


<div class="section-number">

2

</div>


<div>

<h4>
Hospital Information
</h4>

<small>
Location where blood is required.
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
placeholder="Complete hospital address"
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
CONTACT
================================================= -->

<div class="section">


<div class="section-title">


<div class="section-number">

3

</div>


<div>

<h4>
Contact Information
</h4>

<small>
Contact details for the donor.
</small>

</div>


</div>


<div class="row g-3">


<div class="col-md-6">


<label class="form-label">

Contact Phone
<span class="required">*</span>

</label>


<input
type="tel"
name="contact_phone"
class="form-control"
maxlength="10"
placeholder="10-digit mobile number"
value="<?php

echo htmlspecialchars(
    $contact_phone
);

?>"
required>


</div>


<div class="col-md-6">


<label class="form-label">

Requester

</label>


<input
type="text"
class="form-control"
value="<?php

echo htmlspecialchars(
    $requester["full_name"]
);

?>"
readonly>


</div>


</div>

</div>



<!-- =================================================
URGENCY
================================================= -->

<div class="section">


<div class="section-title">


<div class="section-number">

4

</div>


<div>

<h4>
Urgency
</h4>

<small>
Tell the donor how quickly blood is needed.
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


<label
for="normal"
class="normal">


<strong>

<i class="bi bi-check-circle"></i>

Normal

</strong>


<small>

Blood is needed as planned.

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


<label
for="urgent"
class="urgent">


<strong>

<i class="bi bi-exclamation-circle"></i>

Urgent

</strong>


<small>

Blood is needed soon.

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


<label
for="emergency"
class="emergency">


<strong>

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
DATE
================================================= -->

<div class="section">


<div class="section-title">


<div class="section-number">

5

</div>


<div>

<h4>
Required Date
</h4>

<small>
When is the blood required?
</small>

</div>


</div>


<div class="row">


<div class="col-md-6">


<label class="form-label">

Required Date
<span class="required">*</span>

</label>


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


</div>

</div>



<!-- =================================================
MESSAGE
================================================= -->

<div class="section">


<div class="section-title">


<div class="section-number">

6

</div>


<div>

<h4>
Additional Information
</h4>

<small>
Anything else the donor should know.
</small>

</div>


</div>


<textarea
name="additional_message"
class="form-control"
placeholder="Example: Blood is required for surgery tomorrow morning."><?php

echo htmlspecialchars(
    $additional_message
);

?></textarea>


</div>



<!-- =================================================
INFO
================================================= -->

<div class="info-box">


<i class="bi bi-shield-check"></i>

&nbsp;

<strong>Important:</strong>

Please provide accurate information.
The selected donor will receive a notification
about this request. For a medical emergency,
contact the hospital or emergency medical
services directly.

</div>



<!-- =================================================
BUTTONS
================================================= -->

<div class="actions">


<a
href="../blood/search.php"
class="back-btn">

<i class="bi bi-arrow-left"></i>

&nbsp;

Back to Donors

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



<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>