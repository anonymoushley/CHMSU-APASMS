describe('Login Module Tests', () => {
  beforeEach(() => {
    cy.visit('http://localhost/system/students/login.php')
  })

  // TC-006: Successful login
  it('TC-006: Should successfully login with valid credentials', () => {
    const testCredentials = {
      email: 'test@example.com',
      password: 'Test123!'
    }

    cy.get('input[name="email"]').type(testCredentials.email)
    cy.get('input[name="password"]').type(testCredentials.password)
    cy.get('button[type="submit"]').click()

    cy.url().should('include', 'dashboard.php')
    cy.get('.welcome-message').should('exist')
  })

  // TC-007: Login with wrong password
  it('TC-007: Should show error for incorrect password', () => {
    cy.get('input[name="email"]').type('test@example.com')
    cy.get('input[name="password"]').type('wrongpassword')
    cy.get('button[type="submit"]').click()

    cy.get('.alert-danger').should('be.visible')
    cy.get('.alert-danger').should('contain', 'Incorrect password')
  })

  // TC-008: Login with unregistered email
  it('TC-008: Should show error for unregistered email', () => {
    cy.get('input[name="email"]').type('unknown@email.com')
    cy.get('input[name="password"]').type('anypassword')
    cy.get('button[type="submit"]').click()

    cy.get('.alert-danger').should('be.visible')
    cy.get('.alert-danger').should('contain', 'Account not found')
  })

  // TC-009: Empty login fields
  it('TC-009: Should prompt user to fill required fields', () => {
    cy.get('button[type="submit"]').click()

    cy.get('.alert-danger').should('be.visible')
    cy.get('.alert-danger').should('contain', 'fill required fields')
  })

  // TC-010: Password visibility toggle
  it('TC-010: Should toggle password visibility', () => {
    const password = 'TestPassword123'
    
    cy.get('input[name="password"]').type(password)
    cy.get('input[name="password"]').should('have.attr', 'type', 'password')
    
    // Click eye icon to show password
    cy.get('.password-toggle').click()
    cy.get('input[name="password"]').should('have.attr', 'type', 'text')
    
    // Click again to hide password
    cy.get('.password-toggle').click()
    cy.get('input[name="password"]').should('have.attr', 'type', 'password')
  })
})


