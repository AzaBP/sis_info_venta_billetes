<?php
session_start();
unset($_SESSION['admin_simple_auth']);
header('Location: admin_login.php');
exit;

