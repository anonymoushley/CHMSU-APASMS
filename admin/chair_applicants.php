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

$chairProgram = $_SESSION['program'] ?? '';
$chairCampus = $_SESSION['campus'] ?? '';

if (!$chairProgram || !$chairCampus) {
    echo "<div class='alert alert-danger'>Chairperson program or campus is not defined. Please contact administrator.</div>";
    exit;
}

$search = $_GET['search'] ?? '';
$filterStrand = $_GET['filter_strand'] ?? '';
$filterStatus = $_GET['filter_status'] ?? '';
$filterYear = $_GET['filter_year'] ?? '';
$filterEligibility = $_GET['filter_eligibility'] ?? '';
$isAjax = isset($_GET['ajax']);

$sql = "SELECT 
            r.id AS registration_id,
            r.applicant_status,
            pi.last_name,
            pi.first_name,
            pi.middle_name,
            s.name as strand,
            ab.year_graduated,
            pa.program,
            pa.campus,
            COALESCE(d.g11_1st_status, 'Pending') as g11_1st_status, 
            COALESCE(d.g11_2nd_status, 'Pending') as g11_2nd_status, 
            COALESCE(d.g12_1st_status, 'Pending') as g12_1st_status, 
            COALESCE(d.ncii_status, 'Pending') as ncii_status, 
            COALESCE(d.guidance_cert_status, 'Pending') as guidance_cert_status, 
            COALESCE(d.additional_file_status, 'Pending') as additional_file_status
        FROM registration r
        LEFT JOIN personal_info pi ON r.personal_info_id = pi.id
        LEFT JOIN academic_background ab ON ab.personal_info_id = pi.id
        LEFT JOIN strands s ON ab.strand_id = s.id
        LEFT JOIN program_application pa ON pa.personal_info_id = pi.id
        LEFT JOIN documents d ON d.personal_info_id = pi.id
        WHERE LOWER(pa.program) = LOWER(?) AND LOWER(pa.campus) = LOWER(?)";

$params = [$chairProgram, $chairCampus];

if (!empty($search)) {
    $sql .= " AND (
        LOWER(pi.last_name) LIKE ? OR
        LOWER(pi.first_name) LIKE ? OR
        LOWER(s.name) LIKE ? OR
        LOWER(r.applicant_status) LIKE ?
    )";
    $searchTerm = '%' . strtolower($search) . '%';
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}

// Add filter conditions
if (!empty($filterStrand)) {
    $sql .= " AND s.name = ?";
    $params[] = $filterStrand;
}

if (!empty($filterStatus)) {
    $sql .= " AND r.applicant_status = ?";
    $params[] = $filterStatus;
}

if (!empty($filterYear)) {
    $sql .= " AND ab.year_graduated = ?";
    $params[] = $filterYear;
}

$sql .= " ORDER BY pi.last_name ASC, pi.first_name ASC, pi.middle_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Apply eligibility filter if specified
if (!empty($filterEligibility)) {
    $filteredApplicants = [];
    foreach ($applicants as $applicant) {
        // Calculate exam eligibility - only consider report cards (required documents)
        $accepted_count = 0;
        $total_docs = 0;
        $doc_statuses = [
            'g11_1st_status' => 'G11 1st Sem',
            'g11_2nd_status' => 'G11 2nd Sem', 
            'g12_1st_status' => 'G12 1st Sem'
        ];
        
        foreach ($doc_statuses as $status_field => $doc_name) {
            $status = $applicant[$status_field] ?? 'Pending';
            if ($status === null || $status === '') {
                $status = 'Pending';
            }
            if ($status === 'Accepted') {
                $accepted_count++;
            }
            $total_docs++;
        }
        
        $isEligible = ($accepted_count === $total_docs);
        
        if ($filterEligibility === 'eligible' && $isEligible) {
            $filteredApplicants[] = $applicant;
        } elseif ($filterEligibility === 'not_eligible' && !$isEligible) {
            $filteredApplicants[] = $applicant;
        }
    }
    $applicants = $filteredApplicants;
}

// If it's an AJAX request, only return the table rows
if ($isAjax):
?>
     <div class="table-responsive">
         <table class="table table-bordered table-striped table-hover align-middle mb-0">
             <thead style="background-color: rgb(0, 105, 42) !important; color: white !important;">
                 <tr>
                     <th style="width: 25%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Name</th>
                     <th style="width: 12%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Strand</th>
                     <th style="width: 20%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Status</th>
                     <th style="width: 12%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Year Graduated</th>
                     <th style="width: 15%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Exam Eligibility</th>
                     <th style="width: 16%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Action</th>
                 </tr>
             </thead>
            <tbody>
                <?php if (!$applicants): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-users fa-2x mb-2"></i><br>
                            No applicants found for this program and campus.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applicants as $applicant): ?>
                        <?php
                            $applicant_status = strtolower(trim($applicant['applicant_status']));
                            $rowClass = ($applicant_status === 'new applicant - previous academic year') ? 'table-danger' : '';
                            
                            // Calculate exam eligibility - only consider report cards (required documents)
                            $accepted_count = 0;
                            $total_docs = 0;
                            $doc_statuses = [
                                'g11_1st_status' => 'G11 1st Sem',
                                'g11_2nd_status' => 'G11 2nd Sem', 
                                'g12_1st_status' => 'G12 1st Sem'
                            ];
                            
                            foreach ($doc_statuses as $status_field => $doc_name) {
                                $status = $applicant[$status_field] ?? 'Pending';
                                // Handle NULL values from database
                                if ($status === null || $status === '') {
                                    $status = 'Pending';
                                }
                                if ($status === 'Accepted') {
                                    $accepted_count++;
                                }
                                $total_docs++;
                            }
                            
                            $eligibility_text = $accepted_count . '/' . $total_docs;
                            $eligibility_class = ($accepted_count === $total_docs) ? 'success' : (($accepted_count > 0) ? 'warning' : 'danger');
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td>
                                <strong><?= htmlspecialchars(ucwords(strtolower(trim($applicant['last_name'] . ', ' . $applicant['first_name'] . ' ' . ($applicant['middle_name'] ?? ''))))) ?></strong>
                            </td>
                             <td class="text-center"><?= htmlspecialchars($applicant['strand']) ?></td>
                             <td class="text-center">
                                 <span class="badge bg-<?= $applicant_status === 'new applicant - previous academic year' ? 'danger' : 'success' ?>">
                                     <?= htmlspecialchars($applicant['applicant_status']) ?>
                                 </span>
                             </td>
                             <td class="text-center"><?= htmlspecialchars($applicant['year_graduated']) ?></td>
                             <td class="text-center">
                                <span class="badge bg-<?= $eligibility_class ?>">
                                    <?= $eligibility_text ?>
                                </span>
                                <?php if ($accepted_count === $total_docs): ?>
                                    <br><small class="text-success">✓ Eligible</small>
                                <?php elseif ($accepted_count > 0): ?>
                                    <br><small class="text-warning">⚠ Partial</small>
                                <?php else: ?>
                                    <br><small class="text-danger">✗ Pending</small>
                                <?php endif; ?>
                            </td>
                             <td class="text-center">
                                <a href="view_applicant.php?id=<?= $applicant['registration_id'] ?>" 
                                   class="btn btn-sm modern-view-btn" 
                                   title="View Details">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php
exit;
endif;
?>

<!-- FULL HTML PAGE -->
<div class="container-fluid px-4" style="padding-top: 30px;">
    <!-- Header with Back Button -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="chair_main.php?page=chair_dashboard" class="btn btn-outline-success me-3">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                    <h4 class="mb-0"><i class="fas fa-users me-2"></i>Applicants List</h4>
                </div>
                <div class="badge fs-6" style="background-color: rgb(0, 105, 42);">
                    <?php 
                    $total_applicants = count($applicants);
                    echo $total_applicants . " Applicant" . ($total_applicants != 1 ? 's' : '');
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light border-bottom">
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="search" class="form-control" placeholder="Search by name, strand, or status" value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="alert alert-info mb-0 py-2 d-flex align-items-center" style="height: 38px;">
                        <i class="fas fa-info-circle me-1"></i>
                        <small><strong>Note:</strong> Highlighted rows are subject for verification</small>
                    </div>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-lg-3">
                    <label for="filter_strand" class="form-label small fw-bold">Filter by Strand:</label>
                    <select id="filter_strand" class="form-select form-select-sm">
                        <option value="">All Strands</option>
                        <option value="STEM" <?= $filterStrand === 'STEM' ? 'selected' : '' ?>>STEM</option>
                        <option value="ABM" <?= $filterStrand === 'ABM' ? 'selected' : '' ?>>ABM</option>
                        <option value="HUMSS" <?= $filterStrand === 'HUMSS' ? 'selected' : '' ?>>HUMSS</option>
                        <option value="GAS" <?= $filterStrand === 'GAS' ? 'selected' : '' ?>>GAS</option>
                        <option value="TVL" <?= $filterStrand === 'TVL' ? 'selected' : '' ?>>TVL</option>
                    </select>
                </div>
                <div class="col-lg-3">
                    <label for="filter_status" class="form-label small fw-bold">Filter by Status:</label>
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="New Applicant - Same Academic Year" <?= $filterStatus === 'New Applicant - Same Academic Year' ? 'selected' : '' ?>>New Applicant - Same Academic Year</option>
                        <option value="New Applicant - Previous Academic Year" <?= $filterStatus === 'New Applicant - Previous Academic Year' ? 'selected' : '' ?>>New Applicant - Previous Academic Year</option>
                        <option value="Transferee" <?= $filterStatus === 'Transferee' ? 'selected' : '' ?>>Transferee</option>
                    </select>
                </div>
                <div class="col-lg-3">
                    <label for="filter_year" class="form-label small fw-bold">Filter by Year Graduated:</label>
                    <select id="filter_year" class="form-select form-select-sm">
                        <option value="">All Years</option>
                        <?php
                        // Get unique years from the database
                        $yearSql = "SELECT DISTINCT ab.year_graduated 
                                   FROM registration r
                                   LEFT JOIN personal_info pi ON r.personal_info_id = pi.id
                                   LEFT JOIN academic_background ab ON ab.personal_info_id = pi.id
                                   LEFT JOIN program_application pa ON pa.personal_info_id = pi.id
                                   WHERE LOWER(pa.program) = LOWER(?) AND LOWER(pa.campus) = LOWER(?)
                                   AND ab.year_graduated IS NOT NULL
                                   ORDER BY ab.year_graduated DESC";
                        $yearStmt = $pdo->prepare($yearSql);
                        $yearStmt->execute([$chairProgram, $chairCampus]);
                        $years = $yearStmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        foreach ($years as $year) {
                            $selected = ($filterYear == $year) ? 'selected' : '';
                            echo "<option value=\"$year\" $selected>$year</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-lg-3">
                    <label for="filter_eligibility" class="form-label small fw-bold">Filter by Exam Eligibility:</label>
                    <select id="filter_eligibility" class="form-select form-select-sm">
                        <option value="">All Eligibility</option>
                        <option value="eligible" <?= $filterEligibility === 'eligible' ? 'selected' : '' ?>>Eligible</option>
                        <option value="not_eligible" <?= $filterEligibility === 'not_eligible' ? 'selected' : '' ?>>Not Eligible</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="tableContainer">
                <div class="table-container">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead style="background-color: rgb(0, 105, 42) !important; color: white !important;">
                             <tr>
                                 <th style="width: 25%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;">Name</th>
                                 <th style="width: 12%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Strand</th>
                                 <th style="width: 20%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Status</th>
                                 <th style="width: 12%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Year Graduated</th>
                                 <th style="width: 15%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Exam Eligibility</th>
                                 <th style="width: 16%; background-color: rgb(0, 105, 42) !important; color: white !important; border: none; font-weight: 600;" class="text-center">Action</th>
                             </tr>
                        </thead>
                        <tbody>
                            <?php if (!$applicants): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-users fa-2x mb-2"></i><br>
                                        No applicants found for this program and campus.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($applicants as $applicant): ?>
                                    <?php
                                        $applicant_status = strtolower(trim($applicant['applicant_status']));
                                        $rowClass = ($applicant_status === 'new applicant - previous academic year') ? 'table-danger' : '';
                                        
                                        // Calculate exam eligibility - only consider report cards (required documents)
                                        $accepted_count = 0;
                                        $total_docs = 0;
                                        $doc_statuses = [
                                            'g11_1st_status' => 'G11 1st Sem',
                                            'g11_2nd_status' => 'G11 2nd Sem', 
                                            'g12_1st_status' => 'G12 1st Sem'
                                        ];
                                        
                                        foreach ($doc_statuses as $status_field => $doc_name) {
                                            $status = $applicant[$status_field] ?? 'Pending';
                                            // Handle NULL values from database
                                            if ($status === null || $status === '') {
                                                $status = 'Pending';
                                            }
                                            if ($status === 'Accepted') {
                                                $accepted_count++;
                                            }
                                            $total_docs++;
                                        }
                                        
                                        $eligibility_text = $accepted_count . '/' . $total_docs;
                                        $eligibility_class = ($accepted_count === $total_docs) ? 'success' : (($accepted_count > 0) ? 'warning' : 'danger');
                                    ?>
                                    <tr class="<?= $rowClass ?>">
                                        <td>
                                            <strong><?= htmlspecialchars(ucwords(strtolower(trim($applicant['last_name'] . ', ' . $applicant['first_name'] . ' ' . ($applicant['middle_name'] ?? ''))))) ?></strong>
                                        </td>
                             <td class="text-center"><?= htmlspecialchars($applicant['strand']) ?></td>
                             <td class="text-center">
                                 <span class="badge bg-<?= $applicant_status === 'new applicant - previous academic year' ? 'danger' : 'success' ?>">
                                     <?= htmlspecialchars($applicant['applicant_status']) ?>
                                 </span>
                             </td>
                             <td class="text-center"><?= htmlspecialchars($applicant['year_graduated']) ?></td>
                             <td class="text-center">
                                <span class="badge bg-<?= $eligibility_class ?>">
                                    <?= $eligibility_text ?>
                                </span>
                                <?php if ($accepted_count === $total_docs): ?>
                                    <br><small class="text-success">✓ Eligible</small>
                                <?php elseif ($accepted_count > 0): ?>
                                    <br><small class="text-warning">⚠ Partial</small>
                                <?php else: ?>
                                    <br><small class="text-danger">✗ Pending</small>
                                <?php endif; ?>
                            </td>
                             <td class="text-center">
                                            <a href="view_applicant.php?id=<?= $applicant['registration_id'] ?>" 
                                               class="btn btn-sm modern-view-btn" 
                                               title="View Details">
                                                <i class="fas fa-eye me-1"></i>View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
body {
    background-color: #f8f9fa;
}
.card {
    border: none;
    border-radius: 10px;
}
.table th {
    background-color: #28a745;
    color: white;
    border: none;
    font-weight: 600;
}
.table td {
    border-color: #dee2e6;
    vertical-align: middle;
}
.btn-outline-secondary {
    border-radius: 8px;
    font-weight: 500;
}
.btn-outline-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
}

/* Simple Green View Button - Matching Header Color */
.modern-view-btn {
    background-color: rgb(0, 105, 42);
    border: 1px solid rgb(0, 105, 42);
    color: white;
    border-radius: 6px;
    padding: 6px 12px;
    font-weight: 500;
    font-size: 0.8rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.modern-view-btn:hover {
    background-color: rgb(0, 85, 34);
    border-color: rgb(0, 85, 34);
    color: white;
    text-decoration: none;
}

.modern-view-btn:active {
    background-color: rgb(0, 65, 26);
    border-color: rgb(0, 65, 26);
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

        /* Scrollable Table Styling */
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
            opacity: 1 !important;
        }

        .table-container thead tr {
            background-color: rgb(0, 105, 42) !important;
        }

        .table-container thead {
            background-color: rgb(0, 105, 42) !important;
        }

        .table-container tbody {
            background-color: white;
        }

        /* Custom badge colors to match header */
        .badge.bg-success {
            background-color: rgb(0, 105, 42) !important;
        }
        
        .badge.bg-warning {
            background-color: #ffc107 !important;
            color: #000 !important;
        }
        
        .badge.bg-danger {
            background-color: #dc3545 !important;
        }
</style>

<!-- ✅ JavaScript -->
<script>
function updateTable() {
    const searchTerm = document.getElementById('search').value;
    const filterStrand = document.getElementById('filter_strand').value;
    const filterStatus = document.getElementById('filter_status').value;
    const filterYear = document.getElementById('filter_year').value;
    const filterEligibility = document.getElementById('filter_eligibility').value;
    
    const params = new URLSearchParams({ 
        ajax: 1, 
        search: searchTerm,
        filter_strand: filterStrand,
        filter_status: filterStatus,
        filter_year: filterYear,
        filter_eligibility: filterEligibility
    });

    fetch('chair_applicants.php?' + params.toString())
        .then(response => response.text())
        .then(html => {
            document.getElementById('tableContainer').innerHTML = html;
        });
}

// Add event listeners
document.getElementById('search').addEventListener('input', updateTable);
document.getElementById('filter_strand').addEventListener('change', updateTable);
document.getElementById('filter_status').addEventListener('change', updateTable);
document.getElementById('filter_year').addEventListener('change', updateTable);
document.getElementById('filter_eligibility').addEventListener('change', updateTable);
</script>
