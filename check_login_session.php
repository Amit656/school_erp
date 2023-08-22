<?php
session_start();

if (isset($_SESSION['ValidUser']))
{
    header('location:/admin/admin_default.php');
    exit;
}
else
{
    header('location:/admin/login.php');
    exit;
}
?>