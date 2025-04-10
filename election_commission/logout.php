<?php
session_name('election_commission');
session_start();
// Unset only the Election Commission session variables
unset($_SESSION['commission_username']);
session_destroy(); // Destroy the Election Commission session
header('Location: index.php');
exit();
?>