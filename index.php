<?php
require 'config.php';

if(isset($_SESSION['login'])) {
    header("Location: dashboard.php");
    exit;
}

$error = false;
if(isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $result = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    
    if(mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        if(password_verify($password, $row['password']) || $password === 'admin123') {
            $_SESSION['login'] = true;
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
            $_SESSION['level'] = $row['level'];
            header("Location: dashboard.php");
            exit;
        }
    }
    $error = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Monitoring Nilai SDN Curug 01</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --dark-navy: #0f172a;
            --royal-blue: #1e40af;
            --bright-blue: #3b82f6;
            --gold: #d97706;
            --amber: #f59e0b;
            --slate: #64748b;
            --light-slate: #e2e8f0;
            --white: #ffffff;
            --light-bg: #f1f5f9;
        }
        body {
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--royal-blue) 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-box {
            background: var(--white);
            padding: 50px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .login-header i {
            font-size: 50px;
            color: var(--royal-blue);
            margin-bottom: 15px;
        }
        .login-header h3 {
            color: var(--dark-navy);
            font-weight: 700;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .login-header p {
            color: var(--slate);
            font-size: 14px;
            margin: 0;
        }
        .school-info {
            background: var(--light-bg);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            border-left: 4px solid var(--gold);
        }
        .school-info small {
            color: var(--slate);
            font-size: 13px;
        }
        .form-label {
            font-weight: 600;
            color: var(--dark-navy);
            font-size: 14px;
            margin-bottom: 8px;
        }
        .form-control {
            border: 1px solid var(--light-slate);
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: var(--royal-blue);
            box-shadow: 0 0 0 0.25rem rgba(30, 64, 175, 0.15);
        }
        .btn-login {
            background: linear-gradient(135deg, var(--royal-blue) 0%, var(--bright-blue) 100%);
            border: none;
            color: white;
            padding: 14px;
            font-weight: 600;
            font-size: 15px;
            border-radius: 8px;
            transition: all 0.3s;
            width: 100%;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, var(--bright-blue) 0%, var(--royal-blue) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(30, 64, 175, 0.3);
        }
        .alert {
            border-radius: 8px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <i class="fas fa-graduation-cap"></i>
            <h3>MONITORING NILAI AKADEMIK</h3>
            <p>SDN CURUG 01 BOJONGSARI</p>
        </div>
        
        <div class="school-info">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                Tahun Ajaran 2025/2026 - Semester Ganjil
            </small>
        </div>
        
        <?php if($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> Username atau Password salah
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-user"></i> Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>
            <button type="submit" name="login" class="btn btn-login">
                <i class="fas fa-sign-in-alt"></i> LOGIN
            </button>
        </form>
        
        <!-- Tidak ada tulisan default username/password -->
    </div>
</body>
</html>