<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['personal_info_id'])) {
    echo json_encode(['success' => false, 'message' => 'Personal information not found']);
    exit;
}

try {
    // Check if program_application already exists
    $check_stmt = $pdo->prepare("SELECT id FROM program_application WHERE personal_info_id = ?");
    $check_stmt->execute([$_SESSION['personal_info_id']]);
    $existing_program = $check_stmt->fetch();

    if ($existing_program) {
        // Update existing program_application
        $stmt = $pdo->prepare("UPDATE program_application SET 
            campus = ?, college = ?, program = ?
            WHERE personal_info_id = ?");
        
        $stmt->execute([
            $_POST['selected_campus'],
            $_POST['selected_college'],
            $_POST['program'],
            $_SESSION['personal_info_id']
        ]);
    } else {
        // Insert new program_application
        $stmt = $pdo->prepare("INSERT INTO program_application (
            personal_info_id, campus, college, program
        ) VALUES (?, ?, ?, ?)");

        $stmt->execute([
            $_SESSION['personal_info_id'],
            $_POST['selected_campus'],
            $_POST['selected_college'],
            $_POST['program']
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Program application saved successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>