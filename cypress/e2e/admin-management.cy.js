describe('Admin Management Tests', () => {
  beforeEach(() => {
    // Login as admin
    cy.visit('http://localhost/system/admin/admin_login.php')
    cy.get('input[name="email"]').type('admin@example.com')
    cy.get('input[name="password"]').type('admin123')
    cy.get('button[type="submit"]').click()
  })

  // TC-040: Input stanine score
  it('TC-040: Should input stanine score successfully', () => {
    cy.visit('http://localhost/system/admin/update_stanine.php')
    
    cy.get('select[name="applicant_id"]').select('1')
    cy.get('input[name="stanine_score"]').type('7')
    cy.get('button[name="save_stanine"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Score saved and displayed')
  })

  // TC-041: Invalid stanine value
  it('TC-041: Should reject invalid stanine value', () => {
    cy.visit('http://localhost/system/admin/update_stanine.php')
    
    cy.get('select[name="applicant_id"]').select('1')
    cy.get('input[name="stanine_score"]').type('12') // Invalid range
    cy.get('button[name="save_stanine"]').click()
    
    cy.get('.alert-danger').should('be.visible')
    cy.get('.alert-danger').should('contain', 'Invalid range')
  })

  // TC-042: Update stanine score
  it('TC-042: Should update existing stanine score', () => {
    cy.visit('http://localhost/system/admin/update_stanine.php')
    
    cy.get('a[href*="edit_stanine"]').first().click()
    cy.get('input[name="stanine_score"]').clear().type('6')
    cy.get('button[name="update_stanine"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Record updated successfully')
  })

  // TC-043: Auto-calculated eligibility
  it('TC-043: Should auto-calculate eligibility based on stanine', () => {
    cy.visit('http://localhost/system/admin/update_stanine.php')
    
    cy.get('select[name="applicant_id"]').select('1')
    cy.get('input[name="stanine_score"]').type('8')
    cy.get('button[name="save_stanine"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Applicant marked eligible')
  })

  // TC-044: Input plus factor based on strand
  it('TC-044: Should assign plus factor based on strand match', () => {
    cy.visit('http://localhost/system/admin/update_stanine.php')
    
    cy.get('select[name="applicant_id"]').select('1')
    cy.get('input[name="plus_factor"]').type('3')
    cy.get('select[name="strand_match"]').select('ICT Strand')
    cy.get('button[name="save_plus_factor"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Plus points recorded')
  })

  // TC-045: Missing strand info
  it('TC-045: Should warn when strand info is missing', () => {
    cy.visit('http://localhost/system/admin/update_stanine.php')
    
    cy.get('select[name="applicant_id"]').select('2') // Applicant without strand info
    cy.get('input[name="plus_factor"]').type('3')
    cy.get('button[name="save_plus_factor"]').click()
    
    cy.get('.alert-warning').should('be.visible')
    cy.get('.alert-warning').should('contain', 'No strand info')
  })

  // TC-046: Edit plus factor score
  it('TC-046: Should edit plus factor score', () => {
    cy.visit('http://localhost/system/admin/update_stanine.php')
    
    cy.get('a[href*="edit_plus_factor"]').first().click()
    cy.get('input[name="plus_factor"]').clear().type('5')
    cy.get('button[name="update_plus_factor"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Value updated successfully')
  })

  // TC-047: Calculate total rating
  it('TC-047: Should calculate total rating correctly', () => {
    cy.visit('http://localhost/system/admin/recompute_ranks.php')
    
    cy.get('button[name="calculate_total"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Final rating displayed accurately')
  })

  // TC-048: Generate applicant list per campus
  it('TC-048: Should generate applicant list per campus', () => {
    cy.visit('http://localhost/system/admin/reports.php')
    
    cy.get('select[name="campus"]').select('Talisay Campus')
    cy.get('button[name="generate_report"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Report generated')
  })

  // TC-049: Generate list per program
  it('TC-049: Should generate list per program', () => {
    cy.visit('http://localhost/system/admin/reports.php')
    
    cy.get('select[name="program"]').select('BSIS')
    cy.get('button[name="generate_report"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Program report shown')
  })

  // TC-050: Export report to PDF
  it('TC-050: Should export report to PDF', () => {
    cy.visit('http://localhost/system/admin/reports.php')
    
    cy.get('select[name="campus"]').select('Talisay Campus')
    cy.get('button[name="generate_report"]').click()
    cy.get('button[name="export_pdf"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'PDF file downloaded')
  })

  // TC-051: Invalid report filter
  it('TC-051: Should show error for invalid report filter', () => {
    cy.visit('http://localhost/system/admin/reports.php')
    
    // Leave filter blank
    cy.get('button[name="generate_report"]').click()
    
    cy.get('.alert-danger').should('be.visible')
    cy.get('.alert-danger').should('contain', 'Please select filter')
  })

  // TC-052: Generate date range report
  it('TC-052: Should generate date range report', () => {
    cy.visit('http://localhost/system/admin/reports.php')
    
    cy.get('input[name="start_date"]').type('2025-01-01')
    cy.get('input[name="end_date"]').type('2025-01-31')
    cy.get('button[name="generate_report"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Displays correct applicants within range')
  })

  // TC-053: Empty result handling
  it('TC-053: Should handle empty results gracefully', () => {
    cy.visit('http://localhost/system/admin/reports.php')
    
    cy.get('input[name="start_date"]').type('2020-01-01')
    cy.get('input[name="end_date"]').type('2020-01-31')
    cy.get('button[name="generate_report"]').click()
    
    cy.get('.alert-info').should('be.visible')
    cy.get('.alert-info').should('contain', 'No records found')
  })

  // TC-054: Applicant logs out
  it('TC-054: Should allow applicant logout', () => {
    cy.login('test@example.com', 'Test123!')
    cy.get('a[href*="logout"]').click()
    
    cy.url().should('include', 'login.php')
  })

  // TC-055: Session timeout
  it('TC-055: Should handle session timeout', () => {
    cy.login('test@example.com', 'Test123!')
    
    // Wait for session timeout (this would need to be mocked)
    cy.get('.alert-warning').should('be.visible')
    cy.get('.alert-warning').should('contain', 'System logs out automatically')
  })

  // TC-056: Change password feature
  it('TC-056: Should allow password change', () => {
    cy.login('test@example.com', 'Test123!')
    cy.visit('http://localhost/system/students/change_password.php')
    
    cy.get('input[name="current_password"]').type('Test123!')
    cy.get('input[name="new_password"]').type('NewTest123!')
    cy.get('input[name="confirm_password"]').type('NewTest123!')
    cy.get('button[name="change_password"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Password updated successfully')
  })

  // TC-057: Forgot password request
  it('TC-057: Should handle forgot password request', () => {
    cy.visit('http://localhost/system/students/login.php')
    cy.get('a[href*="forgot_password"]').click()
    
    cy.get('input[name="email"]').type('test@example.com')
    cy.get('button[name="send_reset"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Reset link sent')
  })

  // TC-058: Admin deletes applicant
  it('TC-058: Should allow admin to delete applicant', () => {
    cy.visit('http://localhost/system/admin/applicant.php')
    
    cy.get('button[name="delete_applicant"]').first().click()
    cy.get('button[name="confirm_delete"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Record removed from database')
  })

  // TC-059: Chairperson views program applicants
  it('TC-059: Should display program applicants for chairperson', () => {
    cy.visit('http://localhost/system/admin/chair_login.php')
    cy.get('input[name="email"]').type('chair@example.com')
    cy.get('input[name="password"]').type('chair123')
    cy.get('button[type="submit"]').click()
    
    cy.visit('http://localhost/system/admin/chair_applicants.php')
    cy.get('select[name="program"]').select('BSIT')
    cy.get('button[name="filter_applicants"]').click()
    
    cy.get('.applicant-list').should('be.visible')
    cy.get('.applicant-list').should('contain', 'List of BSIT applicants displayed')
  })

  // TC-060: Final admission approval
  it('TC-060: Should allow final admission approval', () => {
    cy.visit('http://localhost/system/admin/chair_applicants.php')
    
    cy.get('button[name="approve_applicant"]').first().click()
    cy.get('button[name="confirm_approval"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Admission status changed to \'Accepted\'')
  })
})


