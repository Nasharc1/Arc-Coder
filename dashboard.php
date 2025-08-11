<?php
// dashboard.php
session_start();
require_once 'config/database.php';
require_once 'config/database.php';
// Simple authentication check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get basic statistics with error handling
try {
    $stats = [];
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM students WHERE student_status = 'Active'");
    $stats['students'] = $stmt->fetch()['count'] ?? 0;
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM teachers WHERE employment_status = 'Active'");
    $stats['teachers'] = $stmt->fetch()['count'] ?? 0;
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM classes WHERE is_active = TRUE");
    $stats['classes'] = $stmt->fetch()['count'] ?? 0;
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM subjects WHERE is_active = TRUE");
    $stats['subjects'] = $stmt->fetch()['count'] ?? 0;
    
    // Get today's attendance summary
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late
        FROM student_attendance WHERE attendance_date = ?");
    $stmt->execute([$today]);
    $attendance = $stmt->fetch() ?: ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0];
    
} catch (Exception $e) {
    $stats = ['students' => 0, 'teachers' => 0, 'classes' => 0, 'subjects' => 0];
    $attendance = ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0];
    $error = "Error loading dashboard data: " . $e->getMessage();
}

// Get role-specific information
$role_info = [];
try {
    switch ($_SESSION['profile_type']) {
        case 'teacher':
            // Get teacher's classes
            $stmt = $conn->prepare("
                SELECT DISTINCT CONCAT(g.grade_name, ' ', c.section_name) as class_name
                FROM timetable t
                JOIN classes c ON t.class_id = c.class_id
                JOIN grades g ON c.grade_id = g.grade_id
                JOIN teachers teach ON t.teacher_id = teach.teacher_id
                JOIN users u ON teach.user_id = u.user_id
                WHERE u.user_id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $role_info['classes'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $role_info['title'] = 'Your Classes';
            break;
            
        case 'student':
            // Get student's class and recent grades
            $stmt = $conn->prepare("
                SELECT CONCAT(g.grade_name, ' ', c.section_name) as class_name
                FROM students s
                JOIN classes c ON s.class_id = c.class_id
                JOIN grades g ON c.grade_id = g.grade_id
                JOIN users u ON s.user_id = u.user_id
                WHERE u.user_id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $role_info['class'] = $stmt->fetchColumn() ?: 'No Class Assigned';
            $role_info['title'] = 'Your Information';
            break;
            
        case 'parent':
            // Get parent's children
            $stmt = $conn->prepare("
                SELECT CONCAT(p.first_name, ' ', p.last_name) as child_name,
                       CONCAT(g.grade_name, ' ', c.section_name) as class_name
                FROM parents par
                JOIN students s ON par.parent_id = s.parent_id
                JOIN persons p ON s.person_id = p.person_id
                LEFT JOIN classes c ON s.class_id = c.class_id
                LEFT JOIN grades g ON c.grade_id = g.grade_id
                JOIN users u ON par.user_id = u.user_id
                WHERE u.user_id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $role_info['children'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $role_info['title'] = 'Your Children';
            break;
    }
} catch (Exception $e) {
    $role_info = ['title' => 'Role Information', 'error' => $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Umoja Junior Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { padding-top: 70px; }
        .stat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            border-left: 4px solid transparent;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card.primary { border-left-color: #0d6efd; }
        .stat-card.success { border-left-color: #198754; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.info { border-left-color: #0dcaf0; }
        .stat-value { font-size: 2.5rem; font-weight: 700; margin-bottom: 10px; }
        .welcome-section { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .quick-action { 
            border-radius: 10px; 
            transition: all 0.3s ease;
            padding: 20px;
            text-decoration: none;
            display: block;
            color: inherit;
        }
        .quick-action:hover { 
            transform: translateY(-2px); 
            color: inherit;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>Umoja Junior Academy
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <?php if ($_SESSION['profile_type'] == 'admin' || $_SESSION['profile_type'] == 'teacher'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="students/list.php">
                            <i class="fas fa-users me-1"></i>Students
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($_SESSION['profile_type'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="teachers/list.php">
                            <i class="fas fa-chalkboard-teacher me-1"></i>Teachers
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-nav">
                    <span class="navbar-text me-3">
                        Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?> 
                        <small>(<?php echo htmlspecialchars($_SESSION['role_name']); ?>)</small>
                    </span>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-6 mb-3">
                        Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>! 👋
                    </h1>
                    <p class="lead mb-0">
                        <?php
                        $greetings = [
                            'admin' => 'Manage your school efficiently with our comprehensive dashboard.',
                            'teacher' => 'Track your students\' progress and manage your classes effectively.',
                            'student' => 'Stay updated with your academic progress and assignments.',
                            'parent' => 'Monitor your child\'s academic journey and school activities.'
                        ];
                        echo $greetings[$_SESSION['profile_type']] ?? 'Welcome to the school management system.';
                        ?>
                    </p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-school fa-5x opacity-75"></i>
                </div>
            </div>
        </div>

        <!-- Error Display -->
        <?php if (isset($error)): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase mb-2">Total Students</h6>
                            <div class="stat-value text-primary"><?php echo number_format($stats['students']); ?></div>
                            <span class="badge bg-primary-subtle text-primary">
                                <i class="fas fa-user-graduate me-1"></i>Active
                            </span>
                        </div>
                        <div class="text-primary opacity-50">
                            <i class="fas fa-user-graduate fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase mb-2">Total Teachers</h6>
                            <div class="stat-value text-success"><?php echo number_format($stats['teachers']); ?></div>
                            <span class="badge bg-success-subtle text-success">
                                <i class="fas fa-chalkboard-teacher me-1"></i>Active
                            </span>
                        </div>
                        <div class="text-success opacity-50">
                            <i class="fas fa-chalkboard-teacher fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase mb-2">Total Classes</h6>
                            <div class="stat-value text-warning"><?php echo number_format($stats['classes']); ?></div>
                            <span class="badge bg-warning-subtle text-warning">
                                <i class="fas fa-door-open me-1"></i>Active
                            </span>
                        </div>
                        <div class="text-warning opacity-50">
                            <i class="fas fa-door-open fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stat-card info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase mb-2">Today's Attendance</h6>
                            <div class="stat-value text-info">
                                <?php 
                                $percentage = $attendance['total'] > 0 ? round(($attendance['present'] / $attendance['total']) * 100, 1) : 0;
                                echo $percentage . '%';
                                ?>
                            </div>
                            <span class="badge bg-info-subtle text-info">
                                <i class="fas fa-calendar-check me-1"></i>
                                <?php echo $attendance['present']; ?>/<?php echo $attendance['total']; ?>
                            </span>
                        </div>
                        <div class="text-info opacity-50">
                            <i class="fas fa-calendar-check fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role-Specific Information -->
        <div class="row mt-4">
            <div class="col-lg-8">
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2 text-primary"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if ($_SESSION['profile_type'] == 'admin' || $_SESSION['profile_type'] == 'teacher'): ?>
                            <div class="col-md-3 mb-3">
                                <a href="students/list.php" class="quick-action bg-primary-subtle text-primary text-center">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <h6>Manage Students</h6>
                                    <small>View and manage student records</small>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="attendance/mark.php" class="quick-action bg-success-subtle text-success text-center">
                                    <i class="fas fa-calendar-check fa-2x mb-2"></i>
                                    <h6>Mark Attendance</h6>
                                    <small>Record daily attendance</small>
                                </a>
                            </div>
                            <?php endif; ?>

                            <?php if ($_SESSION['profile_type'] == 'admin'): ?>
                            <div class="col-md-3 mb-3">
                                <a href="fees/collect.php" class="quick-action bg-warning-subtle text-warning text-center">
                                    <i class="fas fa-money-bill fa-2x mb-2"></i>
                                    <h6>Collect Fees</h6>
                                    <small>Process fee payments</small>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="reports/index.php" class="quick-action bg-info-subtle text-info text-center">
                                    <i class="fas fa-chart-bar fa-2x mb-2"></i>
                                    <h6>View Reports</h6>
                                    <small>Generate analytics</small>
                                </a>
                            </div>
                            <?php endif; ?>

                            <?php if ($_SESSION['profile_type'] == 'student'): ?>
                            <div class="col-md-3 mb-3">
                                <a href="assignments/list.php" class="quick-action bg-primary-subtle text-primary text-center">
                                    <i class="fas fa-tasks fa-2x mb-2"></i>
                                    <h6>My Assignments</h6>
                                    <small>View your assignments</small>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="grades/view.php" class="quick-action bg-success-subtle text-success text-center">
                                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                                    <h6>My Grades</h6>
                                    <small>Check your performance</small>
                                </a>
                            </div>
                            <?php endif; ?>

                            <?php if ($_SESSION['profile_type'] == 'parent'): ?>
                            <div class="col-md-3 mb-3">
                                <a href="student/progress.php" class="quick-action bg-primary-subtle text-primary text-center">
                                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                                    <h6>Child's Progress</h6>
                                    <small>Monitor academic progress</small>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="fees/student.php" class="quick-action bg-warning-subtle text-warning text-center">
                                    <i class="fas fa-money-bill fa-2x mb-2"></i>
                                    <h6>Fee Payments</h6>
                                    <small>View payment history</small>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Role-Specific Information -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2 text-primary"></i>
                            <?php echo $role_info['title']; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($role_info['error'])): ?>
                        <div class="alert alert-warning">
                            <small><?php echo htmlspecialchars($role_info['error']); ?></small>
                        </div>
                        <?php elseif ($_SESSION['profile_type'] == 'teacher' && isset($role_info['classes'])): ?>
                        <h6>Classes You Teach:</h6>
                        <?php if (empty($role_info['classes'])): ?>
                        <p class="text-muted">No classes assigned yet.</p>
                        <?php else: ?>
                        <ul class="list-unstyled">
                            <?php foreach ($role_info['classes'] as $class): ?>
                            <li><i class="fas fa-door-open me-2 text-primary"></i><?php echo htmlspecialchars($class); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>

                        <?php elseif ($_SESSION['profile_type'] == 'student' && isset($role_info['class'])): ?>
                        <h6>Your Class:</h6>
                        <p><i class="fas fa-door-open me-2 text-primary"></i><?php echo htmlspecialchars($role_info['class']); ?></p>

                        <?php elseif ($_SESSION['profile_type'] == 'parent' && isset($role_info['children'])): ?>
                        <h6>Your Children:</h6>
                        <?php if (empty($role_info['children'])): ?>
                        <p class="text-muted">No children records found.</p>
                        <?php else: ?>
                        <?php foreach ($role_info['children'] as $child): ?>
                        <div class="mb-2">
                            <strong><?php echo htmlspecialchars($child['child_name']); ?></strong><br>
                            <small class="text-muted">
                                <i class="fas fa-door-open me-1"></i>
                                <?php echo htmlspecialchars($child['class_name'] ?: 'No Class'); ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- System Status -->
                <div class="card mt-3">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-server me-2 text-success"></i>System Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <p class="mb-2">
                                    <strong>Database:</strong><br>
                                    <span class="badge bg-success">Connected</span>
                                </p>
                                <p class="mb-2">
                                    <strong>Session:</strong><br>
                                    <span class="badge bg-success">Active</span>
                                </p>
                            </div>
                            <div class="col-6">
                                <p class="mb-2">
                                    <strong>Server Time:</strong><br>
                                    <small><?php echo date('Y-m-d H:i:s'); ?></small>
                                </p>
                                <p class="mb-2">
                                    <strong>PHP Version:</strong><br>
                                    <small><?php echo PHP_VERSION; ?></small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>