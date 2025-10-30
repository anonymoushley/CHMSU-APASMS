<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Get personal_info_id from session or POST data
$personal_info_id = $_SESSION['personal_info_id'] ?? $_POST['personal_info_id'] ?? null;

// Debug logging
error_log("Save Documents Debug - Session personal_info_id: " . ($_SESSION['personal_info_id'] ?? 'not set'));
error_log("Save Documents Debug - POST personal_info_id: " . ($_POST['personal_info_id'] ?? 'not set'));
error_log("Save Documents Debug - Final personal_info_id: " . ($personal_info_id ?? 'not set'));

if (!$personal_info_id) {
    echo json_encode(['success' => false, 'message' => 'Personal information not found']);
    exit;
}

try {
    $uploadDir = '../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Check if documents already exist
    $check_stmt = $pdo->prepare("SELECT id FROM documents WHERE personal_info_id = ?");
    $check_stmt->execute([$personal_info_id]);
    $existing_documents = $check_stmt->fetch();

    $g11_1st = '';
    $g11_2nd = '';
    $g12_1st = '';
    $ncii = '';
    $guidance_cert = '';
    $additional_file = '';

    // Handle Grade 11 1st Sem Report Card upload
    if (isset($_FILES['g11_1st']) && $_FILES['g11_1st']['error'] === UPLOAD_ERR_OK) {
        $g11_1st = handleFileUpload($_FILES['g11_1st'], $uploadDir);
        error_log("g11_1st uploaded: " . $g11_1st);
    }

    // Handle Grade 11 2nd Sem Report Card upload
    if (isset($_FILES['g11_2nd']) && $_FILES['g11_2nd']['error'] === UPLOAD_ERR_OK) {
        $g11_2nd = handleFileUpload($_FILES['g11_2nd'], $uploadDir);
    }

    // Handle Grade 12 1st Sem Report Card upload
    if (isset($_FILES['g12_1st']) && $_FILES['g12_1st']['error'] === UPLOAD_ERR_OK) {
        $g12_1st = handleFileUpload($_FILES['g12_1st'], $uploadDir);
    }

    // Handle NC II Certificate upload (if any)
    if (isset($_FILES['ncii']) && $_FILES['ncii']['error'] === UPLOAD_ERR_OK) {
        $ncii = handleFileUpload($_FILES['ncii'], $uploadDir);
    }

    // Handle Guidance Certificate upload (if any)
    if (isset($_FILES['guidance_cert']) && $_FILES['guidance_cert']['error'] === UPLOAD_ERR_OK) {
        $guidance_cert = handleFileUpload($_FILES['guidance_cert'], $uploadDir);
    }

    // Handle Additional File upload (if any)
    if (isset($_FILES['additional_file']) && $_FILES['additional_file']['error'] === UPLOAD_ERR_OK) {
        $additional_file = handleFileUpload($_FILES['additional_file'], $uploadDir);
    }

    if ($existing_documents) {
        // Update existing documents - only update fields that have new files
        error_log("Updating existing documents for personal_info_id: " . $personal_info_id);
        error_log("Values: g11_1st=$g11_1st, g11_2nd=$g11_2nd, g12_1st=$g12_1st, ncii=$ncii, guidance_cert=$guidance_cert, additional_file=$additional_file");
        
        // Build dynamic UPDATE query - only update fields that have new files
        $updateFields = [];
        $updateValues = [];
        
        if (!empty($g11_1st)) {
            $updateFields[] = "g11_1st = ?";
            $updateValues[] = $g11_1st;
        }
        if (!empty($g11_2nd)) {
            $updateFields[] = "g11_2nd = ?";
            $updateValues[] = $g11_2nd;
        }
        if (!empty($g12_1st)) {
            $updateFields[] = "g12_1st = ?";
            $updateValues[] = $g12_1st;
        }
        if (!empty($ncii)) {
            $updateFields[] = "ncii = ?";
            $updateValues[] = $ncii;
        }
        if (!empty($guidance_cert)) {
            $updateFields[] = "guidance_cert = ?";
            $updateValues[] = $guidance_cert;
        }
        if (!empty($additional_file)) {
            $updateFields[] = "additional_file = ?";
            $updateValues[] = $additional_file;
        }
        
        // Only update if there are fields to update
        if (!empty($updateFields)) {
            $updateValues[] = $personal_info_id;
            $sql = "UPDATE documents SET " . implode(", ", $updateFields) . " WHERE personal_info_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateValues);
            error_log("Update executed, affected rows: " . $stmt->rowCount());
        } else {
            error_log("No new files to update, skipping database update");
        }
    } else {
        // Insert new documents
        error_log("Inserting new documents for personal_info_id: " . $personal_info_id);
        error_log("Values: g11_1st=$g11_1st, g11_2nd=$g11_2nd, g12_1st=$g12_1st, ncii=$ncii, guidance_cert=$guidance_cert, additional_file=$additional_file");
        
        $stmt = $pdo->prepare("INSERT INTO documents (
            personal_info_id, g11_1st, g11_2nd, g12_1st,
            ncii, guidance_cert, additional_file
        ) VALUES (?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $personal_info_id,
            $g11_1st,
            $g11_2nd,
            $g12_1st,
            $ncii,
            $guidance_cert,
            $additional_file
        ]);
        
        error_log("Insert executed, new ID: " . $pdo->lastInsertId());
    }

    echo json_encode(['success' => true, 'message' => 'Documents saved successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function handleFileUpload($file, $uploadDir) {
    $fileName = uniqid() . '_' . basename($file['name']);
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $fileName;
    }
    throw new Exception('Failed to upload file: ' . $file['name']);
}
?> 