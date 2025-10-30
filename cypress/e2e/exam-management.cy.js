describe('Exam Management Tests', () => {
  beforeEach(() => {
    // Login as admin
    cy.visit('http://localhost/system/admin/admin_login.php')
    cy.get('input[name="email"]').type('admin@example.com')
    cy.get('input[name="password"]').type('admin123')
    cy.get('button[type="submit"]').click()
    cy.visit('http://localhost/system/admin/exam-management.php')
  })

  // TC-023: Create new exam version
  it('TC-023: Should create new exam version', () => {
    cy.get('input[name="exam_title"]').type('Entrance Exam 2025')
    cy.get('input[name="exam_year"]').type('2025')
    cy.get('textarea[name="description"]').type('Annual entrance examination for 2025')
    
    cy.get('button[name="create_exam"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'New exam version added')
  })

  // TC-024: Edit exam questions
  it('TC-024: Should edit exam questions', () => {
    cy.get('a[href*="update_question"]').first().click()
    
    cy.get('textarea[name="question_text"]').clear().type('What is the capital of the Philippines?')
    cy.get('input[name="option_a"]').clear().type('Manila')
    cy.get('input[name="option_b"]').clear().type('Cebu')
    cy.get('input[name="option_c"]').clear().type('Davao')
    cy.get('input[name="option_d"]').clear().type('Quezon City')
    cy.get('select[name="correct_answer"]').select('A')
    
    cy.get('button[name="save_question"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Changes saved successfully')
  })

  // TC-025: Publish exam version
  it('TC-025: Should publish exam version', () => {
    cy.get('select[name="exam_version"]').select('2025')
    cy.get('button[name="publish_exam"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Exam visible to applicants')
  })

  // TC-026: Unpublish exam version
  it('TC-026: Should unpublish exam version', () => {
    cy.get('select[name="published_exam"]').select('2025')
    cy.get('button[name="unpublish_exam"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Exam hidden from applicants')
  })

  // TC-027: Randomize questions
  it('TC-027: Should randomize question order', () => {
    cy.get('input[name="randomize_questions"]').check()
    cy.get('button[name="save_settings"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Randomize=True')
  })
})

describe('Entrance Exam Tests', () => {
  beforeEach(() => {
    // Login as student
    cy.login('test@example.com', 'Test123!')
    cy.visit('http://localhost/system/students/exam_list.php')
  })

  // TC-028: Applicant starts exam
  it('TC-028: Should start exam and begin timer', () => {
    cy.get('button[name="start_exam"]').click()
    
    cy.url().should('include', 'exam.php')
    cy.get('.timer').should('be.visible')
    cy.get('.timer').should('contain', 'Exam timer begins')
  })

  // TC-029: Auto-submit after timeout
  it('TC-029: Should auto-submit exam when time expires', () => {
    cy.get('button[name="start_exam"]').click()
    
    // Wait for timer to expire (this would need to be mocked in real tests)
    cy.get('.timer').should('contain', '00:00')
    cy.get('.alert-info').should('be.visible')
    cy.get('.alert-info').should('contain', 'Exam auto-submitted')
  })

  // TC-030: Manual submission
  it('TC-030: Should allow manual submission before time ends', () => {
    cy.get('button[name="start_exam"]').click()
    
    // Answer some questions
    cy.get('input[name="answer_1"]').check('A')
    cy.get('input[name="answer_2"]').check('B')
    
    cy.get('button[name="submit_exam"]').click()
    
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Answers saved')
  })

  // TC-031: View exam result
  it('TC-031: Should display exam result correctly', () => {
    cy.get('a[href*="exam_results"]').click()
    
    cy.get('.exam-score').should('be.visible')
    cy.get('.exam-score').should('contain', 'Score displayed correctly')
  })
})


