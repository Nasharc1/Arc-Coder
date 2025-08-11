<?php
require_once 'config/config.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: white;
        }
        .feature-card {
            transition: transform 0.3s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="login.php">
                    <i class="fas fa-sign-in-alt me-1"></i>Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">
                        Welcome to <?php echo SITE_NAME; ?>
                    </h1>
                    <p class="lead mb-4">
                        A comprehensive school management system designed to streamline 
                        academic and administrative processes for modern education.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="login.php" class="btn btn-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Get Started
                        </a>
                        <a href="#features" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-info-circle me-2"></i>Learn More
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-school fa-10x opacity-75"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Key Features</h2>
                <p class="lead text-muted">Everything you need to manage your school efficiently</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-user-graduate fa-3x text-primary mb-3"></i>
                            <h5>Student Management</h5>
                            <p class="text-muted">
                                Complete student information system with enrollment, 
                                academic records, and progress tracking.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-calendar-check fa-3x text-success mb-3"></i>
                            <h5>Attendance Tracking</h5>
                            <p class="text-muted">
                                Digital attendance system for students and teachers 
                                with real-time reporting and analytics.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-money-bill-wave fa-3x text-warning mb-3"></i>
                            <h5>Fee Management</h5>
                            <p class="text-muted">
                                Automated fee collection, receipt generation, and 
                                financial reporting with multiple payment methods.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-clipboard-list fa-3x text-info mb-3"></i>
                            <h5>Examination System</h5>
                            <p class="text-muted">
                                Complete exam management with result processing, 
                                report cards, and performance analytics.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-chalkboard-teacher fa-3x text-danger mb-3"></i>
                            <h5>Teacher Portal</h5>
                            <p class="text-muted">
                                Dedicated teacher interface for managing classes, 
                                assignments, and student progress.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-chart-bar fa-3x text-secondary mb-3"></i>
                            <h5>Analytics & Reports</h5>
                            <p class="text-muted">
                                Comprehensive reporting and analytics dashboard 
                                for data-driven decision making.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p class="mb-0">
                © <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. 
                All rights reserved.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>