describe('Interviewer Management Tests', () => {
  beforeEach(() => {
    // Login as chairperson
    cy.visit('http://localhost/system/admin/chair_login.php')
    cy.get('input[name="email"]').type('chair@example.com')
    cy.get('input[name="password"]').type('chair123')
    cy.get('button[type="submit"]').click()
    cy.visit('http://localhost/system/admin/interviewers.php')
  })

  // TC-032: Add new interviewer
  it('TC-032: Should add new interviewer successfully', () => {
    cy.get('input[name="name"]').type('Dr. Maria Santos')
    cy.get('input[name="email"]').type('maria.santos@example.com')
    cy.get('input[name="contact"]').type('09123456789')
    cy.get('select[name="program"]').select('BSIT')
    
    cy.get('button[name="add_interviewer"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Interviewer added successfully')
  })

  // TC-033: Edit interviewer details
  it('TC-033: Should edit interviewer details', () => {
    cy.get('a[href*="edit_interviewer"]').first().click()
    
    cy.get('input[name="contact"]').clear().type('09987654321')
    cy.get('button[name="update_interviewer"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Changes reflected')
  })

  // TC-034: Delete interviewer
  it('TC-034: Should delete interviewer record', () => {
    cy.get('button[name="delete_interviewer"]').first().click()
    cy.get('button[name="confirm_delete"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Record removed from list')
  })

  // TC-035: Duplicate interviewer prevention
  it('TC-035: Should prevent duplicate interviewer addition', () => {
    cy.get('input[name="name"]').type('Dr. Maria Santos') // Same name as existing
    cy.get('input[name="email"]').type('maria.santos@example.com')
    cy.get('button[name="add_interviewer"]').click()
    
    cy.get('.alert-warning').should('be.visible')
    cy.get('.alert-warning').should('contain', 'Already exists')
  })
})

describe('Interview Evaluation Tests', () => {
  beforeEach(() => {
    // Login as interviewer
    cy.visit('http://localhost/system/admin/interviewer_login.php')
    cy.get('input[name="email"]').type('interviewer@example.com')
    cy.get('input[name="password"]').type('interviewer123')
    cy.get('button[type="submit"]').click()
    cy.visit('http://localhost/system/admin/interviewer_applicants.php')
  })

  // TC-036: Start interview evaluation
  it('TC-036: Should start interview evaluation and save scores', () => {
    cy.get('a[href*="interview_form"]').first().click()
    
    cy.get('input[name="communication_skills"]').type('8')
    cy.get('input[name="technical_knowledge"]').type('7')
    cy.get('input[name="problem_solving"]').type('9')
    cy.get('textarea[name="comments"]').type('Good performance overall')
    
    cy.get('button[name="save_evaluation"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Evaluation saved successfully')
  })

  // TC-037: Missing evaluation field
  it('TC-037: Should prompt for missing evaluation fields', () => {
    cy.get('a[href*="interview_form"]').first().click()
    
    cy.get('input[name="communication_skills"]').type('8')
    // Leave technical_knowledge empty
    cy.get('button[name="save_evaluation"]').click()
    
    cy.get('.alert-danger').should('be.visible')
    cy.get('.alert-danger').should('contain', 'fill all fields')
  })

  // TC-038: View evaluation results
  it('TC-038: Should display evaluation results', () => {
    cy.get('a[href*="view_evaluation"]').first().click()
    
    cy.get('.evaluation-results').should('be.visible')
    cy.get('.evaluation-results').should('contain', 'Displays all criteria with scores')
  })

  // TC-039: Edit submitted evaluation
  it('TC-039: Should edit submitted evaluation', () => {
    cy.get('a[href*="edit_evaluation"]').first().click()
    
    cy.get('input[name="communication_skills"]').clear().type('9')
    cy.get('button[name="update_evaluation"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Changes saved successfully')
  })
})


