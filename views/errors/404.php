<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .error-content {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 600px;
        }
        .error-icon {
            font-size: 6rem;
            color: #858796;
            margin-bottom: 1rem;
        }
        .error-title {
            font-size: 3rem;
            font-weight: bold;
            color: #5a5c69;
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 1.2rem;
            color: #858796;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-search"></i>
            </div>
            <h1 class="error-title">404</h1>
            <h2 class="mb-3">Page Not Found</h2>
            <p class="error-message">
                The page you are looking for doesn't exist or has been moved. 
                Please check the URL and try again.
            </p>
            <div class="d-flex gap-2 justify-content-center">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo APP_URL; ?>/views/dashboard/dashboard.php" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Go to Dashboard
                    </a>
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Go Back
                    </a>
                <?php else: ?>
                    <a href="<?php echo APP_URL; ?>/views/auth/login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

