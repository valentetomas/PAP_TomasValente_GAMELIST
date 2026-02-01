<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "gamelist_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erro na ligação à base de dados: " . $conn->connect_error);
}
