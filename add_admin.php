<?php
require 'wa ra gud/config/database.php';

$database = new Database();
$conn = $database->connect();

$username = 'testadmin';
$password = 'password';
$email = 'testadmin@example.com';
$first_name = 'Test';
$last_name = 'Admin';


$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, email, password, first_name, last_name, role, status) VALUES (:username, :email, :password, :first_name, :last_name, 'admin', 'active')";

$stmt = $conn->prepare($sql);

$stmt->bindValue(':username', $username);
$stmt->bindValue(':email', $email);
$stmt->bindValue(':password', $hashed_password);
$stmt->bindValue(':first_name', $first_name);
$stmt->bindValue(':last_name', $last_name);


if ($stmt->execute()) {
    echo "New admin user created successfully";
} else {
    echo "Error: " . $stmt->errorInfo()[2];
}

?>