<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get interviewer info
$interviewer_id = $_SESSION['interviewer_id'] ?? null;
$interviewer_name = $_SESSION['interviewer_name'] ?? '';

// Get total applicants assigned to this interviewer
$totalSql = "SELECT COUNT(*) AS total 
             FROM program_application pa
             LEFT JOIN personal_info pi ON pa.personal_info_id = pi.id
             LEFT JOIN registration r ON r.personal_info_id = pi.id
             WHERE r.applicant_status = 'For Interview'";
$totalResult = mysqli_query($conn, $totalSql);
$totalApplicants = mysqli_fetch_assoc($totalResult)['total'] ?? 0;

// Get completed interviews count
$completedSql = "SELECT COUNT(DISTINCT sr.personal_info_id) AS completed
                 FROM screening_results sr
                 LEFT JOIN personal_info pi ON sr.personal_info_id = pi.id
                 LEFT JOIN program_application pa ON pi.id = pa.personal_info_id
                 WHERE sr.interview_total_score IS NOT NULL";
$completedResult = mysqli_query($conn, $completedSql);
$completedInterviews = mysqli_fetch_assoc($completedResult)['completed'] ?? 0;

// Get pending interviews count
$pendingInterviews = $totalApplicants - $completedInterviews;
?>

<style>
    .stats-card {
        background: white;
        padding: 20px;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-left: 5px solid rgb(0, 105, 42);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .stats-card .icon {
        font-size: 2.5rem;
        color: rgb(0, 105, 42);
        margin-bottom: 10px;
    }
    
    .stats-card .number {
        font-size: 2.5rem;
        font-weight: bold;
        color: rgb(0, 105, 42);
        margin: 0;
    }
    
    .stats-card .label {
        color: #6c757d;
        font-size: 0.9rem;
        margin: 5px 0 0 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .welcome-section {
        background: linear-gradient(135deg, rgb(0, 105, 42), rgb(0, 85, 34));
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-top: 20px;
        margin-bottom: 30px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .welcome-section h3 {
        margin: 0;
        font-weight: 600;
    }
    
    .welcome-section p {
        margin: 5px 0 0 0;
        opacity: 0.9;
    }
</style>

<div class="container-fluid px-4" style="padding-top: 30px;">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <h3><i class="fas fa-user-circle me-2"></i>Welcome, <?= htmlspecialchars($interviewer_name) ?></h3>
        <p><i class="fas fa-microphone me-1"></i> Interviewer Portal</p>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="stats-card text-center">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <h2 class="number"><?= $totalApplicants ?></h2>
                <p class="label">Total Applicants for Interview</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card text-center">
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h2 class="number"><?= $pendingInterviews ?></h2>
                <p class="label">Pending Interviews</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card text-center">
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="number"><?= $completedInterviews ?></h2>
                <p class="label">Completed Interviews</p>
            </div>
        </div>
    </div>

</div>
