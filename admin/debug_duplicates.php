<?php
// Debug script to investigate duplicate records
require_once '../config/database.php';

echo "<h2>Debug: Investigating Duplicate Records</h2>";

try {
    // Check if screening_results table exists
    $check_table = "SHOW TABLES LIKE 'screening_results'";
    $table_result = $pdo->query($check_table);
    
    if ($table_result->rowCount() == 0) {
        echo "<p style='color: red;'>❌ screening_results table does not exist!</p>";
        echo "<p>This explains why duplicates are occurring. The table needs to be created first.</p>";
        
        // Create the table
        echo "<h3>Creating screening_results table...</h3>";
        $create_table = "CREATE TABLE IF NOT EXISTS screening_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            personal_info_id INT NOT NULL,
            gwa_score DECIMAL(5,2) DEFAULT NULL,
            stanine_result VARCHAR(10) DEFAULT NULL,
            stanine_score DECIMAL(5,2) DEFAULT NULL,
            exam_total_score DECIMAL(5,2) DEFAULT NULL,
            interview_total_score DECIMAL(5,2) DEFAULT NULL,
            communication_skills DECIMAL(5,2) DEFAULT NULL,
            problem_solving DECIMAL(5,2) DEFAULT NULL,
            motivation DECIMAL(5,2) DEFAULT NULL,
            knowledge DECIMAL(5,2) DEFAULT NULL,
            overall_impression DECIMAL(5,2) DEFAULT NULL,
            plus_factor DECIMAL(5,2) DEFAULT NULL,
            rank INT DEFAULT NULL,
            interview_date TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_personal_info (personal_info_id),
            FOREIGN KEY (personal_info_id) REFERENCES personal_info(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($create_table);
        echo "<p style='color: green;'>✅ screening_results table created successfully!</p>";
        
    } else {
        echo "<p style='color: green;'>✅ screening_results table exists</p>";
        
        // Check table structure
        echo "<h3>Table Structure:</h3>";
        $structure = $pdo->query("DESCRIBE screening_results");
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check for duplicates
        echo "<h3>Checking for Duplicates:</h3>";
        $duplicate_check = "SELECT personal_info_id, COUNT(*) as count 
                           FROM screening_results 
                           GROUP BY personal_info_id 
                           HAVING COUNT(*) > 1";
        $duplicates = $pdo->query($duplicate_check);
        
        if ($duplicates->rowCount() > 0) {
            echo "<p style='color: red;'>❌ Found " . $duplicates->rowCount() . " personal_info_ids with duplicate records:</p>";
            echo "<table border='1'><tr><th>personal_info_id</th><th>Count</th></tr>";
            while ($row = $duplicates->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr><td>" . $row['personal_info_id'] . "</td><td>" . $row['count'] . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: green;'>✅ No duplicate records found</p>";
        }
        
        // Show all records
        echo "<h3>All Records in screening_results:</h3>";
        $all_records = $pdo->query("SELECT * FROM screening_results ORDER BY personal_info_id, id");
        echo "<table border='1'><tr><th>ID</th><th>personal_info_id</th><th>stanine_result</th><th>created_at</th></tr>";
        while ($row = $all_records->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['personal_info_id'] . "</td>";
            echo "<td>" . $row['stanine_result'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
