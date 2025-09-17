<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_POST['loginEmail'];
    $password = $_POST['loginPassword'];

    $sql = "SELECT id, password FROM users WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            setcookie("user_id", $id, time() + (86400 * 30), "/");
            header("Location: dashboard.php");
            exit;
        } else {
            echo "Wrong Passwor! Try again";
        }
    } else {
        echo "Cudn't find Email!";
    }

    $stmt->close();
    $conn->close();
}
?>