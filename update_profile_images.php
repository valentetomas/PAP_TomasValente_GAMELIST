<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$upload_dir = "uploads/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$avatar_path = null;
$banner_path = null;

// Upload avatar
if (!empty($_FILES['avatar']['name'])) {
    $avatar_name = uniqid() . "_" . basename($_FILES['avatar']['name']);
    $avatar_target = $upload_dir . $avatar_name;
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_target)) {
        $avatar_path = $avatar_target;
    }
}

// Upload banner
if (!empty($_FILES['banner']['name'])) {
    $banner_name = uniqid() . "_" . basename($_FILES['banner']['name']);
    $banner_target = $upload_dir . $banner_name;
    if (move_uploaded_file($_FILES['banner']['tmp_name'], $banner_target)) {
        $banner_path = $banner_target;
    }
}

// Atualizar no banco apenas o que foi alterado
if ($avatar_path || $banner_path) {
    $query = "UPDATE users SET ";
    $params = [];
    $types = "";

    if ($avatar_path) {
        $query .= "avatar = ?, ";
        $params[] = $avatar_path;
        $types .= "s";
    }
    if ($banner_path) {
        $query .= "banner = ?, ";
        $params[] = $banner_path;
        $types .= "s";
    }

    $query = rtrim($query, ", ");
    $query .= " WHERE id = ?";
    $params[] = $user_id;
    $types .= "i";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
}

header("Location: profile.php");
exit();
