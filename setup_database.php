<?php
// setup_database.php - Complete Database Setup Script for Umoja Junior Academy
// Updated with all fixes and improvements

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🏫 Umoja Junior Academy - Complete Database Setup</h2>\n";
echo "<pre>\n";

require_once "config/database.php";

// Create DB connection
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("❌ Database connection failed.\n");
}

echo "✅ Database connection successful!\n\n";

// Function to execute SQL statements one by one
function executeSQLStatements($conn, $statements) {
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $index => $sql) {
        $sql = trim($sql);
        if (empty($sql) || strpos($sql, '--') === 0) {
            continue; // Skip empty lines and comments
        }
        
        try {
            $conn->exec($sql);
            $success_count++;
            
            // Extract table name or operation for better feedback
            if (preg_match('/CREATE TABLE\s+`?(\w+)`?/i', $sql, $matches)) {
                echo "✅ Created table: {$matches[1]}\n";
            } elseif (preg_match('/INSERT INTO\s+`?(\w+)`?/i', $sql, $matches)) {
                echo "✅ Inserted data into: {$matches[1]}\n";
            } elseif (preg_match('/CREATE VIEW\s+`?(\w+)`?/i', $sql, $matches)) {
                echo "✅ Created view: {$matches[1]}\n";
            } elseif (preg_match('/CREATE PROCEDURE\s+`?(\w+)`?/i', $sql, $matches)) {
                echo "✅ Created procedure: {$matches[1]}\n";
            } elseif (preg_match('/CREATE TRIGGER\s+`?(\w+)`?/i', $sql, $matches)) {
                echo "✅ Created trigger: {$matches[1]}\n";
            } elseif (preg_match('/CREATE INDEX\s+`?(\w+)`?/i', $sql, $matches)) {
                echo "✅ Created index: {$matches[1]}\n";
            } else {
                echo "✅ Executed statement " . ($index + 1) . "\n";
            }
            
        } catch (PDOException $e) {
            $error_count++;
            // Check if it's a "table already exists" error and treat as warning
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠️ Table/View already exists - skipping: " . substr($sql, 0, 50) . "...\n";
                $error_count--; // Don't count as error
                $success_count++; // Count as success
            } else {
                echo "❌ Error in statement " . ($index + 1) . ": " . $e->getMessage() . "\n";
                echo "   SQL: " . substr($sql, 0, 100) . "...\n";
            }
        }
    }
    
    return ['success' => $success_count, 'errors' => $error_count];
}

// UPDATED SQL STATEMENTS ARRAY - FIXED AND IMPROVED
$sql_statements = [
    // Drop tables if they exist (optional - use only if you want fresh install)
    // "DROP TABLE IF EXISTS activity_logs",
    // "DROP TABLE IF EXISTS school_settings",
    // ... (add other DROP statements if needed)

    // ====================================
    // 1. USER MANAGEMENT AND AUTHENTICATION
    // ====================================
    
    "CREATE TABLE IF NOT EXISTS `user_roles` (
        `role_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `role_name` VARCHAR(50) NOT NULL UNIQUE,
        `description` TEXT,
        `permissions` JSON,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `users` (
        `user_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL,
        `role_id` INT(11) NOT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `last_login` TIMESTAMP NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`role_id`) REFERENCES `user_roles`(`role_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // ====================================
    // 2. ACADEMIC STRUCTURE
    // ====================================

    "CREATE TABLE IF NOT EXISTS `academic_years` (
        `academic_year_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `year_name` VARCHAR(20) NOT NULL,
        `start_date` DATE NOT NULL,
        `end_date` DATE NOT NULL,
        `is_current` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `terms` (
        `term_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `academic_year_id` INT(11) NOT NULL,
        `term_name` VARCHAR(20) NOT NULL,
        `term_number` INT(11) NOT NULL,
        `start_date` DATE NOT NULL,
        `end_date` DATE NOT NULL,
        `is_current` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`academic_year_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `grades` (
        `grade_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `grade_name` VARCHAR(20) NOT NULL,
        `grade_level` INT(11) NOT NULL,
        `description` TEXT,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `classes` (
        `class_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `grade_id` INT(11) NOT NULL,
        `section_name` VARCHAR(10) NOT NULL,
        `class_teacher_id` INT(11),
        `capacity` INT(11) DEFAULT 30,
        `academic_year_id` INT(11) NOT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`grade_id`) REFERENCES `grades`(`grade_id`),
        FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`academic_year_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `subjects` (
        `subject_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `subject_name` VARCHAR(100) NOT NULL,
        `subject_code` VARCHAR(20) NOT NULL UNIQUE,
        `subject_category` ENUM('Core', 'Languages', 'Sciences', 'Arts', 'Physical Education', 'Technical', 'Other') DEFAULT 'Core',
        `subject_type` ENUM('Core', 'Elective', 'Extra-curricular') DEFAULT 'Core',
        `credit_hours` INT(11) DEFAULT 1,
        `description` TEXT,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // NEW: Subject Assignments Table (FIXED)
    "CREATE TABLE IF NOT EXISTS `subject_assignments` (
        `assignment_id` INT(11) NOT NULL AUTO_INCREMENT,
        `subject_id` INT(11) NOT NULL,
        `teacher_id` INT(11) NOT NULL,
        `class_id` INT(11) NOT NULL,
        `academic_year` VARCHAR(20) NOT NULL DEFAULT '2025-2026',
        `term` ENUM('Term 1','Term 2','Term 3','All Terms') DEFAULT 'All Terms',
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`assignment_id`),
        UNIQUE KEY `unique_assignment` (`subject_id`,`teacher_id`,`class_id`,`academic_year`,`term`),
        KEY `idx_subject` (`subject_id`),
        KEY `idx_teacher` (`teacher_id`),
        KEY `idx_class` (`class_id`),
        KEY `idx_academic_year` (`academic_year`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `grade_subjects` (
        `grade_subject_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `grade_id` INT(11) NOT NULL,
        `subject_id` INT(11) NOT NULL,
        `is_mandatory` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`grade_id`) REFERENCES `grades`(`grade_id`),
        FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`subject_id`),
        UNIQUE KEY `unique_grade_subject` (`grade_id`, `subject_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // ====================================
    // 3. PEOPLE MANAGEMENT
    // ====================================

    "CREATE TABLE IF NOT EXISTS `persons` (
        `person_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `first_name` VARCHAR(50) NOT NULL,
        `last_name` VARCHAR(50) NOT NULL,
        `middle_name` VARCHAR(50),
        `date_of_birth` DATE,
        `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
        `phone` VARCHAR(20),
        `email` VARCHAR(100),
        `address` TEXT,
        `city` VARCHAR(50),
        `state` VARCHAR(50),
        `postal_code` VARCHAR(20),
        `country` VARCHAR(50) DEFAULT 'Kenya',
        `emergency_contact_name` VARCHAR(100),
        `emergency_contact_phone` VARCHAR(20),
        `profile_photo` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `teachers` (
        `teacher_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `person_id` INT(11) NOT NULL,
        `user_id` INT(11),
        `employee_id` VARCHAR(50) NOT NULL UNIQUE,
        `hire_date` DATE NOT NULL,
        `qualification` VARCHAR(200),
        `specialization` VARCHAR(100),
        `years_experience` INT(11) DEFAULT 0,
        `salary` DECIMAL(10,2),
        `department` VARCHAR(100),
        `employment_status` ENUM('Active', 'On Leave', 'Resigned', 'Terminated') DEFAULT 'Active',
        `is_class_teacher` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`person_id`) REFERENCES `persons`(`person_id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `parents` (
        `parent_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `person_id` INT(11) NOT NULL,
        `user_id` INT(11),
        `occupation` VARCHAR(100),
        `workplace` VARCHAR(200),
        `monthly_income` DECIMAL(10,2),
        `relationship_type` ENUM('Father', 'Mother', 'Guardian', 'Other') NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`person_id`) REFERENCES `persons`(`person_id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `students` (
        `student_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `person_id` INT(11) NOT NULL,
        `user_id` INT(11),
        `admission_number` VARCHAR(50) NOT NULL UNIQUE,
        `admission_date` DATE NOT NULL,
        `class_id` INT(11),
        `parent_id` INT(11),
        `blood_group` VARCHAR(5),
        `medical_conditions` TEXT,
        `student_status` ENUM('Active', 'Graduated', 'Transferred', 'Dropped') DEFAULT 'Active',
        `previous_school` VARCHAR(200),
        `transportation_required` TINYINT(1) DEFAULT 0,
        `hostel_required` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`person_id`) REFERENCES `persons`(`person_id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
        FOREIGN KEY (`class_id`) REFERENCES `classes`(`class_id`) ON DELETE SET NULL,
        FOREIGN KEY (`parent_id`) REFERENCES `parents`(`parent_id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `staff` (
        `staff_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `person_id` INT(11) NOT NULL,
        `user_id` INT(11),
        `employee_id` VARCHAR(50) NOT NULL UNIQUE,
        `hire_date` DATE NOT NULL,
        `position` VARCHAR(100) NOT NULL,
        `department` VARCHAR(100),
        `salary` DECIMAL(10,2),
        `employment_status` ENUM('Active', 'On Leave', 'Resigned', 'Terminated') DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`person_id`) REFERENCES `persons`(`person_id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // ====================================
    // 4. TIMETABLE AND SCHEDULING
    // ====================================

    "CREATE TABLE IF NOT EXISTS `time_slots` (
        `slot_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `slot_name` VARCHAR(50) NOT NULL,
        `start_time` TIME NOT NULL,
        `end_time` TIME NOT NULL,
        `is_break` TINYINT(1) DEFAULT 0,
        `sort_order` INT(11) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `timetable` (
        `timetable_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `class_id` INT(11) NOT NULL,
        `subject_id` INT(11) NOT NULL,
        `teacher_id` INT(11) NOT NULL,
        `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
        `slot_id` INT(11) NOT NULL,
        `room_number` VARCHAR(20),
        `academic_year` VARCHAR(20) DEFAULT '2025-2026',
        `term` ENUM('Term 1', 'Term 2', 'Term 3') DEFAULT 'Term 1',
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`class_id`) REFERENCES `classes`(`class_id`),
        FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`subject_id`),
        FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`teacher_id`),
        FOREIGN KEY (`slot_id`) REFERENCES `time_slots`(`slot_id`),
        UNIQUE KEY `unique_class_day_slot` (`class_id`, `day_of_week`, `slot_id`, `academic_year`, `term`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // ====================================
    // 5. ATTENDANCE MANAGEMENT
    // ====================================

    "CREATE TABLE IF NOT EXISTS `student_attendance` (
        `attendance_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `class_id` INT(11) NOT NULL,
        `attendance_date` DATE NOT NULL,
        `status` ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL DEFAULT 'Present',
        `time_in` TIME,
        `time_out` TIME,
        `remarks` TEXT,
        `marked_by` INT(11) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`),
        FOREIGN KEY (`class_id`) REFERENCES `classes`(`class_id`),
        UNIQUE KEY `unique_student_date` (`student_id`, `attendance_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `teacher_attendance` (
        `teacher_attendance_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `teacher_id` INT(11) NOT NULL,
        `attendance_date` DATE NOT NULL,
        `time_in` TIME,
        `time_out` TIME,
        `status` ENUM('Present', 'Absent', 'Late', 'Half Day', 'On Leave') NOT NULL DEFAULT 'Present',
        `leave_type` ENUM('Sick', 'Casual', 'Maternity', 'Emergency', 'Other') NULL,
        `remarks` TEXT,
        `marked_by` INT(11),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`teacher_id`),
        UNIQUE KEY `unique_teacher_date` (`teacher_id`, `attendance_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // ====================================
    // 6. ACADEMIC ASSESSMENTS (UPDATED)
    // ====================================

    "CREATE TABLE IF NOT EXISTS `exam_types` (
        `exam_type_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `exam_name` VARCHAR(100) NOT NULL,
        `description` TEXT,
        `weightage` DECIMAL(5,2) DEFAULT 100.00,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `exams` (
        `exam_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `exam_name` VARCHAR(200) NOT NULL,
        `exam_type` ENUM('Mid-Term', 'End-Term', 'Assignment', 'Quiz', 'Project') DEFAULT 'Mid-Term',
        `class_id` INT(11) NOT NULL,
        `subject_id` INT(11) NOT NULL,
        `exam_date` DATE NOT NULL,
        `start_time` TIME NOT NULL,
        `end_time` TIME NOT NULL,
        `total_marks` DECIMAL(6,2) NOT NULL DEFAULT 100,
        `pass_marks` DECIMAL(6,2) NOT NULL DEFAULT 50,
        `room_number` VARCHAR(20),
        `teacher_id` INT(11),
        `academic_year` VARCHAR(20) DEFAULT '2025-2026',
        `term` ENUM('Term 1', 'Term 2', 'Term 3') DEFAULT 'Term 1',
        `instructions` TEXT,
        `is_published` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`class_id`) REFERENCES `classes`(`class_id`),
        FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`subject_id`),
        FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`teacher_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `exam_results` (
        `exam_result_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `exam_id` INT(11) NOT NULL,
        `student_id` INT(11) NOT NULL,
        `marks_obtained` DECIMAL(6,2) NOT NULL DEFAULT 0,
        `total_marks` DECIMAL(6,2) NOT NULL DEFAULT 100,
        `percentage` DECIMAL(5,2) GENERATED ALWAYS AS ((marks_obtained / total_marks) * 100) STORED,
        `grade` VARCHAR(5),
        `teacher_comments` TEXT,
        `is_absent` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`exam_id`) REFERENCES `exams`(`exam_id`) ON DELETE CASCADE,
        FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_exam_student` (`exam_id`, `student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // ====================================
    // 7. FINANCIAL MANAGEMENT (UPDATED)
    // ====================================

    "CREATE TABLE IF NOT EXISTS `fee_types` (
        `fee_type_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `fee_name` VARCHAR(100) NOT NULL,
        `fee_category` ENUM('Tuition', 'Transport', 'Meals', 'Uniform', 'Books', 'Activities', 'Examination', 'Other') DEFAULT 'Other',
        `description` TEXT,
        `base_amount` DECIMAL(10,2) NOT NULL,
        `is_mandatory` TINYINT(1) DEFAULT 1,
        `frequency` ENUM('One-time', 'Monthly', 'Quarterly', 'Annually') DEFAULT 'One-time',
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `fee_grade_amounts` (
        `fee_grade_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `fee_type_id` INT(11) NOT NULL,
        `grade_id` INT(11) NOT NULL,
        `amount` DECIMAL(10,2) NOT NULL,
        `academic_year` VARCHAR(20) DEFAULT '2025-2026',
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`fee_type_id`) REFERENCES `fee_types`(`fee_type_id`) ON DELETE CASCADE,
        FOREIGN KEY (`grade_id`) REFERENCES `grades`(`grade_id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_fee_grade_year` (`fee_type_id`, `grade_id`, `academic_year`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `student_fees` (
        `student_fee_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `fee_type_id` INT(11) NOT NULL,
        `amount_due` DECIMAL(10,2) NOT NULL,
        `amount_paid` DECIMAL(10,2) DEFAULT 0.00,
        `due_date` DATE NOT NULL,
        `academic_year` VARCHAR(20) DEFAULT '2025-2026',
        `term` ENUM('Term 1', 'Term 2', 'Term 3') DEFAULT 'Term 1',
        `payment_status` ENUM('Pending', 'Paid', 'Partial', 'Overdue') DEFAULT 'Pending',
        `last_payment_date` DATE NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`) ON DELETE CASCADE,
        FOREIGN KEY (`fee_type_id`) REFERENCES `fee_types`(`fee_type_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `payments` (
        `payment_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `student_id` INT(11) NOT NULL,
        `fee_type_id` INT(11) NOT NULL,
        `amount_paid` DECIMAL(10,2) NOT NULL,
        `payment_date` DATE NOT NULL,
        `payment_method` ENUM('Cash', 'Bank Transfer', 'Mobile Money', 'Cheque', 'Card') NOT NULL,
        `transaction_reference` VARCHAR(100),
        `receipt_number` VARCHAR(50) NOT NULL UNIQUE,
        `academic_year` VARCHAR(20) DEFAULT '2025-2026',
        `term` ENUM('Term 1', 'Term 2', 'Term 3') DEFAULT 'Term 1',
        `collected_by` INT(11) NOT NULL,
        `remarks` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`) ON DELETE CASCADE,
        FOREIGN KEY (`fee_type_id`) REFERENCES `fee_types`(`fee_type_id`) ON DELETE CASCADE,
        FOREIGN KEY (`collected_by`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // ====================================
    // 8. COMMUNICATION AND EVENTS
    // ====================================

    "CREATE TABLE IF NOT EXISTS `announcements` (
        `announcement_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `title` VARCHAR(200) NOT NULL,
        `content` TEXT NOT NULL,
        `target_audience` ENUM('All', 'Students', 'Teachers', 'Parents', 'Staff', 'Specific Class') NOT NULL,
        `target_class_id` INT(11),
        `priority` ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
        `published_by` INT(11) NOT NULL,
        `published_date` DATE NOT NULL,
        `expiry_date` DATE,
        `is_active` TINYINT(1) DEFAULT 1,
        `attachment` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`target_class_id`) REFERENCES `classes`(`class_id`),
        FOREIGN KEY (`published_by`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `events` (
        `event_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `event_name` VARCHAR(200) NOT NULL,
        `description` TEXT,
        `event_date` DATE NOT NULL,
        `start_time` TIME,
        `end_time` TIME,
        `venue` VARCHAR(200),
        `organizer_id` INT(11) NOT NULL,
        `event_type` ENUM('Academic', 'Sports', 'Cultural', 'Meeting', 'Holiday', 'Other') NOT NULL,
        `target_audience` ENUM('All', 'Students', 'Teachers', 'Parents', 'Staff', 'Specific Class') NOT NULL,
        `target_class_id` INT(11),
        `is_mandatory` TINYINT(1) DEFAULT 0,
        `registration_required` TINYINT(1) DEFAULT 0,
        `max_participants` INT(11),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`organizer_id`) REFERENCES `users`(`user_id`),
        FOREIGN KEY (`target_class_id`) REFERENCES `classes`(`class_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // ====================================
    // 9. LIBRARY MANAGEMENT
    // ====================================

    "CREATE TABLE IF NOT EXISTS `books` (
        `book_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
        `isbn` VARCHAR(20) UNIQUE,
        `title` VARCHAR(200) NOT NULL,
        `author` VARCHAR(200) NOT NULL,
        `publisher` VARCHAR(200),
        `publication_year` YEAR,category VARCHAR(100),
total_copies INT(11) DEFAULT 1,
available_copies INT(11) DEFAULT 1,
shelf_location VARCHAR(50),
price DECIMAL(8,2),
is_active TINYINT(1) DEFAULT 1,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS `book_issues` (
    `issue_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
    `book_id` INT(11) NOT NULL,
    `student_id` INT(11),
    `teacher_id` INT(11),
    `staff_id` INT(11),
    `issue_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `return_date` DATE,
    `fine_amount` DECIMAL(6,2) DEFAULT 0.00,
    `status` ENUM('Issued', 'Returned', 'Lost', 'Damaged') DEFAULT 'Issued',
    `issued_by` INT(11) NOT NULL,
    `returned_to` INT(11),
    `remarks` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`book_id`) REFERENCES `books`(`book_id`),
    FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`),
    FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`teacher_id`),
    FOREIGN KEY (`staff_id`) REFERENCES `staff`(`staff_id`),
    FOREIGN KEY (`issued_by`) REFERENCES `users`(`user_id`),
    FOREIGN KEY (`returned_to`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// ====================================
// 10. REPORTS AND ANALYTICS
// ====================================

"CREATE TABLE IF NOT EXISTS `report_cards` (
    `report_card_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
    `student_id` INT(11) NOT NULL,
    `class_id` INT(11) NOT NULL,
    `academic_year` VARCHAR(20) DEFAULT '2025-2026',
    `term` ENUM('Term 1', 'Term 2', 'Term 3') NOT NULL,
    `total_marks` DECIMAL(8,2),
    `obtained_marks` DECIMAL(8,2),
    `percentage` DECIMAL(5,2),
    `grade` VARCHAR(5),
    `rank_in_class` INT(11),
    `attendance_percentage` DECIMAL(5,2),
    `teacher_remarks` TEXT,
    `principal_remarks` TEXT,
    `generated_by` INT(11) NOT NULL,
    `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`),
    FOREIGN KEY (`class_id`) REFERENCES `classes`(`class_id`),
    FOREIGN KEY (`generated_by`) REFERENCES `users`(`user_id`),
    UNIQUE KEY `unique_student_term` (`student_id`, `academic_year`, `term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// ====================================
// 11. SYSTEM CONFIGURATION (UPDATED)
// ====================================

"CREATE TABLE IF NOT EXISTS `school_settings` (
    `setting_id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT,
    `setting_type` ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    `description` TEXT,
    `category` VARCHAR(50),
    `is_editable` TINYINT(1) DEFAULT 1,
    `updated_by` INT(11),
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_id`),
    UNIQUE KEY `unique_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `activity_logs` (
    `log_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT(11),
    `action` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(50),
    `record_id` INT(11),
    `old_data` JSON,
    `new_data` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// ====================================
// 12. BEHAVIORAL RECORDS (NEW)
// ====================================

"CREATE TABLE IF NOT EXISTS `behavioral_records` (
    `record_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
    `student_id` INT(11) NOT NULL,
    `incident_date` DATE NOT NULL,
    `incident_type` ENUM('Positive', 'Disciplinary', 'Academic', 'Social', 'Other') NOT NULL,
    `description` TEXT NOT NULL,
    `action_taken` TEXT,
    `severity` ENUM('Minor', 'Moderate', 'Major', 'Severe') DEFAULT 'Minor',
    `recorded_by` INT(11) NOT NULL,
    `parent_notified` TINYINT(1) DEFAULT 0,
    `follow_up_required` TINYINT(1) DEFAULT 0,
    `academic_year` VARCHAR(20) DEFAULT '2025-2026',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`) ON DELETE CASCADE,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// ====================================
// 13. EXTRACURRICULAR ACTIVITIES (NEW)
// ====================================

"CREATE TABLE IF NOT EXISTS `extracurricular_activities` (
    `activity_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
    `student_id` INT(11) NOT NULL,
    `activity_name` VARCHAR(100) NOT NULL,
    `activity_type` ENUM('Sports', 'Music', 'Drama', 'Art', 'Academic Club', 'Community Service', 'Other') NOT NULL,
    `participation_level` ENUM('Participant', 'Leader', 'Captain', 'Representative') DEFAULT 'Participant',
    `achievements` TEXT,
    `start_date` DATE,
    `end_date` DATE,
    `academic_year` VARCHAR(20) DEFAULT '2025-2026',
    `supervisor_id` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`) ON DELETE CASCADE,
    FOREIGN KEY (`supervisor_id`) REFERENCES `teachers`(`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// ====================================
// 14. INDEXES FOR PERFORMANCE
// ====================================

"CREATE INDEX IF NOT EXISTS `idx_students_admission_number` ON `students`(`admission_number`)",
"CREATE INDEX IF NOT EXISTS `idx_students_class_id` ON `students`(`class_id`)",
"CREATE INDEX IF NOT EXISTS `idx_teachers_employee_id` ON `teachers`(`employee_id`)",
"CREATE INDEX IF NOT EXISTS `idx_attendance_date` ON `student_attendance`(`attendance_date`)",
"CREATE INDEX IF NOT EXISTS `idx_attendance_student_date` ON `student_attendance`(`student_id`, `attendance_date`)",
"CREATE INDEX IF NOT EXISTS `idx_teacher_attendance_date` ON `teacher_attendance`(`teacher_id`, `attendance_date`)",
"CREATE INDEX IF NOT EXISTS `idx_exam_results_student` ON `exam_results`(`student_id`)",
"CREATE INDEX IF NOT EXISTS `idx_exam_results_exam` ON `exam_results`(`exam_id`)",
"CREATE INDEX IF NOT EXISTS `idx_payments_date` ON `payments`(`payment_date`)",
"CREATE INDEX IF NOT EXISTS `idx_timetable_class_day` ON `timetable`(`class_id`, `day_of_week`)",
"CREATE INDEX IF NOT EXISTS `idx_subject_assignments_teacher` ON `subject_assignments`(`teacher_id`)",
"CREATE INDEX IF NOT EXISTS `idx_student_fees_status` ON `student_fees`(`payment_status`)",

// ====================================
// 15. ADD FOREIGN KEY CONSTRAINTS (After table creation)
// ====================================

"ALTER TABLE `subject_assignments` ADD CONSTRAINT `fk_subject_assignment_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE",
"ALTER TABLE `subject_assignments` ADD CONSTRAINT `fk_subject_assignment_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE",
"ALTER TABLE `subject_assignments` ADD CONSTRAINT `fk_subject_assignment_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE",

"ALTER TABLE `classes` ADD CONSTRAINT `fk_classes_class_teacher` FOREIGN KEY (`class_teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE SET NULL"
];
// ====================================
// CREATE VIEWS (Updated and Fixed)
// ====================================
$view_statements = [
"CREATE OR REPLACE VIEW student_details_view AS
SELECT
s.student_id,
s.admission_number,
CONCAT(p.first_name, ' ', COALESCE(p.middle_name, ''), ' ', p.last_name) AS full_name,
p.first_name,
p.last_name,
p.date_of_birth,
p.gender,
p.phone,
p.email,
p.address,
g.grade_name,
c.section_name,
CONCAT(g.grade_name, ' ', c.section_name) AS class_name,
s.student_status,
CONCAT(pp.first_name, ' ', pp.last_name) AS parent_name,
pp.phone AS parent_phone,
pp.email AS parent_email,
s.created_at
FROM students s
JOIN persons p ON s.person_id = p.person_id
LEFT JOIN classes c ON s.class_id = c.class_id
LEFT JOIN grades g ON c.grade_id = g.grade_id
LEFT JOIN parents par ON s.parent_id = par.parent_id
LEFT JOIN persons pp ON par.person_id = pp.person_id",
"CREATE OR REPLACE VIEW `teacher_details_view` AS
SELECT 
    t.teacher_id,
    t.employee_id,
    CONCAT(p.first_name, ' ', COALESCE(p.middle_name, ''), ' ', p.last_name) AS full_name,
    p.first_name,
    p.last_name,
    p.date_of_birth,
    p.gender,
    p.phone,
    p.email,
    p.address,
    t.qualification,
    t.specialization,
    t.years_experience,
    t.department,
    t.employment_status,
    t.is_class_teacher,
    t.hire_date,
    t.created_at
FROM teachers t
JOIN persons p ON t.person_id = p.person_id",

"CREATE OR REPLACE VIEW `daily_attendance_summary` AS
SELECT 
    sa.attendance_date,
    c.class_id,
    CONCAT(g.grade_name, ' ', c.section_name) AS class_name,
    COUNT(sa.student_id) AS total_students,
    SUM(CASE WHEN sa.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
    SUM(CASE WHEN sa.status = 'Absent' THEN 1 ELSE 0 END) AS absent_count,
    SUM(CASE WHEN sa.status = 'Late' THEN 1 ELSE 0 END) AS late_count,
    ROUND((SUM(CASE WHEN sa.status IN ('Present', 'Late') THEN 1 ELSE 0 END) / COUNT(sa.student_id)) * 100, 2) AS attendance_percentage
FROM student_attendance sa
JOIN students s ON sa.student_id = s.student_id
JOIN classes c ON sa.class_id = c.class_id
JOIN grades g ON c.grade_id = g.grade_id
GROUP BY sa.attendance_date, c.class_id",

"CREATE OR REPLACE VIEW `student_fee_summary` AS
SELECT 
    s.student_id,
    s.admission_number,
    CONCAT(p.first_name, ' ', p.last_name) AS student_name,
    CONCAT(g.grade_name, ' ', c.section_name) AS class_name,
    COALESCE(SUM(sf.amount_due), 0) AS total_fees,
    COALESCE(SUM(sf.amount_paid), 0) AS total_paid,
    COALESCE(SUM(sf.amount_due), 0) - COALESCE(SUM(sf.amount_paid), 0) AS balance,
    CASE 
        WHEN COALESCE(SUM(sf.amount_due), 0) - COALESCE(SUM(sf.amount_paid), 0) <= 0 THEN 'Paid'
        WHEN COALESCE(SUM(sf.amount_paid), 0) > 0 THEN 'Partially Paid'
        ELSE 'Pending'
    END AS payment_status
FROM students s
JOIN persons p ON s.person_id = p.person_id
JOIN classes c ON s.class_id = c.class_id
JOIN grades g ON c.grade_id = g.grade_id
LEFT JOIN student_fees sf ON s.student_id = sf.student_id
WHERE s.student_status = 'Active'
GROUP BY s.student_id, s.admission_number, p.first_name, p.last_name, g.grade_name, c.section_name",

"CREATE OR REPLACE VIEW `timetable_view` AS
SELECT 
    tt.timetable_id,
    CONCAT(g.grade_name, ' ', c.section_name) AS class_name,
    sub.subject_name,
    CONCAT(p.first_name, ' ', p.last_name) AS teacher_name,
    tt.day_of_week,
    ts.slot_name,
    ts.start_time,
    ts.end_time,
    tt.room_number,
    tt.academic_year,
    tt.term
FROM timetable tt
JOIN classes c ON tt.class_id = c.class_id
JOIN grades g ON c.grade_id = g.grade_id
JOIN subjects sub ON tt.subject_id = sub.subject_id
JOIN teachers teach ON tt.teacher_id = teach.teacher_id
JOIN persons p ON teach.person_id = p.person_id
JOIN time_slots ts ON tt.slot_id = ts.slot_id
WHERE tt.is_active = 1
ORDER BY 
    FIELD(tt.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
    ts.sort_order"
];
// COMPLETE INSERT DATA STATEMENTS - ALL DEFAULT DATA (Updated)
$insert_statements = [

// ====================================
// INSERT USER ROLES
// ====================================
"INSERT IGNORE INTO `user_roles` (`role_name`, `description`, `permissions`) VALUES
('Super Admin', 'Full system access', '[\"*\"]')",

"INSERT IGNORE INTO `user_roles` (`role_name`, `description`, `permissions`) VALUES
('Principal', 'School administration access', '[\"users\", \"teachers\", \"students\", \"reports\", \"settings\"]')",

"INSERT IGNORE INTO `user_roles` (`role_name`, `description`, `permissions`) VALUES
('Teacher', 'Teaching and classroom management', '[\"students\", \"attendance\", \"grades\", \"assignments\"]')",

"INSERT IGNORE INTO `user_roles` (`role_name`, `description`, `permissions`) VALUES
('Student', 'Student portal access', '[\"assignments\", \"grades\", \"attendance_view\"]')",

"INSERT IGNORE INTO `user_roles` (`role_name`, `description`, `permissions`) VALUES
('Parent', 'Parent portal access', '[\"student_progress\", \"fees\", \"announcements\"]')",

"INSERT IGNORE INTO `user_roles` (`role_name`, `description`, `permissions`) VALUES
('Accountant', 'Financial management access', '[\"fees\", \"payments\", \"financial_reports\"]')",

"INSERT IGNORE INTO `user_roles` (`role_name`, `description`, `permissions`) VALUES
('Librarian', 'Library management access', '[\"books\", \"book_issues\"]')",

// ====================================
// INSERT ACADEMIC STRUCTURE
// ====================================
"INSERT IGNORE INTO `academic_years` (`year_name`, `start_date`, `end_date`, `is_current`) VALUES
('2025-2026', '2025-01-01', '2025-12-31', 1)",

"INSERT IGNORE INTO `terms` (`academic_year_id`, `term_name`, `term_number`, `start_date`, `end_date`, `is_current`) VALUES
(1, 'Term 1', 1, '2025-01-01', '2025-04-30', 1)",

"INSERT IGNORE INTO `terms` (`academic_year_id`, `term_name`, `term_number`, `start_date`, `end_date`, `is_current`) VALUES
(1, 'Term 2', 2, '2025-05-01', '2025-08-31', 0)",

"INSERT IGNORE INTO `terms` (`academic_year_id`, `term_name`, `term_number`, `start_date`, `end_date`, `is_current`) VALUES
(1, 'Term 3', 3, '2025-09-01', '2025-12-31', 0)",

// Insert ALL grades (Pre-Primary to Grade 8)
"INSERT IGNORE INTO `grades` (`grade_name`, `grade_level`, `description`) VALUES
('Pre-Primary 1', 1, 'Pre-Primary 1')",

"INSERT IGNORE INTO `grades` (`grade_name`, `grade_level`, `description`) VALUES
('Pre-Primary 2', 2, 'Pre-Primary 2')",

"INSERT IGNORE INTO `grades` (`grade_name`, `grade_level`, `description`) VALUES
('Grade 1', 3, 'Standard 1')",

"INSERT IGNORE INTO `grades` (`grade_name`, `grade_level`, `description`) VALUES
('Grade 2', 4, 'Standard 2')",

"INSERT IGNORE INTO `grades` (`grade_name`, `grade_level`, `description`) VALUES
('Grade 3', 5, 'Standard 3')",

"INSERT IGNORE INTO `grades` (`grade_name`, `grade_level`, `description`) VALUES
('Grade 4', 6, 'Standard 4')",

"INSERT IGNORE INTO `grades` (`grade_name`, `grade_level`, `description`) VALUES
('Grade 5', 7, 'Standard 5')",

"INSERT IGNORE INTO `grades` (`grade_name`, `grade_level`, `description`) VALUES
('Grade 6', 8, 'Standard 6')",

"INSERT IGNORE INTO `grades` (`grade_name`, `grade_level`, `description`) VALUES
('Grade 7', 9, 'Standard 7')",

"INSERT IGNORE INTO `grades` (`grade_name`, `grade_level`, `description`) VALUES
('Grade 8', 10, 'Standard 8')",

// Insert ALL core subjects with updated categories
// Insert ALL core subjects with updated categories
"INSERT IGNORE INTO `subjects` (`subject_name`, `subject_code`, `subject_type`, `description`) VALUES
('English Language', 'ENG', 'Core', 'English Language and Literature')",

"INSERT IGNORE INTO `subjects` (`subject_name`, `subject_code`, `subject_type`, `description`) VALUES
('Mathematics', 'MATH', 'Core', 'Mathematics')",

"INSERT IGNORE INTO `subjects` (`subject_name`, `subject_code`, `subject_type`, `description`) VALUES
('Kiswahili', 'KIS', 'Core', 'Kiswahili Language')",

"INSERT IGNORE INTO `subjects` (`subject_name`, `subject_code`, `subject_type`, `description`) VALUES
('Science', 'SCI', 'Core', 'Integrated Science')",

"INSERT IGNORE INTO `subjects` (`subject_name`, `subject_code`, `subject_type`, `description`) VALUES
('Social Studies', 'SS', 'Core', 'Social Studies')",

"INSERT IGNORE INTO `subjects` (`subject_name`, `subject_code`, `subject_type`, `description`) VALUES
('Religious Education', 'RE', 'Core', 'Christian Religious Education')",

"INSERT IGNORE INTO `subjects` (`subject_name`, `subject_code`, `subject_type`, `description`) VALUES
('Physical Education', 'PE', 'Core', 'Physical Education and Sports')",

"INSERT IGNORE INTO `subjects` (`subject_name`, `subject_code`, `subject_type`, `description`) VALUES
('Music', 'MUS', 'Elective', 'Music Education')",

"INSERT IGNORE INTO `subjects` (`subject_name`, `subject_code`, `subject_type`, `description`) VALUES
('Art & Craft', 'ART', 'Elective', 'Creative Arts')",

"INSERT IGNORE INTO `subjects` (`subject_name`, `subject_code`, `subject_type`, `description`) VALUES
('Computer Studies', 'ICT', 'Elective', 'Information and Communication Technology')",

// Insert ALL time slots (complete school day)
"INSERT IGNORE INTO `time_slots` (`slot_name`, `start_time`, `end_time`, `is_break`, `sort_order`) VALUES
('Period 1', '08:00:00', '08:40:00', 0, 1)",

"INSERT IGNORE INTO `time_slots` (`slot_name`, `start_time`, `end_time`, `is_break`, `sort_order`) VALUES
('Period 2', '08:40:00', '09:20:00', 0, 2)",

"INSERT IGNORE INTO `time_slots` (`slot_name`, `start_time`, `end_time`, `is_break`, `sort_order`) VALUES
('Period 3', '09:20:00', '10:00:00', 0, 3)",

"INSERT IGNORE INTO `time_slots` (`slot_name`, `start_time`, `end_time`, `is_break`, `sort_order`) VALUES
('Tea Break', '10:00:00', '10:20:00', 1, 4)",

"INSERT IGNORE INTO `time_slots` (`slot_name`, `start_time`, `end_time`, `is_break`, `sort_order`) VALUES
('Period 4', '10:20:00', '11:00:00', 0, 5)",

"INSERT IGNORE INTO `time_slots` (`slot_name`, `start_time`, `end_time`, `is_break`, `sort_order`) VALUES
('Period 5', '11:00:00', '11:40:00', 0, 6)",

"INSERT IGNORE INTO `time_slots` (`slot_name`, `start_time`, `end_time`, `is_break`, `sort_order`) VALUES
('Period 6', '11:40:00', '12:20:00', 0, 7)",

"INSERT IGNORE INTO `time_slots` (`slot_name`, `start_time`, `end_time`, `is_break`, `sort_order`) VALUES
('Lunch Break', '12:20:00', '13:20:00', 1, 8)",

"INSERT IGNORE INTO `time_slots` (`slot_name`, `start_time`, `end_time`, `is_break`, `sort_order`) VALUES
('Period 7', '13:20:00', '14:00:00', 0, 9)",

"INSERT IGNORE INTO `time_slots` (`slot_name`, `start_time`, `end_time`, `is_break`, `sort_order`) VALUES
('Period 8', '14:00:00', '14:40:00', 0, 10)",

// Insert ALL exam types
"INSERT IGNORE INTO `exam_types` (`exam_name`, `description`, `weightage`) VALUES
('Mid-Term Exam', 'Mid-term examination', 30.00)",

"INSERT IGNORE INTO `exam_types` (`exam_name`, `description`, `weightage`) VALUES
('End-Term Exam', 'End of term examination', 70.00)",

"INSERT IGNORE INTO `exam_types` (`exam_name`, `description`, `weightage`) VALUES
('Class Assessment', 'Continuous assessment test', 10.00)",

"INSERT IGNORE INTO `exam_types` (`exam_name`, `description`, `weightage`) VALUES
('Assignment', 'Take-home assignment', 5.00)",

// Insert ALL fee types (complete fee structure) - Updated without categories for now
"INSERT IGNORE INTO `fee_types` (`fee_name`, `description`, `base_amount`, `is_mandatory`, `frequency`) VALUES
('Tuition Fee', 'Monthly tuition fee', 15000.00, 1, 'Monthly')",

"INSERT IGNORE INTO `fee_types` (`fee_name`, `description`, `base_amount`, `is_mandatory`, `frequency`) VALUES
('Admission Fee', 'One-time admission fee', 5000.00, 1, 'One-time')",

"INSERT IGNORE INTO `fee_types` (`fee_name`, `description`, `base_amount`, `is_mandatory`, `frequency`) VALUES
('Activity Fee', 'Co-curricular activities fee', 2000.00, 1, 'Quarterly')",

"INSERT IGNORE INTO `fee_types` (`fee_name`, `description`, `base_amount`, `is_mandatory`, `frequency`) VALUES
('Transport Fee', 'School transport fee', 3000.00, 0, 'Monthly')",

"INSERT IGNORE INTO `fee_types` (`fee_name`, `description`, `base_amount`, `is_mandatory`, `frequency`) VALUES
('Lunch Fee', 'School meals fee', 2500.00, 0, 'Monthly')",

"INSERT IGNORE INTO `fee_types` (`fee_name`, `description`, `base_amount`, `is_mandatory`, `frequency`) VALUES
('Uniform Fee', 'School uniform fee', 4000.00, 1, 'Annually')",

"INSERT IGNORE INTO `fee_types` (`fee_name`, `description`, `base_amount`, `is_mandatory`, `frequency`) VALUES
('Books Fee', 'Textbooks and materials fee', 6000.00, 1, 'Annually')",

"INSERT IGNORE INTO `fee_types` (`fee_name`, `description`, `base_amount`, `is_mandatory`, `frequency`) VALUES
('Examination Fee', 'Examination and assessment fee', 1500.00, 1, 'Quarterly')",

// Insert ALL school settings (complete configuration)
"INSERT IGNORE INTO `school_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `category`) VALUES
('school_name', 'Umoja Junior Academy', 'text', 'Official school name', 'General')",

"INSERT IGNORE INTO `school_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `category`) VALUES
('school_address', 'P.O. Box 123, Ruiru, Kiambu County', 'text', 'School postal address', 'General')",

"INSERT IGNORE INTO `school_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `category`) VALUES
('school_phone', '+254-700-000-000', 'text', 'School contact phone', 'General')",

"INSERT IGNORE INTO `school_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `category`) VALUES
('school_email', 'info@umojajunior.ac.ke', 'text', 'School email address', 'General')",

"INSERT IGNORE INTO `school_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `category`) VALUES
('academic_year_start_month', '1', 'number', 'Month when academic year starts', 'Academic')",

"INSERT IGNORE INTO `school_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `category`) VALUES
('working_days', '[\"Monday\",\"Tuesday\",\"Wednesday\",\"Thursday\",\"Friday\"]', 'json', 'School working days', 'Academic')",

"INSERT IGNORE INTO `school_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `category`) VALUES
('current_academic_year', '2025-2026', 'text', 'Current academic year', 'Academic')",

"INSERT IGNORE INTO `school_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `category`) VALUES
('current_term', 'Term 1', 'text', 'Current academic term', 'Academic')",

// ====================================
// INSERT SAMPLE USERS AND DATA
// ====================================

// Insert sample admin user
"INSERT IGNORE INTO `persons` (`first_name`, `last_name`, `gender`, `phone`, `email`) VALUES
('System', 'Administrator', 'Male', '+254700000001', 'admin@umojajunior.ac.ke')",

"INSERT IGNORE INTO `users` (`username`, `email`, `password_hash`, `role_id`) VALUES
('admin', 'admin@umojajunior.ac.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1)",

// Insert sample teacher
"INSERT IGNORE INTO `persons` (`first_name`, `last_name`, `gender`, `phone`, `email`, `date_of_birth`) VALUES
('Mary', 'Wanjiku', 'Female', '+254700000002', 'mary.wanjiku@umojajunior.ac.ke', '1985-05-15')",
"INSERT IGNORE INTO `users` (`username`, `email`, `password_hash`, `role_id`) VALUES
('mary.wanjiku', 'mary.wanjiku@umojajunior.ac.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3)",

"INSERT IGNORE INTO `teachers` (`person_id`, `user_id`, `employee_id`, `hire_date`, `qualification`, `specialization`, `department`, `is_class_teacher`) VALUES
(2, 2, 'UJATECH001', '2024-01-15', 'Bachelor of Education', 'Mathematics', 'Mathematics Department', 1)",

// Insert sample parent
"INSERT IGNORE INTO `persons` (`first_name`, `last_name`, `gender`, `phone`, `email`) VALUES
('John', 'Mwangi', 'Male', '+254700000003', 'john.mwangi@gmail.com')",

"INSERT IGNORE INTO `users` (`username`, `email`, `password_hash`, `role_id`) VALUES
('john.mwangi', 'john.mwangi@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5)",

"INSERT IGNORE INTO `parents` (`person_id`, `user_id`, `occupation`, `relationship_type`) VALUES
(3, 3, 'Business Owner', 'Father')",

// Insert sample class
"INSERT IGNORE INTO `classes` (`grade_id`, `section_name`, `class_teacher_id`, `academic_year_id`) VALUES
(8, 'A', 1, 1)",

// Insert sample student
"INSERT IGNORE INTO `persons` (`first_name`, `last_name`, `gender`, `phone`, `date_of_birth`) VALUES
('Grace', 'Mwangi', 'Female', '+254700000004', '2012-03-20')",

"INSERT IGNORE INTO `users` (`username`, `email`, `password_hash`, `role_id`) VALUES
('grace.mwangi', 'grace.mwangi@student.umojajunior.ac.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4)",

"INSERT IGNORE INTO `students` (`person_id`, `user_id`, `admission_number`, `admission_date`, `class_id`, `parent_id`) VALUES
(4, 4, 'UJA2025001', '2025-01-15', 1, 1)",

// Assign subjects to Grade 6 (grade_id = 8)
"INSERT IGNORE INTO `grade_subjects` (`grade_id`, `subject_id`) VALUES (8, 1)",
"INSERT IGNORE INTO `grade_subjects` (`grade_id`, `subject_id`) VALUES (8, 2)",
"INSERT IGNORE INTO `grade_subjects` (`grade_id`, `subject_id`) VALUES (8, 3)",
"INSERT IGNORE INTO `grade_subjects` (`grade_id`, `subject_id`) VALUES (8, 4)",
"INSERT IGNORE INTO `grade_subjects` (`grade_id`, `subject_id`) VALUES (8, 5)",
"INSERT IGNORE INTO `grade_subjects` (`grade_id`, `subject_id`) VALUES (8, 6)",
"INSERT IGNORE INTO `grade_subjects` (`grade_id`, `subject_id`) VALUES (8, 7)",
"INSERT IGNORE INTO `grade_subjects` (`grade_id`, `subject_id`) VALUES (8, 8)",
"INSERT IGNORE INTO `grade_subjects` (`grade_id`, `subject_id`) VALUES (8, 9)",
"INSERT IGNORE INTO `grade_subjects` (`grade_id`, `subject_id`) VALUES (8, 10)",

// Insert some sample fee grade amounts
"INSERT IGNORE INTO `fee_grade_amounts` (`fee_type_id`, `grade_id`, `amount`, `academic_year`) VALUES
(1, 1, 12000.00, '2025-2026')", // PP1 Tuition
"INSERT IGNORE INTO `fee_grade_amounts` (`fee_type_id`, `grade_id`, `amount`, `academic_year`) VALUES
(1, 2, 12000.00, '2025-2026')", // PP2 Tuition
"INSERT IGNORE INTO `fee_grade_amounts` (`fee_type_id`, `grade_id`, `amount`, `academic_year`) VALUES
(1, 3, 13000.00, '2025-2026')", // Grade 1 Tuition
"INSERT IGNORE INTO `fee_grade_amounts` (`fee_type_id`, `grade_id`, `amount`, `academic_year`) VALUES
(1, 4, 13000.00, '2025-2026')", // Grade 2 Tuition
"INSERT IGNORE INTO `fee_grade_amounts` (`fee_type_id`, `grade_id`, `amount`, `academic_year`) VALUES
(1, 5, 14000.00, '2025-2026')", // Grade 3 Tuition
"INSERT IGNORE INTO `fee_grade_amounts` (`fee_type_id`, `grade_id`, `amount`, `academic_year`) VALUES
(1, 6, 14000.00, '2025-2026')", // Grade 4 Tuition
"INSERT IGNORE INTO `fee_grade_amounts` (`fee_type_id`, `grade_id`, `amount`, `academic_year`) VALUES
(1, 7, 15000.00, '2025-2026')", // Grade 5 Tuition
"INSERT IGNORE INTO `fee_grade_amounts` (`fee_type_id`, `grade_id`, `amount`, `academic_year`) VALUES
(1, 8, 15000.00, '2025-2026')", // Grade 6 Tuition
"INSERT IGNORE INTO `fee_grade_amounts` (`fee_type_id`, `grade_id`, `amount`, `academic_year`) VALUES
(1, 9, 16000.00, '2025-2026')", // Grade 7 Tuition
"INSERT IGNORE INTO `fee_grade_amounts` (`fee_type_id`, `grade_id`, `amount`, `academic_year`) VALUES
(1, 10, 16000.00, '2025-2026')", // Grade 8 Tuition

// Insert sample student fees for Grace - Updated column names
"INSERT IGNORE INTO `student_fees` (`student_id`, `fee_type_id`, `amount`, `due_date`) VALUES
(1, 1, 15000.00, '2025-02-15')", // Tuition
"INSERT IGNORE INTO `student_fees` (`student_id`, `fee_type_id`, `amount`, `due_date`) VALUES
(1, 3, 2000.00, '2025-02-15')", // Activity Fee

// Insert sample book records
"INSERT IGNORE INTO `books` (`isbn`, `title`, `author`, `publisher`, `publication_year`, `category`, `total_copies`, `available_copies`) VALUES
('978-9966-25-123-4', 'Primary Mathematics Grade 6', 'Kenya Publishers', 'Kenya Educational Publishers', 2024, 'Mathematics', 5, 5)",
"INSERT IGNORE INTO `books` (`isbn`, `title`, `author`, `publisher`, `publication_year`, `category`, `total_copies`, `available_copies`) VALUES
('978-9966-25-124-1', 'English Primary Course Grade 6', 'Language Authors', 'Kenyan Books Ltd', 2024, 'English', 5, 5)",
"INSERT IGNORE INTO `books` (`isbn`, `title`, `author`, `publisher`, `publication_year`, `category`, `total_copies`, `available_copies`) VALUES
('978-9966-25-125-8', 'Science Activity Book Grade 6', 'Science Team', 'Educational Press', 2024, 'Science', 3, 3)"
];


echo "🔄 Creating database tables...\n\n";

// Execute table creation statements
$table_results = executeSQLStatements($conn, $sql_statements);

echo "\n🔄 Creating database views...\n\n";

// Execute view creation statements  
$view_results = executeSQLStatements($conn, $view_statements);

echo "\n🔄 Inserting initial data...\n\n";

// Execute data insertion statements
$data_results = executeSQLStatements($conn, $insert_statements);

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 COMPLETE DATABASE SETUP SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "✅ Tables created/updated: " . $table_results['success'] . "\n";
echo "❌ Table errors: " . $table_results['errors'] . "\n";
echo "✅ Views created/updated: " . $view_results['success'] . "\n";
echo "❌ View errors: " . $view_results['errors'] . "\n";
echo "✅ Data inserted: " . $data_results['success'] . "\n";
echo "❌ Data errors: " . $data_results['errors'] . "\n";
echo str_repeat("=", 60) . "\n";
$total_errors = $table_results['errors'] + $view_results['errors'] + $data_results['errors'];
if ($total_errors == 0) {
echo "🎉 COMPLETE DATABASE SETUP SUCCESSFUL!\n\n";
echo "📋 FEATURES INCLUDED:\n";
echo "   ✅ User Management & Authentication\n";
echo "   ✅ Academic Structure (Years, Terms, Grades, Classes)\n";
echo "   ✅ People Management (Students, Teachers, Parents, Staff)\n";
echo "   ✅ Subject Assignments (FIXED)\n";
echo "   ✅ Timetable & Scheduling\n";
echo "   ✅ Complete Attendance Management\n";
echo "   ✅ Academic Assessments (Exams, Results)\n";
echo "   ✅ Financial Management (Fees, Payments) - ENHANCED\n";
echo "   ✅ Communication & Events\n";
echo "   ✅ Library Management\n";
echo "   ✅ Behavioral Records\n";
echo "   ✅ Extracurricular Activities\n";
echo "   ✅ Reports & Analytics\n";
echo "   ✅ System Configuration\n";
echo "   ✅ Performance Indexes\n";
echo "   ✅ Database Views (UPDATED)\n\n";
echo "📝 DEFAULT LOGIN CREDENTIALS:\n";
echo "   👤 Super Admin: username 'admin', password 'admin123'\n";
echo "   🧑‍🏫 Teacher: username 'mary.wanjiku', password 'teacher123'\n";
echo "   👨‍👩‍👧‍👦 Parent: username 'john.mwangi', password 'parent123'\n";
echo "   🎓 Student: username 'grace.mwangi', password 'student123'\n\n";

echo "🌐 ACCESS YOUR APPLICATION:\n";
echo "   http://localhost/umoja-junior-academy\n\n";

echo "📊 DATABASE STATISTICS:\n";
echo "   📋 Tables: " . ($table_results['success']) . "\n";
echo "   👁️ Views: " . ($view_results['success']) . "\n";
echo "   📝 Sample Records: " . ($data_results['success']) . "\n";
echo "   🏫 Complete School Management System Ready!\n";

echo "\n🆕 NEW FEATURES ADDED:\n";
echo "   ✅ Subject Assignment Table (FIXED)\n";
echo "   ✅ Enhanced Fee Management Structure\n";
echo "   ✅ Grade-specific Fee Amounts\n";
echo "   ✅ Behavioral Records System\n";
echo "   ✅ Extracurricular Activities Tracking\n";
echo "   ✅ Improved Database Views\n";
echo "   ✅ Better Error Handling\n";
} else {
echo "⚠️  Setup completed with $total_errors errors. Please check the error messages above.\n";
echo "💡 You can still proceed if only minor errors occurred.\n";
}
echo "\n🔗 NEXT STEPS:\n";
echo "   1. Test subject assignment: http://localhost/umoja-junior-academy/subjects/\n";
echo "   2. Check fee management: http://localhost/umoja-junior-academy/fees/\n";
echo "   3. Test reports system: http://localhost/umoja-junior-academy/reports/\n";
echo "   4. Login and start using the complete system!\n";
echo "\n💡 TROUBLESHOOTING:\n";
echo "   - If you see 'already exists' warnings, that's normal on re-runs\n";
echo "   - Foreign key errors usually mean dependent tables need to be created first\n";
echo "   - Check phpMyAdmin to verify all tables were created\n";
echo str_repeat("=", 60) . "\n";
echo "</pre>\n";
?>