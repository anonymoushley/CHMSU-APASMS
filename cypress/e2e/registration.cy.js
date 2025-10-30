describe('Registration Module Tests', () => {
  beforeEach(() => {
    cy.visit('http://localhost/system/register.php')
  })

  // TC-001: New applicant registration
  it('TC-001: Should successfully register new applicant with valid data', () => {
    const timestamp = Date.now()
    const testData = {
      firstName: 'John',
      lastName: 'Doe',
      email: `test${timestamp}@example.com`,
      applicantType: 'student'
    }

    cy.get('input[name="firstname"]').type(testData.firstName)
    cy.get('input[name="lastname"]').type(testData.lastName)
    cy.get('input[name="email"]').type(testData.email)
    cy.get('select[name="applicant_type"]').select(testData.applicantType)
    cy.get('button[type="submit"]').click()

    // Verify account creation and email sent
    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Account created')
    cy.url().should('include', 'login.php')
  })

  // TC-002: Registration with missing fields
  it('TC-002: Should show error when required fields are missing', () => {
    cy.get('input[name="firstname"]').type('John')
    // Leave email field blank
    cy.get('button[type="submit"]').click()

    cy.get('.alert-danger').should('be.visible')
    cy.get('.alert-danger').should('contain', 'Required field')
  })

  // TC-003: Duplicate email registration
  it('TC-003: Should prevent duplicate email registration', () => {
    const existingEmail = 'existing@example.com'
    
    cy.get('input[name="firstname"]').type('Jane')
    cy.get('input[name="lastname"]').type('Smith')
    cy.get('input[name="email"]').type(existingEmail)
    cy.get('button[type="submit"]').click()

    cy.get('.alert-danger').should('be.visible')
    cy.get('.alert-danger').should('contain', 'Email already registered')
  })

  // TC-004: Invalid email format
  it('TC-004: Should validate email format', () => {
    cy.get('input[name="firstname"]').type('Test')
    cy.get('input[name="lastname"]').type('User')
    cy.get('input[name="email"]').type('ash123@')
    cy.get('button[type="submit"]').click()

    cy.get('.alert-danger').should('be.visible')
    cy.get('.alert-danger').should('contain', 'Invalid email format')
  })

  // TC-005: Registration with all valid inputs
  it('TC-005: Should complete registration with all valid inputs', () => {
    const timestamp = Date.now()
    const testData = {
      firstName: 'Alice',
      lastName: 'Johnson',
      email: `alice${timestamp}@example.com`,
      applicantType: 'student',
      password: 'Test123!',
      confirmPassword: 'Test123!'
    }

    cy.get('input[name="firstname"]').type(testData.firstName)
    cy.get('input[name="lastname"]').type(testData.lastName)
    cy.get('input[name="email"]').type(testData.email)
    cy.get('select[name="applicant_type"]').select(testData.applicantType)
    cy.get('input[name="password"]').type(testData.password)
    cy.get('input[name="confirm_password"]').type(testData.confirmPassword)
    cy.get('button[type="submit"]').click()

    cy.get('.alert-success').should('be.visible')
    cy.get('.alert-success').should('contain', 'Registration successful')
  })
})


