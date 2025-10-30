#!/usr/bin/env node

/**
 * CHMSU APASMS Test Runner
 * Executes Cypress tests and generates detailed reports with actual results and pass/fail status
 */

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

class TestRunner {
  constructor() {
    this.results = [];
    this.startTime = null;
    this.endTime = null;
  }

  startExecution() {
    this.startTime = new Date();
    console.log('\n🚀 Starting CHMSU APASMS Test Suite Execution');
    console.log('=' .repeat(80));
    console.log(`📅 Execution started at: ${this.startTime.toLocaleString()}`);
    console.log('=' .repeat(80));
  }

  async runTests() {
    try {
      // Run Cypress tests
      console.log('\n📋 Executing Cypress Tests...');
      console.log('-'.repeat(50));
      
      const command = 'npx cypress run --spec "cypress/e2e/test-cases-execution.cy.js" --reporter json --reporter-options output=test-results.json';
      
      execSync(command, { 
        stdio: 'inherit',
        cwd: process.cwd()
      });
      
      console.log('\n✅ Test execution completed successfully!');
      
    } catch (error) {
      console.log('\n❌ Test execution failed:');
      console.log(error.message);
      throw error;
    }
  }

  generateDetailedReport() {
    this.endTime = new Date();
    const totalExecutionTime = this.endTime - this.startTime;
    
    console.log('\n' + '='.repeat(100));
    console.log('📊 CHMSU APASMS - DETAILED TEST EXECUTION REPORT');
    console.log('='.repeat(100));
    
    // Read Cypress results if available
    let cypressResults = null;
    try {
      if (fs.existsSync('test-results.json')) {
        cypressResults = JSON.parse(fs.readFileSync('test-results.json', 'utf8'));
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
      
      // Failed Tests Analysis
      if (failedTests > 0) {
        console.log('\n❌ FAILED TESTS ANALYSIS:');
        console.log('-' .repeat(50));
        
        cypressResults.results.runs.forEach(run => {
          if (run.tests) {
            run.tests.filter(test => test.state === 'failed').forEach(test => {
              console.log(`\n🔴 ${test.title}`);
              if (test.err) {
                console.log(`   Error: ${test.err.message}`);
                console.log(`   Stack: ${test.err.stack}`);
              }
            });
          }
        });
      }
    }
    
    // Recommendations
    console.log('\n💡 RECOMMENDATIONS:');
    console.log('-' .repeat(50));
    
    if (cypressResults) {
      const passRate = cypressResults.stats.tests > 0 ? 
        ((cypressResults.stats.passes / cypressResults.stats.tests) * 100) : 0;
      
      if (passRate >= 90) {
        console.log('🎉 Excellent! Test suite is performing very well.');
      } else if (passRate >= 80) {
        console.log('👍 Good performance, but some improvements needed.');
      } else if (passRate >= 70) {
        console.log('⚠️  Moderate performance, significant improvements required.');
      } else {
        console.log('🚨 Poor performance, immediate attention required.');
      }
      
      if (cypressResults.stats.failures > 0) {
        console.log('🔧 Review failed tests and fix underlying issues.');
        console.log('📝 Check test data and application state before test execution.');
      }
    }
    
    console.log('\n📄 REPORTS GENERATED:');
    console.log('-' .repeat(50));
    console.log('📊 JSON Report: test-results.json');
    console.log('📈 HTML Report: cypress/reports/index.html (if configured)');
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
    const report = this.generateDetailedReport();
    
    // Export to JSON
    const reportData = {
      summary: {
        executionTime: report.executionTime,
        startTime: report.startTime,
        endTime: report.endTime
      },
      cypressResults: report.cypressResults,
      generatedAt: new Date().toISOString()
    };
    
    fs.writeFileSync('execution-report.json', JSON.stringify(reportData, null, 2));
    console.log('\n📄 Execution report exported to: execution-report.json');
  }
}

// Main execution
async function main() {
  const runner = new TestRunner();
  
  try {
    runner.startExecution();
    await runner.runTests();
    runner.exportReport();
    
    console.log('\n🎉 Test execution completed successfully!');
    process.exit(0);
    
  } catch (error) {
    console.log('\n💥 Test execution failed:');
    console.log(error.message);
    process.exit(1);
  }
}

// Run if called directly
if (require.main === module) {
  main();
}

module.exports = TestRunner;

