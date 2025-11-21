<?php
// Config should already be loaded if included from index.php
// But if accessed directly, load it
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}

// Ensure APP_URL is defined
if (!defined('APP_URL')) {
    define('APP_URL', 'http://localhost/projects/projects/ERP');
}

// Redirect if already logged in
if (function_exists('isLoggedIn') && isLoggedIn()) {
    $dashboardUrl = rtrim(APP_URL, '/') . '/views/dashboard/dashboard.php';
    if (function_exists('redirect')) {
        redirect($dashboardUrl);
    } else {
        header('Location: ' . $dashboardUrl);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo rtrim(APP_URL, '/'); ?>/assets/css/style.css" rel="stylesheet">
    
    <style>
        /* Landing Page Specific Styles */
        .landing-navbar {
            background: var(--surface);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border-bottom: var(--border-width-thin) solid var(--glass-border);
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }
        
        .landing-navbar .navbar-brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text);
            text-decoration: none;
        }
        
        .landing-navbar .nav-link {
            color: var(--text);
            font-weight: 600;
            padding: 0.5rem 1rem;
            transition: all var(--transition-normal) var(--transition-ease);
            border-radius: var(--border-radius-md);
        }
        
        .landing-navbar .nav-link:hover {
            color: var(--brand-accent);
            background: rgba(34, 193, 195, 0.1);
        }
        
        .landing-hero {
            min-height: 100vh;
            background: linear-gradient(135deg, #6e56cf 0%, #22c1c3 100%);
            position: relative;
            display: flex;
            align-items: center;
            overflow: hidden;
            padding-top: 80px;
        }
        
        .landing-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1920&q=80') center/cover;
            opacity: 0.2;
            z-index: 0;
        }
        
        .landing-hero-content {
            position: relative;
            z-index: 1;
            padding: 4rem 0;
        }
        
        .landing-hero h1 {
            font-size: 4rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            animation: fadeInUp 0.6s ease-out;
            letter-spacing: -0.02em;
        }
        
        .landing-hero p {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease-out;
        }
        
        .landing-features {
            padding: 5rem 0;
            background: var(--bg);
        }
        
        .feature-card {
            background: var(--surface);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: var(--border-width-thin) solid var(--glass-border);
            border-radius: var(--border-radius-xl);
            padding: 2.5rem;
            text-align: center;
            transition: all var(--transition-normal) var(--transition-ease);
            height: 100%;
            box-shadow: var(--shadow-glass);
        }
        
        .feature-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-xl);
            border-color: rgba(255, 255, 255, 0.25);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: var(--brand-accent);
            margin-bottom: 1.5rem;
            transition: all var(--transition-normal) var(--transition-ease);
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
            color: var(--brand-primary);
        }
        
        .feature-card h4 {
            color: var(--text);
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .feature-card p {
            color: var(--muted);
        }
        
        .landing-stats {
            padding: 4rem 0;
            background: var(--surface);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            position: relative;
        }
        
        .stat-item {
            text-align: center;
            color: var(--text);
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--brand-primary);
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }
        
        .stat-label {
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--muted);
        }
        
        .landing-cta {
            padding: 5rem 0;
            background: var(--bg);
            text-align: center;
        }
        
        .landing-showcase {
            padding: 5rem 0;
            background: var(--bg-secondary);
        }
        
        .showcase-image {
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-xl);
            transition: all var(--transition-normal) var(--transition-ease);
            overflow: hidden;
        }
        
        .showcase-image:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-xl);
        }
        
        .showcase-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .landing-hero h1 {
                font-size: 2.5rem;
            }
            
            .landing-hero p {
                font-size: 1rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .landing-hero {
                padding-top: 70px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="landing-navbar">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a class="navbar-brand" href="<?php echo rtrim(APP_URL, '/'); ?>">
                    <i class="fas fa-tasks me-2"></i>
                    <?php echo APP_NAME; ?>
                </a>
                <div class="d-flex align-items-center gap-3">
                    <a href="<?php echo rtrim(APP_URL, '/'); ?>/views/auth/login.php" class="nav-link">Login</a>
                    <a href="<?php echo rtrim(APP_URL, '/'); ?>/views/auth/register.php" class="btn btn-gradient">Get Started</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="landing-hero">
        <div class="container">
            <div class="landing-hero-content">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <h1 class="animate-on-scroll">Manage Projects. Complete Tasks. Achieve More.</h1>
                        <p class="animate-on-scroll">A powerful ERP system designed to streamline your workflow and boost productivity. Collaborate with your team, track progress, and deliver results.</p>
                        <div class="d-flex gap-3 flex-wrap animate-on-scroll">
                            <a href="<?php echo rtrim(APP_URL, '/'); ?>/views/auth/login.php" class="btn btn-gradient btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Get Started
                            </a>
                            <a href="#features" class="btn btn-glass btn-lg">
                                <i class="fas fa-info-circle me-2"></i>Learn More
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6 text-center">
                        <div class="animate-on-scroll">
                            <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&q=80" alt="Dashboard Preview" class="img-fluid rounded-3 shadow-lg" style="max-height: 500px; object-fit: cover; border: 2px solid rgba(255,255,255,0.2);">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="landing-features" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="page-title animate-on-scroll">Powerful Features</h2>
                <p class="page-description animate-on-scroll">Everything you need to manage your projects efficiently</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card animate-on-scroll">
                        <div class="feature-icon">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <h4>Project Management</h4>
                        <p>Create, organize, and track projects with ease. Monitor progress, assign team members, and stay on top of deadlines.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card animate-on-scroll">
                        <div class="feature-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h4>Task Tracking</h4>
                        <p>Break down projects into manageable tasks. Set priorities, due dates, and track completion status in real-time.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card animate-on-scroll">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Team Collaboration</h4>
                        <p>Work together seamlessly. Assign tasks, share comments, and keep everyone in the loop with real-time updates.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card animate-on-scroll">
                        <div class="feature-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h4>Analytics & Reports</h4>
                        <p>Get insights into your team's performance with comprehensive analytics and customizable reports.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card animate-on-scroll">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h4>Notifications</h4>
                        <p>Stay informed with real-time notifications for task updates, deadlines, and important events.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card animate-on-scroll">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Secure & Reliable</h4>
                        <p>Your data is protected with enterprise-grade security. Regular backups ensure your work is always safe.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Showcase Section -->
    <section class="landing-showcase">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <div class="showcase-image animate-on-scroll">
                        <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&q=80" alt="Analytics Dashboard" class="img-fluid">
                    </div>
                </div>
                <div class="col-lg-6">
                    <h2 class="page-title animate-on-scroll">Real-Time Analytics</h2>
                    <p class="page-description animate-on-scroll">Track your team's performance with comprehensive analytics and visual reports. Make data-driven decisions with interactive charts and graphs.</p>
                    <ul class="list-unstyled mt-4">
                        <li class="mb-3 animate-on-scroll">
                            <i class="fas fa-check-circle me-2" style="color: var(--brand-accent);"></i>
                            <strong>Performance Metrics</strong> - Monitor team productivity and project progress
                        </li>
                        <li class="mb-3 animate-on-scroll">
                            <i class="fas fa-check-circle me-2" style="color: var(--brand-accent);"></i>
                            <strong>Custom Reports</strong> - Generate detailed reports in PDF or Excel format
                        </li>
                        <li class="mb-3 animate-on-scroll">
                            <i class="fas fa-check-circle me-2" style="color: var(--brand-accent);"></i>
                            <strong>Visual Dashboards</strong> - Beautiful charts and graphs for better insights
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="landing-stats">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-item animate-on-scroll">
                        <div class="stat-number" data-count="1000">0</div>
                        <div class="stat-label">Active Users</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item animate-on-scroll">
                        <div class="stat-number" data-count="5000">0</div>
                        <div class="stat-label">Projects Completed</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item animate-on-scroll">
                        <div class="stat-number" data-count="50000">0</div>
                        <div class="stat-label">Tasks Managed</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item animate-on-scroll">
                        <div class="stat-number" data-count="99">0</div>
                        <div class="stat-label">% Uptime</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="landing-cta">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="page-title animate-on-scroll">Ready to Get Started?</h2>
                    <p class="page-description animate-on-scroll mb-4">Join thousands of teams already using our platform to manage their projects more efficiently.</p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap animate-on-scroll">
                        <a href="<?php echo rtrim(APP_URL, '/'); ?>/views/auth/register.php" class="btn btn-gradient btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </a>
                        <a href="<?php echo rtrim(APP_URL, '/'); ?>/views/auth/login.php" class="btn btn-glass btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4" style="background: var(--bg-secondary); border-top: 4px solid var(--border);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0" style="color: var(--text);">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0" style="color: var(--muted); font-weight: 600;">
                        Made by <span style="color: var(--brand-primary);">Hesra Rash</span> Engineer
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo rtrim(APP_URL, '/'); ?>/assets/js/main.js"></script>
    
    <script>
        // Animate numbers on scroll
        function animateNumbers() {
            const numbers = document.querySelectorAll('.stat-number[data-count]');
            
            numbers.forEach(number => {
                const target = parseInt(number.getAttribute('data-count'));
                let current = 0;
                const increment = target / 30;
                const duration = 300;
                const frameTime = duration / 30;
                
                const counter = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        number.textContent = target;
                        clearInterval(counter);
                    } else {
                        number.textContent = Math.floor(current);
                    }
                }, frameTime);
            });
        }
        
        // Trigger number animation when stats section is visible
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateNumbers();
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        const statsSection = document.querySelector('.landing-stats');
        if (statsSection) {
            statsObserver.observe(statsSection);
        }
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
