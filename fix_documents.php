<?php
require_once 'config/database.php';

echo "<h2>Fix Documents Database</h2>";

try {
    // First, let's see what's in the documents table
    $sql = "SELECT * FROM documents WHERE personal_info_id = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $doc = $stmt->fetch();
    
    echo "<h3>Current Document Record:</h3>";
    echo "<pre>";
    print_r($doc);
    echo "</pre>";
    
    // Let's find some files in the uploads directory that we can use
    $uploads_dir = 'uploads/';
    $files = scandir($uploads_dir);
    $sample_files = [];
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && !is_dir($uploads_dir . $file)) {
            $sample_files[] = $file;
            if (count($sample_files) >= 3) break; // Get 3 sample files
        }
    }
    
    echo "<h3>Sample Files Found:</h3>";
    echo "<pre>";
    print_r($sample_files);
    echo "</pre>";
    
    if (count($sample_files) >= 3) {
        // Update the document record with sample files
        $sql = "UPDATE documents SET 
                g11_1st = ?, 
                g11_2nd = ?, 
                g12_1st = ? 
                WHERE personal_info_id = 1";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $sample_files[0],
            $sample_files[1], 
            $sample_files[2]
        ]);
        
        if ($result) {
            echo "<p style='color: green;'><strong>Success!</strong> Updated document record with sample files.</p>";
            
            // Check the updated record
            $sql = "SELECT * FROM documents WHERE personal_info_id = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $updated_doc = $stmt->fetch();
            
            echo "<h3>Updated Document Record:</h3>";
            echo "<pre>";
            print_r($updated_doc);
            echo "</pre>";
            
        } else {
            echo "<p style='color: red;'><strong>Error:</strong> Failed to update document record.</p>";
        }
    } else {
        echo "<p style='color: red;'><strong>Error:</strong> Not enough sample files found.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
