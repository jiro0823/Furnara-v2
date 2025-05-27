<?php
// Must come before output or session start
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, "/");
    session_start();
}

ob_start();

$conn = mysqli_connect('localhost', 'root', '', 'furnaradb');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
