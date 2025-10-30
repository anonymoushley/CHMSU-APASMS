// Test Results Reporter
class TestResultsReporter {
  constructor() {
    this.results = []
    this.startTime = null
    this.endTime = null
  }

  startTestSuite() {
    this.startTime = new Date()
    console.log('\n🚀 Starting CHMSU APASMS Test Suite Execution')
    console.log('=' .repeat(80))
  }

  addResult(testCase, status, actualResult, error = null, executionTime = null) {
    const result = {
      id: testCase.id,
      scenario: testCase.scenario,
      preConditions: testCase.preConditions,
      steps: testCase.steps,
      testData: testCase.testData,
      expected: testCase.expected,
      actualResult: actualResult,
      status: status, // 'PASS' or 'FAIL'
      error: error,
      executionTime: executionTime,
      timestamp: new Date().toISOString()
    }
    
    this.results.push(result)
    
    // Log individual test result
    const statusIcon = status === 'PASS' ? '✅' : '❌'
    console.log(`\n${statusIcon} ${testCase.id}: ${testCase.scenario}`)
    console.log(`   Expected: ${testCase.expected}`)
    console.log(`   Actual: ${actualResult}`)
    if (error) console.log(`   Error: ${error}`)
    if (executionTime) console.log(`   Execution Time: ${executionTime}ms`)
  }

  generateDetailedReport() {
    this.endTime = new Date()
    const totalExecutionTime = this.endTime - this.startTime
    
    const totalTests = this.results.length
    const passedTests = this.results.filter(r => r.status === 'PASS').length
    const failedTests = this.results.filter(r => r.status === 'FAIL').length
    const passRate = ((passedTests / totalTests) * 100).toFixed(2)
    
    console.log('\n' + '='.repeat(100))
    console.log('📊 CHMSU APASMS - DETAILED TEST EXECUTION REPORT')
    console.log('='.repeat(100))
    
    // Summary Statistics
    console.log('\n📈 SUMMARY STATISTICS:')
    console.log('-' .repeat(50))
    console.log(`Total Tests Executed: ${totalTests}`)
    console.log(`✅ Passed: ${passedTests}`)
    console.log(`❌ Failed: ${failedTests}`)
    console.log(`📊 Pass Rate: ${passRate}%`)
    console.log(`⏱️  Total Execution Time: ${(totalExecutionTime / 1000).toFixed(2)} seconds`)
    console.log(`📅 Execution Date: ${this.startTime.toLocaleString()}`)
    
    // Module-wise breakdown
    console.log('\n📋 MODULE-WISE BREAKDOWN:')
    console.log('-' .repeat(50))
    
    const modules = {
      'Registration': this.results.filter(r => r.id.startsWith('TC-00') && parseInt(r.id.split('-')[1]) <= 10),
      'Login': this.results.filter(r => r.id.startsWith('TC-0') && parseInt(r.id.split('-')[1]) >= 11 && parseInt(r.id.split('-')[1]) <= 20),
      'Profiling': this.results.filter(r => r.id.startsWith('TC-0') && parseInt(r.id.split('-')[1]) >= 21 && parseInt(r.id.split('-')[1]) <= 45),
      'Document Upload': this.results.filter(r => r.id.startsWith('TC-0') && parseInt(r.id.split('-')[1]) >= 46 && parseInt(r.id.split('-')[1]) <= 55),
      'Entrance Exam': this.results.filter(r => r.id.startsWith('TC-0') && parseInt(r.id.split('-')[1]) >= 56 && parseInt(r.id.split('-')[1]) <= 75),
      'Interview Evaluation': this.results.filter(r => r.id.startsWith('TC-0') && parseInt(r.id.split('-')[1]) >= 76 && parseInt(r.id.split('-')[1]) <= 90),
      'Stanine Input': this.results.filter(r => r.id.startsWith('TC-0') && parseInt(r.id.split('-')[1]) >= 91 && parseInt(r.id.split('-')[1]) <= 95),
      'Plus Factors': this.results.filter(r => r.id.startsWith('TC-0') && parseInt(r.id.split('-')[1]) >= 96 && parseInt(r.id.split('-')[1]) <= 100),
      'Reporting': this.results.filter(r => r.id.startsWith('TC-') && parseInt(r.id.split('-')[1]) >= 101 && parseInt(r.id.split('-')[1]) <= 115)
    }
    
    Object.entries(modules).forEach(([moduleName, moduleResults]) => {
      if (moduleResults.length > 0) {
        const modulePassed = moduleResults.filter(r => r.status === 'PASS').length
        const moduleFailed = moduleResults.filter(r => r.status === 'FAIL').length
        const modulePassRate = ((modulePassed / moduleResults.length) * 100).toFixed(1)
        console.log(`${moduleName.padEnd(20)}: ${modulePassed}/${moduleResults.length} passed (${modulePassRate}%)`)
      }
    })
    
    // Failed Tests Details
    if (failedTests > 0) {
      console.log('\n❌ FAILED TESTS DETAILS:')
      console.log('-' .repeat(50))
      this.results.filter(r => r.status === 'FAIL').forEach(result => {
        console.log(`\n🔴 ${result.id}: ${result.scenario}`)
        console.log(`   Pre-conditions: ${result.preConditions}`)
        console.log(`   Test Steps: ${result.steps}`)
        console.log(`   Test Data: ${result.testData}`)
        console.log(`   Expected: ${result.expected}`)
        console.log(`   Actual: ${result.actualResult}`)
        console.log(`   Error: ${result.error}`)
        console.log(`   Timestamp: ${result.timestamp}`)
      })
    }
    
    // Performance Analysis
    console.log('\n⚡ PERFORMANCE ANALYSIS:')
    console.log('-' .repeat(50))
    
    const executionTimes = this.results.filter(r => r.executionTime).map(r => r.executionTime)
    if (executionTimes.length > 0) {
      const avgExecutionTime = executionTimes.reduce((a, b) => a + b, 0) / executionTimes.length
      const maxExecutionTime = Math.max(...executionTimes)
      const minExecutionTime = Math.min(...executionTimes)
      
      console.log(`Average Test Execution Time: ${avgExecutionTime.toFixed(2)}ms`)
      console.log(`Fastest Test Execution Time: ${minExecutionTime}ms`)
      console.log(`Slowest Test Execution Time: ${maxExecutionTime}ms`)
    }
    
    // Recommendations
    console.log('\n💡 RECOMMENDATIONS:')
    console.log('-' .repeat(50))
    
    if (passRate >= 90) {
      console.log('🎉 Excellent! Test suite is performing very well.')
    } else if (passRate >= 80) {
      console.log('👍 Good performance, but some improvements needed.')
    } else if (passRate >= 70) {
      console.log('⚠️  Moderate performance, significant improvements required.')
    } else {
      console.log('🚨 Poor performance, immediate attention required.')
    }
    
    if (failedTests > 0) {
      console.log('🔧 Review failed tests and fix underlying issues.')
    }
    
    console.log('\n' + '='.repeat(100))
    
    return {
      total: totalTests,
      passed: passedTests,
      failed: failedTests,
      passRate: parseFloat(passRate),
      executionTime: totalExecutionTime,
      moduleBreakdown: modules,
      results: this.results
    }
  }

  exportToJSON(filename = 'test-results.json') {
    const report = this.generateDetailedReport()
    const fs = require('fs')
    fs.writeFileSync(filename, JSON.stringify(report, null, 2))
    console.log(`\n📄 Detailed report exported to: ${filename}`)
  }

  exportToCSV(filename = 'test-results.csv') {
    const csvHeader = 'ID,Scenario,PreConditions,Steps,TestData,Expected,Actual,Status,Error,ExecutionTime,Timestamp\n'
    const csvRows = this.results.map(result => 
      `"${result.id}","${result.scenario}","${result.preConditions}","${result.steps}","${result.testData}","${result.expected}","${result.actualResult}","${result.status}","${result.error || ''}","${result.executionTime || ''}","${result.timestamp}"`
    ).join('\n')
    
    const fs = require('fs')
    fs.writeFileSync(filename, csvHeader + csvRows)
    console.log(`\n📊 CSV report exported to: ${filename}`)
  }
}

// Export for use in test files
export default TestResultsReporter

