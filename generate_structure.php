<?php

$paths = [
    // Config
    "config/config.php",
    "config/database.php",

    // Includes
    "includes/auth.php",
    "includes/navigation.php",
    "includes/SchoolManagementSystem.php",

    // Classes
    "classes/AttendanceManager.php",
    "classes/StudentManager.php",
    "classes/TeacherManager.php",
    "classes/ExamManager.php",
    "classes/FeeManager.php",

    // Assets
    "assets/css/dashboard.css",
    "assets/js/dashboard.js",
    "assets/images/avatar-placeholder.png",
    "assets/templates/student-import-template.xlsx",

    // API
    "api/recent-activities.php",
    "api/upcoming-events.php",
    "api/search-students.php",
    "api/get-student-fees.php",
    "api/get-payment-analytics.php",

    // Students
    "students/list.php",
    "students/add.php",
    "students/edit.php",
    "students/view.php",
    "students/import-students.php",
    "students/export-students.php",
    "students/bulk-operations.php",

    // Teachers
    "teachers/list.php",
    "teachers/add.php",
    "teachers/edit.php",
    "teachers/assignments.php",

    // Attendance
    "attendance/mark.php",
    "attendance/student.php",
    "attendance/teacher.php",
    "attendance/reports.php",

    // Academics
    "academics/classes.php",
    "academics/subjects.php",
    "academics/timetable.php",
    "academics/assignments.php",

    // Exams
    "exams/schedule.php",
    "exams/results.php",
    "exams/report-cards.php",
    "exams/analysis.php",

    // Fees
    "fees/collect.php",
    "fees/structure.php",
    "fees/reports.php",
    "fees/defaulters.php",

    // Communication
    "communication/announcements.php",
    "communication/events.php",
    "communication/messages.php",
    "communication/sms.php",

    // Reports
    "reports/index.php",

    // Settings
    "settings/school.php",
    "settings/users.php",
    "settings/backup.php",
    "settings/system.php",

    // Root files
    "login.php",
    "dashboard.php",
    "logout.php",
    "profile.php",
    "unauthorized.php",
    "index.php",
    "README.md",
];

// Create each file with empty content and necessary folders
foreach ($paths as $filePath) {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "Created directory: $dir\n";
    }
    if (!file_exists($filePath)) {
        file_put_contents($filePath, "");
        echo "Created file: $filePath\n";
    }
}

echo "\n✅ Project structure created in current directory successfully.\n";
