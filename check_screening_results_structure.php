<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=admission', 'root', '');
    
    // Check the structure of screening_results table
    echo "screening_results table structure:\n";
    $structure = $pdo->query('DESCRIBE screening_results')->fetchAll();
    foreach($structure as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check if there are any existing records
    $count = $pdo->query('SELECT COUNT(*) as count FROM screening_results')->fetch()['count'];
    echo "\nTotal records in screening_results: " . $count . "\n";
    
    if($count > 0) {
        echo "\nSample data:\n";
        $samples = $pdo->query('SELECT * FROM screening_results LIMIT 1')->fetchAll();
        foreach($samples as $sample) {
            foreach($sample as $key => $value) {
                if(!is_numeric($key)) {
                    echo "$key: $value\n";
                }
            }
        }
    }
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>



