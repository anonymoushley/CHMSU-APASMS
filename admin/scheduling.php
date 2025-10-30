<?php
// Include database connection
require_once '../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Add security headers to prevent caching and improve security
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Get sub-page parameter
$sub_page = isset($_GET['sub']) ? $_GET['sub'] : 'exam';

// Get filter parameters
$filterStatus = $_GET['filter_status'] ?? '';

// Handle form submissions
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_schedule') {
        $schedule_date = $_POST['schedule_date'] ?? '';
        $schedule_start_time = $_POST['schedule_start_time'] ?? '';
        $schedule_venues = $_POST['schedule_venues'] ?? [];
        
        if ($schedule_date && $schedule_start_time && !empty($schedule_venues)) {
            try {
                // Join all selected venues with comma
                $venues_string = implode(', ', $schedule_venues);
                
                // Create a single schedule record with all venues
                $stmt = $pdo->prepare("INSERT INTO schedules (event_date, event_time, venue, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$schedule_date, $schedule_start_time, $venues_string]);
                
                $success_message = "Schedule created successfully with " . count($schedule_venues) . " venue(s).";
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&success=schedule_created");
                exit();
            } catch (PDOException $e) {
                $error_message = "Failed to create schedule: " . $e->getMessage();
            }
        } else {
            $error_message = "Please fill in all required fields and select at least one venue.";
        }
    } elseif ($action === 'add_interview_schedule') {
        $interview_date = $_POST['interview_date'] ?? '';
        $interview_start_time = $_POST['interview_start_time'] ?? '';
        $interview_end_time = $_POST['interview_end_time'] ?? '';
        $interview_venue = $_POST['interview_venue'] ?? '';
        $interview_applicant = $_POST['interview_applicant'] ?? '';
        
        if ($interview_date && $interview_start_time && $interview_end_time && $interview_venue && $interview_applicant) {
            try {
                // Create a single schedule record for the interview
                $stmt = $pdo->prepare("INSERT INTO schedules (event_date, event_time, end_time, venue, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$interview_date, $interview_start_time, $interview_end_time, $interview_venue]);
                $schedule_id = $pdo->lastInsertId();
                
                // Create schedule_applicants junction table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS `schedule_applicants` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `schedule_id` int(11) NOT NULL,
                    `applicant_id` int(11) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_schedule_applicant` (`schedule_id`, `applicant_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
                
                // Link the applicant to this schedule
                $stmt = $pdo->prepare("INSERT INTO schedule_applicants (schedule_id, applicant_id, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$schedule_id, $interview_applicant]);
                
                $success_message = "Interview schedule added successfully.";
                // Redirect to prevent form resubmission - stay on scheduling page
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&success=interview_scheduled");
                exit();
                } catch (PDOException $e) {
                $error_message = "Failed to add interview schedule: " . $e->getMessage();
                }
            } else {
                $error_message = "Please fill in all required fields.";
            }
    } elseif ($action === 'assign_to_schedule') {
        $applicant_ids = $_POST['applicant_ids'] ?? '';
        $schedule_id = $_POST['schedule_id'] ?? '';
        
        if ($applicant_ids && $schedule_id) {
            try {
                // Create schedule_applicants junction table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS `schedule_applicants` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `schedule_id` int(11) NOT NULL,
                    `applicant_id` int(11) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_schedule_applicant` (`schedule_id`, `applicant_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
                
                // Link all applicants to the selected schedule
                $applicant_id_array = explode(',', $applicant_ids);
                $count = 0;
                
                foreach ($applicant_id_array as $applicant_id) {
                    if (is_numeric($applicant_id)) {
                        $stmt = $pdo->prepare("INSERT IGNORE INTO schedule_applicants (schedule_id, applicant_id, created_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$schedule_id, $applicant_id]);
                        if ($stmt->rowCount() > 0) {
                            $count++;
                        }
                    }
                }
                
                $success_message = "Successfully assigned {$count} applicants to the schedule.";
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&success=applicants_assigned");
                exit();
            } catch (PDOException $e) {
                $error_message = "Failed to assign applicants to schedule: " . $e->getMessage();
            }
        } else {
            $error_message = "Please select applicants and a schedule.";
            }
    } elseif ($action === 'set_exam_schedule') {
        $applicant_ids = $_POST['applicant_ids'] ?? '';
        $exam_date = $_POST['exam_date'] ?? '';
        $exam_start_time = $_POST['exam_start_time'] ?? '';
        $exam_venue = $_POST['exam_venue'] ?? '';
        
        if ($applicant_ids && $exam_date && $exam_start_time && $exam_venue) {
            try {
                // Create a single schedule record for the group
                $stmt = $pdo->prepare("INSERT INTO schedules (event_date, event_time, venue, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$exam_date, $exam_start_time, $exam_venue]);
                $schedule_id = $pdo->lastInsertId();
                
                // Create schedule_applicants junction table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS `schedule_applicants` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `schedule_id` int(11) NOT NULL,
                    `applicant_id` int(11) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_schedule_applicant` (`schedule_id`, `applicant_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
                
                // Link all applicants to this single schedule
                $applicant_id_array = explode(',', $applicant_ids);
                $count = 0;
                
                foreach ($applicant_id_array as $applicant_id) {
                    if (is_numeric($applicant_id)) {
                        $stmt = $pdo->prepare("INSERT INTO schedule_applicants (schedule_id, applicant_id, created_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$schedule_id, $applicant_id]);
                        $count++;
                    }
                }
                
                $success_message = "Exam schedule created successfully for {$count} selected applicants.";
                // Redirect to prevent form resubmission - stay on scheduling page
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&success=exam_scheduled");
                exit();
                } catch (PDOException $e) {
                $error_message = "Failed to set exam schedule: " . $e->getMessage();
                }
        } else {
            $error_message = "Please fill in all required fields (date, start time, venue) and select applicants.";
        }
    } elseif ($action === 'assign_to_interview_schedule') {
        $applicant_ids = $_POST['applicant_ids'] ?? '';
        $schedule_id = $_POST['schedule_id'] ?? '';
        
        if ($applicant_ids && $schedule_id) {
            try {
                // Create interview_schedule_applicants junction table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS `interview_schedule_applicants` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `schedule_id` int(11) NOT NULL,
                    `applicant_id` int(11) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_interview_schedule_applicant` (`schedule_id`, `applicant_id`),
                    KEY `idx_interview_schedule_id` (`schedule_id`),
                    KEY `idx_interview_applicant_id` (`applicant_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
                
                // Link all selected applicants to this interview schedule
                $applicant_id_array = explode(',', $applicant_ids);
                $count = 0;
                
                foreach ($applicant_id_array as $applicant_id) {
                    if (is_numeric($applicant_id)) {
                        $stmt = $pdo->prepare("INSERT INTO interview_schedule_applicants (schedule_id, applicant_id, created_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$schedule_id, $applicant_id]);
                        $count++;
                    }
                }
                
                $success_message = "Successfully assigned {$count} applicants to interview schedule.";
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&sub=interview&success=interview_assigned");
                exit();
                } catch (PDOException $e) {
                $error_message = "Failed to assign applicants to interview schedule: " . $e->getMessage();
                }
            } else {
                $error_message = "Please select applicants and a schedule.";
            }
    } elseif ($action === 'create_interview_schedule') {
        $interview_date = $_POST['interview_date'] ?? '';
        $interview_start_time = $_POST['interview_start_time'] ?? '';
        $interview_venues = $_POST['interview_venues'] ?? [];
        
        if ($interview_date && $interview_start_time && !empty($interview_venues)) {
            try {
                // Join all selected venues with comma
                $venues_string = implode(', ', $interview_venues);
                
                // Create interview_schedules table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS `interview_schedules` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `event_date` date NOT NULL,
                    `event_time` time NOT NULL,
                    `venue` varchar(255) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
                
                // Create a single interview schedule record with all venues
                $stmt = $pdo->prepare("INSERT INTO interview_schedules (event_date, event_time, venue, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$interview_date, $interview_start_time, $venues_string]);
                
                $success_message = "Interview schedule created successfully with " . count($interview_venues) . " venue(s).";
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&sub=interview&success=interview_schedule_created");
                exit();
            } catch (PDOException $e) {
                $error_message = "Failed to create interview schedule: " . $e->getMessage();
            }
        } else {
            $error_message = "Please fill in all required fields and select at least one venue.";
        }
    } elseif ($action === 'set_interview_schedule') {
        $applicant_ids = $_POST['applicant_ids'] ?? '';
        $interview_date = $_POST['interview_date'] ?? '';
        $interview_start_time = $_POST['interview_start_time'] ?? '';
        $interview_venue = $_POST['interview_venue'] ?? '';

        if ($applicant_ids && $interview_date && $interview_start_time && $interview_venue) {
            try {
                // Ensure dedicated interview tables exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS `interview_schedules` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `event_date` date NOT NULL,
                    `event_time` time NOT NULL,
                    `venue` varchar(255) DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

                $pdo->exec("CREATE TABLE IF NOT EXISTS `interview_schedule_applicants` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `schedule_id` int(11) NOT NULL,
                    `applicant_id` int(11) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_interview_schedule_applicant` (`schedule_id`, `applicant_id`),
                    KEY `idx_interview_schedule_id` (`schedule_id`),
                    KEY `idx_interview_applicant_id` (`applicant_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

                // Create a single interview schedule record for the group
                $stmt = $pdo->prepare("INSERT INTO interview_schedules (event_date, event_time, venue, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$interview_date, $interview_start_time, $interview_venue]);
                $schedule_id = $pdo->lastInsertId();

                // Link all selected applicants to this interview schedule
                $applicant_id_array = explode(',', $applicant_ids);
                $count = 0;
                foreach ($applicant_id_array as $applicant_id) {
                    if (is_numeric($applicant_id)) {
                        $stmt = $pdo->prepare("INSERT INTO interview_schedule_applicants (schedule_id, applicant_id, created_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$schedule_id, $applicant_id]);
                        $count++;
                    }
                }

                $success_message = "Interview schedule created successfully for {$count} selected applicants.";
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=scheduling&sub=interview&success=interview_scheduled");
                exit();
            } catch (PDOException $e) {
                $error_message = "Failed to set interview schedule: " . $e->getMessage();
            }
        } else {
            $error_message = "Please fill in all required fields (date, start time, end time, venue) and select applicants.";
        }
    }
}

// Handle success messages from redirects
$success_message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'schedule_created':
            $success_message = "Schedule created successfully!";
            break;
        case 'applicants_assigned':
            $success_message = "Applicants assigned to schedule successfully!";
            break;
        case 'exam_scheduled':
            $success_message = "Exam schedule created successfully!";
            break;
        case 'interview_scheduled':
            $success_message = "Interview schedule added successfully!";
            break;
        case 'interview_assigned':
            $success_message = "Applicants assigned to interview schedule successfully!";
            break;
        case 'interview_schedule_created':
            $success_message = "Interview schedule created successfully!";
            break;
    }
}

// Get all schedules with applicant count
try {
    $stmt = $pdo->query("SELECT s.*, COUNT(sa.applicant_id) as applicant_count 
                        FROM schedules s 
                        LEFT JOIN schedule_applicants sa ON s.id = sa.schedule_id 
                        GROUP BY s.id 
                        ORDER BY s.event_date, s.event_time");
    $all_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filter out schedules that already have applicants assigned (for dropdown)
    $schedules = array_filter($all_schedules, function($schedule) {
        return $schedule['applicant_count'] == 0;
    });
    
    // Keep all schedules for viewing (including assigned ones)
    $all_schedules_for_viewing = $all_schedules;
} catch (PDOException $e) {
    $schedules = [];
    $all_schedules_for_viewing = [];
    $error_message = "Failed to fetch schedules: " . $e->getMessage();
}

// Handle AJAX request for getting students for a specific schedule
if (isset($_GET['action']) && $_GET['action'] === 'get_schedule_students') {
    // Clear any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    
    $schedule_id = $_GET['schedule_id'] ?? '';
    
    if ($schedule_id) {
        try {
            $stmt = $pdo->prepare("SELECT pi.*, r.id as applicant_id, pa.campus, pa.college, pa.program
                                 FROM personal_info pi 
                                 LEFT JOIN registration r ON pi.id = r.personal_info_id
                                 LEFT JOIN program_application pa ON pi.id = pa.personal_info_id 
                                 INNER JOIN schedule_applicants sa ON r.id = sa.applicant_id
                                 WHERE sa.schedule_id = ?
                                 ORDER BY pi.last_name, pi.first_name");
            $stmt->execute([$schedule_id]);
            $schedule_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($schedule_students);
            exit();
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to fetch students: ' . $e->getMessage()]);
            exit();
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Schedule ID is required']);
        exit();
    }
}

// Handle AJAX request for getting students for a specific interview schedule
if (isset($_GET['action']) && $_GET['action'] === 'get_interview_schedule_students') {
    // Clear any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    
    $schedule_id = $_GET['schedule_id'] ?? '';
    
    if ($schedule_id) {
        try {
            $stmt = $pdo->prepare("SELECT pi.*, r.id as applicant_id, pa.campus, pa.college, pa.program
                                 FROM personal_info pi 
                                 LEFT JOIN registration r ON pi.id = r.personal_info_id
                                 LEFT JOIN program_application pa ON pi.id = pa.personal_info_id 
                                 INNER JOIN interview_schedule_applicants isa ON r.id = isa.applicant_id
                                 WHERE isa.schedule_id = ?
                                 ORDER BY pi.last_name, pi.first_name");
            $stmt->execute([$schedule_id]);
            $schedule_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($schedule_students);
            exit();
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to fetch interview students: ' . $e->getMessage()]);
            exit();
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Schedule ID is required']);
        exit();
    }
}

// Get applicants with all required documents accepted and their exam status
try {
    // First, check if document status fields exist
    $status_fields_exist = false;
    try {
        $check_stmt = $pdo->query("SHOW COLUMNS FROM documents LIKE '%_status'");
        $status_fields_exist = $check_stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $status_fields_exist = false;
    }
    
    if ($status_fields_exist) {
        // Query with document status fields - only show applicants with all documents accepted
        $stmt = $pdo->query("SELECT pi.*, r.id as applicant_id, pa.campus, pa.college, pa.program,
                            CASE WHEN sa.schedule_id IS NOT NULL THEN 'Scheduled' ELSE 'Not Scheduled' END as exam_status,
                            s.event_date as scheduled_date
                            FROM personal_info pi 
                            LEFT JOIN registration r ON pi.id = r.personal_info_id
                            LEFT JOIN program_application pa ON pi.id = pa.personal_info_id 
                            LEFT JOIN documents d ON pi.id = d.personal_info_id
                            LEFT JOIN schedule_applicants sa ON r.id = sa.applicant_id
                            LEFT JOIN schedules s ON sa.schedule_id = s.id
                            WHERE d.g11_1st IS NOT NULL 
                            AND d.g11_2nd IS NOT NULL 
                            AND d.g12_1st IS NOT NULL 
                            AND (d.g11_1st_status = 'Accepted' OR d.g11_1st_status IS NULL)
                            AND (d.g11_2nd_status = 'Accepted' OR d.g11_2nd_status IS NULL)
                            AND (d.g12_1st_status = 'Accepted' OR d.g12_1st_status IS NULL)
                            AND NOT EXISTS (
                                SELECT 1 FROM documents d2 
                                WHERE d2.personal_info_id = pi.id 
                                AND (
                                    (d2.g11_1st_status = 'Rejected') OR
                                    (d2.g11_2nd_status = 'Rejected') OR
                                    (d2.g12_1st_status = 'Rejected')
                                )
                            )
                            GROUP BY pi.id
                            ORDER BY pi.last_name, pi.first_name");
    } else {
        // Fallback: Query without status fields - only show applicants with documents uploaded
        $stmt = $pdo->query("SELECT pi.*, r.id as applicant_id, pa.campus, pa.college, pa.program,
                            CASE WHEN sa.schedule_id IS NOT NULL THEN 'Scheduled' ELSE 'Not Scheduled' END as exam_status,
                            s.event_date as scheduled_date
                            FROM personal_info pi 
                            LEFT JOIN registration r ON pi.id = r.personal_info_id
                            LEFT JOIN program_application pa ON pi.id = pa.personal_info_id 
                            LEFT JOIN documents d ON pi.id = d.personal_info_id
                            LEFT JOIN schedule_applicants sa ON r.id = sa.applicant_id
                            LEFT JOIN schedules s ON sa.schedule_id = s.id
                            WHERE d.g11_1st IS NOT NULL 
                            AND d.g11_2nd IS NOT NULL 
                            AND d.g12_1st IS NOT NULL 
                            GROUP BY pi.id
                            ORDER BY pi.last_name, pi.first_name");
    }
    
    $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Apply status filter if specified
    if (!empty($filterStatus)) {
        $applicants = array_filter($applicants, function($applicant) use ($filterStatus) {
            return $applicant['exam_status'] === $filterStatus;
        });
    }
    
    // Count scheduled applicants
    $scheduled_count = 0;
    $total_count = count($applicants);
    foreach($applicants as $applicant) {
        if($applicant['exam_status'] === 'Scheduled') {
            $scheduled_count++;
        }
    }
    
} catch (PDOException $e) {
    $applicants = [];
    $scheduled_count = 0;
    $total_count = 0;
    $error_message = "Failed to fetch applicants: " . $e->getMessage();
}

// Get applicants eligible for interview (exam score >= 50%)
try {
    $stmt = $pdo->query("SELECT pi.*, r.id as applicant_id, pa.campus, pa.college, pa.program,
                         COALESCE(sr.exam_total_score, 0) AS exam_total_score,
                         CASE WHEN isa.schedule_id IS NOT NULL THEN 'Scheduled' ELSE 'Not Scheduled' END as interview_status,
                         isch.event_date as scheduled_date
                         FROM personal_info pi
                         LEFT JOIN registration r ON pi.id = r.personal_info_id
                         LEFT JOIN program_application pa ON pi.id = pa.personal_info_id
                         LEFT JOIN screening_results sr ON sr.personal_info_id = pi.id
                         LEFT JOIN interview_schedule_applicants isa ON r.id = isa.applicant_id
                         LEFT JOIN interview_schedules isch ON isa.schedule_id = isch.id
                         WHERE COALESCE(sr.exam_total_score, 0) >= 50
                         GROUP BY pi.id
                         ORDER BY pi.last_name, pi.first_name");
    $interview_applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $interview_applicants = [];
}

// Fetch interview schedules and counts from dedicated tables if present
try {
    // Make sure tables exist before selecting (no-op if they don't)
    $pdo->query("SELECT 1 FROM interview_schedules LIMIT 1");
    $stmt = $pdo->query("SELECT isch.*, 
                         COALESCE(COUNT(isa.applicant_id), 0) AS applicant_count
                         FROM interview_schedules isch
                         LEFT JOIN interview_schedule_applicants isa ON isch.id = isa.schedule_id
                         GROUP BY isch.id
                         ORDER BY isch.event_date, isch.event_time");
    $interview_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $interview_schedules = [];
}

// Get rooms for venue dropdown
try {
    $stmt = $pdo->query("SELECT r.*, b.name as building_name 
                        FROM rooms r 
                        LEFT JOIN buildings b ON r.building_id = b.id 
                        WHERE r.status = 'active' 
                        ORDER BY b.name, r.room_number");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $rooms = [];
    $error_message = "Failed to fetch rooms: " . $e->getMessage();
}
?>

<style>
    /* Custom Date Input Styling - Flat Design */
    input[type="date"] {
        background: #ffffff;
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 8px 12px;
        font-size: 14px;
        color: #495057;
        box-shadow: none;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    input[type="date"]:focus {
        border-color: rgb(0, 105, 42);
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    
    input[type="date"]::-webkit-calendar-picker-indicator {
        cursor: pointer;
        opacity: 1;
    }
    
    input[type="date"]::-webkit-inner-spin-button,
    input[type="date"]::-webkit-clear-button {
        display: none;
    }
    
    /* Custom Time Input Styling */
    input[type="time"] {
        background: #ffffff;
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 8px 12px;
        font-size: 14px;
        color: #495057;
        box-shadow: none;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    input[type="time"]:focus {
        border-color: rgb(0, 105, 42);
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    
    input[type="time"]::-webkit-calendar-picker-indicator {
        cursor: pointer;
        opacity: 1;
    }
    
    /* Form Control Overrides */
    .form-control {
        border-radius: 4px;
        border: 1px solid #ced4da;
        box-shadow: none;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .form-control:focus {
        border-color: rgb(0, 105, 42);
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    
    /* Back Button Theme Styling - Matching Header Color */
    .btn-outline-success {
        border-color: rgb(0, 105, 42);
        color: rgb(0, 105, 42);
        font-weight: 500;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .btn-outline-success:hover {
        background-color: rgb(0, 105, 42);
        border-color: rgb(0, 105, 42);
        color: white;
    }

    .btn-outline-success:active {
        background-color: rgb(0, 85, 34);
        border-color: rgb(0, 85, 34);
        color: white;
    }

    .nav-pills .nav-link {
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .nav-pills .nav-link.active {
        background-color: rgb(0, 105, 42) !important;
        color: white !important;
    }

    .nav-pills .nav-link {
        color: rgb(0, 105, 42) !important;
    }

    .nav-pills .nav-link:hover:not(.active) {
        background-color: rgba(25, 135, 84, 0.1);
        color: rgb(0, 105, 42) !important;
    }
    
    /* Green checkbox styling - Override Bootstrap defaults */
    input[type="checkbox"].applicant-checkbox {
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        width: 1.25rem !important;
        height: 1.25rem !important;
        border: 2px solid rgb(0, 105, 42) !important;
        border-radius: 0.25rem !important;
        background-color: transparent !important;
        cursor: pointer !important;
        position: relative !important;
    }
    
    input[type="checkbox"].applicant-checkbox:checked {
        background-color: rgb(0, 105, 42) !important;
        border-color: rgb(0, 105, 42) !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='m6 10 3 3 6-6'/%3e%3c/svg%3e") !important;
        background-size: 100% 100% !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
    }
    
    input[type="checkbox"].applicant-checkbox:focus {
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25) !important;
        border-color: rgb(0, 105, 42) !important;
        outline: none !important;
    }
    
    input[type="checkbox"].applicant-checkbox:hover {
        border-color: rgb(0, 105, 42) !important;
    }
    
    input[type="checkbox"].applicant-checkbox:disabled {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
    }
    
    /* Interview checkbox styling - Same as applicant checkbox */
    input[type="checkbox"].interview-applicant-checkbox {
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        width: 1.25rem !important;
        height: 1.25rem !important;
        border: 2px solid rgb(0, 105, 42) !important;
        border-radius: 0.25rem !important;
        background-color: transparent !important;
        cursor: pointer !important;
        position: relative !important;
    }
    
    input[type="checkbox"].interview-applicant-checkbox:checked {
        background-color: rgb(0, 105, 42) !important;
        border-color: rgb(0, 105, 42) !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='m6 10 3 3 6-6'/%3e%3c/svg%3e") !important;
        background-size: 100% 100% !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
    }
    
    input[type="checkbox"].interview-applicant-checkbox:focus {
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25) !important;
        border-color: rgb(0, 105, 42) !important;
        outline: none !important;
    }
    
    input[type="checkbox"].interview-applicant-checkbox:hover {
        border-color: rgb(0, 105, 42) !important;
    }
    
    input[type="checkbox"].interview-applicant-checkbox:disabled {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        background-color: #f8f9fa !important;
        border-color: #dee2e6 !important;
    }
    
    /* Remove spinner arrows from number inputs */
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    
    input[type="number"] {
        -moz-appearance: textfield;
    }
    
    /* Center text in range input boxes */
    #rangeInput, #rangeInputInterview {
        text-align: center;
        font-size: 16px !important;
        font-weight: 600;
    }
</style>

<!-- Add meta tags to prevent caching -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<div class="container-fluid px-4" style="padding-top: 30px;">
    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
        <!-- Toasts will be dynamically added here -->
    </div>
    
    <!-- Header with Back Button -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="?page=<?= isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'chairperson' ? 'chair_dashboard' : (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'interviewer' ? 'interviewer_dashboard' : 'dashboard') ?>" class="btn btn-outline-success me-3">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                    <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Scheduling Management</h4>
                </div>
            </div>
        </div>
            </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
            <?php if (isset($success_message) && !empty($success_message)): ?>
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header" style="background-color: #d4edda; border-color: #c3e6cb;">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    <strong class="me-auto text-success">Success</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body" style="background-color: #d4edda;">
                    <?= $success_message ?>
                </div>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header" style="background-color: #f8d7da; border-color: #f5c6cb;">
                    <i class="fas fa-exclamation-circle text-danger me-2"></i>
                    <strong class="me-auto text-danger">Error</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body" style="background-color: #f8d7da;">
                    <?= $error_message ?>
                </div>
                </div>
            <?php endif; ?>
    </div>

    <!-- Navigation Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills" id="schedulingTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $sub_page === 'exam' ? 'active' : '' ?>" id="exam-tab" data-bs-toggle="pill" data-bs-target="#exam" type="button" role="tab" aria-controls="exam" aria-selected="<?= $sub_page === 'exam' ? 'true' : 'false' ?>">
                        <i class="fas fa-clipboard-check me-2"></i>Exam Scheduling
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $sub_page === 'interview' ? 'active' : '' ?>" id="interview-tab" data-bs-toggle="pill" data-bs-target="#interview" type="button" role="tab" aria-controls="interview" aria-selected="<?= $sub_page === 'interview' ? 'true' : 'false' ?>">
                        <i class="fas fa-microphone me-2"></i>Interview Scheduling
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="schedulingTabContent">
        <!-- Exam Scheduling Tab -->
        <div class="tab-pane fade <?= $sub_page === 'exam' ? 'show active' : '' ?>" id="exam" role="tabpanel" aria-labelledby="exam-tab">
            <!-- Applicants Selection Section -->
            <div class="row mb-4">
                <div class="col-12">
            <div class="card">
                        <div class="card-header text-white" style="background-color: rgb(0, 105, 42);">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-users"></i> Select Applicants for Exam (Documents Accepted)</h5>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex align-items-center">
                                        <span class="me-2" style="color: white; font-weight: 500;">Range:</span>
                                        <input type="number" id="rangeInput" class="form-control" min="0" step="1" value="0" oninput="updateRangeFromInput()" onfocus="handleRangeInputFocus()" onblur="handleRangeInputBlur()" style="width: 40px; height: 31px; border-color: rgb(0, 105, 42); font-size: 12px; padding: 4px 6px; line-height: 1;">
                                    </div>
                                    <button type="button" class="btn btn-light btn-sm" onclick="selectAllApplicants()">
                                        <i class="fas fa-check-square"></i> Select All
                                    </button>
                                </div>
                            </div>
                </div>
                <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <span class="me-2">Number of applicants:</span>
                                        <span class="badge" style="background-color: rgb(0, 105, 42);" id="unscheduledCount"><?= $total_count - $scheduled_count ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-danger me-2" onclick="createNewSchedule()">
                                            <i class="fas fa-plus"></i> Create Schedule
                                        </button>
                                        <button type="button" class="btn btn-warning me-2" id="setExamScheduleBtn" onclick="setExamSchedule()" disabled>
                                            <i class="fas fa-calendar-plus"></i> Assign to Schedule
                                        </button>
                                        <button type="button" class="btn" id="viewExamSchedulesBtn" onclick="viewExamSchedules()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                                            <i class="fas fa-calendar-alt"></i> View Schedules
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50"></th>
                                            <th>Name</th>
                                            <th class="text-center">Campus</th>
                                            <th class="text-center">Program</th>
                                    </tr>
                                </thead>
                                <tbody>
                                        <?php if (empty($applicants)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">
                                                    <i class="fas fa-users fa-2x mb-2"></i><br>
                                                    No Applicants Found
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($applicants as $applicant): ?>
                                                <?php if ($applicant['exam_status'] !== 'Scheduled'): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="applicant-checkbox" value="<?= $applicant['applicant_id'] ?>" onchange="updateSelectedCount()">
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars(ucwords(strtolower(trim($applicant['last_name'] . ', ' . $applicant['first_name'] . ' ' . ($applicant['middle_name'] ?? ''))))) ?></strong>
                                                </td>
                                                <td class="text-center"><?= htmlspecialchars($applicant['campus'] ?? 'N/A') ?></td>
                                                <td class="text-center"><?= htmlspecialchars($applicant['program'] ?? 'N/A') ?></td>
                                            </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </div>
                </div>
            </div>

</div>

        <!-- Interview Scheduling Tab -->
        <div class="tab-pane fade <?= $sub_page === 'interview' ? 'show active' : '' ?>" id="interview" role="tabpanel" aria-labelledby="interview-tab">
            <!-- Applicants Selection Section (Interview Eligible: Exam >= 50%) -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header text-white" style="background-color: rgb(0, 105, 42);">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-users"></i> Select Applicants for Interview (Exam ≥ 50%)</h5>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex align-items-center">
                                        <span class="me-2" style="color: white; font-weight: 500;">Range:</span>
                                        <input type="number" id="rangeInputInterview" class="form-control" min="0" step="1" value="0" oninput="updateRangeFromInputInterview()" onfocus="handleRangeInputFocusInterview()" onblur="handleRangeInputBlurInterview()" style="width: 40px; height: 31px; border-color: rgb(0, 105, 42); font-size: 12px; padding: 4px 6px; line-height: 1;">
                                    </div>
                                    <button type="button" class="btn btn-light btn-sm" onclick="selectAllApplicantsInterview()">
                                        <i class="fas fa-check-square"></i> Select All
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <span class="me-2">Number of applicants:</span>
                                        <span class="badge" style="background-color: rgb(0, 105, 42);" id="unscheduledCountInterview"><?= count(array_filter($interview_applicants, function($applicant) { return $applicant['interview_status'] === 'Not Scheduled'; })) ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-danger me-2" onclick="createNewInterviewSchedule()">
                                            <i class="fas fa-plus"></i> Create Schedule
                                        </button>
                                        <button type="button" class="btn btn-warning me-2" id="setInterviewScheduleBtn" onclick="setInterviewSchedule()" disabled>
                                            <i class="fas fa-calendar-plus"></i> Assign to Schedule
                                        </button>
                                        <button type="button" class="btn" id="viewInterviewStudentListBtn" onclick="viewInterviewSchedules()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                                            <i class="fas fa-calendar-alt"></i> View Schedules
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50"></th>
                                            <th>Name</th>
                                            <th class="text-center">Campus</th>
                                            <th class="text-center">Program</th>
                                            <th class="text-center">Exam Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($interview_applicants)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    <i class="fas fa-users fa-2x mb-2"></i><br>
                                                    No Eligible Applicants Found
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($interview_applicants as $applicant): ?>
                                                <?php if ($applicant['interview_status'] !== 'Scheduled'): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="interview-applicant-checkbox" value="<?= $applicant['applicant_id'] ?>" onchange="updateSelectedCountInterview()">
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars(ucwords(strtolower(trim($applicant['last_name'] . ', ' . $applicant['first_name'] . ' ' . ($applicant['middle_name'] ?? ''))))) ?></strong>
                                                </td>
                                                <td class="text-center"><?= htmlspecialchars($applicant['campus'] ?? 'N/A') ?></td>
                                                <td class="text-center"><?= htmlspecialchars($applicant['program'] ?? 'N/A') ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary">
                                                        <?= number_format((float)($applicant['exam_total_score'] ?? 0), 2) ?>%
                                                    </span>
                                                </td>
                                            </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Schedule Modal -->
<div class="modal fade" id="createScheduleModal" tabindex="-1" aria-labelledby="createScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="createScheduleModalLabel">
                    <i class="fas fa-plus"></i> Create New Schedule
                </h5>
            </div>
            <form method="POST" id="createScheduleForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_schedule">
                    
                    <div class="mb-3">
                        <label for="create_schedule_date" class="form-label">Schedule Date *</label>
                        <input type="date" class="form-control" id="create_schedule_date" name="schedule_date" required min="<?= date('Y-m-d') ?>" style="position: relative; z-index: 1050;">
                    </div>
                    <div class="mb-3">
                        <label for="create_schedule_start_time" class="form-label">Start Time *</label>
                        <input type="time" class="form-control" id="create_schedule_start_time" name="schedule_start_time" required style="position: relative; z-index: 1050;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Venues *</label>
                        <div class="row">
                            <?php foreach ($rooms as $room): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="schedule_venues[]" value="<?= htmlspecialchars($room['building_name'] . ' - Room ' . $room['room_number']) ?>" id="venue_<?= $room['id'] ?>">
                                        <label class="form-check-label" for="venue_<?= $room['id'] ?>">
                                            <?= htmlspecialchars($room['building_name'] . ' - Room ' . $room['room_number']) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">Select one or more venues for this schedule.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn" onclick="showCreateScheduleConfirmation()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                        <i class="fas fa-check-circle"></i> Review & Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Schedule Confirmation Modal -->
<div class="modal fade" id="createScheduleConfirmModal" tabindex="-1" aria-labelledby="createScheduleConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: rgb(0, 105, 42);">
                <h5 class="modal-title" id="createScheduleConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Schedule Creation
                </h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <strong>Please review the schedule details:</strong>
                </div>
                <div id="createScheduleDetails">
                    <!-- Details will be populated by JavaScript -->
                </div>
                <div class="alert alert-success mt-3">
                    <i class="fas fa-question-circle"></i> Are you sure you want to create this schedule?
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn" onclick="proceedWithCreateSchedule()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                    <i class="fas fa-check"></i> Yes, Create Schedule
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Assignment Confirmation Modal -->
<div class="modal fade" id="examScheduleConfirmModal" tabindex="-1" aria-labelledby="examScheduleConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: rgb(0, 105, 42);">
                <h5 class="modal-title" id="examScheduleConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Assignment
                </h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <strong>Please review the assignment details:</strong>
                </div>
                <div id="examScheduleDetails">
                    <!-- Details will be populated by JavaScript -->
                </div>
                <div class="alert alert-success mt-3">
                    <i class="fas fa-question-circle"></i> Are you sure you want to assign these applicants to the selected schedule?
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn" onclick="proceedWithExamSchedule()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                    <i class="fas fa-check"></i> Yes, Assign to Schedule
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Assign to Schedule Modal -->
<div class="modal fade" id="setExamScheduleModal" tabindex="-1" aria-labelledby="setExamScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="setExamScheduleModalLabel">
                    <i class="fas fa-calendar-plus"></i> Assign to Schedule
                </h5>
            </div>
            <form method="POST" id="examScheduleForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_to_schedule">
                    <input type="hidden" name="applicant_ids" id="selectedApplicantIds">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Select a schedule to assign the selected applicants to:</strong>
                    </div>
                    
                            <div class="mb-3">
                        <label for="modal_schedule_select" class="form-label">Available Schedules *</label>
                        <select class="form-control" id="modal_schedule_select" name="schedule_id" required>
                            <option value="">Select a schedule</option>
                            <?php foreach ($schedules as $schedule): ?>
                                <option value="<?= $schedule['id'] ?>">
                                    <?= date('M d, Y', strtotime($schedule['event_date'])) ?> - 
                                    <?= date('g:i A', strtotime($schedule['event_time'])) ?> - 
                                    <?= htmlspecialchars($schedule['venue'] ?? 'No venue') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if (empty($schedules)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> <strong>No schedules available.</strong> Please create a schedule first.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn" onclick="showAssignScheduleConfirmation()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);" <?= empty($schedules) ? 'disabled' : '' ?>>
                        <i class="fas fa-check-circle"></i> Assign to Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Exam Schedules Modal -->
<div class="modal fade" id="examSchedulesModal" tabindex="-1" aria-labelledby="examSchedulesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="examSchedulesModalLabel">
                    <i class="fas fa-calendar-alt"></i> Exam Schedules
                </h5>
            </div>
            <div class="modal-body">
                <div id="examSchedulesContent">
                    <!-- Exam schedules table will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Set Interview Schedule Modal -->
<div class="modal fade" id="setInterviewScheduleModal" tabindex="-1" aria-labelledby="setInterviewScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="setInterviewScheduleModalLabel">
                    <i class="fas fa-calendar-plus"></i> Assign to Schedule
                </h5>
            </div>
            <form method="POST" id="interviewScheduleForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_to_interview_schedule">
                    <input type="hidden" name="applicant_ids" id="selectedInterviewApplicantIds">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Select a schedule to assign the selected applicants to:</strong>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modal_interview_schedule_select" class="form-label">Available Interview Schedules *</label>
                        <select class="form-control" id="modal_interview_schedule_select" name="schedule_id" required>
                            <option value="">Select a schedule</option>
                            <?php 
                            // Get interview schedules for dropdown
                            $dropdown_schedules = [];
                            try {
                                $stmt = $pdo->prepare("SELECT * FROM interview_schedules ORDER BY event_date, event_time");
                                $stmt->execute();
                                $dropdown_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            } catch (PDOException $e) {
                                // Handle error silently
                            }
                            foreach ($dropdown_schedules as $schedule): ?>
                                <option value="<?= $schedule['id'] ?>">
                                    <?= date('M d, Y', strtotime($schedule['event_date'])) ?> - 
                                    <?= date('g:i A', strtotime($schedule['event_time'])) ?> - 
                                    <?= htmlspecialchars($schedule['venue'] ?? 'No venue') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if (empty($dropdown_schedules)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> <strong>No interview schedules available.</strong> Please create a schedule first.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn" onclick="showAssignInterviewScheduleConfirmation()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);" <?= empty($dropdown_schedules) ? 'disabled' : '' ?>>
                        <i class="fas fa-check-circle"></i> Assign to Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Interview Schedule Confirmation Modal -->
<div class="modal fade" id="assignInterviewScheduleConfirmModal" tabindex="-1" aria-labelledby="assignInterviewScheduleConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: rgb(0, 105, 42);">
                <h5 class="modal-title" id="assignInterviewScheduleConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Interview Schedule Assignment
                </h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <strong>Please review the assignment details:</strong>
                </div>
                <div id="assignInterviewScheduleDetails">
                    <!-- Details will be populated by JavaScript -->
                </div>
                <div class="alert alert-success mt-3">
                    <i class="fas fa-question-circle"></i> Are you sure you want to assign these applicants to the selected interview schedule?
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn" onclick="proceedWithAssignInterviewSchedule()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                    <i class="fas fa-check"></i> Yes, Assign to Schedule
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Interview Schedules Modal -->
<div class="modal fade" id="interviewSchedulesModal" tabindex="-1" aria-labelledby="interviewSchedulesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="interviewSchedulesModalLabel">
                    <i class="fas fa-calendar-alt"></i> Interview Schedules
                </h5>
            </div>
            <div class="modal-body">
                <div id="interviewSchedulesContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Interview Schedule Modal -->
<div class="modal fade" id="createInterviewScheduleModal" tabindex="-1" aria-labelledby="createInterviewScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="createInterviewScheduleModalLabel">
                    <i class="fas fa-plus"></i> Create New Interview Schedule
                </h5>
            </div>
            <form method="POST" id="createInterviewScheduleForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_interview_schedule">
                    
                    <div class="mb-3">
                        <label for="create_interview_date" class="form-label">Schedule Date *</label>
                        <input type="date" class="form-control" id="create_interview_date" name="interview_date" required min="<?= date('Y-m-d') ?>" style="position: relative; z-index: 1050;">
                    </div>
                    <div class="mb-3">
                        <label for="create_interview_start_time" class="form-label">Start Time *</label>
                        <input type="time" class="form-control" id="create_interview_start_time" name="interview_start_time" required style="position: relative; z-index: 1050;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Venues *</label>
                        <div class="row">
                            <?php foreach ($rooms as $room): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interview_venues[]" value="<?= htmlspecialchars($room['building_name'] . ' - Room ' . $room['room_number']) ?>" id="interview_venue_<?= $room['id'] ?>">
                                        <label class="form-check-label" for="interview_venue_<?= $room['id'] ?>">
                                            <?= htmlspecialchars($room['building_name'] . ' - Room ' . $room['room_number']) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">Select one or more venues for this interview schedule.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn" onclick="showCreateInterviewScheduleConfirmation()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                        <i class="fas fa-check-circle"></i> Review & Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Interview Schedule Confirmation Modal -->
<div class="modal fade" id="createInterviewScheduleConfirmModal" tabindex="-1" aria-labelledby="createInterviewScheduleConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: rgb(0, 105, 42);">
                <h5 class="modal-title" id="createInterviewScheduleConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Interview Schedule Creation
                </h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <strong>Please review the interview schedule details:</strong>
                </div>
                <div id="createInterviewScheduleDetails">
                    <!-- Details will be populated by JavaScript -->
                </div>
                <div class="alert alert-success mt-3">
                    <i class="fas fa-question-circle"></i> Are you sure you want to create this interview schedule?
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn" onclick="proceedWithCreateInterviewSchedule()" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                    <i class="fas fa-check"></i> Yes, Create Schedule
                </button>
            </div>
        </div>
    </div>
</div>
<!-- View Student List Modal -->
<div class="modal fade" id="viewStudentListModal" tabindex="-1" aria-labelledby="viewStudentListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="viewStudentListModalLabel">
                    <i class="fas fa-users"></i> Student List
                </h5>
            </div>
            <div class="modal-body">
                <div class="mb-3" id="scheduleInfoContainer" style="display: none;">
                    <div id="scheduleInfo" class="alert alert-success" style="min-height: 40px;">
                        <!-- Schedule information will be populated here -->
                    </div>
                </div>
                <div id="studentListContent">
                    <!-- Student list will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" id="exportStudentListBtn" onclick="exportStudentList()" style="display: none; background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42);">
                    <i class="fas fa-download"></i> Export to CSV
                </button>
                <button type="button" class="btn btn-secondary" id="backToSchedulesBtn" onclick="closeStudentListModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Toast notification functions
function showToast(message, type = 'success', duration = 2000) {
    const toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) return;
    
    const toastId = 'toast-' + Date.now();
    const iconClass = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    const bgClass = type === 'success' ? 'text-bg-success' : 'bg-danger';
    const typeLabel = type === 'success' ? 'Success' : 'Error';
    
    const toastHTML = `
        <div id="${toastId}" class="toast ${bgClass} text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header ${bgClass} text-white border-0">
                <i class="${iconClass} me-2"></i>
                <strong class="me-auto">${typeLabel}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: duration
    });
    
    toast.show();
    
    // Remove from DOM after hiding
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Clear URL parameters immediately to prevent success message from persisting
    if (window.location.search.includes('success=')) {
        const url = new URL(window.location);
        url.searchParams.delete('success');
        window.history.replaceState({}, '', url);
        
        // Also hide any empty success toasts
        const successToasts = document.querySelectorAll('.toast');
        successToasts.forEach(toast => {
            const toastBody = toast.querySelector('.toast-body');
            if (toastBody && (!toastBody.textContent.trim() || toastBody.textContent.trim() === '')) {
                toast.style.display = 'none';
            }
        });
    }
    
    // Auto-hide toasts after 3 seconds (exclude modal content)
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        // Skip toasts that are inside modals
        if (!toast.closest('.modal')) {
            setTimeout(() => {
                const bsToast = new bootstrap.Toast(toast);
                bsToast.hide();
            }, 3000);
        }
    });
    
    // Hide success messages after 5 seconds (exclude modal content)
    const successAlerts = document.querySelectorAll('.alert-success');
    successAlerts.forEach(alert => {
        // Skip alerts that are inside modals
        if (!alert.closest('.modal')) {
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }
    });
    
    // Global modal cleanup function
    function cleanupModalBackdrop() {
        // Remove any remaining backdrop
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        
        // Remove modal-open class from body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
    
    // Add cleanup to all modals
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', cleanupModalBackdrop);
    });
    
    // Prevent form resubmission
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
        });
    });
    

    // Handle tab switching via URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const subPage = urlParams.get('sub');
    
    if (subPage === 'interview') {
        const interviewTab = new bootstrap.Tab(document.getElementById('interview-tab'));
        interviewTab.show();
    } else {
        const examTab = new bootstrap.Tab(document.getElementById('exam-tab'));
        examTab.show();
    }

    // Update URL when tabs are clicked
    document.getElementById('exam-tab').addEventListener('click', function() {
        const url = new URL(window.location);
        url.searchParams.set('sub', 'exam');
        window.history.pushState({}, '', url);
    });

    document.getElementById('interview-tab').addEventListener('click', function() {
        const url = new URL(window.location);
        url.searchParams.set('sub', 'interview');
        window.history.pushState({}, '', url);
    });
    
    // Show PHP messages as toasts
    <?php if (isset($success_message) && !empty($success_message)): ?>
        showToast('<?= addslashes($success_message) ?>', 'success', 2000);
    <?php endif; ?>
    
    <?php if (isset($error_message) && !empty($error_message)): ?>
        showToast('<?= addslashes($error_message) ?>', 'error', 2000);
    <?php endif; ?>
    
    // Add event listener to clear data when student list modal is hidden
    const studentListModal = document.getElementById('viewStudentListModal');
    if (studentListModal) {
        studentListModal.addEventListener('hidden.bs.modal', function() {
            // Clear global data when modal is hidden
            window.currentStudentData = null;
            window.currentScheduleInfo = null;
            
            // Clear the student list content
            document.getElementById('studentListContent').innerHTML = '';
            
            // Hide export button
            document.getElementById('exportStudentListBtn').style.display = 'none';
        });
    }
});

// Function to update selected count
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.applicant-checkbox:checked');
    const totalCheckboxes = document.querySelectorAll('.applicant-checkbox:not([disabled])');
    const count = checkboxes.length;
    const total = totalCheckboxes.length;
    
    console.log('=== updateSelectedCount DEBUG ===');
    console.log('Checked checkboxes:', count);
    console.log('Total available checkboxes:', total);
    console.log('Checkbox elements found:', checkboxes);
    
    // Update count displays with null checks
    const selectedCountEl = document.getElementById('selectedCount');
    const totalCountEl = document.getElementById('totalCount');
    
    console.log('selectedCount element found:', selectedCountEl);
    console.log('totalCount element found:', totalCountEl);
    
    if (selectedCountEl) {
        selectedCountEl.textContent = count;
        console.log('Updated selectedCount to:', count);
    } else {
        console.error('selectedCount element NOT FOUND!');
    }
    if (totalCountEl) {
        totalCountEl.textContent = total;
        console.log('Updated totalCount to:', total);
    } else {
        console.error('totalCount element NOT FOUND!');
    }
    
    // Enable/disable the Set Exam Schedule button
    const setExamBtn = document.getElementById('setExamScheduleBtn');
    const viewStudentBtn = document.getElementById('viewStudentListBtn');
    
    console.log('setExamBtn found:', setExamBtn);
    console.log('viewStudentBtn found:', viewStudentBtn);
    
    if (setExamBtn) {
        console.log('Before update - disabled:', setExamBtn.disabled, 'classes:', setExamBtn.className);
        
        if (count > 0) {
            setExamBtn.disabled = false;
            setExamBtn.classList.remove('btn-secondary');
            setExamBtn.classList.add('btn-warning');
            setExamBtn.style.backgroundColor = '#ffc107';
            setExamBtn.style.color = '#000';
            setExamBtn.style.borderColor = '#ffc107';
            console.log('✅ Button ENABLED - disabled:', setExamBtn.disabled, 'classes:', setExamBtn.className);
        } else {
            setExamBtn.disabled = true;
            setExamBtn.classList.remove('btn-warning');
            setExamBtn.classList.add('btn-secondary');
            setExamBtn.style.backgroundColor = '';
            setExamBtn.style.color = '';
            setExamBtn.style.borderColor = '';
            console.log('❌ Button DISABLED - disabled:', setExamBtn.disabled, 'classes:', setExamBtn.className);
        }
    } else {
        console.error('❌ setExamBtn element NOT FOUND!');
    }
    
    // Exam Schedule button is always enabled
    if (viewStudentBtn) {
        viewStudentBtn.disabled = false;
        viewStudentBtn.classList.remove('btn-secondary');
        viewStudentBtn.classList.add('btn-success');
        viewStudentBtn.style.backgroundColor = 'rgb(0, 105, 42)';
        viewStudentBtn.style.color = 'white';
        viewStudentBtn.style.borderColor = 'rgb(0, 105, 42)';
        console.log('viewStudentBtn enabled');
    } else {
        console.error('viewStudentBtn element NOT FOUND!');
    }
    
    // Update select all button text based on current state
    const selectAllBtn = document.querySelector('button[onclick="selectAllApplicants()"]');
    if (selectAllBtn) {
        const totalCheckboxes = document.querySelectorAll('.applicant-checkbox:not([disabled])').length;
        if (count === totalCheckboxes && totalCheckboxes > 0) {
            selectAllBtn.innerHTML = '<i class="fas fa-square"></i> Unselect All';
        } else {
            selectAllBtn.innerHTML = '<i class="fas fa-check-square"></i> Select All';
        }
    }
    
    console.log('=== END updateSelectedCount DEBUG ===');
}

// ================= Interview Tab JS (mirrors exam scheduling) =================
// Counters and range for interview selection
let currentRangeInterview = 0;

document.addEventListener('DOMContentLoaded', function() {
    const availableCount = document.querySelectorAll('.interview-applicant-checkbox').length;
    const totalCountEl = document.getElementById('totalCountInterview');
    if (totalCountEl) totalCountEl.textContent = availableCount;

    currentRangeInterview = 0;
    const rangeInput = document.getElementById('rangeInputInterview');
    if (rangeInput) {
        rangeInput.value = currentRangeInterview;
    }
    selectCurrentRangeInterview();
});

function updateSelectedCountInterview() {
    const checkboxes = document.querySelectorAll('.interview-applicant-checkbox:checked');
    const totalCheckboxes = document.querySelectorAll('.interview-applicant-checkbox');
    const count = checkboxes.length;
    const total = totalCheckboxes.length;

    const selectedCountEl = document.getElementById('selectedCountInterview');
    const totalCountEl = document.getElementById('totalCountInterview');
    if (selectedCountEl) selectedCountEl.textContent = count;
    if (totalCountEl) totalCountEl.textContent = total;

    // Enable/disable the Set Interview Schedule button
    const setInterviewBtn = document.getElementById('setInterviewScheduleBtn');
    
    if (setInterviewBtn) {
        if (count > 0) {
            setInterviewBtn.disabled = false;
            setInterviewBtn.classList.remove('btn-secondary');
            setInterviewBtn.classList.add('btn-warning');
            setInterviewBtn.style.backgroundColor = '#ffc107';
            setInterviewBtn.style.color = '#000';
            setInterviewBtn.style.borderColor = '#ffc107';
        } else {
            setInterviewBtn.disabled = true;
            setInterviewBtn.classList.remove('btn-warning');
            setInterviewBtn.classList.add('btn-secondary');
            setInterviewBtn.style.backgroundColor = '';
            setInterviewBtn.style.color = '';
            setInterviewBtn.style.borderColor = '';
        }
    }
    
    // Update select all button text based on current state
    const selectAllBtn = document.querySelector('button[onclick="selectAllApplicantsInterview()"]');
    if (selectAllBtn) {
        if (count === total && total > 0) {
            selectAllBtn.innerHTML = '<i class="fas fa-square"></i> Unselect All';
        } else {
            selectAllBtn.innerHTML = '<i class="fas fa-check-square"></i> Select All';
        }
    }
}

function selectAllApplicantsInterview() {
    const checkboxes = document.querySelectorAll('.interview-applicant-checkbox');
    const checkedCount = document.querySelectorAll('.interview-applicant-checkbox:checked').length;
    const shouldSelectAll = checkedCount < checkboxes.length;
    
    checkboxes.forEach(cb => { 
        cb.checked = shouldSelectAll; 
    });
    
    // Update button text
    const selectAllBtn = document.querySelector('button[onclick="selectAllApplicantsInterview()"]');
    if (selectAllBtn) {
        if (shouldSelectAll) {
            selectAllBtn.innerHTML = '<i class="fas fa-check-square"></i> Select All';
        } else {
            selectAllBtn.innerHTML = '<i class="fas fa-square"></i> Unselect All';
        }
    }
    
    updateSelectedCountInterview();
}

// Function to update range from number input for interview
function updateRangeFromInputInterview() {
    const rangeInput = document.getElementById('rangeInputInterview');
    if (rangeInput) {
        // Get the raw input value and clean it
        let inputValue = rangeInput.value.trim();
        
        // If input is empty or just whitespace, treat as 0
        if (inputValue === '' || inputValue === '0') {
            inputValue = 0;
        } else {
            inputValue = parseInt(inputValue) || 0;
        }
        
        const unscheduledCount = document.querySelectorAll('.interview-applicant-checkbox').length;
        
        console.log('Interview raw input value:', rangeInput.value);
        console.log('Interview processed input value:', inputValue);
        console.log('Interview unscheduled applicants count:', unscheduledCount);
        
        // Validate input against unscheduled applicants
        if (inputValue > unscheduledCount && unscheduledCount > 0) {
            showToast(`Input exceeds unscheduled applicants. Available: ${unscheduledCount}`, 'error', 3000);
            // Don't change the input field value, just limit the selection
            currentRangeInterview = unscheduledCount;
        } else if (inputValue < 0) {
            showToast('Input cannot be negative', 'error', 3000);
            rangeInput.value = 0;
            currentRangeInterview = 0;
        } else {
            currentRangeInterview = inputValue;
        }
        
        console.log('Interview range updated from input:', currentRangeInterview);
        selectCurrentRangeInterview();
    }
}

function selectCurrentRangeInterview() {
    const allCheckboxes = document.querySelectorAll('.interview-applicant-checkbox');
    allCheckboxes.forEach(cb => { cb.checked = false; });
    const availableCheckboxes = Array.from(allCheckboxes);
    const toSelect = Math.min(currentRangeInterview, availableCheckboxes.length);
    for (let i = 0; i < toSelect; i++) {
        if (availableCheckboxes[i]) availableCheckboxes[i].checked = true;
    }
    
    updateSelectedCountInterview();
}

// Function to handle interview range input focus and typing
function handleRangeInputFocusInterview() {
    const rangeInput = document.getElementById('rangeInputInterview');
    if (rangeInput) {
        // Clear the input when user focuses and it contains only "0"
        if (rangeInput.value === '0') {
            rangeInput.value = '';
        }
    }
}

// Function to handle interview range input blur
function handleRangeInputBlurInterview() {
    const rangeInput = document.getElementById('rangeInputInterview');
    if (rangeInput) {
        // If input is empty on blur, set it to 0
        if (rangeInput.value.trim() === '') {
            rangeInput.value = '0';
            currentRangeInterview = 0;
            selectCurrentRangeInterview();
        }
    }
}

function setInterviewSchedule() {
    console.log('setInterviewSchedule called');
    const selectedCheckboxes = document.querySelectorAll('.interview-applicant-checkbox:checked');
    console.log('Selected checkboxes:', selectedCheckboxes.length);
    
    if (selectedCheckboxes.length === 0) {
        showToast('Please select at least one applicant.', 'error', 2000);
        return;
    }
    
    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    console.log('Selected IDs:', selectedIds);
    
    // Check if modal element exists
    const modalElement = document.getElementById('setInterviewScheduleModal');
    console.log('Modal element:', modalElement);
    
    if (!modalElement) {
        console.error('Modal element not found!');
        showToast('Modal not found. Please refresh the page.', 'error', 5000);
        return;
    }
    
    // Show assign to schedule modal
    const interviewModal = new bootstrap.Modal(modalElement);
    document.getElementById('selectedInterviewApplicantIds').value = selectedIds.join(',');
    console.log('Showing modal...');
    interviewModal.show();
}

function proceedWithInterviewSchedule() {
    // Submit the form
    document.getElementById('interviewScheduleForm').submit();
}

// Function to show interview schedule confirmation
function showInterviewScheduleConfirmation() {
    const date = document.getElementById('modal_interview_date').value;
    const start = document.getElementById('modal_interview_start_time').value;
    const end = document.getElementById('modal_interview_end_time').value;
    const venue = document.getElementById('modal_interview_venue').value;
    const selectedIds = document.getElementById('selectedInterviewApplicantIds').value;
    
    // Validate form
    if (!date || !start || !end || !venue) {
        showToast('Please fill in all required fields.', 'error', 2000);
        return;
    }
    
    if (!selectedIds) {
        showToast('Please select at least one applicant.', 'error', 2000);
        return;
    }
    
    // Get selected applicant names
    const selectedCheckboxes = document.querySelectorAll('.interview-applicant-checkbox:checked');
    const selectedNames = Array.from(selectedCheckboxes).map(cb => {
        const row = cb.closest('tr');
        return row.querySelector('td:nth-child(2) strong').textContent;
    });
    
    // Format date and time for display
    const eventDate = new Date(date).toLocaleDateString('en-US', { 
        year: 'numeric', month: 'short', day: 'numeric' 
    });
    
    const startTime = new Date('1970-01-01T' + start);
    const startTimeStr = startTime.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
    
    const endTime = new Date('1970-01-01T' + end);
    const endTimeStr = endTime.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
    
    // Populate confirmation modal
    document.getElementById('interviewScheduleDetails').innerHTML = `
        <div class="row">
            <div class="col-12">
                <strong>Interview Date:</strong> ${eventDate}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Time:</strong> ${startTimeStr} - ${endTimeStr}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Venue:</strong> ${venue}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Selected Applicants:</strong> ${selectedNames.length} applicants
            </div>
        </div>
    `;
    
    // Hide interview schedule modal and show confirmation
    const interviewModal = bootstrap.Modal.getInstance(document.getElementById('setInterviewScheduleModal'));
    interviewModal.hide();
    
    const confirmModal = new bootstrap.Modal(document.getElementById('interviewScheduleConfirmModal'));
    confirmModal.show();
}

function viewInterviewSchedules() {
    const modal = new bootstrap.Modal(document.getElementById('interviewSchedulesModal'));
    
    // Get all schedules from PHP data (including assigned ones for viewing)
    const allSchedules = <?= json_encode($interview_schedules ?? []) ?>;
    
    let scheduleRows = '';
    if (allSchedules.length > 0) {
        // Since schedules are now already grouped (one schedule per group), we can use them directly
        const groupedSchedules = allSchedules.map(schedule => ({
            id: schedule.id,
            event_date: schedule.event_date,
            event_time: schedule.event_time,
            end_time: schedule.end_time,
            venue: schedule.venue,
            applicant_count: schedule.applicant_count !== undefined ? schedule.applicant_count : 0
        }));
        
        console.log('Grouped interview schedules:', groupedSchedules);
        console.log('First interview schedule applicant_count:', groupedSchedules[0]?.applicant_count, 'Type:', typeof groupedSchedules[0]?.applicant_count);
        
        // Display schedules directly since they're already grouped
        groupedSchedules.forEach((schedule, index) => {
            console.log(`Interview Schedule ${index}:`, schedule);
            console.log(`Interview Schedule ${index} applicant_count:`, schedule.applicant_count, 'Type:', typeof schedule.applicant_count);
            const eventDate = new Date(schedule.event_date).toLocaleDateString('en-US', { 
                year: 'numeric', month: 'short', day: 'numeric' 
            });
            
            const startTime = new Date('1970-01-01T' + schedule.event_time);
            
            const startTimeStr = startTime.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
            
            const buttonText = 'View Student List (' + schedule.applicant_count + ')';
            scheduleRows += `
                <tr style="border-bottom: 1px solid #f1f3f4;">
                    <td style="border: none; padding: 12px 8px; color: #495057;">${eventDate}</td>
                    <td style="border: none; padding: 12px 8px; color: #495057; font-weight: 500;">${startTimeStr}</td>
                    <td style="border: none; padding: 12px 8px; color: #495057;">${schedule.venue}</td>
                    <td class="text-center" style="border: none; padding: 12px 8px;">
                        <button type="button" class="btn btn-sm" onclick="viewStudentsForInterviewSchedule(${schedule.id}, 'Interview Schedule', '${eventDate}', '${startTimeStr}', '${schedule.venue}')" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42); border-radius: 4px; padding: 6px 12px; font-size: 12px;">
                            <i class="fas fa-users"></i> ${buttonText}
                        </button>
                    </td>
                </tr>
            `;
        });
    } else {
        // Update schedule info to show no schedules message
        document.getElementById('scheduleInfo').innerHTML = '<strong>No Interview Schedules Found</strong>';
        scheduleRows = `
            <tr>
                <td colspan="4" class="text-center text-muted">
                    <i class="fas fa-calendar-times fa-2x mb-2"></i><br>
                    No interview schedules found
                </td>
            </tr>
        `;
    }
    
    document.getElementById('interviewSchedulesContent').innerHTML = `
        <div class="mb-4">
            <div class="table-responsive">
                <table class="table table-sm" style="border: none; box-shadow: none;">
                    <thead style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                        <tr>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Date</th>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Time</th>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Venue</th>
                            <th class="text-center" style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${scheduleRows}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    modal.show();
}

// Function to view students for a specific interview schedule
function viewStudentsForInterviewSchedule(scheduleId, eventName, eventDate, eventTime, venue) {
    const modal = new bootstrap.Modal(document.getElementById('viewStudentListModal'));
    
    // Clear any previous data and content immediately to prevent showing cached data
    window.currentStudentData = null;
    window.currentScheduleInfo = null;
    
    // Clear the student list content immediately
    document.getElementById('studentListContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading students...</p>
        </div>
    `;
    
    // Hide export button initially
    document.getElementById('exportStudentListBtn').style.display = 'none';
    
    // Reset the back button
    document.querySelector('.modal-footer .btn-secondary').innerHTML = '<i class="fas fa-times"></i> Close';
    document.querySelector('.modal-footer .btn-secondary').setAttribute('onclick', 'closeStudentListModal()');
    
    // Show the schedule info container and populate it
    document.getElementById('scheduleInfoContainer').style.display = 'block';
    document.getElementById('scheduleInfo').innerHTML = `
        <strong>${eventName}</strong><br>
        <small class="text-muted">${eventDate} at ${eventTime}</small><br>
        <small class="text-muted"><i class="fas fa-map-marker-alt"></i> ${venue}</small>
    `;
    
    // Ensure content persists after modal is shown
    setTimeout(() => {
        document.getElementById('scheduleInfoContainer').style.display = 'block';
        document.getElementById('scheduleInfo').innerHTML = `
            <strong>${eventName}</strong><br>
            <small class="text-muted">${eventDate} at ${eventTime}</small><br>
            <small class="text-muted"><i class="fas fa-map-marker-alt"></i> ${venue}</small>
        `;
    }, 100);
    
    console.log('Interview Schedule ID:', scheduleId);
    
    modal.show();
    
    // Fetch students for this specific interview schedule via AJAX with cache-busting
    fetch(`scheduling.php?action=get_interview_schedule_students&schedule_id=${scheduleId}&t=${Date.now()}`)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response is not JSON');
            }
            
            return response.json();
        })
        .then(data => {
            if (data.error) {
                document.getElementById('studentListContent').innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h5 class="text-muted">Error</h5>
                        <p>${data.error}</p>
                    </div>
                `;
                return;
            }
            
            const scheduledApplicants = data;
            
            console.log('Scheduled interview applicants found:', scheduledApplicants);
            
            if (scheduledApplicants.length > 0) {
                let studentRows = '';
                scheduledApplicants.forEach((applicant, index) => {
                    // Format name properly
                    const fullName = `${applicant.last_name}, ${applicant.first_name} ${applicant.middle_name || ''}`.trim();
                    const formattedName = fullName.split(' ').map(word => 
                        word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
                    ).join(' ');
                    
                    studentRows += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><strong>${formattedName}</strong></td>
                            <td class="text-center">${applicant.program || 'N/A'}</td>
                            <td class="text-center"><span class="badge" style="background-color: rgb(0, 105, 42);">Scheduled</span></td>
                        </tr>
                    `;
                });
                
                document.getElementById('studentListContent').innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-hover" id="studentListTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th class="text-center">Program</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${studentRows}
                            </tbody>
                        </table>
                    </div>
                `;
                
                // Show export button and store data for export
                document.getElementById('exportStudentListBtn').style.display = 'inline-block';
                window.currentStudentData = scheduledApplicants;
                window.currentScheduleInfo = {
                    eventName: eventName,
                    eventDate: eventDate,
                    eventTime: eventTime,
                    venue: venue
                };
                
                // Update back button to return to interview schedules
                document.querySelector('.modal-footer .btn-secondary').innerHTML = '<i class="fas fa-arrow-left"></i> Back to Interview Schedules';
                document.querySelector('.modal-footer .btn-secondary').setAttribute('onclick', 'backToInterviewSchedules()');
                
            } else {
                document.getElementById('studentListContent').innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <h5 class="text-muted">No Students Found</h5>
                        <p>No students are scheduled for this interview.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error fetching interview schedule students:', error);
            document.getElementById('studentListContent').innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h5 class="text-muted">Error</h5>
                    <p>Failed to load student data. Please try again.</p>
                </div>
            `;
        });
}

// Function to go back to interview schedules table view
function backToInterviewSchedules() {
    // Close the student list modal
    const studentListModal = bootstrap.Modal.getInstance(document.getElementById('viewStudentListModal'));
    if (studentListModal) {
        studentListModal.hide();
    }
    
    // Open the interview schedules modal
    const interviewSchedulesModal = new bootstrap.Modal(document.getElementById('interviewSchedulesModal'));
    
    // Get all schedules from PHP data and display the table (including assigned ones)
    const allSchedules = <?= json_encode($interview_schedules ?? []) ?>;
    
    let scheduleRows = '';
    if (allSchedules.length > 0) {
        allSchedules.forEach(schedule => {
            const eventDate = new Date(schedule.event_date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit'
            });
            
            const startTime = new Date(`2000-01-01T${schedule.event_time}`);
            const startTimeStr = startTime.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            const buttonText = 'View Student List (' + schedule.applicant_count + ')';
            scheduleRows += `
                <tr style="border-bottom: 1px solid #f1f3f4;">
                    <td style="border: none; padding: 12px 8px; color: #495057;">${eventDate}</td>
                    <td style="border: none; padding: 12px 8px; color: #495057; font-weight: 500;">${startTimeStr}</td>
                    <td style="border: none; padding: 12px 8px; color: #495057;">${schedule.venue}</td>
                    <td class="text-center" style="border: none; padding: 12px 8px;">
                        <button type="button" class="btn btn-sm" onclick="viewStudentsForInterviewSchedule(${schedule.id}, 'Interview Schedule', '${eventDate}', '${startTimeStr}', '${schedule.venue}')" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42); border-radius: 4px; padding: 6px 12px; font-size: 12px;">
                            <i class="fas fa-users"></i> ${buttonText}
                        </button>
                    </td>
                </tr>
            `;
        });
    } else {
        scheduleRows = `
            <tr>
                <td colspan="4" class="text-center text-muted">
                    <i class="fas fa-calendar-times fa-2x mb-2"></i><br>
                    No interview schedules found
                </td>
            </tr>
        `;
    }
    
    document.getElementById('interviewSchedulesContent').innerHTML = `
        <div class="mb-4">
            <div class="table-responsive">
                <table class="table table-sm" style="border: none; box-shadow: none;">
                    <thead style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                        <tr>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Date</th>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Time</th>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Venue</th>
                            <th class="text-center" style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${scheduleRows}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    interviewSchedulesModal.show();
}

// Function to create new interview schedule
function createNewInterviewSchedule() {
    const modal = new bootstrap.Modal(document.getElementById('createInterviewScheduleModal'));
    modal.show();
}

// Function to show create interview schedule confirmation
function showCreateInterviewScheduleConfirmation() {
    const date = document.getElementById('create_interview_date').value;
    const start = document.getElementById('create_interview_start_time').value;
    const venueCheckboxes = document.querySelectorAll('input[name="interview_venues[]"]:checked');
    
    // Validate form
    if (!date || !start) {
        showToast('Please fill in all required fields.', 'error', 2000);
        return;
    }
    
    if (venueCheckboxes.length === 0) {
        showToast('Please select at least one venue.', 'error', 2000);
        return;
    }
    
    // Get selected venues
    const selectedVenues = Array.from(venueCheckboxes).map(cb => cb.value);
    
    // Format date and time for display
    const eventDate = new Date(date).toLocaleDateString('en-US', { 
        year: 'numeric', month: 'short', day: 'numeric' 
    });
    
    const startTime = new Date('1970-01-01T' + start);
    const startTimeStr = startTime.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
    
    // Populate confirmation modal
    document.getElementById('createInterviewScheduleDetails').innerHTML = `
        <div class="row">
            <div class="col-12">
                <strong>Interview Date:</strong> ${eventDate}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Start Time:</strong> ${startTimeStr}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Selected Venues:</strong> ${selectedVenues.length} venue(s)
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <small class="text-muted">${selectedVenues.join(', ')}</small>
            </div>
        </div>
    `;
    
    // Hide create schedule modal and show confirmation
    const createModal = bootstrap.Modal.getInstance(document.getElementById('createInterviewScheduleModal'));
    createModal.hide();
    
    const confirmModal = new bootstrap.Modal(document.getElementById('createInterviewScheduleConfirmModal'));
    confirmModal.show();
}

// Function to proceed with create interview schedule
function proceedWithCreateInterviewSchedule() {
    // Submit the form
    document.getElementById('createInterviewScheduleForm').submit();
}

// Function to assign applicants to interview schedule
function assignToInterviewSchedule() {
    const selectedCheckboxes = document.querySelectorAll('.interview-applicant-checkbox:checked');
    if (selectedCheckboxes.length === 0) {
        showToast('Please select at least one applicant.', 'error', 2000);
        return;
    }
    
    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    const modalElement = document.getElementById('assignToInterviewScheduleModal');
    
    if (!modalElement) {
        showToast('Modal not found. Please refresh the page.', 'error', 5000);
        return;
    }
    
    const modal = new bootstrap.Modal(modalElement);
    document.getElementById('selectedInterviewApplicantIds').value = selectedIds.join(',');
    modal.show();
}

// Function to show assign interview schedule confirmation
function showAssignInterviewScheduleConfirmation() {
    const scheduleSelect = document.getElementById('modal_interview_schedule_select');
    const selectedIds = document.getElementById('selectedInterviewApplicantIds').value;
    
    // Validate form
    if (!scheduleSelect.value) {
        showToast('Please select a schedule.', 'error', 2000);
        return;
    }
    
    // Get selected schedule details
    const selectedOption = scheduleSelect.options[scheduleSelect.selectedIndex];
    const scheduleText = selectedOption.text;
    
    // Get selected applicant names
    const selectedCheckboxes = document.querySelectorAll('.interview-applicant-checkbox:checked');
    const selectedNames = Array.from(selectedCheckboxes).map(cb => {
        const row = cb.closest('tr');
        return row.querySelector('td:nth-child(2) strong').textContent;
    });
    
    // Populate confirmation modal
    document.getElementById('assignInterviewScheduleDetails').innerHTML = `
        <div class="row">
            <div class="col-12">
                <strong>Selected Schedule:</strong> ${scheduleText}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Selected Applicants:</strong> ${selectedNames.length} applicants
            </div>
        </div>
    `;
    
    // Hide assign schedule modal and show confirmation
    const assignModal = bootstrap.Modal.getInstance(document.getElementById('setInterviewScheduleModal'));
    assignModal.hide();
    
    const confirmModal = new bootstrap.Modal(document.getElementById('assignInterviewScheduleConfirmModal'));
    confirmModal.show();
}

// Function to proceed with assign interview schedule
function proceedWithAssignInterviewSchedule() {
    // Submit the form
    document.getElementById('interviewScheduleForm').submit();
}

// Function to view student list from selected interview applicants
function viewStudentListFromInterviewSelection() {
    const selectedCheckboxes = document.querySelectorAll('.interview-applicant-checkbox:checked');
    const selectedNames = Array.from(selectedCheckboxes).map(cb => {
        const row = cb.closest('tr');
        return row.querySelector('td:nth-child(2) strong').textContent;
    });
    
    const modal = new bootstrap.Modal(document.getElementById('viewStudentListModal'));
    
    // Clear any previous data
    window.currentStudentData = null;
    window.currentScheduleInfo = null;
    
    // Clear the student list content
    document.getElementById('studentListContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading selected applicants...</p>
        </div>
    `;
    
    // Hide export button initially
    document.getElementById('exportStudentListBtn').style.display = 'none';
    
    // Reset the back button
    setBackButtonForCurrentSection();
    
    // Show the schedule info container
    document.getElementById('scheduleInfoContainer').style.display = 'block';
    document.getElementById('scheduleInfo').innerHTML = `
        <strong>Selected Interview Applicants</strong><br>
        <small class="text-muted">${selectedNames.length} applicants selected</small>
    `;
    
    modal.show();
    
    // Display selected applicants
    if (selectedNames.length > 0) {
        let studentRows = '';
        selectedNames.forEach((name, index) => {
            studentRows += `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${name}</strong></td>
                    <td class="text-center">N/A</td>
                    <td class="text-center"><span class="badge bg-warning">Selected</span></td>
                </tr>
            `;
        });
        
        document.getElementById('studentListContent').innerHTML = `
            <div class="table-responsive">
                <table class="table table-hover" id="studentListTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th class="text-center">Program</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${studentRows}
                    </tbody>
                </table>
            </div>
        `;
        
        // Store data for potential export
        window.currentStudentData = selectedNames.map((name, index) => ({
            last_name: name.split(',')[0] || '',
            first_name: name.split(',')[1]?.trim().split(' ')[0] || '',
            middle_name: name.split(',')[1]?.trim().split(' ').slice(1).join(' ') || '',
            program: 'N/A'
        }));
        
        window.currentScheduleInfo = {
            eventName: 'Selected Interview Applicants',
            eventDate: 'N/A',
            eventTime: 'N/A',
            venue: 'N/A'
        };
        
        // Show export button
        document.getElementById('exportStudentListBtn').style.display = 'inline-block';
        
    } else {
        document.getElementById('studentListContent').innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-users fa-3x mb-3"></i>
                <h5 class="text-muted">No Applicants Selected</h5>
                <p>Please select applicants to view their details.</p>
            </div>
        `;
    }
}

// Function to update unscheduled count display for interview
function updateUnscheduledCountInterview() {
    const unscheduledCount = document.querySelectorAll('.interview-applicant-checkbox:not([disabled])').length;
    
    const unscheduledCountEl = document.getElementById('unscheduledCountInterview');
    
    if (unscheduledCountEl) {
        unscheduledCountEl.textContent = unscheduledCount;
    }
}

// Function to show success toast for interview
function showSuccessToastInterview(message) {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1060';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toastId = 'exportToastInterview_' + Date.now();
    const toastElement = document.createElement('div');
    toastElement.id = toastId;
    toastElement.className = 'toast align-items-center text-bg-success border-0';
    toastElement.setAttribute('role', 'alert');
    toastElement.setAttribute('aria-live', 'assertive');
    toastElement.setAttribute('aria-atomic', 'true');
    toastElement.setAttribute('data-bs-delay', '3000');
    
    toastElement.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-check-circle me-2"></i>${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toastElement);
    
    // Show the toast
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Remove the toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        if (toastElement.parentNode) {
            toastElement.parentNode.removeChild(toastElement);
        }
    });
}

// Function to close student list modal
function closeStudentListModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('viewStudentListModal'));
    if (modal) {
        modal.hide();
    }
}

// Function to toggle select all
function toggleSelectAll() {
    console.log('toggleSelectAll called');
    const checkboxes = document.querySelectorAll('.applicant-checkbox:not([disabled])');
    const checkedCount = document.querySelectorAll('.applicant-checkbox:checked').length;
    const shouldSelectAll = checkedCount < checkboxes.length;
    
    console.log('Current checked count:', checkedCount, 'Total checkboxes:', checkboxes.length, 'Should select all:', shouldSelectAll);
    
    checkboxes.forEach(checkbox => {
        if (checkbox) {
            checkbox.checked = shouldSelectAll;
        }
    });
    
    updateSelectedCount();
    console.log('Toggle complete - all selected:', shouldSelectAll);
}

// Function to toggle select all applicants
function selectAllApplicants() {
    console.log('selectAllApplicants called');
    const checkboxes = document.querySelectorAll('.applicant-checkbox:not([disabled])');
    const checkedCount = document.querySelectorAll('.applicant-checkbox:checked').length;
    const shouldSelectAll = checkedCount < checkboxes.length;
    
    console.log('Current checked count:', checkedCount, 'Total checkboxes:', checkboxes.length, 'Should select all:', shouldSelectAll);
    
    checkboxes.forEach(checkbox => {
        if (checkbox) {
            checkbox.checked = shouldSelectAll;
        }
    });
    
    // Update button text
    const selectAllBtn = document.querySelector('button[onclick="selectAllApplicants()"]');
    if (selectAllBtn) {
        if (shouldSelectAll) {
            selectAllBtn.innerHTML = '<i class="fas fa-check-square"></i> Select All';
        } else {
            selectAllBtn.innerHTML = '<i class="fas fa-square"></i> Unselect All';
        }
    }
    
    updateSelectedCount();
    console.log('Toggle complete - all selected:', shouldSelectAll);
}

// Current range value
let currentRange = 0;

// Initialize range display on page load
document.addEventListener('DOMContentLoaded', function() {
    const availableCount = document.querySelectorAll('.applicant-checkbox:not([disabled])').length;
    console.log('Available checkboxes on load:', availableCount);
    
    // Always reset to 0 on page load/refresh
    currentRange = 0;
    const rangeInput = document.getElementById('rangeInput');
    if (rangeInput) {
        rangeInput.value = currentRange;
    }
    console.log('Range initialized to:', currentRange);
    
    // Automatically select the initial range (0 = no selection)
    selectCurrentRange();
    updateUnscheduledCount();
});

// Function to update range from number input
function updateRangeFromInput() {
    const rangeInput = document.getElementById('rangeInput');
    if (rangeInput) {
        // Get the raw input value and clean it
        let inputValue = rangeInput.value.trim();
        
        // If input is empty or just whitespace, treat as 0
        if (inputValue === '' || inputValue === '0') {
            inputValue = 0;
        } else {
            inputValue = parseInt(inputValue) || 0;
        }
        
        const unscheduledCount = document.querySelectorAll('.applicant-checkbox:not([disabled])').length;
        
        console.log('=== RANGE INPUT DEBUG ===');
        console.log('Raw input value:', rangeInput.value);
        console.log('Processed input value:', inputValue);
        console.log('Unscheduled applicants count:', unscheduledCount);
        console.log('All checkboxes:', document.querySelectorAll('.applicant-checkbox').length);
        console.log('Disabled checkboxes (scheduled):', document.querySelectorAll('.applicant-checkbox[disabled]').length);
        
        // Validate input against unscheduled applicants
        if (inputValue > unscheduledCount && unscheduledCount > 0) {
            showToast(`Input exceeds unscheduled applicants. Available: ${unscheduledCount}`, 'error', 3000);
            // Don't change the input field value, just limit the selection
            currentRange = unscheduledCount;
        } else if (inputValue < 0) {
            showToast('Input cannot be negative', 'error', 3000);
            rangeInput.value = 0;
            currentRange = 0;
        } else {
            currentRange = inputValue;
        }
        
        console.log('Final currentRange:', currentRange);
        console.log('=== END RANGE INPUT DEBUG ===');
        selectCurrentRange();
    }
}

// Function to handle range input focus and typing
function handleRangeInputFocus() {
    const rangeInput = document.getElementById('rangeInput');
    if (rangeInput) {
        // Clear the input when user focuses and it contains only "0"
        if (rangeInput.value === '0') {
            rangeInput.value = '';
        }
    }
}

// Function to handle range input blur
function handleRangeInputBlur() {
    const rangeInput = document.getElementById('rangeInput');
    if (rangeInput) {
        // If input is empty on blur, set it to 0
        if (rangeInput.value.trim() === '') {
            rangeInput.value = '0';
            currentRange = 0;
            selectCurrentRange();
        }
    }
}

// Function to select the current range
function selectCurrentRange() {
    console.log('selectCurrentRange called with range:', currentRange);
    
    // First, uncheck all
    const allCheckboxes = document.querySelectorAll('.applicant-checkbox:not([disabled])');
    console.log('Found checkboxes:', allCheckboxes.length);
    allCheckboxes.forEach(checkbox => {
        if (checkbox) {
            checkbox.checked = false;
        }
    });
    
    // Then select the first N available applicants
    const availableCheckboxes = Array.from(document.querySelectorAll('.applicant-checkbox:not([disabled])'));
    const toSelect = Math.min(currentRange, availableCheckboxes.length);
    console.log('Will select:', toSelect, 'checkboxes');
    
    for (let i = 0; i < toSelect; i++) {
        if (availableCheckboxes[i]) {
            availableCheckboxes[i].checked = true;
            console.log('Checked checkbox', i);
        }
    }
    
    // Update select all checkbox state
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = (toSelect === availableCheckboxes.length);
    }
    
    updateSelectedCount();
    updateUnscheduledCount();
    console.log('Selection complete');
}

// Function to create new schedule
function createNewSchedule() {
    const modal = new bootstrap.Modal(document.getElementById('createScheduleModal'));
    modal.show();
}

// Function to show create schedule confirmation
function showCreateScheduleConfirmation() {
    // Get form data
    const scheduleDate = document.getElementById('create_schedule_date').value;
    const startTime = document.getElementById('create_schedule_start_time').value;
    const venueCheckboxes = document.querySelectorAll('input[name="schedule_venues[]"]:checked');
    
    // Validate form
    if (!scheduleDate || !startTime || venueCheckboxes.length === 0) {
        showToast('Please fill in all required fields and select at least one venue.', 'error', 2000);
        return;
    }
    
    // Format date
    const formattedDate = new Date(scheduleDate).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric'
    });
    
    // Format time
    const startTimeFormatted = new Date('1970-01-01T' + startTime).toLocaleTimeString('en-US', {
        hour: 'numeric', minute: '2-digit', hour12: true
    });
    
    // Get selected venues
    const selectedVenues = Array.from(venueCheckboxes).map(cb => cb.value);
    
    // Populate confirmation modal
    document.getElementById('createScheduleDetails').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <strong>Date:</strong> ${formattedDate}
            </div>
            <div class="col-md-6">
                <strong>Time:</strong> ${startTimeFormatted}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Selected Venues (${selectedVenues.length}):</strong><br>
                ${selectedVenues.map(venue => `• ${venue}`).join('<br>')}
            </div>
        </div>
    `;
    
    // Hide create schedule modal and show confirmation
    const createModal = bootstrap.Modal.getInstance(document.getElementById('createScheduleModal'));
    createModal.hide();
    
    const confirmModal = new bootstrap.Modal(document.getElementById('createScheduleConfirmModal'));
    confirmModal.show();
}

// Function to proceed with create schedule
function proceedWithCreateSchedule() {
    // Submit the form
    document.getElementById('createScheduleForm').submit();
}

// Function to set exam schedule for selected applicants
function setExamSchedule() {
    console.log('setExamSchedule called');
    const selectedCheckboxes = document.querySelectorAll('.applicant-checkbox:checked');
    console.log('Selected checkboxes:', selectedCheckboxes.length);
    
    if (selectedCheckboxes.length === 0) {
        showToast('Please select at least one applicant.', 'error', 2000);
        return;
    }
    
    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    console.log('Selected IDs:', selectedIds);
    
    // Check if modal element exists
    const modalElement = document.getElementById('setExamScheduleModal');
    console.log('Modal element:', modalElement);
    
    if (!modalElement) {
        console.error('Modal element not found!');
        showToast('Modal not found. Please refresh the page.', 'error', 5000);
        return;
    }
    
    // Show assign to schedule modal
    const examModal = new bootstrap.Modal(modalElement);
    document.getElementById('selectedApplicantIds').value = selectedIds.join(',');
    console.log('Showing modal...');
    examModal.show();
}

// Function to show assign schedule confirmation
function showAssignScheduleConfirmation() {
    const scheduleSelect = document.getElementById('modal_schedule_select');
    const selectedIds = document.getElementById('selectedApplicantIds').value;
    
    // Validate form
    if (!scheduleSelect.value) {
        showToast('Please select a schedule.', 'error', 2000);
        return;
    }
    
    // Get selected schedule details
    const selectedOption = scheduleSelect.options[scheduleSelect.selectedIndex];
    const scheduleText = selectedOption.text;
    
    // Get selected applicant names
    const selectedCheckboxes = document.querySelectorAll('.applicant-checkbox:checked');
    const selectedNames = Array.from(selectedCheckboxes).map(cb => {
        const row = cb.closest('tr');
        return row.querySelector('td:nth-child(2) strong').textContent;
    });
    
    // Populate confirmation modal
    document.getElementById('examScheduleDetails').innerHTML = `
        <div class="row">
            <div class="col-12">
                <strong>Selected Schedule:</strong> ${scheduleText}
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Selected Applicants:</strong> ${selectedNames.length} applicants
            </div>
        </div>
    `;
    
    // Hide assign schedule modal and show confirmation
    const assignModal = bootstrap.Modal.getInstance(document.getElementById('setExamScheduleModal'));
    assignModal.hide();
    
    const confirmModal = new bootstrap.Modal(document.getElementById('examScheduleConfirmModal'));
    confirmModal.show();
}

// Function to show exam schedule confirmation (legacy - kept for compatibility)
function showExamScheduleConfirmation() {
    // This function is now replaced by showAssignScheduleConfirmation
    showAssignScheduleConfirmation();
}

// Function to proceed with exam schedule creation
function proceedWithExamSchedule() {
    // Submit the form
    document.getElementById('examScheduleForm').submit();
}

// Function to get current section (exam or interview)
function getCurrentSection() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('sub') || 'exam';
}

// Function to set appropriate back button based on current section
function setBackButtonForCurrentSection() {
    const currentSection = getCurrentSection();
    const backBtn = document.querySelector('.modal-footer .btn-secondary');
    
    if (backBtn) {
        if (currentSection === 'interview') {
            backBtn.innerHTML = '<i class="fas fa-arrow-left"></i> Back to Interview Schedules';
            backBtn.setAttribute('onclick', 'backToInterviewSchedules()');
        } else {
            backBtn.innerHTML = '<i class="fas fa-arrow-left"></i> Back to Exam Schedules';
            backBtn.setAttribute('onclick', 'backToExamSchedules()');
        }
    }
}

// Function to view student list from selected applicants
function viewStudentListFromSelection() {
    const selectedCheckboxes = document.querySelectorAll('.applicant-checkbox:checked');
    const selectedNames = Array.from(selectedCheckboxes).map(cb => {
        const row = cb.closest('tr');
        return row.querySelector('td:nth-child(2) strong').textContent;
    });
    
    const modal = new bootstrap.Modal(document.getElementById('viewStudentListModal'));
    
    // Clear any previous data
    window.currentStudentData = null;
    window.currentScheduleInfo = null;
    
    // Clear the student list content
    document.getElementById('studentListContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading selected applicants...</p>
        </div>
    `;
    
    // Hide export button initially
    document.getElementById('exportStudentListBtn').style.display = 'none';
    
    // Reset the back button
    setBackButtonForCurrentSection();
    
    // Show the schedule info container
    document.getElementById('scheduleInfoContainer').style.display = 'block';
    document.getElementById('scheduleInfo').innerHTML = `
        <strong>Selected Exam Applicants</strong><br>
        <small class="text-muted">${selectedNames.length} applicants selected</small>
    `;
    
    modal.show();
    
    // Display selected applicants
    if (selectedNames.length > 0) {
        let studentRows = '';
        selectedNames.forEach((name, index) => {
            studentRows += `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${name}</strong></td>
                    <td class="text-center">N/A</td>
                    <td class="text-center"><span class="badge bg-warning">Selected</span></td>
                </tr>
            `;
        });
        
        document.getElementById('studentListContent').innerHTML = `
            <div class="table-responsive">
                <table class="table table-hover" id="studentListTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th class="text-center">Program</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${studentRows}
                    </tbody>
                </table>
            </div>
        `;
        
        // Store data for potential export
        window.currentStudentData = selectedNames.map((name, index) => ({
            last_name: name.split(',')[0] || '',
            first_name: name.split(',')[1]?.trim().split(' ')[0] || '',
            middle_name: name.split(',')[1]?.trim().split(' ').slice(1).join(' ') || '',
            program: 'N/A'
        }));
        
        window.currentScheduleInfo = {
            eventName: 'Selected Exam Applicants',
            eventDate: 'N/A',
            eventTime: 'N/A',
            venue: 'N/A'
        };
        
        // Show export button
        document.getElementById('exportStudentListBtn').style.display = 'inline-block';
        
    } else {
        document.getElementById('studentListContent').innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-users fa-3x mb-3"></i>
                <h5 class="text-muted">No Applicants Selected</h5>
                <p>Please select applicants to view their details.</p>
            </div>
        `;
    }
}

// Function to view exam schedules
function viewExamSchedules() {
    const modal = new bootstrap.Modal(document.getElementById('examSchedulesModal'));
    
    // Get all schedules from PHP data (including assigned ones for viewing)
    const allSchedules = <?= json_encode($all_schedules_for_viewing) ?>;
    
    let scheduleRows = '';
    if (allSchedules.length > 0) {
        // Since schedules are now already grouped (one schedule per group), we can use them directly
        const groupedSchedules = allSchedules.map(schedule => ({
            id: schedule.id,
            event_date: schedule.event_date,
            event_time: schedule.event_time,
            end_time: schedule.end_time,
            venue: schedule.venue,
            applicant_count: schedule.applicant_count !== undefined ? schedule.applicant_count : 0
        }));
        
        console.log('Grouped exam schedules:', groupedSchedules);
        console.log('First schedule applicant_count:', groupedSchedules[0]?.applicant_count, 'Type:', typeof groupedSchedules[0]?.applicant_count);
        
        // Display schedules directly since they're already grouped
        groupedSchedules.forEach((schedule, index) => {
            console.log(`Exam Schedule ${index}:`, schedule);
            console.log(`Exam Schedule ${index} applicant_count:`, schedule.applicant_count, 'Type:', typeof schedule.applicant_count);
            const eventDate = new Date(schedule.event_date).toLocaleDateString('en-US', { 
                year: 'numeric', month: 'short', day: 'numeric' 
            });
            
            const startTime = new Date('1970-01-01T' + schedule.event_time);
            
            const startTimeStr = startTime.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
            
            scheduleRows += `
                <tr style="border-bottom: 1px solid #f1f3f4;">
                    <td style="border: none; padding: 12px 8px; color: #495057;">${eventDate}</td>
                    <td style="border: none; padding: 12px 8px; color: #495057; font-weight: 500;">${startTimeStr}</td>
                    <td style="border: none; padding: 12px 8px; color: #495057;">${schedule.venue}</td>
                    <td class="text-center" style="border: none; padding: 12px 8px;">
                        <button type="button" class="btn btn-sm" onclick="viewStudentsForSchedule(${schedule.id}, 'Schedule', '${eventDate}', '${startTimeStr}', '${schedule.venue}')" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42); border-radius: 4px; padding: 6px 12px; font-size: 12px;">
                            <i class="fas fa-users"></i> View Student List (${schedule.applicant_count})
                        </button>
                    </td>
                </tr>
            `;
        });
    } else {
        // Update schedule info to show no schedules message
        document.getElementById('scheduleInfo').innerHTML = '<strong>No Exam Schedules Found</strong>';
        scheduleRows = `
            <tr>
                <td colspan="4" class="text-center text-muted">
                    <i class="fas fa-calendar-times fa-2x mb-2"></i><br>
                    No exam schedules found
                </td>
            </tr>
        `;
    }
    
    document.getElementById('examSchedulesContent').innerHTML = `
        <div class="mb-4">
            <div class="table-responsive">
                <table class="table table-sm" style="border: none; box-shadow: none;">
                    <thead style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                        <tr>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Date</th>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Time</th>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Venue</th>
                            <th class="text-center" style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${scheduleRows}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    modal.show();
}

// Function to view students for a specific schedule
function viewStudentsForSchedule(scheduleId, eventName, eventDate, eventTime, venue) {
    const modal = new bootstrap.Modal(document.getElementById('viewStudentListModal'));
    
    // Clear any previous data and content immediately to prevent showing cached data
    window.currentStudentData = null;
    window.currentScheduleInfo = null;
    
    // Clear the student list content immediately
    document.getElementById('studentListContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading students...</p>
        </div>
    `;
    
    // Hide export button initially
    document.getElementById('exportStudentListBtn').style.display = 'none';
    
    // Reset the back button
    document.querySelector('.modal-footer .btn-secondary').innerHTML = '<i class="fas fa-times"></i> Close';
    document.querySelector('.modal-footer .btn-secondary').setAttribute('onclick', 'closeStudentListModal()');
    
    // Show the schedule info container and populate it
    document.getElementById('scheduleInfoContainer').style.display = 'block';
    document.getElementById('scheduleInfo').innerHTML = `
        <strong>${eventName}</strong><br>
        <small class="text-muted">${eventDate} at ${eventTime}</small><br>
        <small class="text-muted"><i class="fas fa-map-marker-alt"></i> ${venue}</small>
    `;
    
    // Ensure content persists after modal is shown
    setTimeout(() => {
        document.getElementById('scheduleInfoContainer').style.display = 'block';
        document.getElementById('scheduleInfo').innerHTML = `
            <strong>${eventName}</strong><br>
            <small class="text-muted">${eventDate} at ${eventTime}</small><br>
            <small class="text-muted"><i class="fas fa-map-marker-alt"></i> ${venue}</small>
        `;
    }, 100);
    
    console.log('Schedule ID:', scheduleId);
    
    modal.show();
    
    // Fetch students for this specific schedule via AJAX with cache-busting
    fetch(`scheduling.php?action=get_schedule_students&schedule_id=${scheduleId}&t=${Date.now()}`)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response is not JSON');
            }
            
            return response.json();
        })
        .then(data => {
            if (data.error) {
                document.getElementById('studentListContent').innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h5 class="text-muted">Error</h5>
                        <p>${data.error}</p>
                    </div>
                `;
                return;
            }
            
            const scheduledApplicants = data;
            
            console.log('Scheduled applicants found:', scheduledApplicants);
            
            if (scheduledApplicants.length > 0) {
                let studentRows = '';
                scheduledApplicants.forEach((applicant, index) => {
                    // Format name properly
                    const fullName = `${applicant.last_name}, ${applicant.first_name} ${applicant.middle_name || ''}`.trim();
                    const formattedName = fullName.split(' ').map(word => 
                        word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
                    ).join(' ');
                    
                    studentRows += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><strong>${formattedName}</strong></td>
                            <td class="text-center">${applicant.program || 'N/A'}</td>
                            <td class="text-center"><span class="badge" style="background-color: rgb(0, 105, 42);">Scheduled</span></td>
                        </tr>
                    `;
                });
                
                document.getElementById('studentListContent').innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-hover" id="studentListTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th class="text-center">Program</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${studentRows}
                            </tbody>
                        </table>
                    </div>
                `;
                
                // Show export button and store data for export
                document.getElementById('exportStudentListBtn').style.display = 'inline-block';
                document.querySelector('.modal-footer .btn-secondary').innerHTML = '<i class="fas fa-arrow-left"></i> Back to Schedules';
                document.querySelector('.modal-footer .btn-secondary').setAttribute('onclick', 'backToExamSchedules()');
                window.currentStudentData = scheduledApplicants;
                window.currentScheduleInfo = {
                    eventName: eventName,
                    eventDate: eventDate,
                    eventTime: eventTime,
                    venue: venue
                };
            } else {
                document.getElementById('studentListContent').innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <h5 class="text-muted">No Student Found</h5>
                        <p>No students are currently enrolled for this schedule.</p>
                    </div>
                `;
                
                // Hide export button when no students
                document.getElementById('exportStudentListBtn').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error fetching students:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                name: error.name
            });
            
            document.getElementById('studentListContent').innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h5 class="text-muted">Error</h5>
                    <p>Failed to load students. Please try again.</p>
                    <small class="text-muted">Error: ${error.message}</small>
                    <br><small class="text-muted">Check browser console for more details.</small>
                </div>
            `;
        });
}

// Function to view student list for a specific schedule
function viewStudentList(scheduleId, eventName, eventDate, eventTime, venue = 'N/A') {
    // For now, show a placeholder modal - you can implement actual student list fetching
    const modal = new bootstrap.Modal(document.getElementById('viewStudentListModal'));
    // Show the schedule info container and populate it
    document.getElementById('scheduleInfoContainer').style.display = 'block';
    document.getElementById('scheduleInfo').innerHTML = `
        <strong>${eventName}</strong><br>
        <small class="text-muted">${eventDate} at ${eventTime}</small><br>
        <small class="text-muted"><i class="fas fa-map-marker-alt"></i> ${venue}</small>
    `;
    
    // Ensure content persists after modal is shown
    setTimeout(() => {
        document.getElementById('scheduleInfoContainer').style.display = 'block';
        document.getElementById('scheduleInfo').innerHTML = `
            <strong>${eventName}</strong><br>
            <small class="text-muted">${eventDate} at ${eventTime}</small><br>
            <small class="text-muted"><i class="fas fa-map-marker-alt"></i> ${venue}</small>
        `;
    }, 100);
    document.getElementById('studentListContent').innerHTML = `
        <div class="text-center text-muted">
            <i class="fas fa-users fa-3x mb-3"></i>
            <p>Student list for this schedule will be displayed here.</p>
            <p><small>Schedule ID: ${scheduleId}</small></p>
        </div>
    `;
    modal.show();
}

// Function to go back to exam schedules table view
function backToExamSchedules() {
    // Close the student list modal
    const studentListModal = bootstrap.Modal.getInstance(document.getElementById('viewStudentListModal'));
    if (studentListModal) {
        studentListModal.hide();
    }
    
    // Open the exam schedules modal
    const examSchedulesModal = new bootstrap.Modal(document.getElementById('examSchedulesModal'));
    
    // Get all schedules from PHP data and display the table (including assigned ones)
    const allSchedules = <?= json_encode($all_schedules_for_viewing) ?>;
    
    let scheduleRows = '';
    if (allSchedules.length > 0) {
        allSchedules.forEach(schedule => {
            const eventDate = new Date(schedule.event_date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit'
            });
            
            const startTime = new Date(`2000-01-01T${schedule.event_time}`);
            const startTimeStr = startTime.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            scheduleRows += `
                <tr style="border-bottom: 1px solid #f1f3f4;">
                    <td style="border: none; padding: 12px 8px; color: #495057;">${eventDate}</td>
                    <td style="border: none; padding: 12px 8px; color: #495057; font-weight: 500;">${startTimeStr}</td>
                    <td style="border: none; padding: 12px 8px; color: #495057;">${schedule.venue}</td>
                    <td class="text-center" style="border: none; padding: 12px 8px;">
                        <button type="button" class="btn btn-sm" onclick="viewStudentsForSchedule(${schedule.id}, 'Schedule', '${eventDate}', '${startTimeStr}', '${schedule.venue}')" style="background-color: rgb(0, 105, 42); color: white; border-color: rgb(0, 105, 42); border-radius: 4px; padding: 6px 12px; font-size: 12px;">
                            <i class="fas fa-users"></i> View Student List (${schedule.applicant_count})
                        </button>
                    </td>
                </tr>
            `;
        });
    } else {
        scheduleRows = `
            <tr>
                <td colspan="4" class="text-center text-muted">
                    <i class="fas fa-calendar-times fa-2x mb-2"></i><br>
                    No exam schedules found
                </td>
            </tr>
        `;
    }
    
    document.getElementById('examSchedulesContent').innerHTML = `
        <div class="mb-4">
            <div class="table-responsive">
                <table class="table table-sm" style="border: none; box-shadow: none;">
                    <thead style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                        <tr>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Date</th>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Time</th>
                            <th style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Venue</th>
                            <th class="text-center" style="border: none; padding: 12px 8px; font-weight: 600; color: #495057;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${scheduleRows}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    examSchedulesModal.show();
}

// Function to close modal
function closeModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('viewStudentListModal'));
    if (modal) {
        modal.hide();
    }
}

// Function to export student list to CSV
function exportStudentList() {
    if (!window.currentStudentData || window.currentStudentData.length === 0) {
        showToast('No student data to export.', 'error', 2000);
        return;
    }
    
    const scheduleInfo = window.currentScheduleInfo;
    const students = window.currentStudentData;
    
    // Create CSV content
    let csvContent = `"Schedule Information"\n`;
    csvContent += `"Event: ${scheduleInfo.eventName}"\n`;
    csvContent += `"Date: ${scheduleInfo.eventDate}"\n`;
    csvContent += `"Time: ${scheduleInfo.eventTime}"\n`;
    csvContent += `"Venue: ${scheduleInfo.venue}"\n\n`;
    
    csvContent += `"#","Name","Program"\n`;
    
    students.forEach((student, index) => {
        // Format name properly
        const fullName = `${student.last_name}, ${student.first_name} ${student.middle_name || ''}`.trim();
        const formattedName = fullName.split(' ').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
        ).join(' ');
        
        const program = student.program || 'N/A';
        
        csvContent += `"${index + 1}","${formattedName}","${program}"\n`;
    });
    
    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    
    // Generate filename with date and time
    const now = new Date();
    const dateStr = now.toISOString().split('T')[0];
    const timeStr = now.toTimeString().split(' ')[0].replace(/:/g, '-');
    const filename = `student_list_${dateStr}_${timeStr}.csv`;
    
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Show success message
    showSuccessToast('Student list exported successfully!');
}

// Function to update unscheduled count display
function updateUnscheduledCount() {
    const unscheduledCount = document.querySelectorAll('.applicant-checkbox:not([disabled])').length;
    
    const unscheduledCountEl = document.getElementById('unscheduledCount');
    
    if (unscheduledCountEl) {
        unscheduledCountEl.textContent = unscheduledCount;
    }
}

// Function to show success toast
function showSuccessToast(message) {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1060';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toastId = 'exportToast_' + Date.now();
    const toastElement = document.createElement('div');
    toastElement.id = toastId;
    toastElement.className = 'toast align-items-center text-bg-success border-0';
    toastElement.setAttribute('role', 'alert');
    toastElement.setAttribute('aria-live', 'assertive');
    toastElement.setAttribute('aria-atomic', 'true');
    toastElement.setAttribute('data-bs-delay', '3000');
    
    toastElement.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-check-circle me-2"></i>${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toastElement);
    
    // Show the toast
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Remove the toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}
</script>