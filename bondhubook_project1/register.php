<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['registerName']);
        $email = filter_var(trim($_POST['registerEmail']), FILTER_VALIDATE_EMAIL);
        $password = $_POST['registerPassword'];
        $confirm_password = $_POST['registerConfirmPassword'];

        if (empty($name) || empty($email) || empty($password)) {
            throw new Exception("All fields are required");
        }

        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match");
        }

        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters");
        }
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            throw new Exception("Email already registered");
        }
        $stmt->close();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashed_password);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $_SESSION['user_id'] = $user_id;
            setcookie("user_id", $user_id, time() + (86400 * 365), "/");
            
            header("Location: dashboard.php");
            exit();
        } else {
            throw new Exception("Registration failed: " . $stmt->error);
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    } finally {
        if (isset($stmt)) $stmt->close();
        $conn->close();
    }
}
?>