<?php

// Database server
$host = "localhost:3307";

// MySQL username
$username = "root";

// MySQL password
$password = "";

// Database name
$database = "blood";


// Create database connection
$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database
);


// Check connection
if (!$conn) {

    die(
        "Database Connection Failed: "
        . mysqli_connect_error()
    );
}
// if($conn)
//     {
//         echo "Database Connection Successful";
//     }



// Set character encoding
mysqli_set_charset($conn, "utf8mb4");

?>