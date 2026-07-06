<?php
require '../includes/db.php';
session_destroy();
header('Location: login.php');
exit;
?>