<!DOCTYPE html>
<html>
<head>
    <title>Applicant Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .id-picture-container {
            width: 120px;
            height: 120px;
            border: 1px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 15px;
            right: 15px;
            overflow: hidden;
            background-color: #f8f9fa;
            transition: all 0.2s ease;
        }
        .id-picture-container:hover {
            background-color: #e9ecef;
            border-color: #6c757d;
            box-shadow: 0 0 5px rgba(0,0,0,0.2);
        }
        .id-picture-preview {
            max-width: 100%;
            max-height: 100%;
            display: none;
        }
        .placeholder-text {
            color: #6c757d;
            text-align: center;
            font-size: 0.8rem;
        }
        .card-body {
            position: relative;
            padding-top: 25px;
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 700px;">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Applicant Registration</h5>
        </div>
        <div class="card-body">
            <!-- Image preview container that acts as a clickable area -->
            <div class="id-picture-container" id="previewContainer" style="cursor: pointer;" onclick="document.getElementById('idPicture').click();">
                <div class="placeholder-text"> Insert 2x2 ID</div>
                <img id="picturePreview" name=id_picture class="id-picture-preview" alt="ID Preview">
            </div>
            
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">2x2 ID Picture</label>
                    <input type="file" name="id_picture" id="idPicture" class="form-control" accept="image/*" required style="display: none;">
                    <div class="form-text">Click the preview box in the upper right to upload your 2x2 ID picture</div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="birth_date" class="form-control" id="birthDate" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sex</label>
                        <select name="sex" class="form-select" required>
                            <option value="">-- Select --</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Region</label>
                        <input type="text" name="region" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Province</label>
                        <input type="text" name="province" class="form-control" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Barangay</label>
                        <input type="text" name="barangay" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Street / Purok</label>
                        <input type="text" name="street" class="form-control" required>
                    </div>
                </div>
                <button class="btn btn-success w-100" type="submit">Submit</button>
            </form>
        </div>
    </div>
</div>

<script>
    // JavaScript to handle image preview
    document.getElementById('idPicture').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('picturePreview');
                preview.src = e.target.result;
                preview.style.display = 'block';
                document.querySelector('.placeholder-text').style.display = 'none';
            }
            reader.readAsDataURL(file);
        }
    });
</script>
<div class="container mt-5 mb-5">
    <h3 class="mb-4">Socio-Demographic Profile</h3>
    <form method="POST">
        <div class="card p-4 mb-4">
            <h5>Personal Info</h5>

            <label><b>Marital Status:</b></label><br>
            <?php foreach(["Single", "Married", "Divorced", "Domestic Partnership", "Others"] as $opt): ?>
                <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="marital_status" value="<?= $opt ?>" required>
                    <label class="form-check-label"><?= $opt ?></label>
                </div>
            <?php endforeach; ?>

            <hr>

            <label>Religious Affiliation:</label><br>
            <?php foreach(["None", "Christianity", "Islam", "Hinduism", "Others"] as $opt): ?>
                <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="religion" value="<?= $opt ?>" required>
                    <label class="form-check-label"><?= $opt ?></label>
                </div>
            <?php endforeach; ?>

            <hr>

            <label>Sexual Orientation:</label><br>
            <?php foreach(["Heterosexual", "Homosexual", "Bisexual", "Others"] as $opt): ?>
                <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="orientation" value="<?= $opt ?>" required>
                    <label class="form-check-label"><?= $opt ?></label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card p-4 mb-4">
            <h5>Parental Status</h5>

            <label>Father Status:</label><br>
            <?php foreach(["Alive; Away", "Alive; at Home", "Deceased", "Unknown"] as $opt): ?>
                <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="father_status" value="<?= $opt ?>" required>
                    <label class="form-check-label"><?= $opt ?></label>
                </div>
            <?php endforeach; ?>

            <hr>

            <label>Father Education Level:</label><br>
            <?php foreach(["No High School Diploma", "High School Diploma", "Bachelor’s Degree", "Graduate Degree"] as $opt): ?>
                <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="father_education" value="<?= $opt ?>" required>
                    <label class="form-check-label"><?= $opt ?></label>
                </div>
            <?php endforeach; ?>

            <hr>

            <label>Father Employment:</label><br>
            <?php foreach(["Employed Full-Time", "Employed Part-Time", "Unemployed"] as $opt): ?>
                <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="father_employment" value="<?= $opt ?>" required>
                    <label class="form-check-label"><?= $opt ?></label>
                </div>
            <?php endforeach; ?>

            <hr>

            <label>Mother Status:</label><br>
            <?php foreach(["Alive; Away", "Alive; at Home", "Deceased", "Unknown"] as $opt): ?>
                <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="mother_status" value="<?= $opt ?>" required>
                    <label class="form-check-label"><?= $opt ?></label>
                </div>
            <?php endforeach; ?>

            <hr>

            <label>Mother Education Level:</label><br>
            <?php foreach(["No High School Diploma", "High School Diploma", "Bachelor’s Degree", "Graduate Degree"] as $opt): ?>
                <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="mother_education" value="<?= $opt ?>" required>
                    <label class="form-check-label"><?= $opt ?></label>
                </div>
            <?php endforeach; ?>

            <hr>

            <label>Mother Employment:</label><br>
            <?php foreach(["Employed Full-Time", "Employed Part-Time", "Unemployed"] as $opt): ?>
                <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="mother_employment" value="<?= $opt ?>" required>
                    <label class="form-check-label"><?= $opt ?></label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card p-4 mb-4">
            <h5>Other Details</h5>

            <label>Number of Siblings:</label><br>
            <?php foreach(["None", "One", "Two or more"] as $opt): ?>
                <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="siblings" value="<?= $opt ?>" required>
                    <label class="form-check-label"><?= $opt ?></label>
                </div>
            <?php endforeach; ?>

            <hr>

            <label>Currently Living With:</label><br>
            <?php foreach(["Both parents", "One parent only", "Relatives", "Alone"] as $opt): ?>
                <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="living_with" value="<?= $opt ?>" required>
                    <label class="form-check-label"><?= $opt ?></label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card p-4 mb-4">
            <h5>Technology Access</h5>
            <?php
                $tech = [
                    "access_computer" => "Access to personal computer at home",
                    "access_internet" => "Internet access at home",
                    "access_mobile" => "Access to mobile device(s)"
                ];
                foreach ($tech as $name => $label):
            ?>
                <label><?= $label ?>:</label><br>
                <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="<?= $name ?>" value="1" required>
                    <label class="form-check-label">Yes</label>
                </div>
                <div class="form-check form-check-inline mb-2">
                    <input type="radio" class="form-check-input" name="<?= $name ?>" value="0">
                    <label class="form-check-label">No</label>
                </div><br>
            <?php endforeach; ?>
        </div>

        <div class="card p-4 mb-4">
            <h5>Other Determinants</h5>
            <?php
                $other = [
                    "indigenous_group" => "Member of an indigenous group",
                    "first_gen_college" => "First in family to attend college",
                    "has_disability" => "Has a disability",
                    "was_scholar" => "Scholar during high school",
                    "received_honors" => "Received academic honors"
                ];

                foreach ($other as $name => $label):
            ?>
                <label><?= $label ?>:</label><br>
                <div class="form-check form-check-inline">
                    <input type="radio" onclick="toggleDisabilityDetail()" class="form-check-input" id="<?= $name ?>_yes" name="<?= $name ?>" value="1" required>
                    <label class="form-check-label">Yes</label>
                </div>
                <div class="form-check form-check-inline mb-2">
                    <input type="radio" onclick="toggleDisabilityDetail()" class="form-check-input" name="<?= $name ?>" value="0">
                    <label class="form-check-label">No</label>
                </div><br>
            <?php endforeach; ?>

            <div class="mt-3" id="disability_detail" style="display:none;">
                <label>If yes, specify disability:</label>
                <input type="text" class="form-control" name="disability_detail">
            </div>
        </div>

        <button class="btn btn-primary" type="submit">Submit</button>
    </form>
</div>
</body>
</html>