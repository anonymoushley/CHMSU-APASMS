describe('Applicant Profiling Tests (Steps 1-5)', () => {
  beforeEach(() => {
    // Login first
    cy.login('test@example.com', 'Test123!')
    cy.visit('http://localhost/system/students/profiling.php')
  })

  // TC-011: Step 1 - Personal info save
  it('TC-011: Should save personal info and proceed to Step 2', () => {
    cy.get('input[name="firstname"]').type('John')
    cy.get('input[name="lastname"]').type('Doe')
    cy.get('input[name="middlename"]').type('Michael')
    cy.get('input[name="birthdate"]').type('2000-01-01')
    cy.get('select[name="gender"]').select('Male')
    cy.get('input[name="contact"]').type('09123456789')
    cy.get('textarea[name="address"]').type('123 Main Street, City')
    
    cy.get('button[name="save_step1"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Data saved')
    cy.url().should('include', 'step2')
  })

  // TC-012: Step 1 - Missing required fields
  it('TC-012: Should show error for missing required fields', () => {
    cy.get('input[name="firstname"]').type('John')
    // Leave lastname empty
    cy.get('button[name="save_step1"]').click()
    
    cy.get('.alert-danger').should('be.visible')
    cy.get('.alert-danger').should('contain', 'Required field missing')
  })

  // TC-013: Step 2 - Educational background
  it('TC-013: Should save educational background and proceed to Step 3', () => {
    cy.get('input[name="school"]').type('Sample High School')
    cy.get('input[name="strand"]').type('STEM')
    cy.get('input[name="gpa"]').type('95')
    
    cy.get('button[name="save_step2"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Data saved')
    cy.url().should('include', 'step3')
  })

  // TC-014: Step 3 - Campus & College selection
  it('TC-014: Should save campus and college selection', () => {
    cy.get('input[name="campus"]').check('Talisay')
    cy.get('select[name="college"]').select('College of Computer Studies')
    
    cy.get('button[name="save_step3"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Selection saved')
  })

  // TC-015: Step 4 - Document upload validation
  it('TC-015: Should validate document upload requirements', () => {
    // Upload incomplete files (missing report card)
    cy.get('input[name="birth_certificate"]').selectFile('cypress/fixtures/sample.pdf')
    // Skip report card upload
    
    cy.get('button[name="save_step4"]').click()
    
    cy.get('.alert-danger').should('be.visible')
    cy.get('.alert-danger').should('contain', 'missing files')
  })

  // TC-016: Step 5 - Review and confirm
  it('TC-016: Should confirm profile and lock for review', () => {
    cy.get('button[name="confirm_profile"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Profile locked')
  })

  // TC-017: Return to previous step
  it('TC-017: Should navigate back to previous step with saved data', () => {
    cy.get('button[name="back_to_step2"]').click()
    
    cy.url().should('include', 'step2')
    cy.get('input[name="school"]').should('have.value', 'Sample High School')
  })

  // TC-018: Session saving test
  it('TC-018: Should resume at last completed step after logout/login', () => {
    // Complete step 1
    cy.get('input[name="firstname"]').type('Test')
    cy.get('input[name="lastname"]').type('User')
    cy.get('button[name="save_step1"]').click()
    
    // Logout
    cy.get('a[href="logout.php"]').click()
    
    // Login again
    cy.login('test@example.com', 'Test123!')
    cy.visit('http://localhost/system/students/profiling.php')
    
    // Should resume at step 2
    cy.url().should('include', 'step2')
  })

  // TC-019: Upload Grade 11 1st Sem Card
  it('TC-019: Should successfully upload Grade 11 1st Sem Card', () => {
    cy.get('input[name="grade11_1st_sem"]').selectFile('cypress/fixtures/grade11_1st_sem.pdf')
    cy.get('button[name="upload_grade11_1st"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'File uploaded successfully')
  })

  // TC-020: Invalid file type upload
  it('TC-020: Should reject invalid file type', () => {
    cy.get('input[name="grade11_1st_sem"]').selectFile('cypress/fixtures/invalid_file.exe')
    cy.get('button[name="upload_grade11_1st"]').click()
    
    cy.get('.alert-danger').should('be.visible')
    cy.get('.alert-danger').should('contain', 'Invalid file format')
  })

  // TC-021: Upload multiple files
  it('TC-021: Should upload multiple files in additional uploads', () => {
    cy.get('input[name="additional_files[]"]').selectFile([
      'cypress/fixtures/file1.pdf',
      'cypress/fixtures/file2.pdf'
    ])
    cy.get('button[name="upload_additional"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'All files uploaded')
  })

  // TC-022: Missing optional file
  it('TC-022: Should allow continuation without optional file', () => {
    // Skip NCII file upload
    cy.get('button[name="save_step4"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Data saved')
  })
})


