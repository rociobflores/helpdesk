<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
$usuario = $_SESSION['usuario'];
echo json_encode($usuario);
?>