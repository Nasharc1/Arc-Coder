<?php
// repair_database.php - Fix Database Schema Issues (MariaDB Compatible)
require_once "config/database.php";

$db = new Database();
$conn = $db->getConnection();

echo "<h2>🔧 Database Schema Repair</h2>\n";
echo "<pre>\n";

// Function to check if column exists
function columnExists($conn, $table, $column) {
    try {
        $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Function to check if table exists
function tableExists($conn, $table) {
    try {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Function to get column definition
function getColumnDefinition($conn, $table, $column) {
    try {
        $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` WHERE Field = ?");
        $stmt->execute([$column]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

$repair_queries = [];

echo "🔍 Checking database structure...\n\n";

// 1. Fix subjects table - add missing columns
if (tableExists($conn, 'subjects')) {
    echo "📋 Checking subjects table...\n";
    if (!columnExists($conn, 'subjects', 'subject_category')) {
        $repair_queries[] = "ALTER TABLE `subjects` ADD COLUMN `subject_category` ENUM('Core', 'Languages', 'Sciences', 'Arts', 'Physical Education', 'Technical', 'Other') DEFAULT 'Core' AFTER `subject_code`";
        echo "   ➕ Will add subject_category column\n";
    } else {
        echo "   ✅ subject_category column exists\n";
    }
}

// 2. Fix fee_types table - add missing columns
if (tableExists($conn, 'fee_types')) {
    echo "📋 Checking fee_types table...\n";
    
    if (!columnExists($conn, 'fee_types', 'fee_category')) {
        $repair_queries[] = "ALTER TABLE `fee_types` ADD COLUMN `fee_category` ENUM('Tuition', 'Transport', 'Meals', 'Uniform', 'Books', 'Activities', 'Examination', 'Other') DEFAULT 'Other' AFTER `fee_name`";
        echo "   ➕ Will add fee_category column\n";
    } else {
        echo "   ✅ fee_category column exists\n";
    }
    
    // Handle base_amount vs amount column
    if (columnExists($conn, 'fee_types', 'amount') && !columnExists($conn, 'fee_types', 'base_amount')) {
        // MariaDB compatible syntax - use CHANGE instead of RENAME
        $repair_queries[] = "ALTER TABLE `fee_types` CHANGE COLUMN `amount` `base_amount` DECIMAL(10,2) NOT NULL";
        echo "   🔄 Will rename amount to base_amount\n";
    } elseif (!columnExists($conn, 'fee_types', 'base_amount')) {
        $repair_queries[] = "ALTER TABLE `fee_types` ADD COLUMN `base_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `description`";
        echo "   ➕ Will add base_amount column\n";
    } else {
        echo "   ✅ base_amount column exists\n";
    }
}

// 3. Fix student_fees table - add missing columns
if (tableExists($conn, 'student_fees')) {
    echo "📋 Checking student_fees table...\n";
    
    if (!columnExists($conn, 'student_fees', 'amount_due')) {
        $repair_queries[] = "ALTER TABLE `student_fees` ADD COLUMN `amount_due` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `fee_type_id`";
        echo "   ➕ Will add amount_due column\n";
    } else {
        echo "   ✅ amount_due column exists\n";
    }
    
    if (!columnExists($conn, 'student_fees', 'amount_paid')) {
        $repair_queries[] = "ALTER TABLE `student_fees` ADD COLUMN `amount_paid` DECIMAL(10,2) DEFAULT 0.00 AFTER `amount_due`";
        echo "   ➕ Will add amount_paid column\n";
    } else {
        echo "   ✅ amount_paid column exists\n";
    }
    
    if (!columnExists($conn, 'student_fees', 'payment_status')) {
        $repair_queries[] = "ALTER TABLE `student_fees` ADD COLUMN `payment_status` ENUM('Pending', 'Paid', 'Partial', 'Overdue') DEFAULT 'Pending' AFTER `amount_paid`";
        echo "   ➕ Will add payment_status column\n";
    } else {
        echo "   ✅ payment_status column exists\n";
    }
    
    if (!columnExists($conn, 'student_fees', 'academic_year')) {
        $repair_queries[] = "ALTER TABLE `student_fees` ADD COLUMN `academic_year` VARCHAR(20) DEFAULT '2025-2026' AFTER `payment_status`";
        echo "   ➕ Will add academic_year column\n";
    } else {
        echo "   ✅ academic_year column exists\n";
    }
    
    if (!columnExists($conn, 'student_fees', 'term')) {
        $repair_queries[] = "ALTER TABLE `student_fees` ADD COLUMN `term` ENUM('Term 1', 'Term 2', 'Term 3') DEFAULT 'Term 1' AFTER `academic_year`";
        echo "   ➕ Will add term column\n";
    } else {
        echo "   ✅ term column exists\n";
    }
}

// 4. Fix timetable table - add missing columns
if (tableExists($conn, 'timetable')) {
    echo "📋 Checking timetable table...\n";
    
    if (!columnExists($conn, 'timetable', 'academic_year')) {
        $repair_queries[] = "ALTER TABLE `timetable` ADD COLUMN `academic_year` VARCHAR(20) DEFAULT '2025-2026' AFTER `room_number`";
        echo "   ➕ Will add academic_year column\n";
    } else {
        echo "   ✅ academic_year column exists\n";
    }
    
    if (!columnExists($conn, 'timetable', 'term')) {
        $repair_queries[] = "ALTER TABLE `timetable` ADD COLUMN `term` ENUM('Term 1', 'Term 2', 'Term 3') DEFAULT 'Term 1' AFTER `academic_year`";
        echo "   ➕ Will add term column\n";
    } else {
        echo "   ✅ term column exists\n";
    }
}

// Execute repair queries
echo "\n🔧 Executing repair queries...\n\n";

foreach ($repair_queries as $index => $query) {
    try {
        $conn->exec($query);
        echo "✅ Repair " . ($index + 1) . ": Success\n";
        echo "   " . substr($query, 0, 80) . "...\n\n";
    } catch (Exception $e) {
        echo "❌ Repair " . ($index + 1) . ": Failed\n";
        echo "   Error: " . $e->getMessage() . "\n";
        echo "   Query: " . substr($query, 0, 80) . "...\n\n";
    }
}

// Additional fix: Update subject_category for existing subjects
if (columnExists($conn, 'subjects', 'subject_category')) {
    echo "🔄 Updating existing subject categories...\n";
    
    $subject_updates = [
        "UPDATE `subjects` SET `subject_category` = 'Languages' WHERE `subject_code` IN ('ENG', 'KIS')",
        "UPDATE `subjects` SET `subject_category` = 'Core' WHERE `subject_code` IN ('MATH', 'SS', 'RE')",
        "UPDATE `subjects` SET `subject_category` = 'Sciences' WHERE `subject_code` = 'SCI'",
        "UPDATE `subjects` SET `subject_category` = 'Physical Education' WHERE `subject_code` = 'PE'",
        "UPDATE `subjects` SET `subject_category` = 'Arts' WHERE `subject_code` IN ('MUS', 'ART')",
        "UPDATE `subjects` SET `subject_category` = 'Technical' WHERE `subject_code` = 'ICT'"
    ];
    
    foreach ($subject_updates as $update) {
        try {
            $conn->exec($update);
            echo "   ✅ Updated subject categories\n";
        } catch (Exception $e) {
            echo "   ⚠️ Category update failed: " . $e->getMessage() . "\n";
        }
    }
}

// Additional fix: Update fee_category for existing fee types
if (columnExists($conn, 'fee_types', 'fee_category')) {
    echo "🔄 Updating existing fee categories...\n";
    
    $fee_updates = [
        "UPDATE `fee_types` SET `fee_category` = 'Tuition' WHERE `fee_name` LIKE '%Tuition%'",
        "UPDATE `fee_types` SET `fee_category` = 'Transport' WHERE `fee_name` LIKE '%Transport%'",
        "UPDATE `fee_types` SET `fee_category` = 'Meals' WHERE `fee_name` LIKE '%Lunch%' OR `fee_name` LIKE '%Meal%'",
        "UPDATE `fee_types` SET `fee_category` = 'Uniform' WHERE `fee_name` LIKE '%Uniform%'",
        "UPDATE `fee_types` SET `fee_category` = 'Books' WHERE `fee_name` LIKE '%Book%'",
        "UPDATE `fee_types` SET `fee_category` = 'Activities' WHERE `fee_name` LIKE '%Activity%'",
        "UPDATE `fee_types` SET `fee_category` = 'Examination' WHERE `fee_name` LIKE '%Exam%'"
    ];
    
    foreach ($fee_updates as $update) {
        try {
            $conn->exec($update);
            echo "   ✅ Updated fee categories\n";
        } catch (Exception $e) {
            echo "   ⚠️ Category update failed: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n🎉 Database repair completed!\n";
echo "Now you can re-run setup_database.php or test your application\n";
echo "</pre>\n";
?>