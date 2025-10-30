#!/usr/bin/env node

/**
 * CHMSU APASMS Test Runner - Specific for CHMSU_APASMS_Test_Cases.js
 * Executes Cypress tests based on your specific test cases file
 */

const { execSync } = require('child_process');
const fs = require('fs');

class CHMSUTestRunner {
  constructor() {
    this.startTime = null;
    this.endTime = null;
  }

  startExecution() {
    this.startTime = new Date();
    console.log('\n🚀 Starting CHMSU APASMS Test Cases Execution');
    console.log('=' .repeat(80));
    console.log(`📅 Execution started at: ${this.startTime.toLocaleString()}`);
    console.log(`📁 Testing file: CHMSU_APASMS_Test_Cases.js`);
    console.log('=' .repeat(80));
  }

  async runTests() {
    try {
      // Run Cypress tests for CHMSU test cases
      console.log('\n📋 Executing CHMSU Test Cases...');
      console.log('-'.repeat(50));
      
      const command = 'npx cypress run --spec "cypress/e2e/chmsu-test-cases.cy.js" --reporter json --reporter-options output=chmsu-test-results.json';
      
      execSync(command, { 
        stdio: 'inherit',
        cwd: process.cwd()
      });
      
      console.log('\n✅ CHMSU test execution completed successfully!');
      
    } catch (error) {
      console.log('\n❌ CHMSU test execution failed:');
      console.log(error.message);
      throw error;
    }
  }

  generateReport() {
    this.endTime = new Date();
    const totalExecutionTime = this.endTime - this.startTime;
    
    console.log('\n' + '='.repeat(100));
    console.log('📊 CHMSU APASMS - TEST CASES EXECUTION REPORT');
    console.log('='.repeat(100));
    
    // Read Cypress results if available
    let cypressResults = null;
    try {
      if (fs.existsSync('chmsu-test-results.json')) {
        cypressResults = JSON.parse(fs.readFileSync('chmsu-test-results.json', 'utf8'));
      }
    } catch (error) {
      console.log('⚠️  Could not read Cypress results file');
    }
    
    // Summary Statistics
    console.log('\n📈 SUMMARY STATISTICS:');
    console.log('-' .repeat(50));
    console.log(`⏱️  Total Execution Time: ${(totalExecutionTime / 1000).toFixed(2)} seconds`);
    console.log(`📅 Execution Date: ${this.startTime.toLocaleString()}`);
    console.log(`📅 Completion Date: ${this.endTime.toLocaleString()}`);
    console.log(`📁 Test File: CHMSU_APASMS_Test_Cases.js`);
    
    if (cypressResults) {
      const totalTests = cypressResults.stats.tests || 0;
      const passedTests = cypressResults.stats.passes || 0;
      const failedTests = cypressResults.stats.failures || 0;
      const passRate = totalTests > 0 ? ((passedTests / totalTests) * 100).toFixed(2) : 0;
      
      console.log(`📊 Total Tests Executed: ${totalTests}`);
      console.log(`✅ Passed: ${passedTests}`);
      console.log(`❌ Failed: ${failedTests}`);
      console.log(`📈 Pass Rate: ${passRate}%`);
      
      // Test Results Details
      if (cypressResults.results && cypressResults.results.runs) {
        console.log('\n📋 DETAILED TEST RESULTS:');
        console.log('-' .repeat(50));
        
        cypressResults.results.runs.forEach((run, runIndex) => {
          console.log(`\n🔍 Test Run ${runIndex + 1}:`);
          console.log(`   Spec: ${run.spec.name}`);
          console.log(`   Duration: ${run.stats.duration}ms`);
          
          if (run.tests) {
            run.tests.forEach(test => {
              const status = test.state === 'passed' ? '✅ PASS' : '❌ FAIL';
              console.log(`   ${status}: ${test.title}`);
              
              if (test.state === 'failed' && test.err) {
                console.log(`      Error: ${test.err.message}`);
              }
            });
          }
        });
      }
    }
    
    // Recommendations
    console.log('\n💡 RECOMMENDATIONS:');
    console.log('-' .repeat(50));
    console.log('📝 To add more test cases:');
    console.log('   1. Edit CHMSU_APASMS_Test_Cases.js');
    console.log('   2. Add new test case objects to the testCases array');
    console.log('   3. Run: npm run test:chmsu');
    console.log('   4. Check results in console and chmsu-test-results.json');
    
    console.log('\n📄 REPORTS GENERATED:');
    console.log('-' .repeat(50));
    console.log('📊 JSON Report: chmsu-test-results.json');
    console.log('📋 Console Report: Above detailed analysis');
    
    console.log('\n' + '='.repeat(100));
    
    return {
      executionTime: totalExecutionTime,
      startTime: this.startTime,
      endTime: this.endTime,
      cypressResults: cypressResults
    };
  }

  exportReport() {
    const report = this.generateReport();
    
    // Export to JSON
    const reportData = {
      summary: {
        executionTime: report.executionTime,
        startTime: report.startTime,
        endTime: report.endTime,
        testFile: 'CHMSU_APASMS_Test_Cases.js'
      },
      cypressResults: report.cypressResults,
      generatedAt: new Date().toISOString()
    };
    
    fs.writeFileSync('chmsu-execution-report.json', JSON.stringify(reportData, null, 2));
    console.log('\n📄 CHMSU execution report exported to: chmsu-execution-report.json');
  }
}

// Main execution
async function main() {
  const runner = new CHMSUTestRunner();
  
  try {
    runner.startExecution();
    await runner.runTests();
    runner.exportReport();
    
    console.log('\n🎉 CHMSU test execution completed successfully!');
    process.exit(0);
    
  } catch (error) {
    console.log('\n💥 CHMSU test execution failed:');
    console.log(error.message);
    process.exit(1);
  }
}

// Run if called directly
if (require.main === module) {
  main();
}

module.exports = CHMSUTestRunner;

