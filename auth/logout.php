<?php
require_once __DIR__ . '/../includes/init.php';

$_SESSION = array();

session_destroy();


header("Location: ../index.php");

exit();
?>