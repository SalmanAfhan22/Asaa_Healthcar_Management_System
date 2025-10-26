<?php
// Include config (it will handle session)
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - ASAA Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .access-denied-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 text-center">
                <div class="card access-denied-card">
                    <div class="card-body p-5">
                        <i class="fas fa-shield-alt fa-5x text-danger mb-4"></i>
                        <h1 class="display-4 text-danger mb-3">Access Denied</h1>
                        <p class="lead mb-4">You don't have permission to access this resource.</p>
                        
                        <?php if (isset($_SESSION['user_name'])): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-user"></i> 
                                Logged in as: <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                                (<?= htmlspecialchars($_SESSION['user_role']) ?>)
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="javascript:history.back()" class="btn btn-primary me-3">
                                <i class="fas fa-arrow-left"></i> Go Back
                            </a>
                            <a href="<?= BASE_URL ?>/views/auth/login.php" class="btn btn-success">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
