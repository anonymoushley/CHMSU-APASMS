<?php
ob_start(); // Start output buffering

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// PHPMailer and DB setup
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../vendor/autoload.php';
require_once '../config/database.php';

// Correct: use consistent naming
$personal_info_id = $_POST['personal_info_id'] ?? null;
$field = $_POST['field'] ?? null;
$action = $_POST['action'] ?? null;

// Validate required inputs
if (!$personal_info_id || !$field || !$action) {
    die('Missing required information.');
}

// Update document status
$stmt = $pdo->prepare("UPDATE documents SET {$field}_status = ? WHERE personal_info_id = ?");
$stmt->execute([$action, $personal_info_id]);

// Fetch applicant info
$query = $pdo->prepare("
    SELECT r.id AS registration_id, r.email_address, pi.first_name, pi.last_name 
    FROM registration r 
    JOIN personal_info pi ON r.personal_info_id = pi.id 
    WHERE pi.id = ?
");
$query->execute([$personal_info_id]);
$info = $query->fetch(PDO::FETCH_ASSOC);

if ($info && $info['email_address']) {
    $email = $info['email_address'];
    $full_name = $info['first_name'] . ' ' . $info['last_name'];

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'acregalado.chmsu@gmail.com';
        $mail->Password = 'vvekpeviojyyysfq'; // App password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('acregalado.chmsu@gmail.com', 'CHMSU Admissions');
        $mail->addAddress($email, $full_name);

        $statusMessage = $action === 'Accepted' ? 'accepted' : 'rejected';
        $docLabels = [
            'g11_1st' => 'G11 1st Sem Report Card',
            'g11_2nd' => 'G11 2nd Sem Report Card',
            'g12_1st' => 'G12 1st Sem Report Card',
            'ncii' => 'NC II Certificate',
            'guidance_cert' => 'Certification from Guidance Office',
            'additional_file' => 'Additional File'
        ];
        $docLabel = $docLabels[$field] ?? ucfirst($field);

        $mail->isHTML(true);
        $mail->Subject = "CHMSU Document Verification Update";
        $mail->Body = "
            <p>Dear <strong>{$full_name}</strong>,</p>
            <p>Your document <strong>{$docLabel}</strong> has been <strong>{$statusMessage}</strong> by the admissions office.</p>
            <p>Thank you,<br>CHMSU Admissions Team</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

// Return JSON response for AJAX handling
$docLabels = [
    'g11_1st' => 'G11 1st Sem Report Card',
    'g11_2nd' => 'G11 2nd Sem Report Card',
    'g12_1st' => 'G12 1st Sem Report Card',
    'ncii' => 'NC II Certificate',
    'guidance_cert' => 'Certification from Guidance Office',
    'additional_file' => 'Additional File'
];
$docLabel = $docLabels[$field] ?? ucfirst($field);

$response = [
    'success' => true,
    'message' => "Document '{$docLabel}' has been {$action} successfully.",
    'action' => $action,
    'document' => $docLabel,
    'registration_id' => $info['registration_id'] ?? null
];

header('Content-Type: application/json');
echo json_encode($response);
exit;
