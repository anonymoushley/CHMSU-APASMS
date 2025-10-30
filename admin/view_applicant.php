<?php
require_once '../config/database.php';

// Get applicant ID from URL
$applicant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($applicant_id <= 0) {
    echo '<div class="alert alert-danger">Invalid applicant ID.</div>';
    exit();
}

// Fetch registration to get personal_info_id
$stmt = $pdo->prepare('SELECT * FROM registration WHERE id = ?');
$stmt->execute([$applicant_id]);
$registration = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$registration) {
    echo '<div class="alert alert-danger">Applicant not found.</div>';
    exit();
}
$personal_info_id = $registration['personal_info_id'] ?? null;
if (!$personal_info_id) {
    echo '<div class="alert alert-danger">No profiling data found for this applicant.</div>';
    exit();
}

// Fetch all profiling data
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

// Fetch uploaded images
$uploads = $pdo->prepare('SELECT * FROM uploads WHERE applicant_id = ?');
$uploads->execute([$applicant_id]);
$uploads = $uploads->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Applicant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .resume-section { margin-bottom: 2rem; }
        .resume-section h5 { border-bottom: 1px solid #ccc; padding-bottom: 0.5rem; margin-bottom: 1rem; background-color: rgb(0, 105, 42); color: white; padding: 0.5rem; border-radius: 2px; }
        .resume-label { font-weight: bold; color: #000; font-family: "Ice", sans-serif; }
        .id-picture {border-radius: 5px; width: 2in;
    height: 2in;
    object-fit: cover; /* crop/stretch while maintaining coverage */
    border: 1px solid #ccc; /* optional border */
    display: block;}
        .card {max-width: 90%; margin: auto; }
        .modal-dialog {max-width: 50%; margin: auto; }
        
        /* Header Color Theme Buttons */
        .btn-header-theme {
            background-color: rgb(0, 105, 42);
            color: white;
            border: 1px solid rgb(0, 105, 42);
            transition: all 0.2s ease;
        }
        
        .btn-header-theme:hover {
            background-color: rgb(0, 85, 34);
            border-color: rgb(0, 85, 34);
            color: white;
        }
        
        .btn-header-theme:active {
            background-color: rgb(0, 65, 26);
            border-color: rgb(0, 65, 26);
            color: white;
        }
    </style>
</head>
<body>
<div class="container">
    
    <div class="card shadow mb-3 mt-2">
        <div class="card-header text-white" style="background-color: rgb(0, 105, 42);">
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
                            <p><span class="resume-label">Name:</span> <?= htmlspecialchars($personal['last_name'] . ', ' . $personal['first_name'] . ' ' . $personal['middle_name']) ?></p>
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
                                <th class="text-center">Document</th>
                                <th class="text-center">View</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
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
                            
                            // Define which documents are required vs optional
                            $required_docs = ['g11_1st', 'g11_2nd', 'g12_1st'];
                            $optional_docs = ['ncii', 'guidance_cert', 'additional_file'];
                            foreach ($doc_fields as $field => $label):
                                $file = $documents[$field];
                                $status = $documents[$field . '_status'] ?? 'Pending';
                            ?>
                            <tr>
                                <td class="text-center"><?= $label ?></td>
                                <td class="text-center">
                                    <?php if ($file): ?>
                                        <button type="button"
                                            class="btn btn-sm btn-header-theme"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewDocumentModal"
                                            data-file="../uploads/<?= htmlspecialchars($file) ?>"
                                            data-label="<?= htmlspecialchars($label) ?>">
                                            View
                                        </button>
                                    <?php else: ?>
                                        <?php if (in_array($field, $optional_docs)): ?>
                                            <span class="text-muted">No document</span>
                                        <?php else: ?>
                                            <span class="text-muted">No file</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $status === 'Accepted' ? 'success' : ($status === 'Rejected' ? 'danger' : 'secondary') ?>">
                                        <?= $status ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($file): ?>
                                        <?php if ($status !== 'Accepted'): ?>
                                    <form method="post" action="verify_document.php" style="display:inline;">
    <input type="hidden" name="personal_info_id" value="<?= $personal_info_id ?>">
    <input type="hidden" name="field" value="<?= $field ?>">

    <button 
        type="button"
        class="btn btn-sm btn-header-theme" 
        data-bs-toggle="modal"
        data-bs-target="#confirmAcceptModal"
        data-document="<?= htmlspecialchars($label) ?>"
        data-field="<?= $field ?>"
    >
        Accept
    </button>

    <button 
        type="button"
        class="btn btn-danger btn-sm" 
        data-bs-toggle="modal"
        data-bs-target="#confirmRejectModal"
        data-document="<?= htmlspecialchars($label) ?>"
        data-field="<?= $field ?>"
    >
        Reject
    </button>
</form>
                                        <?php else: ?>
                                            <span class="text-success">
                                                <i class="fas fa-check-circle"></i> No action needed
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if (in_array($field, $optional_docs)): ?>
                                            <span class="text-muted">
                                                <i class="fas fa-info-circle"></i> No document
                                            </span>
                                        <?php else: ?>
                                            <span class="text-warning">
                                                <i class="fas fa-exclamation-triangle"></i> Required
                                            </span>
                                        <?php endif; ?>
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
        <a href="chair_main.php?page=chair_applicants" class="btn mb-3 btn-header-theme">Back to List</a>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
    <!-- Toasts will be dynamically added here -->
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

<!-- Accept Document Confirmation Modal -->
<div class="modal fade" id="confirmAcceptModal" tabindex="-1" aria-labelledby="confirmAcceptModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
        <h5 class="modal-title" id="confirmAcceptModalLabel">Confirm Document Acceptance</h5>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to <strong>ACCEPT</strong> this document?</p>
        <p><strong>Document:</strong> <span id="acceptDocumentName"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="post" action="verify_document.php" style="display:inline;" id="acceptForm">
          <input type="hidden" name="personal_info_id" value="<?= $personal_info_id ?>">
          <input type="hidden" name="action" value="Accepted">
          <input type="hidden" name="field" id="acceptField" value="">
          <button type="submit" class="btn" style="background-color: rgb(0, 105, 42); color: white; border: 1px solid rgb(0, 105, 42);">Accept Document</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Reject Document Confirmation Modal -->
<div class="modal fade" id="confirmRejectModal" tabindex="-1" aria-labelledby="confirmRejectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="confirmRejectModalLabel">Confirm Document Rejection</h5>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to <strong>REJECT</strong> this document?</p>
        <p><strong>Document:</strong> <span id="rejectDocumentName"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="post" action="verify_document.php" style="display:inline;" id="rejectForm">
          <input type="hidden" name="personal_info_id" value="<?= $personal_info_id ?>">
          <input type="hidden" name="action" value="Rejected">
          <input type="hidden" name="field" id="rejectField" value="">
          <button type="submit" class="btn btn-danger">Reject Document</button>
        </form>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    
document.addEventListener('DOMContentLoaded', function() {
    // Document View Modal
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

    // Accept Document Modal
    var acceptModal = document.getElementById('confirmAcceptModal');
    acceptModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var documentName = button.getAttribute('data-document');
        var field = button.getAttribute('data-field');
        
        document.getElementById('acceptDocumentName').textContent = documentName;
        document.getElementById('acceptField').value = field;
    });

    // Reject Document Modal
    var rejectModal = document.getElementById('confirmRejectModal');
    rejectModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var documentName = button.getAttribute('data-document');
        var field = button.getAttribute('data-field');
        
        document.getElementById('rejectDocumentName').textContent = documentName;
        document.getElementById('rejectField').value = field;
    });

    // Handle Accept Document Form Submission
    document.getElementById('acceptForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        fetch('verify_document.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('confirmAcceptModal'));
                modal.hide();
                // Reload page after a short delay to show updated status
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showToast('Error: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error processing request', 'error');
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

    // Handle Reject Document Form Submission
    document.getElementById('rejectForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        fetch('verify_document.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('confirmRejectModal'));
                modal.hide();
                // Reload page after a short delay to show updated status
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showToast('Error: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error processing request', 'error');
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

});

// Toast notification function
function showToast(message, type = 'success', duration = 3000) {
    const toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) return;
    
    const toastId = 'toast-' + Date.now();
    const iconClass = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
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
</script>
</body>
</html> 