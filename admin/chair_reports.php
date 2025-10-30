<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// Check if user is logged in as chairperson
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'chairperson') {
    echo "<div class='alert alert-danger'>Access denied. Please login as chairperson.</div>";
    exit;
}

$conn = new mysqli("localhost", "root", "", "admission");

// Get chairperson's assigned program and campus
$chair_program = $_SESSION['program'] ?? '';
$chair_campus = $_SESSION['campus'] ?? '';

if (!$chair_program || !$chair_campus) {
    echo "<div class='alert alert-danger'>Chairperson program or campus is not defined. Please contact administrator.</div>";
    exit;
}

// SQL query with proper FROM and JOINs
$sql = "
    SELECT 
        pi.id as personal_info_id,
        pi.last_name, pi.first_name, pi.contact_number,
        s.name as strand, ab.g11_1st_avg, ab.g11_2nd_avg, ab.g12_1st_avg,
        sr.gwa_score, sr.stanine_result, sr.stanine_score, 
        sr.exam_total_score, sr.interview_total_score,
        sr.plus_factor, sr.rank, d.ncii_status
    FROM program_application pa
    LEFT JOIN personal_info pi ON pa.personal_info_id = pi.id
    LEFT JOIN academic_background ab ON ab.personal_info_id = pi.id
    LEFT JOIN strands s ON ab.strand_id = s.id
    LEFT JOIN documents d ON d.personal_info_id = pi.id
    LEFT JOIN screening_results sr ON sr.personal_info_id = pi.id
    WHERE LOWER(pa.program) = LOWER(?) AND LOWER(pa.campus) = LOWER(?)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $chair_program, $chair_campus);
$stmt->execute();
$result = $stmt->get_result();

// Function to calculate plus factor based on strand and NCII status
function calculatePlusFactor($strand, $ncii_status) {
    $strand = strtolower(trim($strand ?? ''));
    $ncii_status = strtolower(trim($ncii_status ?? ''));
    
    // Check if applicant has NCII certificate (status is 'Accepted')
    $has_ncii = ($ncii_status === 'accepted');
    
    // Check if applicant is from STEM or specific TVL strands (TVL-ICT, TVL-CSS, TVL-PROGRAMMING)
    $is_stem_it = in_array($strand, ['stem', 'tvl-ict', 'tvl-css', 'tvl-programming', 'stem/it']);
    
    // Apply plus factor logic
    if ($is_stem_it && $has_ncii) {
        return 5; // STEM/IT strand + NCII = 5
    } elseif ($is_stem_it && !$has_ncii) {
        return 3; // STEM/IT strand only = 3
    } elseif (!$is_stem_it && $has_ncii) {
        return 1; // NCII only = 1
    } else {
        return 0; // None both = 0
    }
}
?>


<style>
    th, td { 
        vertical-align: middle !important; 
        font-size: 11px; 
    }
    
    .card-header { 
        background-color: rgb(0, 105, 42) !important; 
        color: white !important;
    }
    
    .table-container {
        max-height: 70vh;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
    }

    /* Custom Scrollbar Styling */
    .table-container::-webkit-scrollbar {
        width: 8px;
    }

    .table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: rgb(0, 105, 42);
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background: rgb(0, 85, 34);
    }

    /* Hide scrollbar for Firefox */
    .table-container {
        scrollbar-width: thin;
        scrollbar-color: rgb(0, 105, 42) #f1f1f1;
    }

    .table-container thead th {
        position: sticky;
        top: 0;
        background-color: rgb(0, 105, 42) !important;
        color: white !important;
        z-index: 10;
        border-bottom: 2px solid #dee2e6;
    }

    .table-container thead tr {
        background-color: rgb(0, 105, 42) !important;
    }

    .table-container thead {
        background-color: rgb(0, 105, 42) !important;
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

    /* Print Styling */
    @media print {
        @page {
            margin: 0.5in;
        }
        
        * {
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        
        .no-print {
            display: none !important;
        }
        
        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            page-break-inside: avoid;
            position: relative;
            top: 0;
            left: 0;
            width: 100%;
        }
        
        .print-header img {
            height: 60px;
            margin-bottom: 10px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        
        .print-header h3 {
            margin: 0;
            color: #000 !important;
            font-size: 18px;
            font-weight: bold;
        }
        
        .print-header h4 {
            margin: 10px 0 0 0;
            color: #000 !important;
            font-size: 16px;
            font-weight: bold;
        }
        
        .print-header p {
            margin: 5px 0 0 0;
            color: #000 !important;
            font-size: 14px;
        }t
        
        body {
            background: white !important;
            color: black !important;
            font-size: 12px;
        }
        
        .container-fluid {
            padding: 0 !important;
        }
        
        .card {
            border: 1px solid #000 !important;
            box-shadow: none !important;
            margin: 0 !important;
        }
        
        .table {
            font-size: 10px !important;
        }
        
        .table th,
        .table td {
            border: 1px solid #000 !important;
            padding: 4px !important;
        }
    }
    
    .print-header {
        display: none;
    }

    /* Stanine input styling */
    .stanine-input {
        width: 80px;
        text-align: center;
        font-size: 12px;
        padding: 2px 5px;
    }

    .stanine-input:focus {
        border-color: rgb(0, 105, 42);
        box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25);
    }
</style>

<div class="container-fluid px-4" style="padding-top: 30px;">
    <!-- Print Header (hidden on screen, visible when printing) -->
    <div class="print-header">
        <img src="images/chmsu.png" alt="CHMSU Logo">
        <h3>Carlos Hilado Memorial State University</h3>
        <p>Academic Program Application and Screening Management System</p>
        <h4>Applicant Screening Report - <?= htmlspecialchars($chair_program) ?> (<?= htmlspecialchars($chair_campus) ?>)</h4>
    </div>
    <!-- Header with Back Button -->
    <div class="row mb-4 no-print">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="chair_main.php?page=chair_dashboard" class="btn btn-outline-success me-3">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                    <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i>Applicant Screening Report</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light border-bottom">
            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Screening Results - <?= htmlspecialchars($chair_program) ?> (<?= htmlspecialchars($chair_campus) ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table id="screeningTable" class="table table-bordered table-striped text-center mb-0">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Strand</th>
                        <th>Contact</th>
                        <th>G11 1st</th>
                        <th>G11 2nd</th>
                        <th>G12 1st</th>
                        <th>GWA (10%)</th>
                        <th>Stanine</th>
                        <th>Stanine Score (15%)</th>
                        <th>Initial Total</th>
                        <th>Exam Score</th>
                        <th>Exam (40%)</th>
                        <th>Interview Score</th>
                        <th>Interview (35%)</th>
                        <th>Plus Factor</th>
                        <th>Final Rating</th>
                        <th>Rank</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): 
                        // Use 0 if NULL
                        $gwa_score = is_numeric($row['gwa_score']) ? $row['gwa_score'] : 0;
                        $stanine_score = is_numeric($row['stanine_score']) ? $row['stanine_score'] : 0;
                        $exam_score = is_numeric($row['exam_total_score']) ? $row['exam_total_score'] : 0;
                        $interview_score = is_numeric($row['interview_total_score']) ? $row['interview_total_score'] : 0;
                        // Calculate plus factor based on strand and NCII status
                        $plus_factor = calculatePlusFactor($row['strand'], $row['ncii_status']);

                        // Calculate weights
                        $gwa_pct = ($gwa_score / 100) * 10;
                        $stanine_pct = $stanine_score * 0.15;
                        $initial_total = $gwa_pct + $stanine_pct;
                        $exam_pct = ($exam_score / 100) * 40;
                        $interview_pct = ($interview_score / 100) * 35;
                        $final_rating = $initial_total + $exam_pct + $interview_pct + $plus_factor;
                        ?>
                    <tr>
                        <td><?= htmlspecialchars(ucwords(strtolower("{$row['last_name']}, {$row['first_name']}"))) ?></td>
                        <td><?= $row['strand'] ?: '-' ?></td>
                        <td><?= $row['contact_number'] ?: '-' ?></td>
                        <td><?= is_numeric($row['g11_1st_avg']) ? number_format($row['g11_1st_avg'], 2) : '-' ?></td>
                        <td><?= is_numeric($row['g11_2nd_avg']) ? number_format($row['g11_2nd_avg'], 2) : '-' ?></td>
                        <td><?= is_numeric($row['g12_1st_avg']) ? number_format($row['g12_1st_avg'], 2) : '-' ?></td>
                        <td><?= number_format((($row['g11_1st_avg']+$row['g11_2nd_avg']+$row['g12_1st_avg'])/3)*.1, 2) ?></td>
                        <td>
                            <input type="text" class="form-control form-control-sm stanine-input" 
                                   value="<?= htmlspecialchars($row['stanine_result'] ?: '') ?>" 
                                   data-applicant-id="<?= $row['personal_info_id'] ?? '' ?>"
                                   placeholder="Enter stanine">
                        </td>
                        <td><?= number_format($stanine_pct, 2) ?></td>
                        <td><?= number_format($initial_total, 2) ?></td>
                        <td><?= number_format($exam_score, 2) ?></td>
                        <td><?= number_format($exam_pct, 2) ?></td>
                        <td><?= number_format($interview_score, 2) ?></td>
                        <td><?= number_format($interview_pct, 2) ?></td>
                        <td><?= number_format($plus_factor, 2) ?></td>
                        <td><strong><?= number_format($final_rating, 2) ?></strong></td>
                        <td><?= $row['rank'] ?: '-' ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- JS and DataTables scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<script>
    $(document).ready(function() {
        $('#screeningTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'print',
                {
                    extend: 'colvis',
                    collectionLayout: 'fixed two-column',
                    postfixButtons: ['colvisRestore']
                }
            ],
            order: [[15, 'desc']],
            info: false,
            paging: false,
            lengthChange: false
        });
    });

    // Handle stanine input updates
    document.addEventListener('DOMContentLoaded', function() {
        const stanineInputs = document.querySelectorAll('.stanine-input');
        
        function toNumber(value) {
            const n = parseFloat((value || '').toString().replace(/[^0-9.\-]/g, ''));
            return isNaN(n) ? 0 : n;
        }

        function computeStanineScore(rawValue) {
            const v = toNumber(rawValue);
            if (v >= 0 && v <= 100) return v; // already a percentage
            if (v >= 1 && v <= 9) return v; // return raw stanine value 1-9
            return 0;
        }

        function recalcRow(input) {
            const tr = input.closest('tr');
            if (!tr) return;

            const tds = tr.querySelectorAll('td');
            // Column indices based on header
            const gwaPct = toNumber(tds[6]?.textContent);
            const examPct = toNumber(tds[11]?.textContent);
            const interviewPct = toNumber(tds[13]?.textContent);
            const plusFactor = toNumber(tds[14]?.textContent);

            const stanineScore = computeStanineScore(input.value);
            const staninePct = stanineScore * 0.15;
            const initialTotal = gwaPct + staninePct;
            const finalRating = initialTotal + examPct + interviewPct + plusFactor;

            // Update cells
            if (tds[8]) tds[8].textContent = staninePct.toFixed(2);
            if (tds[9]) tds[9].textContent = initialTotal.toFixed(2);
            if (tds[15]) tds[15].querySelector('strong')
                ? tds[15].querySelector('strong').textContent = finalRating.toFixed(2)
                : tds[15].textContent = finalRating.toFixed(2);
        }

        function recomputeAllRanks() {
            const rows = Array.from(document.querySelectorAll('#screeningTable tbody tr'));
            const scored = rows.map((tr) => {
                const finalCell = tr.querySelectorAll('td')[15];
                const finalText = finalCell?.querySelector('strong')?.textContent || finalCell?.textContent || '0';
                const finalVal = toNumber(finalText);
                return { tr, finalVal };
            });

            scored.sort((a, b) => b.finalVal - a.finalVal);

            let currentRank = 0;
            let lastScore = null;
            let position = 0;
            for (const item of scored) {
                position += 1;
                if (lastScore === null || item.finalVal !== lastScore) {
                    currentRank = position;
                    lastScore = item.finalVal;
                }
                const rankCell = item.tr.querySelectorAll('td')[16];
                if (rankCell) rankCell.textContent = currentRank.toString();
            }
        }

        function updateStanine(input) {
            const stanineValue = input.value.trim();
            const applicantId = input.getAttribute('data-applicant-id');
            
            if (applicantId && stanineValue) {
                // Update stanine in database
                fetch('update_stanine.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `applicant_id=${applicantId}&stanine=${encodeURIComponent(stanineValue)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success indicator
                        input.style.borderColor = '#28a745';
                        setTimeout(() => {
                            input.style.borderColor = '';
                        }, 2000);
                    } else {
                        alert('Error updating stanine: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating stanine');
                });
            }
        }
        
        stanineInputs.forEach(input => {
            // Recalculate row values as user types (real-time UI update)
            input.addEventListener('input', function() {
                recalcRow(this);
                recomputeAllRanks();
            });
            // Initialize current values on load
            recalcRow(input);
            recomputeAllRanks();

            // Save on blur (clicking outside)
            input.addEventListener('blur', function() {
                updateStanine(this);
            });
            
            // Save on Enter key press
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    updateStanine(this);
                    this.blur(); // Remove focus from input
                    recomputeAllRanks();
                }
            });
        });
    });
</script>
