# CHMSU APASMS - Cypress Test Suite

This repository contains a comprehensive Cypress test suite for the CHMSU APASMS (Admission Processing and Student Management System) based on the provided test cases.

## 📋 Test Coverage

The test suite covers all 115 test cases from your `testCases.js` file, organized by modules:

- **Registration Module** (TC-001 to TC-010)
- **Login Module** (TC-011 to TC-020)  
- **Applicant Profiling** (TC-021 to TC-045)
- **Document Upload** (TC-046 to TC-055)
- **Entrance Exam** (TC-056 to TC-075)
- **Interview Evaluation** (TC-076 to TC-090)
- **Stanine Input** (TC-091 to TC-095)
- **Plus Factors** (TC-096 to TC-100)
- **Reporting** (TC-101 to TC-115)

## 🚀 Quick Start

### Prerequisites
- Node.js (v14 or higher)
- XAMPP server running
- CHMSU APASMS system accessible at `http://localhost/system`

### Installation
```bash
# Install dependencies
npm install

# Install Cypress (if not already installed)
npx cypress install
```

## 🧪 Running Tests

### Basic Commands

```bash
# Run all tests
npm test

# Run specific test cases from your testCases.js
npm run test:cases

# Run complete test suite
npm run test:complete

# Open Cypress Test Runner (Interactive Mode)
npm run test:open
```

### Module-Specific Tests

```bash
# Registration tests only
npm run test:registration

# Login tests only
npm run test:login

# Profiling tests only
npm run test:profiling

# Exam management tests only
npm run test:exam

# Interview tests only
npm run test:interview

# Admin management tests only
npm run test:admin

# Additional validation tests only
npm run test:validation
```

### Advanced Options

```bash
# Run with browser visible (headed mode)
npm run test:headed

# Run in specific browser
npm run test:chrome
npm run test:firefox

# Generate JSON report
npm run test:json

# Generate HTML report
npm run test:html

# Run with detailed reporting
npm run test:report
```

## 📊 Test Results & Reporting

### Console Output
Each test execution provides detailed console output including:
- ✅ **PASS** / ❌ **FAIL** status for each test case
- Expected vs Actual results
- Error messages for failed tests
- Execution time and performance metrics

### Generated Reports
- **JSON Report**: `test-results.json` - Machine-readable test results
- **Execution Report**: `execution-report.json` - Detailed execution analysis
- **HTML Report**: `cypress/reports/index.html` - Visual test report

### Sample Output
```
✅ TC-001: Applicant Registration - Test Case 1
   Expected: Expected result 1 for Applicant Registration verified successfully.
   Actual: Registration successful, account created

❌ TC-002: Applicant Registration - Test Case 2
   Expected: Expected result 2 for Applicant Registration verified successfully.
   Actual: Test execution failed
   Error: Element not found: input[name="lastname"]

📊 SUMMARY STATISTICS:
Total Tests: 115
✅ Passed: 108
❌ Failed: 7
📈 Pass Rate: 93.91%
```

## 🔧 Configuration

### Cypress Configuration (`cypress.config.js`)
```javascript
module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost/system',
    viewportWidth: 1280,
    viewportHeight: 720,
    defaultCommandTimeout: 10000,
    video: true,
    screenshotOnRunFailure: true
  }
});
```

### Test Data
Test fixtures are located in `cypress/fixtures/`:
- `sample.pdf` - Sample document for upload tests
- `grade11_1st_sem.pdf` - Grade 11 report card
- `invalid_file.exe` - Invalid file type for testing

## 📁 File Structure

```
cypress/
├── e2e/
│   ├── test-cases-execution.cy.js    # Main test suite based on testCases.js
│   ├── complete-test-suite.cy.js    # Comprehensive test suite
│   ├── registration.cy.js           # Registration module tests
│   ├── login.cy.js                   # Login module tests
│   ├── profiling.cy.js               # Profiling module tests
│   ├── exam-management.cy.js         # Exam management tests
│   ├── interview.cy.js               # Interview evaluation tests
│   ├── admin-management.cy.js       # Admin management tests
│   └── additional-validation.cy.js   # Additional validation tests
├── fixtures/                         # Test data files
├── support/
│   └── test-reporter.js             # Test reporting utilities
└── config.js                        # Cypress configuration
```

## 🐛 Troubleshooting

### Common Issues

1. **Server Not Running**
   ```
   Error: ECONNREFUSED
   Solution: Ensure XAMPP is running and system is accessible at http://localhost/system
   ```

2. **Element Not Found**
   ```
   Error: Element not found: input[name="email"]
   Solution: Check if the form elements exist and have correct names
   ```

3. **Test Timeout**
   ```
   Error: Command timeout
   Solution: Increase timeout in cypress.config.js or check application performance
   ```

### Debug Mode
```bash
# Run with debug output
DEBUG=cypress:* npm run test:cases

# Run specific test with verbose output
npx cypress run --spec "cypress/e2e/test-cases-execution.cy.js" --headed
```

## 📈 Performance Monitoring

The test suite includes performance monitoring:
- Individual test execution times
- Total suite execution time
- Pass/fail rates by module
- Performance recommendations

## 🔄 Continuous Integration

For CI/CD integration:
```bash
# Run tests in CI environment
npm run test:json

# Generate reports for CI
npm run test:report
```

## 📝 Customization

### Adding New Test Cases
1. Add test case data to `testCases` array in `test-cases-execution.cy.js`
2. Implement test logic in the appropriate switch case
3. Update the test case ID range filters

### Modifying Test Data
1. Update test data in `cypress/fixtures/`
2. Modify test data references in test files
3. Ensure test data matches application requirements

## 📞 Support

For issues or questions:
1. Check the troubleshooting section above
2. Review Cypress documentation: https://docs.cypress.io
3. Check application logs for server-side issues

---

**Happy Testing! 🎉**

