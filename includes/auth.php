<?php
session_start();
if (!isset($_SESSION['gymflow_user'])) {
    header('Location: login.php');
    exit;
}