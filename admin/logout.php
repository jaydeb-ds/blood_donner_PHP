<?php

session_start();


// ================================================
// CLEAR ADMIN SESSION
// ================================================

unset($_SESSION["admin_logged_in"]);

unset($_SESSION["admin_email"]);

unset($_SESSION["admin_name"]);


// ================================================
// DESTROY SESSION
// ================================================

session_destroy();


// ================================================
// REDIRECT TO ADMIN LOGIN
// ================================================

header("Location: login.php");

exit();

?>