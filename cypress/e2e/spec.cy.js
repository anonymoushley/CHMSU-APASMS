describe('Student Management System Tests', () => {
  beforeEach(() => {
    // Reset state before each test
    cy.visit('http://localhost/system/students/login.php')
  })

  it('should display login page correctly', () => {
    cy.get('h2').should('contain', 'Student Login')
    cy.get('input[name="email"]').should('exist')
    cy.get('input[name="password"]').should('exist')
    cy.get('button[type="submit"]').should('exist')
  })

  it('should show error on invalid login', () => {
    cy.get('input[name="email"]').type('invalid@email.com')
    cy.get('input[name="password"]').type('wrongpassword')
    cy.get('button[type="submit"]').click()
    cy.get('.alert-danger').should('be.visible')
  })

  it('should navigate to registration page', () => {
    cy.contains('Register').click()
    cy.url().should('include', 'register.php')
    cy.get('h2').should('contain', 'Student Registration')
  })

  it('should validate registration form', () => {
    cy.contains('Register').click()
    cy.get('button[type="submit"]').click()
    cy.get('.alert-danger').should('be.visible')
  })

  it('should successfully register new student', () => {
    cy.contains('Register').click()
    cy.get('input[name="firstname"]').type('Test')
    cy.get('input[name="lastname"]').type('Student')
    cy.get('input[name="email"]').type(`test${Date.now()}@example.com`)
    cy.get('input[name="password"]').type('Test123!')
    cy.get('input[name="confirm_password"]').type('Test123!')
    cy.get('button[type="submit"]').click()
    cy.get('.alert-success').should('be.visible')
  })

  it('should successfully login and access dashboard', () => {
    const testEmail = 'test@example.com'
    const testPassword = 'Test123!'
    
    cy.get('input[name="email"]').type(testEmail)
    cy.get('input[name="password"]').type(testPassword)
    cy.get('button[type="submit"]').click()
    
    cy.url().should('include', 'dashboard.php')
    cy.get('.welcome-message').should('exist')
  })

  it('should allow profile update', () => {
    // Login first
    cy.login(testEmail, testPassword) // Create a custom command for login

    cy.contains('Profile').click()
    cy.get('input[name="phone"]').clear().type('1234567890')
    cy.get('button[type="submit"]').click()
    cy.get('.alert-success').should('be.visible')
  })

  it('should allow password change', () => {
    // Login first
    cy.login(testEmail, testPassword)

    cy.contains('Change Password').click()
    cy.get('input[name="current_password"]').type(testPassword)
    cy.get('input[name="new_password"]').type('NewTest123!')
    cy.get('input[name="confirm_new_password"]').type('NewTest123!')
    cy.get('button[type="submit"]').click()
    cy.get('.alert-success').should('be.visible')
  })

  it('should allow logout', () => {
    // Login first
    cy.login(testEmail, testPassword)

    cy.contains('Logout').click()
    cy.url().should('include', 'login.php')
  })
})

// Add custom commands to support tests
Cypress.Commands.add('login', (email, password) => {
  cy.visit('http://localhost/system/students/login.php')
  cy.get('input[name="email"]').type(email)
  cy.get('input[name="password"]').type(password)
  cy.get('button[type="submit"]').click()
})