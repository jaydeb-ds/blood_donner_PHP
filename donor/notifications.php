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

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "donor"
) {

    header("Location: ../login.php");
    exit();

}


$user_id = (int) $_SESSION["user_id"];


// =====================================================
// GET DONOR INFORMATION
// =====================================================

$donor_sql = "
    SELECT
        u.full_name,
        d.blood_group

    FROM users u

    INNER JOIN donor_profiles d
        ON u.id = d.user_id

    WHERE u.id = ?

    LIMIT 1
";


$donor_stmt = mysqli_prepare(
    $conn,
    $donor_sql
);

mysqli_stmt_bind_param(
    $donor_stmt,
    "i",
    $user_id
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

    die("Donor profile not found.");

}


$donor =
    mysqli_fetch_assoc(
        $donor_result
    );


mysqli_stmt_close(
    $donor_stmt
);


$donor_name =
    $donor["full_name"];


// =====================================================
// MARK SINGLE NOTIFICATION AS READ
// =====================================================

if (
    isset($_GET["read"]) &&
    is_numeric($_GET["read"])
) {

    $notification_id =
        (int) $_GET["read"];


    $read_sql = "
        UPDATE notifications

        SET is_read = 1

        WHERE id = ?

        AND user_id = ?
    ";


    $read_stmt =
        mysqli_prepare(
            $conn,
            $read_sql
        );


    mysqli_stmt_bind_param(
        $read_stmt,
        "ii",
        $notification_id,
        $user_id
    );


    mysqli_stmt_execute(
        $read_stmt
    );


    mysqli_stmt_close(
        $read_stmt
    );


    // Redirect to remove query string

    header(
        "Location: notifications.php"
    );

    exit();

}


// =====================================================
// MARK ALL AS READ
// =====================================================

if (
    isset($_GET["mark_all"])
) {

    $mark_all_sql = "
        UPDATE notifications

        SET is_read = 1

        WHERE user_id = ?

        AND is_read = 0
    ";


    $mark_all_stmt =
        mysqli_prepare(
            $conn,
            $mark_all_sql
        );


    mysqli_stmt_bind_param(
        $mark_all_stmt,
        "i",
        $user_id
    );


    mysqli_stmt_execute(
        $mark_all_stmt
    );


    mysqli_stmt_close(
        $mark_all_stmt
    );


    header(
        "Location: notifications.php"
    );

    exit();

}


// =====================================================
// DELETE NOTIFICATION
// =====================================================

if (
    isset($_GET["delete"]) &&
    is_numeric($_GET["delete"])
) {

    $notification_id =
        (int) $_GET["delete"];


    $delete_sql = "
        DELETE FROM notifications

        WHERE id = ?

        AND user_id = ?
    ";


    $delete_stmt =
        mysqli_prepare(
            $conn,
            $delete_sql
        );


    mysqli_stmt_bind_param(
        $delete_stmt,
        "ii",
        $notification_id,
        $user_id
    );


    mysqli_stmt_execute(
        $delete_stmt
    );


    mysqli_stmt_close(
        $delete_stmt
    );


    header(
        "Location: notifications.php"
    );

    exit();

}


// =====================================================
// FILTER
// =====================================================

$filter =
    isset($_GET["filter"])
    ? $_GET["filter"]
    : "all";


$allowed_filters = [
    "all",
    "unread",
    "blood_request",
    "donation",
    "account",
    "system"
];


if (
    !in_array(
        $filter,
        $allowed_filters,
        true
    )
) {

    $filter = "all";

}


// =====================================================
// GET NOTIFICATIONS
// =====================================================

$sql = "
    SELECT
        id,
        title,
        message,
        type,
        related_id,
        is_read,
        created_at

    FROM notifications

    WHERE user_id = ?
";


$params = [
    $user_id
];

$types = "i";


// Unread filter

if ($filter === "unread") {

    $sql .= "
        AND is_read = 0
    ";

}


// Notification type filter

elseif (
    in_array(
        $filter,
        [
            "blood_request",
            "donation",
            "account",
            "system"
        ],
        true
    )
) {

    $sql .= "
        AND type = ?
    ";

    $types .= "s";

    $params[] =
        $filter;

}


// Order

$sql .= "
    ORDER BY
        is_read ASC,
        created_at DESC
";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


// =====================================================
// DYNAMIC BIND PARAM
// =====================================================

$bind_params = [];

$bind_params[] =
    $types;


foreach (
    $params as $key => $value
) {

    $bind_params[] =
        &$params[$key];

}


call_user_func_array(
    [
        $stmt,
        "bind_param"
    ],
    $bind_params
);


mysqli_stmt_execute(
    $stmt
);


$result =
    mysqli_stmt_get_result(
        $stmt
    );


// =====================================================
// UNREAD COUNT
// =====================================================

$count_sql = "
    SELECT COUNT(*) AS unread_count

    FROM notifications

    WHERE user_id = ?

    AND is_read = 0
";


$count_stmt =
    mysqli_prepare(
        $conn,
        $count_sql
    );


mysqli_stmt_bind_param(
    $count_stmt,
    "i",
    $user_id
);


mysqli_stmt_execute(
    $count_stmt
);


$count_result =
    mysqli_stmt_get_result(
        $count_stmt
    );


$count_row =
    mysqli_fetch_assoc(
        $count_result
    );


$unread_count =
    (int) $count_row["unread_count"];


mysqli_stmt_close(
    $count_stmt
);


// =====================================================
// TOTAL COUNT
// =====================================================

$total_sql = "
    SELECT COUNT(*) AS total_count

    FROM notifications

    WHERE user_id = ?
";


$total_stmt =
    mysqli_prepare(
        $conn,
        $total_sql
    );


mysqli_stmt_bind_param(
    $total_stmt,
    "i",
    $user_id
);


mysqli_stmt_execute(
    $total_stmt
);


$total_result =
    mysqli_stmt_get_result(
        $total_stmt
    );


$total_row =
    mysqli_fetch_assoc(
        $total_result
    );


$total_count =
    (int) $total_row["total_count"];


mysqli_stmt_close(
    $total_stmt
);


// =====================================================
// HELPER: TIME AGO
// =====================================================

function timeAgo($datetime)
{

    $time =
        strtotime($datetime);

    $current =
        time();

    $difference =
        $current - $time;


    if ($difference < 60) {

        return "Just now";

    }


    if ($difference < 3600) {

        $minutes =
            floor(
                $difference / 60
            );

        return $minutes .
            " minute" .
            ($minutes > 1 ? "s" : "") .
            " ago";

    }


    if ($difference < 86400) {

        $hours =
            floor(
                $difference / 3600
            );

        return $hours .
            " hour" .
            ($hours > 1 ? "s" : "") .
            " ago";

    }


    if ($difference < 604800) {

        $days =
            floor(
                $difference / 86400
            );

        return $days .
            " day" .
            ($days > 1 ? "s" : "") .
            " ago";

    }


    return date(
        "d M Y",
        $time
    );
}


// =====================================================
// NOTIFICATION ICON
// =====================================================

function getNotificationIcon($type)
{

    switch ($type) {

        case "blood_request":

            return "bi-droplet-fill";

        case "donation":

            return "bi-heart-pulse-fill";

        case "account":

            return "bi-person-fill";

        default:

            return "bi-info-circle-fill";
    }
}


// =====================================================
// NOTIFICATION COLOR
// =====================================================

function getNotificationClass($type)
{

    switch ($type) {

        case "blood_request":

            return "notification-blood";

        case "donation":

            return "notification-donation";

        case "account":

            return "notification-account";

        default:

            return "notification-system";
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
Notifications | BloodConnect
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

}


/* =====================================================
SIDEBAR
===================================================== */

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


/* =====================================================
MAIN
===================================================== */

.main {

    margin-left: 250px;

    min-height: 100vh;
}


/* =====================================================
TOPBAR
===================================================== */

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


/* =====================================================
CONTENT
===================================================== */

.content {

    padding: 35px;

    max-width: 1100px;

    margin: auto;
}


/* =====================================================
HEADER
===================================================== */

.page-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-end;

    margin-bottom: 25px;
}


.page-header h2 {

    font-size: 28px;

    font-weight: 800;

    margin-bottom: 6px;
}


.page-header p {

    color: #6b7280;

    font-size: 14px;

    margin: 0;
}


.mark-all {

    color: var(--primary);

    text-decoration: none;

    font-size: 13px;

    font-weight: 700;

    background: white;

    border: 1px solid #eee;

    border-radius: 9px;

    padding: 10px 15px;
}


.mark-all:hover {

    background: var(--light-red);

    color: var(--dark-red);
}


/* =====================================================
SUMMARY
===================================================== */

.summary {

    display: flex;

    align-items: center;

    gap: 15px;

    background: white;

    border: 1px solid #eee;

    border-radius: 15px;

    padding: 18px 20px;

    margin-bottom: 20px;
}


.summary-icon {

    width: 48px;

    height: 48px;

    border-radius: 13px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;
}


.summary strong {

    font-size: 15px;
}


.summary small {

    display: block;

    color: #6b7280;

    margin-top: 3px;
}


.unread-number {

    margin-left: auto;

    background: var(--primary);

    color: white;

    min-width: 32px;

    height: 32px;

    padding: 0 8px;

    border-radius: 50px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 12px;

    font-weight: 800;
}


/* =====================================================
FILTER
===================================================== */

.filter-tabs {

    background: white;

    border: 1px solid #eee;

    border-radius: 13px;

    padding: 7px;

    display: flex;

    gap: 5px;

    margin-bottom: 20px;

    overflow-x: auto;
}


.filter-tabs a {

    white-space: nowrap;

    text-decoration: none;

    color: #6b7280;

    padding: 9px 14px;

    border-radius: 8px;

    font-size: 12px;

    font-weight: 600;
}


.filter-tabs a:hover {

    background: #f3f4f6;
}


.filter-tabs a.active {

    background: var(--primary);

    color: white;
}


/* =====================================================
NOTIFICATION LIST
===================================================== */

.notification-list {

    background: white;

    border: 1px solid #eee;

    border-radius: 18px;

    overflow: hidden;
}


/* =====================================================
NOTIFICATION ITEM
===================================================== */

.notification-item {

    display: flex;

    align-items: flex-start;

    gap: 15px;

    padding: 20px 22px;

    border-bottom: 1px solid #f1f1f1;

    position: relative;

    transition: 0.2s;
}


.notification-item:last-child {

    border-bottom: none;
}


.notification-item:hover {

    background: #fafafa;
}


.notification-item.unread {

    background: #fffafa;
}


.notification-item.unread::before {

    content: "";

    position: absolute;

    left: 0;

    top: 0;

    bottom: 0;

    width: 3px;

    background: var(--primary);
}


/* =====================================================
NOTIFICATION ICON
===================================================== */

.notification-icon {

    width: 48px;

    height: 48px;

    flex-shrink: 0;

    border-radius: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;
}


.notification-blood {

    background: #fff1f2;

    color: #e63946;
}


.notification-donation {

    background: #ecfdf5;

    color: #059669;
}


.notification-account {

    background: #eff6ff;

    color: #2563eb;
}


.notification-system {

    background: #f3f4f6;

    color: #4b5563;
}


/* =====================================================
BODY
===================================================== */

.notification-body {

    flex: 1;

    min-width: 0;
}


.notification-title {

    font-size: 14px;

    font-weight: 800;

    margin-bottom: 5px;
}


.notification-message {

    color: #6b7280;

    font-size: 13px;

    line-height: 1.6;

    margin-bottom: 7px;
}


.notification-time {

    color: #9ca3af;

    font-size: 11px;
}


/* =====================================================
ACTIONS
===================================================== */

.notification-actions {

    display: flex;

    align-items: center;

    gap: 7px;
}


.action-btn {

    width: 34px;

    height: 34px;

    border: 1px solid #eee;

    background: white;

    color: #6b7280;

    border-radius: 8px;

    display: flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    font-size: 14px;
}


.action-btn:hover {

    color: var(--primary);

    background: var(--light-red);

}


.unread-dot {

    width: 8px;

    height: 8px;

    border-radius: 50%;

    background: var(--primary);

    margin-top: 6px;
}


/* =====================================================
EMPTY STATE
===================================================== */

.empty-state {

    padding: 75px 25px;

    text-align: center;
}


.empty-icon {

    width: 75px;

    height: 75px;

    border-radius: 20px;

    background: var(--light-red);

    color: var(--primary);

    display: flex;

    align-items: center;

    justify-content: center;

    margin: auto auto 20px;

    font-size: 30px;
}


.empty-state h4 {

    font-weight: 800;

    margin-bottom: 8px;
}


.empty-state p {

    color: #6b7280;

    font-size: 13px;

    max-width: 500px;

    margin: auto;
}


/* =====================================================
RESPONSIVE
===================================================== */

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


    .page-header {

        display: block;
    }


    .page-header h2 {

        font-size: 24px;
    }


    .mark-all {

        display: inline-block;

        margin-top: 15px;
    }


    .notification-item {

        padding: 18px 15px;

        gap: 10px;
    }


    .notification-icon {

        width: 42px;

        height: 42px;

        font-size: 17px;
    }


    .notification-actions {

        flex-direction: column;
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

<a href="profile.php">

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

<a
href="notifications.php"
class="active">

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



<!-- =====================================================
MAIN
===================================================== -->

<main class="main">


<!-- =====================================================
TOPBAR
===================================================== -->

<header class="topbar">


<div class="page-title">

Notifications

</div>


<div class="top-user">


<div class="user-avatar">

<?php

echo strtoupper(
    substr(
        $donor_name,
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
    $donor_name
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



<!-- =====================================================
CONTENT
===================================================== -->

<div class="content">


<!-- =====================================================
HEADER
===================================================== -->

<div class="page-header">


<div>

<h2>

Notifications

</h2>


<p>

Stay updated with your blood donation
activities and requests.

</p>

</div>


<?php if ($unread_count > 0): ?>

<a
href="notifications.php?mark_all=1"
class="mark-all">

<i class="bi bi-check2-all"></i>

&nbsp;

Mark All as Read

</a>

<?php endif; ?>


</div>



<!-- =====================================================
SUMMARY
===================================================== -->

<div class="summary">


<div class="summary-icon">

<i class="bi bi-bell-fill"></i>

</div>


<div>

<strong>

Your Notifications

</strong>


<small>

You have
<?php

echo $unread_count;

?>
unread notification<?php

echo $unread_count !== 1
    ? "s"
    : "";

?>.

</small>

</div>


<?php if ($unread_count > 0): ?>

<div class="unread-number">

<?php

echo $unread_count;

?>

</div>

<?php endif; ?>


</div>



<!-- =====================================================
FILTER TABS
===================================================== -->

<div class="filter-tabs">


<a
href="notifications.php?filter=all"
class="<?php

echo $filter === "all"
    ? "active"
    : "";

?>">

All

</a>


<a
href="notifications.php?filter=unread"
class="<?php

echo $filter === "unread"
    ? "active"
    : "";

?>">

Unread

<?php if ($unread_count > 0): ?>

(
<?php

echo $unread_count;

?>
)

<?php endif; ?>

</a>


<a
href="notifications.php?filter=blood_request"
class="<?php

echo $filter === "blood_request"
    ? "active"
    : "";

?>">

<i class="bi bi-droplet-fill"></i>

Blood Requests

</a>


<a
href="notifications.php?filter=donation"
class="<?php

echo $filter === "donation"
    ? "active"
    : "";

?>">

<i class="bi bi-heart-pulse-fill"></i>

Donations

</a>


<a
href="notifications.php?filter=account"
class="<?php

echo $filter === "account"
    ? "active"
    : "";

?>">

<i class="bi bi-person-fill"></i>

Account

</a>


<a
href="notifications.php?filter=system"
class="<?php

echo $filter === "system"
    ? "active"
    : "";

?>">

<i class="bi bi-info-circle"></i>

System

</a>


</div>



<!-- =====================================================
NOTIFICATION LIST
===================================================== -->

<div class="notification-list">


<?php if (
    mysqli_num_rows($result) > 0
): ?>


<?php while (
    $notification =
        mysqli_fetch_assoc($result)
): ?>


<?php

$notification_class =
    getNotificationClass(
        $notification["type"]
    );


$notification_icon =
    getNotificationIcon(
        $notification["type"]
    );

?>


<div class="
notification-item
<?php

echo $notification["is_read"] == 0
    ? "unread"
    : "";

?>">


<!-- ICON -->

<div
class="
notification-icon
<?php

echo $notification_class;

?>">

<i class="
bi
<?php

echo $notification_icon;

?>">
</i>

</div>



<!-- BODY -->

<div class="notification-body">


<div class="notification-title">

<?php

echo htmlspecialchars(
    $notification["title"]
);

?>

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


<div class="notification-time">

<i class="bi bi-clock"></i>

<?php

echo timeAgo(
    $notification["created_at"]
);

?>

</div>


</div>



<!-- UNREAD DOT -->

<?php if (
    $notification["is_read"] == 0
): ?>

<div class="unread-dot"></div>

<?php endif; ?>



<!-- ACTIONS -->

<div class="notification-actions">


<?php if (
    $notification["is_read"] == 0
): ?>

<a
href="notifications.php?read=<?php
echo $notification["id"];
?>"
class="action-btn"
title="Mark as read">

<i class="bi bi-check2"></i>

</a>

<?php endif; ?>


<a
href="notifications.php?delete=<?php
echo $notification["id"];
?>"
class="action-btn"
title="Delete"
onclick="
return confirm(
'Delete this notification?'
);
">

<i class="bi bi-trash3"></i>

</a>


</div>


</div>


<?php endwhile; ?>


<?php else: ?>


<!-- =================================================
EMPTY STATE
================================================= -->

<div class="empty-state">


<div class="empty-icon">

<i class="bi bi-bell-slash"></i>

</div>


<h4>

No Notifications

</h4>


<p>

You don't have any notifications
for this category right now.

When there is a new blood request,
donation update, or account update,
it will appear here.

</p>


</div>


<?php endif; ?>


</div>


</div>

</main>



<!-- Bootstrap -->

<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>