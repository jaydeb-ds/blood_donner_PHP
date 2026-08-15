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


$donor_id = (int) $_SESSION["user_id"];


// =====================================================
// GET REQUEST ID
// =====================================================

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {

    header("Location: blood-requests.php");

    exit();

}


$request_id = (int) $_GET["id"];


// =====================================================
// VARIABLES
// =====================================================

$error = "";

$success = "";


// =====================================================
// GET REQUEST + DONOR RELATION
// =====================================================

$sql = "

    SELECT

        br.id AS request_id,

        br.requester_id,

        br.patient_name,

        br.blood_group,

        br.units_required,

        br.hospital_name,

        br.hospital_address,

        br.city,

        br.district,

        br.state,

        br.pincode,

        br.contact_phone,

        br.urgency,

        br.required_date,

        br.additional_message,

        br.status AS request_status,

        br.created_at AS request_created_at,

        brd.id AS relation_id,

        brd.status AS donor_status,

        brd.created_at AS relation_created_at,

        u.full_name AS requester_name,

        u.email AS requester_email,

        d.blood_group AS donor_blood_group,

        d.availability AS donor_availability

    FROM blood_request_donors brd

    INNER JOIN blood_requests br
        ON brd.request_id = br.id

    INNER JOIN users u
        ON br.requester_id = u.id

    INNER JOIN donor_profiles d
        ON brd.donor_id = d.user_id

    WHERE brd.request_id = ?

    AND brd.donor_id = ?

    LIMIT 1

";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $request_id,
    $donor_id
);


mysqli_stmt_execute(
    $stmt
);


$result = mysqli_stmt_get_result(
    $stmt
);


if (
    mysqli_num_rows($result) !== 1
) {

    mysqli_stmt_close($stmt);

    die("
        <div style='
            font-family:Arial;
            padding:40px;
            text-align:center;
        '>

            <h2>
                Blood Request Not Found
            </h2>

            <p>
                This request does not exist or
                has not been assigned to you.
            </p>

            <a href='blood-requests.php'>
                Back to Blood Requests
            </a>

        </div>
    ");

}


$request = mysqli_fetch_assoc(
    $result
);


mysqli_stmt_close($stmt);


// =====================================================
// HANDLE DONOR RESPONSE
// =====================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["response"])
) {


    $response =
        trim($_POST["response"]);


    // =================================================
    // VALID RESPONSE
    // =================================================

    if (
        !in_array(
            $response,
            [
                "Accepted",
                "Rejected"
            ],
            true
        )
    ) {

        $error =
            "Invalid response.";

    }


    // =================================================
    // CHECK CURRENT STATUS
    // =================================================

    elseif (
        $request["donor_status"] !== "Pending"
    ) {

        $error =
            "You have already responded to this request.";

    }


    // =================================================
    // CHECK REQUEST STATUS
    // =================================================

    elseif (
        $request["request_status"] !== "Pending"
    ) {

        $error =
            "This blood request is no longer active.";

    }


    // =================================================
    // ACCEPT / REJECT
    // =================================================

    else {


        mysqli_begin_transaction($conn);


        try {


            // =========================================
            // UPDATE DONOR RESPONSE
            // =========================================

            $update_relation_sql = "

                UPDATE blood_request_donors

                SET status = ?

                WHERE id = ?

                AND donor_id = ?

                AND request_id = ?

            ";


            $update_relation_stmt =
                mysqli_prepare(
                    $conn,
                    $update_relation_sql
                );


            mysqli_stmt_bind_param(
                $update_relation_stmt,
                "siii",
                $response,
                $request["relation_id"],
                $donor_id,
                $request_id
            );


            if (
                !mysqli_stmt_execute(
                    $update_relation_stmt
                )
            ) {

                throw new Exception(
                    "Unable to update donor response."
                );

            }


            mysqli_stmt_close(
                $update_relation_stmt
            );


            // =========================================
            // IF ACCEPTED
            // =========================================

            if ($response === "Accepted") {


                $request_status =
                    "Accepted";


                $update_request_sql = "

                    UPDATE blood_requests

                    SET status = ?

                    WHERE id = ?

                    AND status = 'Pending'

                ";


                $update_request_stmt =
                    mysqli_prepare(
                        $conn,
                        $update_request_sql
                    );


                mysqli_stmt_bind_param(
                    $update_request_stmt,
                    "si",
                    $request_status,
                    $request_id
                );


                if (
                    !mysqli_stmt_execute(
                        $update_request_stmt
                    )
                ) {

                    throw new Exception(
                        "Unable to update blood request."
                    );

                }


                mysqli_stmt_close(
                    $update_request_stmt
                );


                // =====================================
                // NOTIFY REQUESTER
                // =====================================

                $notification_title =
                    "Blood Request Accepted";


                $notification_message =
                    $request["requester_name"] .
                    ", " .
                    "your blood request has been accepted by donor " .
                    $_SESSION["full_name"] ??
                    "a donor";


                $notification_message =
                    "Your " .
                    $request["blood_group"] .
                    " blood request has been accepted by a donor. " .
                    "Please contact the donor to coordinate the donation.";


                $notification_type =
                    "request_response";


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
                    $request["requester_id"],
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
                        "Unable to create requester notification."
                    );

                }


                mysqli_stmt_close(
                    $notification_stmt
                );


            }


            // =========================================
            // IF REJECTED
            // =========================================

            else {


                $notification_title =
                    "Blood Request Update";


                $notification_message =
                    "A donor was unable to accept your " .
                    $request["blood_group"] .
                    " blood request. " .
                    "You can try contacting another available donor.";


                $notification_type =
                    "request_response";


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
                    $request["requester_id"],
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
                        "Unable to create requester notification."
                    );

                }


                mysqli_stmt_close(
                    $notification_stmt
                );

            }


            // =========================================
            // COMMIT
            // =========================================

            mysqli_commit($conn);


            $success =
                $response === "Accepted"
                ? "You accepted this blood request successfully."
                : "You rejected this blood request.";


            // =========================================
            // UPDATE LOCAL DATA
            // =========================================

            $request["donor_status"] =
                $response;


            if ($response === "Accepted") {

                $request["request_status"] =
                    "Accepted";

            }


        }
        catch (
            Exception $e
        ) {


            mysqli_rollback($conn);


            $error =
                $e->getMessage();

        }

    }

}


// =====================================================
// DONOR INITIAL
// =====================================================

$donor_initial =
    strtoupper(
        substr(
            $_SESSION["full_name"]
            ?? "D",
            0,
            1
        )
    );


// =====================================================
// URGENCY CLASS
// =====================================================

$urgency_class = "";

if (
    $request["urgency"] === "Emergency"
) {

    $urgency_class =
        "emergency";

}

elseif (
    $request["urgency"] === "Urgent"
) {

    $urgency_class =
        "urgent";

}

else {

    $urgency_class =
        "normal";

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
Respond to Blood Request | BloodConnect
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

    height: 72px;

    background: white;

    border-bottom: 1px solid #eee;

    display: flex;

    align-items: center;

}


.navbar-container {

    width: 100%;

    max-width: 1150px;

    margin: auto;

    padding: 0 20px;

    display: flex;

    align-items: center;

    justify-content: space-between;

}


.logo {

    display: flex;

    align-items: center;

    gap: 10px;

    color: var(--dark);

    text-decoration: none;

    font-size: 20px;

    font-weight: 800;

}


.logo-icon {

    width: 39px;

    height: 39px;

    border-radius: 11px;

    background: var(--primary);

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

}


.nav-links {

    display: flex;

    gap: 20px;

    align-items: center;

}


.nav-links a {

    color: #4b5563;

    text-decoration: none;

    font-size: 12px;

    font-weight: 600;

}


.nav-links a:hover {

    color: var(--primary);

}


/* =====================================================
MAIN
===================================================== */

.page-wrapper {

    max-width: 1050px;

    margin: 40px auto;

    padding: 0 20px;

}


/* =====================================================
PAGE HEADER
===================================================== */

.page-header {

    margin-bottom: 25px;

}


.back-link {

    color: var(--gray);

    text-decoration: none;

    font-size: 11px;

    font-weight: 600;

    display: inline-flex;

    align-items: center;

    gap: 5px;

    margin-bottom: 15px;

}


.back-link:hover {

    color: var(--primary);

}


.page-header h1 {

    font-size: 28px;

    font-weight: 800;

    margin: 0 0 7px;

}


.page-header p {

    color: var(--gray);

    font-size: 13px;

    margin: 0;

}


/* =====================================================
ALERT
===================================================== */

.alert-custom {

    padding: 14px 17px;

    border-radius: 11px;

    margin-bottom: 20px;

    font-size: 12px;

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
GRID
===================================================== */

.request-grid {

    display: grid;

    grid-template-columns:
        1fr 320px;

    gap: 20px;

}


/* =====================================================
CARD
===================================================== */

.card-box {

    background: white;

    border: 1px solid #eee;

    border-radius: 18px;

    padding: 25px;

    box-shadow:
        0 8px 30px
        rgba(0,0,0,0.04);

}


.card-title {

    display: flex;

    align-items: center;

    gap: 10px;

    font-size: 16px;

    font-weight: 800;

    margin-bottom: 20px;

}


.title-icon {

    width: 38px;

    height: 38px;

    border-radius: 10px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

}


/* =====================================================
PATIENT HEADER
===================================================== */

.patient-header {

    display: flex;

    align-items: center;

    gap: 14px;

    padding-bottom: 20px;

    margin-bottom: 20px;

    border-bottom: 1px solid #eee;

}


.patient-avatar {

    width: 55px;

    height: 55px;

    border-radius: 15px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;

    font-weight: 800;

}


.patient-info h2 {

    font-size: 18px;

    font-weight: 800;

    margin: 0 0 5px;

}


.patient-info p {

    color: var(--gray);

    font-size: 11px;

    margin: 0;

}


/* =====================================================
BLOOD BADGE
===================================================== */

.blood-badge {

    margin-left: auto;

    background: var(--light-red);

    color: var(--primary);

    border-radius: 12px;

    padding: 11px 15px;

    font-size: 16px;

    font-weight: 800;

}


/* =====================================================
DETAIL ROW
===================================================== */

.detail-row {

    display: flex;

    gap: 15px;

    padding: 14px 0;

    border-bottom: 1px solid #f3f4f6;

}


.detail-row:last-child {

    border-bottom: none;

}


.detail-icon {

    width: 35px;

    height: 35px;

    background: #f9fafb;

    border-radius: 9px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: var(--primary);

    flex-shrink: 0;

}


.detail-label {

    color: #9ca3af;

    font-size: 10px;

    margin-bottom: 3px;

}


.detail-value {

    font-size: 12px;

    font-weight: 700;

    line-height: 1.5;

}


/* =====================================================
URGENCY
===================================================== */

.urgency {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 7px 11px;

    border-radius: 50px;

    font-size: 10px;

    font-weight: 800;

}


.urgency.normal {

    background: #ecfdf5;

    color: #047857;

}


.urgency.urgent {

    background: #fff7ed;

    color: #c2410c;

}


.urgency.emergency {

    background: #fff1f2;

    color: #b91c1c;

}


/* =====================================================
STATUS
===================================================== */

.status-box {

    padding: 14px;

    border-radius: 11px;

    background: #f9fafb;

    margin-bottom: 18px;

}


.status-label {

    color: #9ca3af;

    font-size: 9px;

    text-transform: uppercase;

    font-weight: 800;

    margin-bottom: 5px;

}


.status-value {

    font-size: 13px;

    font-weight: 800;

}


.pending {

    color: #c2410c;

}


.accepted {

    color: #047857;

}


.rejected {

    color: #b91c1c;

}


/* =====================================================
DONOR CARD
===================================================== */

.donor-profile {

    display: flex;

    align-items: center;

    gap: 12px;

    margin-bottom: 20px;

}


.donor-avatar {

    width: 48px;

    height: 48px;

    background: var(--light-red);

    color: var(--primary);

    border-radius: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: 800;

}


.donor-profile strong {

    display: block;

    font-size: 13px;

}


.donor-profile small {

    color: var(--gray);

    font-size: 10px;

}


/* =====================================================
ACTION BUTTONS
===================================================== */

.action-form {

    margin-bottom: 10px;

}


.action-btn {

    width: 100%;

    border: none;

    padding: 12px;

    border-radius: 10px;

    font-size: 12px;

    font-weight: 800;

    cursor: pointer;

}


.accept-btn {

    background: #059669;

    color: white;

}


.accept-btn:hover {

    background: #047857;

}


.reject-btn {

    background: #fff1f2;

    color: #b91c1c;

    border: 1px solid #fecaca;

}


.reject-btn:hover {

    background: #fee2e2;

}


/* =====================================================
WARNING
===================================================== */

.warning-box {

    background: #fffbeb;

    border: 1px solid #fde68a;

    color: #92400e;

    border-radius: 11px;

    padding: 12px;

    font-size: 10px;

    line-height: 1.6;

    margin-top: 15px;

}


/* =====================================================
FOOTER
===================================================== */

footer {

    text-align: center;

    color: #9ca3af;

    font-size: 10px;

    padding: 30px 20px;

}


footer strong {

    color: #111827;

}


/* =====================================================
RESPONSIVE
===================================================== */

@media(max-width: 800px) {

    .request-grid {

        grid-template-columns: 1fr;

    }


    .blood-badge {

        margin-left: 0;

    }

}


@media(max-width: 500px) {

    .navbar {

        height: auto;

        padding: 15px 0;

    }


    .nav-links {

        gap: 10px;

    }


    .nav-links a {

        font-size: 10px;

    }


    .page-wrapper {

        margin-top: 25px;

    }


    .card-box {

        padding: 20px;

    }


    .patient-header {

        align-items: flex-start;

        flex-wrap: wrap;

    }

}

</style>

</head>


<body>


<!-- =====================================================
NAVBAR
===================================================== -->

<nav class="navbar">


<div class="navbar-container">


<a
href="dashboard.php"
class="logo">


<div class="logo-icon">

<i class="bi bi-heart-pulse-fill"></i>

</div>


BloodConnect

</a>


<div class="nav-links">


<a href="dashboard.php">

Dashboard

</a>


<a href="blood-requests.php">

Blood Requests

</a>


<a href="notifications.php">

Notifications

</a>


<a href="../logout.php">

Logout

</a>


</div>


</div>

</nav>



<!-- =====================================================
MAIN
===================================================== -->

<div class="page-wrapper">


<div class="page-header">


<a
href="blood-requests.php"
class="back-link">

<i class="bi bi-arrow-left"></i>

Back to Blood Requests

</a>


<h1>

Blood Donation Request

</h1>


<p>

Review the request carefully before accepting
or rejecting it.

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
REQUEST GRID
===================================================== -->

<div class="request-grid">


<!-- =================================================
LEFT: REQUEST DETAILS
================================================= -->

<div>


<div class="card-box">


<div class="card-title">


<div class="title-icon">

<i class="bi bi-person-vcard-fill"></i>

</div>


Request Details


</div>



<!-- PATIENT -->

<div class="patient-header">


<div class="patient-avatar">

<?php

echo htmlspecialchars(
    strtoupper(
        substr(
            $request["patient_name"],
            0,
            1
        )
    )
);

?>

</div>


<div class="patient-info">


<h2>

<?php

echo htmlspecialchars(
    $request["patient_name"]
);

?>

</h2>


<p>

Requested by
<strong>

<?php

echo htmlspecialchars(
    $request["requester_name"]
);

?>

</strong>

</p>


</div>


<div class="blood-badge">

<i class="bi bi-droplet-fill"></i>

<?php

echo htmlspecialchars(
    $request["blood_group"]
);

?>

</div>


</div>



<!-- BLOOD UNITS -->

<div class="detail-row">


<div class="detail-icon">

<i class="bi bi-droplet-fill"></i>

</div>


<div>

<div class="detail-label">

Blood Requirement

</div>


<div class="detail-value">

<?php

echo htmlspecialchars(
    $request["units_required"]
);

?>

Unit<?php

echo $request["units_required"] != 1
    ? "s"
    : "";

?>

of

<?php

echo htmlspecialchars(
    $request["blood_group"]
);

?>

</div>

</div>


</div>



<!-- HOSPITAL -->

<div class="detail-row">


<div class="detail-icon">

<i class="bi bi-hospital-fill"></i>

</div>


<div>

<div class="detail-label">

Hospital

</div>


<div class="detail-value">

<?php

echo htmlspecialchars(
    $request["hospital_name"]
);

?>


<br>

<span
style="
font-weight:500;
color:#6b7280;
">

<?php

echo htmlspecialchars(
    $request["hospital_address"]
);

?>

</span>

</div>

</div>


</div>



<!-- LOCATION -->

<div class="detail-row">


<div class="detail-icon">

<i class="bi bi-geo-alt-fill"></i>

</div>


<div>

<div class="detail-label">

Location

</div>


<div class="detail-value">

<?php

echo htmlspecialchars(
    $request["city"]
);

?>,

<?php

echo htmlspecialchars(
    $request["district"]
);

?>,

<?php

echo htmlspecialchars(
    $request["state"]
);

?>


<br>

<?php

echo htmlspecialchars(
    $request["pincode"]
);

?>

</div>

</div>


</div>



<!-- REQUIRED DATE -->

<div class="detail-row">


<div class="detail-icon">

<i class="bi bi-calendar-event-fill"></i>

</div>


<div>

<div class="detail-label">

Required Date

</div>


<div class="detail-value">

<?php

echo date(
    "d M Y",
    strtotime(
        $request["required_date"]
    )
);

?>

</div>

</div>


</div>



<!-- URGENCY -->

<div class="detail-row">


<div class="detail-icon">

<i class="bi bi-exclamation-circle-fill"></i>

</div>


<div>

<div class="detail-label">

Urgency

</div>


<div>

<span class="
urgency
<?php

echo $urgency_class;

?>">

<i class="bi bi-circle-fill"
style="font-size:5px;">
</i>

<?php

echo htmlspecialchars(
    $request["urgency"]
);

?>

</span>

</div>

</div>


</div>



<!-- MESSAGE -->

<?php if (
    !empty(
        $request["additional_message"]
    )
): ?>


<div class="detail-row">


<div class="detail-icon">

<i class="bi bi-chat-left-text-fill"></i>

</div>


<div>

<div class="detail-label">

Additional Message

</div>


<div class="detail-value">

<?php

echo nl2br(
    htmlspecialchars(
        $request[
            "additional_message"
        ]
    )
);

?>

</div>

</div>


</div>


<?php endif; ?>



<!-- CREATED -->

<div class="detail-row">


<div class="detail-icon">

<i class="bi bi-clock-fill"></i>

</div>


<div>

<div class="detail-label">

Request Created

</div>


<div class="detail-value">

<?php

echo date(
    "d M Y, h:i A",
    strtotime(
        $request[
            "request_created_at"
        ]
    )
);

?>

</div>

</div>


</div>


</div>


</div>



<!-- =================================================
RIGHT: RESPONSE
================================================= -->

<div>


<div class="card-box">


<div class="card-title">


<div class="title-icon">

<i class="bi bi-person-heart"></i>

</div>


Your Response


</div>



<div class="donor-profile">


<div class="donor-avatar">

<?php

echo htmlspecialchars(
    $donor_initial
);

?>

</div>


<div>

<strong>

You are responding as a donor

</strong>


<small>

Blood Group:

<?php

echo htmlspecialchars(
    $request[
        "donor_blood_group"
    ]
);

?>

</small>

</div>


</div>



<!-- STATUS -->

<div class="status-box">


<div class="status-label">

Your Response

</div>


<div class="
status-value
<?php

if (
    $request["donor_status"]
    === "Accepted"
) {

    echo "accepted";

}

elseif (
    $request["donor_status"]
    === "Rejected"
) {

    echo "rejected";

}

else {

    echo "pending";

}

?>">

<?php

echo htmlspecialchars(
    $request["donor_status"]
);

?>

</div>


</div>



<?php if (
    $request["donor_status"]
    === "Pending" &&
    $request["request_status"]
    === "Pending"
): ?>


<!-- ACCEPT -->

<form
method="POST"
class="action-form"
onsubmit="
return confirm(
'Are you sure you want to accept this blood request?'
);
">


<input
type="hidden"
name="response"
value="Accepted">


<button
type="submit"
class="
action-btn
accept-btn">


<i class="bi bi-check-circle-fill"></i>

&nbsp;

Accept Request

</button>


</form>



<!-- REJECT -->

<form
method="POST"
class="action-form"
onsubmit="
return confirm(
'Are you sure you want to reject this blood request?'
);
">


<input
type="hidden"
name="response"
value="Rejected">


<button
type="submit"
class="
action-btn
reject-btn">


<i class="bi bi-x-circle-fill"></i>

&nbsp;

Reject Request

</button>


</form>


<?php elseif (
    $request["donor_status"]
    === "Accepted"
): ?>


<div class="warning-box"
style="
background:#ecfdf5;
border-color:#a7f3d0;
color:#047857;
">


<i class="bi bi-check-circle-fill"></i>

&nbsp;

You accepted this request.

Please contact the requester at:

<br><br>

<strong>

<?php

echo htmlspecialchars(
    $request["contact_phone"]
);

?>

</strong>

</div>


<?php elseif (
    $request["donor_status"]
    === "Rejected"
): ?>


<div class="warning-box"
style="
background:#fff1f2;
border-color:#fecaca;
color:#b91c1c;
">


<i class="bi bi-x-circle-fill"></i>

&nbsp;

You rejected this blood request.

</div>


<?php else: ?>


<div class="warning-box">


<i class="bi bi-info-circle-fill"></i>

&nbsp;

This request is no longer available
for a response.

</div>


<?php endif; ?>


<div class="warning-box">


<i class="bi bi-shield-exclamation"></i>

&nbsp;

Only accept this request if you are
actually willing and medically eligible
to donate. Follow the hospital's
instructions before donating blood.

</div>


</div>


<!-- CONTACT REQUESTER -->

<?php if (
    $request["donor_status"]
    === "Accepted"
): ?>


<div class="card-box"
style="margin-top:20px;">


<div class="card-title">


<div class="title-icon">

<i class="bi bi-telephone-fill"></i>

</div>


Contact Requester


</div>


<div
style="
font-size:12px;
font-weight:700;
margin-bottom:8px;
">

<?php

echo htmlspecialchars(
    $request["requester_name"]
);

?>

</div>


<div
style="
font-size:11px;
color:#6b7280;
margin-bottom:15px;
">

<i class="bi bi-telephone"></i>

<?php

echo htmlspecialchars(
    $request["contact_phone"]
);

?>

</div>


<a
href="tel:<?php

echo htmlspecialchars(
    $request["contact_phone"]
);

?>"
style="
display:block;
background:#059669;
color:white;
text-align:center;
padding:11px;
border-radius:9px;
text-decoration:none;
font-size:12px;
font-weight:800;
">


<i class="bi bi-telephone-fill"></i>

&nbsp;

Call Requester

</a>


</div>


<?php endif; ?>


</div>


</div>


</div>



<!-- =====================================================
FOOTER
===================================================== -->

<footer>

<strong>
BloodConnect
</strong>

&nbsp; | &nbsp;

Every donation can save a life.

</footer>


</body>

</html>