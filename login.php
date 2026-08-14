<?php

session_start();

require_once "config/database.php";


// If user is already logged in
if (isset($_SESSION["user_id"])) {

    if ($_SESSION["role"] === "admin") {

        header("Location: admin/dashboard.php");
        exit();

    } elseif ($_SESSION["role"] === "donor") {

        header("Location: donor/dashboard.php");
        exit();

    } elseif ($_SESSION["role"] === "requester") {

        header("Location: requester/dashboard.php");
        exit();
    }
}


$error = "";


// ========================================
// LOGIN FORM
// ========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];


    // ========================================
    // VALIDATION
    // ========================================

    if (empty($email) || empty($password)) {

        $error = "Please enter your email and password.";

    } else {

        // ========================================
        // FIND USER
        // ========================================

        $sql = "
            SELECT
                id,
                full_name,
                email,
                phone,
                password,
                role,
                status
            FROM users
            WHERE email = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);


        // ========================================
        // CHECK USER
        // ========================================

        if (mysqli_num_rows($result) === 1) {

            $user = mysqli_fetch_assoc($result);


            // ========================================
            // CHECK ACCOUNT STATUS
            // ========================================

            if ($user["status"] !== "active") {

                $error =
                    "Your account is currently inactive.";

            }

            // ========================================
            // VERIFY PASSWORD
            // ========================================

            elseif (
                password_verify(
                    $password,
                    $user["password"]
                )
            ) {


                // ========================================
                // CREATE SESSION
                // ========================================

                session_regenerate_id(true);


                $_SESSION["user_id"] =
                    $user["id"];

                $_SESSION["full_name"] =
                    $user["full_name"];

                $_SESSION["email"] =
                    $user["email"];

                $_SESSION["phone"] =
                    $user["phone"];

                $_SESSION["role"] =
                    $user["role"];


                // ========================================
                // REDIRECT BASED ON ROLE
                // ========================================

                if ($user["role"] === "admin") {

                    header(
                        "Location: admin/dashboard.php"
                    );

                    exit();

                }

                elseif ($user["role"] === "donor") {

                    header(
                        "Location: donor/dashboard.php"
                    );

                    exit();

                }

                elseif ($user["role"] === "requester") {

                    header(
                        "Location: requester/dashboard.php"
                    );

                    exit();

                }

            }

            else {

                $error =
                    "Invalid email or password.";

            }

        }

        else {

            $error =
                "Invalid email or password.";

        }

        mysqli_stmt_close($stmt);
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

<title>Login | BloodConnect</title>


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

    min-height: 100vh;

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
LOGIN SECTION
======================================== */

.login-section {

    min-height: calc(100vh - 75px);

    display: flex;

    align-items: center;

    padding: 60px 15px;
}


/* ========================================
LOGIN CARD
======================================== */

.login-card {

    background: white;

    border-radius: 24px;

    padding: 40px;

    box-shadow:
        0 20px 60px rgba(0,0,0,0.08);

    border: 1px solid #f1f1f1;

    max-width: 450px;

    width: 100%;

    margin: auto;
}


/* ========================================
LOGIN ICON
======================================== */

.login-icon {

    width: 75px;

    height: 75px;

    background: var(--primary);

    color: white;

    border-radius: 22px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: 0 auto 20px;

    font-size: 32px;

    box-shadow:
        0 12px 30px rgba(230,57,70,0.25);
}


/* ========================================
TITLE
======================================== */

.login-title {

    text-align: center;

    margin-bottom: 30px;
}

.login-title h1 {

    font-size: 32px;

    font-weight: 800;

    margin-bottom: 10px;
}

.login-title p {

    color: #6b7280;

    font-size: 14px;

    margin: 0;
}


/* ========================================
FORM
======================================== */

.form-label {

    font-size: 14px;

    font-weight: 600;

    margin-bottom: 8px;
}

.form-control {

    border: 1px solid #e5e7eb;

    border-radius: 10px;

    padding: 13px 14px;

    font-size: 14px;
}

.form-control:focus {

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
LOGIN BUTTON
======================================== */

.login-submit {

    width: 100%;

    border: none;

    background: var(--primary);

    color: white;

    padding: 13px;

    border-radius: 10px;

    font-weight: 700;

    font-size: 15px;

    transition: 0.3s;
}

.login-submit:hover {

    background: var(--dark-red);

    transform: translateY(-2px);

    box-shadow:
        0 10px 25px rgba(230,57,70,0.2);
}


/* ========================================
ERROR
======================================== */

.error-message {

    background: #fff1f2;

    border: 1px solid #fecdd3;

    color: #be123c;

    border-radius: 10px;

    padding: 12px 15px;

    margin-bottom: 20px;

    font-size: 14px;
}


/* ========================================
REGISTER LINK
======================================== */

.register-text {

    text-align: center;

    margin-top: 25px;

    color: #6b7280;

    font-size: 14px;
}

.register-text a {

    color: var(--primary);

    font-weight: 700;

    text-decoration: none;
}

.register-text a:hover {

    text-decoration: underline;
}


/* ========================================
BACK HOME
======================================== */

.back-home {

    text-align: center;

    margin-top: 18px;
}

.back-home a {

    color: #6b7280;

    text-decoration: none;

    font-size: 13px;
}

.back-home a:hover {

    color: var(--primary);
}


/* ========================================
MOBILE
======================================== */

@media(max-width: 576px) {

    .login-card {

        padding: 30px 20px;

    }

    .login-title h1 {

        font-size: 28px;

    }

}

</style>

</head>


<body>


<!-- ========================================
NAVBAR
======================================== -->

<nav class="navbar">

<div class="container">


<a
class="navbar-brand"
href="index.php">

<span class="logo-icon">

<i class="bi bi-heart-pulse-fill"></i>

</span>

BloodConnect

</a>


</div>

</nav>



<!-- ========================================
LOGIN
======================================== -->

<section class="login-section">

<div class="container">


<div class="login-card">


<!-- ICON -->

<div class="login-icon">

<i class="bi bi-person-fill"></i>

</div>



<!-- TITLE -->

<div class="login-title">

<h1>
Welcome Back
</h1>

<p>
Login to your BloodConnect account
</p>

</div>



<!-- ERROR -->

<?php if (!empty($error)): ?>

<div class="error-message">

<i class="bi bi-exclamation-circle-fill"></i>

&nbsp;

<?php echo htmlspecialchars($error); ?>

</div>

<?php endif; ?>



<!-- LOGIN FORM -->

<form
method="POST"
action="login.php">


<!-- EMAIL -->

<div class="mb-3">

<label class="form-label">

Email Address

</label>

<input
type="email"
name="email"
class="form-control"
placeholder="Enter your email"
autocomplete="email"
required>

</div>



<!-- PASSWORD -->

<div class="mb-4">

<label class="form-label">

Password

</label>


<div class="password-wrapper">

<input
type="password"
name="password"
id="password"
class="form-control"
placeholder="Enter your password"
autocomplete="current-password"
required>


<button
type="button"
class="password-toggle"
onclick="togglePassword()">

<i
class="bi bi-eye"
id="passwordIcon">
</i>

</button>

</div>

</div>



<!-- LOGIN -->

<button
type="submit"
class="login-submit">

<i class="bi bi-box-arrow-in-right"></i>

&nbsp;

Login

</button>


</form>



<!-- REGISTER -->

<div class="register-text">

Don't have an account?

<a href="register.php">

Become a Donor

</a>

</div>



<!-- HOME -->

<div class="back-home">

<a href="index.php">

<i class="bi bi-arrow-left"></i>

Back to Home

</a>

</div>


</div>

</div>

</section>



<!-- ========================================
JAVASCRIPT
======================================== -->

<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    const icon =
        document.getElementById("passwordIcon");


    if (password.type === "password") {

        password.type = "text";

        icon.classList.remove("bi-eye");

        icon.classList.add("bi-eye-slash");

    }

    else {

        password.type = "password";

        icon.classList.remove("bi-eye-slash");

        icon.classList.add("bi-eye");

    }

}

</script>


</body>

</html>