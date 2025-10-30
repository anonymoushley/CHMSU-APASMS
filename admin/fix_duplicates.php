<?php
// Script to fix duplicate records in screening_results table
$conn = new mysqli("localhost", "root", "", "admission");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Fixing Duplicate Records in screening_results</h2>";

try {
    // First, let's see the current duplicates
    echo "<h3>Current Duplicates:</h3>";
    $duplicate_check = "SELECT personal_info_id, COUNT(*) as count 
                       FROM screening_results 
                       GROUP BY personal_info_id 
                       HAVING COUNT(*) > 1";
    $duplicates = $conn->query($duplicate_check);
    
    if ($duplicates->num_rows > 0) {
        echo "<table border='1'><tr><th>personal_info_id</th><th>Count</th></tr>";
        while ($row = $duplicates->fetch_assoc()) {
            echo "<tr><td>" . $row['personal_info_id'] . "</td><td>" . $row['count'] . "</td></tr>";
        }
        echo "</table>";
        
        // Clean up duplicates by keeping only the latest record (highest ID) for each personal_info_id
        echo "<h3>Cleaning up duplicates...</h3>";
        
        $cleanup_sql = "DELETE sr1 FROM screening_results sr1
                       INNER JOIN screening_results sr2 
                       WHERE sr1.personal_info_id = sr2.personal_info_id 
                       AND sr1.id < sr2.id";
        
        if ($conn->query($cleanup_sql)) {
            echo "<p style='color: green;'>✅ Duplicates cleaned up successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Error cleaning up duplicates: " . $conn->error . "</p>";
        }
        
        // Verify cleanup
        echo "<h3>Verification:</h3>";
        $verify = $conn->query($duplicate_check);
        if ($verify->num_rows == 0) {
            echo "<p style='color: green;'>✅ No more duplicates found!</p>";
        } else {
            echo "<p style='color: red;'>❌ Still have duplicates after cleanup</p>";
        }
        
    } else {
        echo "<p style='color: green;'>✅ No duplicates found</p>";
    }
    
    // Add unique constraint to prevent future duplicates
    echo "<h3>Adding unique constraint...</h3>";
    $add_constraint = "ALTER TABLE screening_results ADD UNIQUE KEY unique_personal_info (personal_info_id)";
    
    if ($conn->query($add_constraint)) {
        echo "<p style='color: green;'>✅ Unique constraint added successfully!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Could not add unique constraint (might already exist): " . $conn->error . "</p>";
    }
    
    // Show final state
    echo "<h3>Final Records:</h3>";
    $final_records = $conn->query("SELECT id, personal_info_id, stanine_result FROM screening_results ORDER BY personal_info_id, id");
    echo "<table border='1'><tr><th>ID</th><th>personal_info_id</th><th>stanine_result</th></tr>";
    while ($row = $final_records->fetch_assoc()) {
        echo "<tr><td>" . $row['id'] . "</td><td>" . $row['personal_info_id'] . "</td><td>" . $row['stanine_result'] . "</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
} finally {
    $conn->close();
}
?>
