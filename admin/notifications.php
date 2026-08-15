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

$admin_initial =
    strtoupper(
        substr(
            $admin_name,
            0,
            1
        )
    );


// =====================================================
// CREATE NOTIFICATION
// =====================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["create_notification"])
) {

    $title =
        trim(
            $_POST["title"] ?? ""
        );

    $message =
        trim(
            $_POST["message"] ?? ""
        );

    $type =
        trim(
            $_POST["type"] ?? "General"
        );

    $recipient =
        trim(
            $_POST["recipient"] ?? "all"
        );


    // -----------------------------------------------
    // VALIDATION
    // -----------------------------------------------

    if (
        empty($title) ||
        empty($message)
    ) {

        $error =
            "Title and message are required.";

    } else {

        /*
         * If recipient is "all", create one
         * general notification.
         *
         * If a donor/user is selected,
         * create notification for that user.
         */

        if ($recipient === "all") {

            $sql = "
                INSERT INTO notifications
                (
                    user_id,
                    title,
                    message,
                    type
                )
                VALUES
                (
                    NULL,
                    ?,
                    ?,
                    ?
                )
            ";

            $stmt =
                mysqli_prepare(
                    $conn,
                    $sql
                );

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $title,
                $message,
                $type
            );


            if (
                mysqli_stmt_execute($stmt)
            ) {

                $success =
                    "Notification sent successfully to all users.";

            } else {

                $error =
                    "Unable to create notification.";

            }


            mysqli_stmt_close($stmt);

        } else {

            // ---------------------------------------
            // SPECIFIC USER
            // ---------------------------------------

            if (
                !is_numeric($recipient)
            ) {

                $error =
                    "Invalid recipient.";

            } else {

                $recipient_id =
                    (int)$recipient;


                $sql = "
                    INSERT INTO notifications
                    (
                        user_id,
                        title,
                        message,
                        type
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ";


                $stmt =
                    mysqli_prepare(
                        $conn,
                        $sql
                    );


                mysqli_stmt_bind_param(
                    $stmt,
                    "isss",
                    $recipient_id,
                    $title,
                    $message,
                    $type
                );


                if (
                    mysqli_stmt_execute($stmt)
                ) {

                    $success =
                        "Notification sent successfully.";

                } else {

                    $error =
                        "Unable to create notification.";

                }


                mysqli_stmt_close($stmt);

            }

        }

    }

}


// =====================================================
// MARK NOTIFICATION AS READ
// =====================================================

if (
    isset($_GET["read"]) &&
    is_numeric($_GET["read"])
) {

    $notification_id =
        (int)$_GET["read"];


    $sql = "
        UPDATE notifications
        SET is_read = 1
        WHERE id = ?
    ";


    $stmt =
        mysqli_prepare(
            $conn,
            $sql
        );


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $notification_id
    );


    if (
        mysqli_stmt_execute($stmt)
    ) {

        $success =
            "Notification marked as read.";

    }


    mysqli_stmt_close($stmt);

}


// =====================================================
// MARK ALL AS READ
// =====================================================

if (
    isset($_GET["read_all"])
) {

    $sql = "
        UPDATE notifications
        SET is_read = 1
    ";


    if (
        mysqli_query(
            $conn,
            $sql
        )
    ) {

        $success =
            "All notifications marked as read.";

    }

}


// =====================================================
// DELETE NOTIFICATION
// =====================================================

if (
    isset($_GET["delete"]) &&
    is_numeric($_GET["delete"])
) {

    $notification_id =
        (int)$_GET["delete"];


    $sql = "
        DELETE FROM notifications
        WHERE id = ?
    ";


    $stmt =
        mysqli_prepare(
            $conn,
            $sql
        );


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $notification_id
    );


    if (
        mysqli_stmt_execute($stmt)
    ) {

        $success =
            "Notification deleted successfully.";

    } else {

        $error =
            "Unable to delete notification.";

    }


    mysqli_stmt_close($stmt);

}


// =====================================================
// SEARCH
// =====================================================

$search =
    trim(
        $_GET["search"] ?? ""
    );


// =====================================================
// NOTIFICATION QUERY
// =====================================================

$sql = "

    SELECT

        n.id,

        n.user_id,

        n.title,

        n.message,

        n.type,

        n.is_read,

        n.created_at,

        u.full_name,

        u.email

    FROM notifications n

    LEFT JOIN users u
        ON n.user_id = u.id

    WHERE 1 = 1

";


$params = [];

$types = "";


if (
    !empty($search)
) {

    $sql .= "

        AND (

            n.title LIKE ?

            OR n.message LIKE ?

            OR u.full_name LIKE ?

            OR u.email LIKE ?

        )

    ";


    $search_value =
        "%" . $search . "%";


    $params[] =
        $search_value;

    $params[] =
        $search_value;

    $params[] =
        $search_value;

    $params[] =
        $search_value;


    $types .= "ssss";

}


$sql .= "

    ORDER BY
        n.created_at DESC

";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


if (
    !empty($params)
) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );

}


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


// =====================================================
// STATISTICS
// =====================================================


// Total

$total_notifications = 0;


$count_result =
    mysqli_query(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM notifications
        "
    );


if ($count_result) {

    $row =
        mysqli_fetch_assoc(
            $count_result
        );

    $total_notifications =
        (int)$row["total"];

}


// Unread

$unread_notifications = 0;


$count_result =
    mysqli_query(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM notifications
        WHERE is_read = 0
        "
    );


if ($count_result) {

    $row =
        mysqli_fetch_assoc(
            $count_result
        );

    $unread_notifications =
        (int)$row["total"];

}


// Read

$read_notifications = 0;


$count_result =
    mysqli_query(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM notifications
        WHERE is_read = 1
        "
    );


if ($count_result) {

    $row =
        mysqli_fetch_assoc(
            $count_result
        );

    $read_notifications =
        (int)$row["total"];

}


// =====================================================
// GET DONORS / USERS FOR RECIPIENT SELECT
// =====================================================

$users_sql = "

    SELECT

        u.id,

        u.full_name,

        u.email

    FROM users u

    ORDER BY
        u.full_name ASC

";


$users_result =
    mysqli_query(
        $conn,
        $users_sql
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Notifications | BloodConnect Admin
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
STATISTICS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 13px;

    margin-bottom: 18px;

}


.stat-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 14px;

    padding: 17px;

}


.stat-content {

    display: flex;

    justify-content: space-between;

    align-items: center;

}


.stat-label {

    color: #6b7280;

    font-size: 9px;

}


.stat-number {

    font-size: 23px;

    font-weight: 800;

    margin-top: 5px;

}


.stat-icon {

    width: 40px;

    height: 40px;

    border-radius: 11px;

    background: #fff1f2;

    color: #e63946;

    display: flex;

    align-items: center;

    justify-content: center;

}


/* =====================================================
CREATE NOTIFICATION
===================================================== */

.create-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 17px;

    padding: 20px;

    margin-bottom: 18px;

}


.card-title {

    font-size: 14px;

    font-weight: 800;

    margin-bottom: 17px;

}


.form-label {

    font-size: 9px;

    font-weight: 700;

    margin-bottom: 6px;

}


.form-control,
.form-select {

    border: 1px solid #e5e7eb;

    border-radius: 8px;

    padding: 9px 11px;

    font-size: 10px;

}


textarea.form-control {

    min-height: 85px;

    resize: vertical;

}


.form-control:focus,
.form-select:focus {

    border-color: #e63946;

    box-shadow:
        0 0 0 3px
        rgba(230,57,70,.08);

}


.send-btn {

    border: none;

    background: #e63946;

    color: white;

    border-radius: 8px;

    padding: 10px 18px;

    font-size: 10px;

    font-weight: 700;

}


.send-btn:hover {

    background: #d62839;

}


/* =====================================================
NOTIFICATION LIST
===================================================== */

.list-card {

    background: white;

    border: 1px solid #eee;

    border-radius: 17px;

    padding: 20px;

}


.list-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 17px;

}


.list-header h3 {

    font-size: 14px;

    font-weight: 800;

    margin: 0;

}


.read-all {

    background: #f3f4f6;

    color: #374151;

    text-decoration: none;

    padding: 8px 12px;

    border-radius: 8px;

    font-size: 9px;

    font-weight: 700;

}


.search-box {

    margin-bottom: 16px;

}


.search-box form {

    display: flex;

    gap: 7px;

}


.search-box input {

    flex: 1;

}


.search-btn {

    border: none;

    background: #111827;

    color: white;

    border-radius: 8px;

    padding: 9px 15px;

    font-size: 9px;

    font-weight: 700;

}


.notification {

    display: flex;

    align-items: flex-start;

    gap: 13px;

    padding: 15px 0;

    border-bottom: 1px solid #f3f4f6;

}


.notification:last-child {

    border-bottom: none;

}


.notification.unread {

    background: #fffafa;

    margin-left: -10px;

    margin-right: -10px;

    padding-left: 10px;

    padding-right: 10px;

    border-radius: 10px;

}


.notification-icon {

    width: 38px;

    height: 38px;

    min-width: 38px;

    border-radius: 10px;

    background: #fff1f2;

    color: #e63946;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 14px;

}


.notification-content {

    flex: 1;

}


.notification-title {

    font-size: 10px;

    font-weight: 800;

    margin-bottom: 4px;

}


.notification-message {

    color: #6b7280;

    font-size: 9px;

    line-height: 1.6;

}


.notification-meta {

    display: flex;

    flex-wrap: wrap;

    gap: 7px;

    margin-top: 7px;

    align-items: center;

}


.notification-type {

    background: #eff6ff;

    color: #2563eb;

    padding: 4px 7px;

    border-radius: 50px;

    font-size: 7px;

    font-weight: 800;

}


.recipient {

    color: #9ca3af;

    font-size: 7px;

}


.notification-time {

    color: #9ca3af;

    font-size: 7px;

}


.unread-badge {

    background: #e63946;

    color: white;

    padding: 4px 7px;

    border-radius: 50px;

    font-size: 7px;

    font-weight: 800;

}


.notification-actions {

    display: flex;

    gap: 5px;

}


.action-btn {

    width: 28px;

    height: 28px;

    border-radius: 7px;

    display: flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    font-size: 10px;

}


.read-btn {

    background: #ecfdf5;

    color: #059669;

}


.delete-btn {

    background: #fff1f2;

    color: #dc2626;

}


.empty {

    text-align: center;

    padding: 45px 20px;

    color: #9ca3af;

}


.empty i {

    display: block;

    font-size: 30px;

    margin-bottom: 10px;

}


/* =====================================================
RESPONSIVE
===================================================== */

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


    .stats {

        grid-template-columns: 1fr;

    }


    .admin-profile {

        display: none;

    }


    .notification {

        flex-wrap: wrap;

    }


    .notification-actions {

        margin-left: 51px;

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


<a
href="notifications.php"
class="active">

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


<a href="settings.php">

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
Notifications
</h1>

<p>
Send important updates to donors and users.
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
STATISTICS
===================================================== -->

<div class="stats">


<div class="stat-card">


<div class="stat-content">


<div>

<div class="stat-label">
Total Notifications
</div>


<div class="stat-number">

<?php

echo $total_notifications;

?>

</div>

</div>


<div class="stat-icon">

<i class="bi bi-bell-fill"></i>

</div>


</div>


</div>



<div class="stat-card">


<div class="stat-content">


<div>

<div class="stat-label">
Unread
</div>


<div
class="stat-number"
style="color:#e63946;">

<?php

echo $unread_notifications;

?>

</div>

</div>


<div
class="stat-icon"
style="
background:#fff1f2;
color:#e63946;
">

<i class="bi bi-bell-fill"></i>

</div>


</div>


</div>



<div class="stat-card">


<div class="stat-content">


<div>

<div class="stat-label">
Read
</div>


<div
class="stat-number"
style="color:#059669;">

<?php

echo $read_notifications;

?>

</div>

</div>


<div
class="stat-icon"
style="
background:#ecfdf5;
color:#059669;
">

<i class="bi bi-check2-all"></i>

</div>


</div>


</div>


</div>



<!-- =====================================================
CREATE NOTIFICATION
===================================================== -->

<div class="create-card">


<div class="card-title">

<i class="bi bi-send-fill"></i>

&nbsp;

Create Notification

</div>


<form
method="POST"
action="notifications.php">


<div class="row g-3">


<!-- TITLE -->

<div class="col-md-6">


<label class="form-label">
Notification Title
</label>


<input
type="text"
name="title"
class="form-control"
placeholder="Example: Urgent Blood Needed"
maxlength="150"
required>


</div>



<!-- TYPE -->

<div class="col-md-3">


<label class="form-label">
Type
</label>


<select
name="type"
class="form-select">


<option value="General">
General
</option>


<option value="Urgent">
Urgent
</option>


<option value="Blood Request">
Blood Request
</option>


<option value="Announcement">
Announcement
</option>


<option value="System">
System
</option>


</select>


</div>



<!-- RECIPIENT -->

<div class="col-md-3">


<label class="form-label">
Send To
</label>


<select
name="recipient"
class="form-select">


<option value="all">

All Users

</option>


<?php if (
    $users_result &&
    mysqli_num_rows(
        $users_result
    ) > 0
): ?>


<?php while (
    $user =
    mysqli_fetch_assoc(
        $users_result
    )
): ?>


<option
value="<?php
echo (int)$user["id"];
?>">

<?php

echo htmlspecialchars(
    $user["full_name"]
);

?>

</option>


<?php endwhile; ?>


<?php endif; ?>


</select>


</div>



<!-- MESSAGE -->

<div class="col-12">


<label class="form-label">
Message
</label>


<textarea
name="message"
class="form-control"
placeholder="Write your notification message..."
required></textarea>


</div>



<!-- SEND -->

<div class="col-12">


<button
type="submit"
name="create_notification"
class="send-btn">

<i class="bi bi-send-fill"></i>

&nbsp;

Send Notification

</button>


</div>


</div>


</form>


</div>



<!-- =====================================================
NOTIFICATION LIST
===================================================== -->

<div class="list-card">


<div class="list-header">


<h3>

Notification History

</h3>


<a
href="notifications.php?read_all=1"
class="read-all"

onclick="
return confirm(
'Mark all notifications as read?'
);
">

<i class="bi bi-check2-all"></i>

&nbsp;

Mark All Read

</a>


</div>



<!-- SEARCH -->

<div class="search-box">


<form
method="GET"
action="notifications.php">


<input
type="text"
name="search"
class="form-control"
placeholder="Search notifications..."
value="<?php

echo htmlspecialchars(
    $search
);

?>">


<button
type="submit"
class="search-btn">

<i class="bi bi-search"></i>

Search

</button>


</form>


</div>



<!-- NOTIFICATIONS -->

<?php if (
    mysqli_num_rows($result) > 0
): ?>


<?php while (
    $notification =
    mysqli_fetch_assoc($result)
): ?>


<div class="
notification
<?php

if (
    $notification["is_read"] == 0
) {

    echo "unread";

}

?>">


<!-- ICON -->

<div class="notification-icon">


<?php

$type =
    strtolower(
        $notification["type"]
    );


if (
    $type === "urgent"
): ?>

<i class="bi bi-exclamation-triangle-fill"></i>


<?php elseif (
    $type === "blood request"
): ?>

<i class="bi bi-droplet-fill"></i>


<?php elseif (
    $type === "announcement"
): ?>

<i class="bi bi-megaphone-fill"></i>


<?php elseif (
    $type === "system"
): ?>

<i class="bi bi-gear-fill"></i>


<?php else: ?>

<i class="bi bi-bell-fill"></i>

<?php endif; ?>


</div>



<!-- CONTENT -->

<div class="notification-content">


<div class="notification-title">

<?php

echo htmlspecialchars(
    $notification["title"]
);

?>


<?php if (
    $notification["is_read"] == 0
): ?>

<span class="unread-badge">

Unread

</span>

<?php endif; ?>


</div>


<div class="notification-message">

<?php

echo nl2br(
    htmlspecialchars(
        $notification["message"]
    )
);

?>

</div>


<div class="notification-meta">


<span class="notification-type">

<?php

echo htmlspecialchars(
    $notification["type"]
);

?>

</span>



<span class="recipient">


<?php if (
    $notification["user_id"] === null
): ?>

<i class="bi bi-people-fill"></i>

&nbsp;

All Users


<?php else: ?>

<i class="bi bi-person-fill"></i>

&nbsp;

<?php

echo htmlspecialchars(
    $notification["full_name"]
);

?>

<?php endif; ?>


</span>



<span class="notification-time">

<i class="bi bi-clock"></i>

&nbsp;

<?php

echo date(
    "d M Y, h:i A",
    strtotime(
        $notification["created_at"]
    )
);

?>

</span>


</div>


</div>



<!-- ACTIONS -->

<div class="notification-actions">


<?php if (
    $notification["is_read"] == 0
): ?>


<a
href="notifications.php?read=<?php
echo (int)$notification["id"];
?>"

class="action-btn read-btn"

title="Mark as read">

<i class="bi bi-check2"></i>

</a>


<?php endif; ?>


<a
href="notifications.php?delete=<?php
echo (int)$notification["id"];
?>"

class="action-btn delete-btn"

title="Delete"

onclick="
return confirm(
'Delete this notification?'
);
">

<i class="bi bi-trash3-fill"></i>

</a>


</div>


</div>


<?php endwhile; ?>


<?php else: ?>


<div class="empty">

<i class="bi bi-bell-slash"></i>

No notifications found.

</div>


<?php endif; ?>


</div>


</main>


</body>

</html>

<?php

mysqli_stmt_close($stmt);

?>