<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">You must be logged in to view this page.</div>';
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch registration to get personal_info_id
$stmt = $pdo->prepare('SELECT * FROM registration WHERE id = ?');
$stmt->execute([$user_id]);
$registration = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$registration) {
    echo '<div class="alert alert-danger">Applicant not found.</div>';
    exit();
}
$personal_info_id = $registration['personal_info_id'] ?? null;
if (!$personal_info_id) {
    echo '<div class="alert alert-warning">Please complete your profiling to view your account details.</div>';
    exit();
}

// Fetch all profiling sections
$personal = $pdo->prepare('SELECT * FROM personal_info WHERE id = ?');
$personal->execute([$personal_info_id]);
$personal = $personal->fetch(PDO::FETCH_ASSOC);

$socio = $pdo->prepare('SELECT * FROM socio_demographic WHERE personal_info_id = ?');
$socio->execute([$personal_info_id]);
$socio = $socio->fetch(PDO::FETCH_ASSOC);

$academic = $pdo->prepare('SELECT ab.*, s.name as strand_name FROM academic_background ab LEFT JOIN strands s ON ab.strand_id = s.id WHERE ab.personal_info_id = ?');
$academic->execute([$personal_info_id]);
$academic = $academic->fetch(PDO::FETCH_ASSOC);

$program = $pdo->prepare('SELECT * FROM program_application WHERE personal_info_id = ?');
$program->execute([$personal_info_id]);
$program = $program->fetch(PDO::FETCH_ASSOC);

$documents = $pdo->prepare('SELECT * FROM documents WHERE personal_info_id = ?');
$documents->execute([$personal_info_id]);
$documents = $documents->fetch(PDO::FETCH_ASSOC);
?>

    <style>
        .resume-section { margin-bottom: 2rem; }
        .resume-section h5 { border-bottom: 1px solid #ccc; padding-bottom: 0.5rem; margin-bottom: 1rem; background-color:rgb(187, 224, 204); padding: 0.5rem; border-radius: 2px; }
        .resume-label { font-weight: bold; color: #000; font-family: "Ice", sans-serif; }
        .id-picture {border-radius: 5px; width: 2in;
    height: 2in;
    object-fit: cover; /* crop/stretch while maintaining coverage */
    border: 1px solid #ccc; /* optional border */
    display: block;}
        .card {max-width: 100%; margin: auto; }
        .modal-dialog {max-width: 50%; margin: auto; }
    </style>
</head>
<body>
<div class="container">
    
    <div class="card shadow mb-3">
        <div class="card-header bg-success text-white">
         <h3 class="mb=0">Applicant: <?= htmlspecialchars($registration['id']) ?></h3>
        </div>
        <div class="card-body">
            <!-- Personal Info -->
            <div class="resume-section">
                <h5>Personal Information</h5>
                <?php if ($personal): ?>
                    <div class="row">
                        <div class="col-md-3 mt-2">
                            <?php if (!empty($personal['id_picture'])): ?>
                                <img src="../uploads/id_pictures/<?= htmlspecialchars($personal['id_picture']) ?>" class="id-picture mb-2" alt="ID Picture">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <p><span class="resume-label">Name:</span> <?= htmlspecialchars(ucfirst(strtolower($personal['last_name'])) . ', ' . ucfirst(strtolower($personal['first_name'])) . ' ' . ucfirst(strtolower($personal['middle_name']))) ?></p>
                            <p><span class="resume-label">Date of Birth:</span> <?= htmlspecialchars($personal['date_of_birth']) ?></p>
                            <p><span class="resume-label">Age:</span> <?= htmlspecialchars($personal['age']) ?></p>
                            <p><span class="resume-label">Sex:</span> <?= htmlspecialchars($personal['sex']) ?></p>
                            <p><span class="resume-label">Contact Number:</span> <?= htmlspecialchars($personal['contact_number']) ?></p>
                            <p><span class="resume-label">Address:</span> <?= htmlspecialchars($personal['street_purok']) ?>, Brgy. <?= htmlspecialchars($personal['barangay']) ?>, <?= htmlspecialchars($personal['city']) ?>, <?= htmlspecialchars($personal['province']) ?>, <?= htmlspecialchars($personal['region']) ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <p>No personal information found.</p>
                <?php endif; ?>
            </div>

            <!-- Academic Background -->
                <div class="resume-section">
                <h5>Academic Background</h5>
                               <?php if ($academic): ?>
                    <div class=row>
                        <div class="col-md-6">
                    <p><span class="resume-label">Last School Attended:</span> <?= htmlspecialchars($academic['last_school_attended']) ?></p>
                    <p><span class="resume-label">Strand:</span> <?= htmlspecialchars($academic['strand_name'] ?? 'N/A') ?></p>
                    <p><span class="resume-label">Year Graduated:</span> <?= htmlspecialchars($academic['year_graduated']) ?></p>
                        </div>
                        <div class="col-md-6">
                    <p><span class="resume-label">G11 1st Sem Avg:</span> <?= htmlspecialchars($academic['g11_1st_avg']) ?></p>
                    <p><span class="resume-label">G11 2nd Sem Avg:</span> <?= htmlspecialchars($academic['g11_2nd_avg']) ?></p>
                    <p><span class="resume-label">G12 1st Sem Avg:</span> <?= htmlspecialchars($academic['g12_1st_avg']) ?></p>
                    <p><span class="resume-label">Academic Award:</span> <?= htmlspecialchars($academic['academic_award']) ?></p>
                </div>
                </div>
                <?php else: ?>
                    <p>No academic background found.</p>
                <?php endif; ?>
            </div>
            <!-- Program Application -->
            <div class="resume-section">
                <h5>Program Application</h5>
                <?php if ($program): ?>
                    <p><span class="resume-label">Campus Choice:</span> <?= htmlspecialchars($program['campus']) ?></p>
                    <p><span class="resume-label">College:</span> <?= htmlspecialchars($program['college']) ?></p>
                    <p><span class="resume-label">Program Choice:</span> <?= htmlspecialchars($program['program']) ?></p>
                <?php else: ?>
                    <p>No program application found.</p>
                <?php endif; ?>
            </div>

<!-- Socio-Demographic -->
            <div class="resume-section">
                <h5>Socio-Demographic Profile</h5>
                <?php if ($socio): ?>
                    <div class="row">
                                <div class="col-md-6">
                            <p><span class="resume-label">Marital Status:</span> <?= htmlspecialchars($socio['marital_status']) ?></p>
                            <p><span class="resume-label">Religion:</span> <?= htmlspecialchars($socio['religion']) ?></p>
                            <p><span class="resume-label">Orientation:</span> <?= htmlspecialchars($socio['orientation']) ?></p>
                            <p><span class="resume-label">Father Status:</span> <?= htmlspecialchars($socio['father_status']) ?></p>
                            <p><span class="resume-label">Father Education:</span> <?= htmlspecialchars($socio['father_education']) ?></p>
                            <p><span class="resume-label">Father Employment:</span> <?= htmlspecialchars($socio['father_employment']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><span class="resume-label">Mother Status:</span> <?= htmlspecialchars($socio['mother_status']) ?></p>
                            <p><span class="resume-label">Mother Education:</span> <?= htmlspecialchars($socio['mother_education']) ?></p>
                            <p><span class="resume-label">Mother Employment:</span> <?= htmlspecialchars($socio['mother_employment']) ?></p>
                            <p><span class="resume-label">Siblings:</span> <?= htmlspecialchars($socio['siblings']) ?></p>
                            <p><span class="resume-label">Living With:</span> <?= htmlspecialchars($socio['living_with']) ?></p>
                        </div>
                    </div>
                 <h5>Technology Access</h5>
                            <p><span class="resume-label">The student applicant has access to a personal computer at home:</span> <?= htmlspecialchars($socio['access_computer']) ?></p>
                            <p><span class="resume-label">The student applicant has internet access at home:</span> <?= htmlspecialchars($socio['access_internet']) ?></p>
                            <p><span class="resume-label">The student applicant has access to mobile device(s):</span> <?= htmlspecialchars($socio['access_mobile']) ?></p>
            
                    <h5>Other Details</h5>
                            <p><span class="resume-label">The student applicant is part of an indigenous group in the Philippines:</span> <?= htmlspecialchars($socio['indigenous_group']) ?></p>
                            <p><span class="resume-label">The student applicant is the first in their family to attend college:</span> <?= htmlspecialchars($socio['first_gen_college']) ?></p>
                            <p><span class="resume-label">The student applicant was a scholar:</span> <?= htmlspecialchars($socio['was_scholar']) ?></p>
                            <p><span class="resume-label">The student applicant received any academic honors in high school:</span> <?= htmlspecialchars($socio['received_honors']) ?></p>
                            <p><span class="resume-label">The student applicant has a disability:</span> <?= htmlspecialchars($socio['has_disability']) ?></p>
                            <p><span class="resume-label">Disability Detail:</span> <?= htmlspecialchars($socio['disability_detail']) ?></p>
                    
                            
                <?php else: ?>
                    <p>No socio-demographic information found.</p>
                <?php endif; ?>
            </div>

            <!-- Documents -->
            <div class="resume-section">
                <h5>Uploaded Documents</h5>
                <?php if ($documents): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>View</th>
                            
                            
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $doc_fields = [
                                'g11_1st' => 'G11 1st Sem Report Card',
                                'g11_2nd' => 'G11 2nd Sem Report Card',
                                'g12_1st' => 'G12 1st Sem Report Card',
                                'ncii' => 'NCII Certificate',
                                'guidance_cert' => 'Guidance Certificate',
                                'additional_file' => 'Additional File'
                            ];
                            foreach ($doc_fields as $field => $label):
                                $file = $documents[$field];
                                $status = $documents[$field . '_status'] ?? 'Pending';
                            ?>
                            <tr>
                                <td><?= $label ?></td>
                                <td>
                                    <?php if ($file): ?>
                                        <button type="button"
                                            class="btn btn-sm btn-info"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewDocumentModal"
                                            data-file="../uploads/<?= htmlspecialchars($file) ?>"
                                            data-label="<?= htmlspecialchars($label) ?>">
                                            View
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">No file</span>
                                    <?php endif; ?>
                                </td>
                        
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No documents uploaded.</p>
                <?php endif; ?>
            </div>
            </div>
    </div>
</div>
<!-- Document View Modal -->
<div class="modal fade" id="viewDocumentModal" tabindex="-1" aria-labelledby="viewDocumentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewDocumentModalLabel">View Document</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center" id="documentModalBody">
        <!-- Content will be loaded by JS -->
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var viewModal = document.getElementById('viewDocumentModal');
    viewModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var file = button.getAttribute('data-file');
        var label = button.getAttribute('data-label');
        var modalTitle = viewModal.querySelector('.modal-title');
        var modalBody = document.getElementById('documentModalBody');
        modalTitle.textContent = 'View: ' + label;

        // Determine file type
        var ext = file.split('.').pop().toLowerCase();
        if(['jpg','jpeg','png','gif','bmp','webp'].includes(ext)) {
            modalBody.innerHTML = '<img src="' + file + '" class="img-fluid" alt="Document Image">';
        } else if(ext === 'pdf') {
            modalBody.innerHTML = '<embed src="' + file + '" type="application/pdf" width="100%" height="600px" />';
        } else {
            modalBody.innerHTML = '<a href="' + file + '" target="_blank">Open Document</a>';
        }
    });
});
</script>
</body>
</html> 