<?php

namespace Nexa\Testing;

use Nexa\Logging\Logger;

class TestRunner
{
    private $logger;
    private $testClasses = [];
    private $results = [];
    private $startTime;
    private $endTime;
    private $verbose = false;
    
    public function __construct($verbose = false)
    {
        // $this->logger = new Logger(); // Commenté car Logger n'existe pas encore
        $this->verbose = $verbose;
    }

    /**
     * Add a test class to run
     */
    public function addTestClass($className)
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Test class '$className' not found");
        }
        
        if (!is_subclass_of($className, TestCase::class)) {
            throw new \InvalidArgumentException("Class '$className' must extend TestCase");
        }
        
        $this->testClasses[] = $className;
    }

    /**
     * Discover test classes in a directory
     */
    public function discoverTests($directory, $namespace = '')
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException("Directory '$directory' not found");
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $className = $namespace . '\\' . str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relativePath);
                
                if (class_exists($className) && is_subclass_of($className, TestCase::class)) {
                    $this->addTestClass($className);
                }
            }
        }
    }

    /**
     * Run all tests
     */
    public function run()
    {
        $this->startTime = microtime(true);
        $this->results = [
            'classes' => [],
            'summary' => [
                'total_classes' => 0,
                'total_tests' => 0,
                'passed' => 0,
                'failed' => 0,
                'assertions' => 0,
                'time' => 0
            ]
        ];
        
        $this->output("\n" . str_repeat('=', 60));
        $this->output("NEXA FRAMEWORK TEST RUNNER");
        $this->output(str_repeat('=', 60) . "\n");
        
        foreach ($this->testClasses as $className) {
            $this->runTestClass($className);
        }
        
        $this->endTime = microtime(true);
        $this->results['summary']['time'] = round($this->endTime - $this->startTime, 3);
        
        $this->displaySummary();
        
        return $this->results;
    }

    /**
     * Run tests for a specific class
     */
    private function runTestClass($className)
    {
        $this->output("Running tests for: $className");
        
        $testInstance = new $className();
        $testMethods = $testInstance->getTestMethods();
        
        $classResults = [
            'class' => $className,
            'tests' => [],
            'summary' => [
                'total' => count($testMethods),
                'passed' => 0,
                'failed' => 0,
                'assertions' => 0
            ]
        ];
        
        // Run setUpBeforeClass if it exists
        if (method_exists($className, 'setUpBeforeClass')) {
            $className::setUpBeforeClass();
        }
        
        foreach ($testMethods as $method) {
            $result = $this->runSingleTest($testInstance, $method);
            $classResults['tests'][$method] = $result;
            
            if ($result['status'] === 'passed') {
                $classResults['summary']['passed']++;
                $this->results['summary']['passed']++;
                $this->output("  ✓ $method", 'green');
            } else {
                $classResults['summary']['failed']++;
                $this->results['summary']['failed']++;
                $this->output("  ✗ $method", 'red');
                
                if ($this->verbose && !empty($result['message'])) {
                    $this->output("    Error: " . $result['message'], 'red');
                }
            }
            
            $classResults['summary']['assertions'] += $result['assertions'];
            $this->results['summary']['assertions'] += $result['assertions'];
        }
        
        // Run tearDownAfterClass if it exists
        if (method_exists($className, 'tearDownAfterClass')) {
            $className::tearDownAfterClass();
        }
        
        $this->results['classes'][] = $classResults;
        $this->results['summary']['total_classes']++;
        $this->results['summary']['total_tests'] += count($testMethods);
        
        $this->output("");
    }

    /**
     * Run a single test method
     */
    private function runSingleTest($testInstance, $method)
    {
        try {
            return $testInstance->runTest($method);
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'assertions' => 0,
                'failures' => [],
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    /**
     * Display test summary
     */
    private function displaySummary()
    {
        $summary = $this->results['summary'];
        
        $this->output(str_repeat('=', 60));
        $this->output("TEST SUMMARY");
        $this->output(str_repeat('=', 60));
        
        $this->output("Classes: {$summary['total_classes']}");
        $this->output("Tests: {$summary['total_tests']}");
        $this->output("Assertions: {$summary['assertions']}");
        $this->output("Time: {$summary['time']} seconds");
        $this->output("");
        
        if ($summary['failed'] === 0) {
            $this->output("✓ All tests passed ({$summary['passed']} tests)", 'green');
        } else {
            $this->output("✗ {$summary['failed']} test(s) failed, {$summary['passed']} passed", 'red');
            
            if ($this->verbose) {
                $this->displayFailures();
            }
        }
        
        $this->output("");
    }

    /**
     * Display detailed failure information
     */
    private function displayFailures()
    {
        $this->output("\nFAILURES:");
        $this->output(str_repeat('-', 40));
        
        foreach ($this->results['classes'] as $classResult) {
            foreach ($classResult['tests'] as $testName => $testResult) {
                if ($testResult['status'] === 'failed') {
                    $this->output("\n{$classResult['class']}::{$testName}", 'red');
                    
                    if (!empty($testResult['message'])) {
                        $this->output("  Message: {$testResult['message']}");
                    }
                    
                    if (!empty($testResult['failures'])) {
                        foreach ($testResult['failures'] as $failure) {
                            $this->output("  Failure: {$failure['message']} (line {$failure['line']})");
                        }
                    }
                    
                    if (!empty($testResult['trace']) && $this->verbose) {
                        $this->output("  Stack trace:");
                        $lines = explode("\n", $testResult['trace']);
                        foreach (array_slice($lines, 0, 5) as $line) {
                            $this->output("    $line");
                        }
                    }
                }
            }
        }
    }

    /**
     * Output text with optional color
     */
    private function output($text, $color = null)
    {
        $colors = [
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'blue' => "\033[34m",
            'reset' => "\033[0m"
        ];
        
        if ($color && isset($colors[$color])) {
            echo $colors[$color] . $text . $colors['reset'] . "\n";
        } else {
            echo $text . "\n";
        }
    }

    /**
     * Generate test report in various formats
     */
    public function generateReport($format = 'json', $outputFile = null)
    {
        switch ($format) {
            case 'json':
                $report = json_encode($this->results, JSON_PRETTY_PRINT);
                break;
                
            case 'xml':
                $report = $this->generateXmlReport();
                break;
                
            case 'html':
                $report = $this->generateHtmlReport();
                break;
                
            default:
                throw new \InvalidArgumentException("Unsupported report format: $format");
        }
        
        if ($outputFile) {
            file_put_contents($outputFile, $report);
            $this->output("Report saved to: $outputFile");
        }
        
        return $report;
    }

    /**
     * Generate XML report (JUnit format)
     */
    private function generateXmlReport()
    {
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $testsuites = $xml->createElement('testsuites');
        $testsuites->setAttribute('tests', $this->results['summary']['total_tests']);
        $testsuites->setAttribute('failures', $this->results['summary']['failed']);
        $testsuites->setAttribute('time', $this->results['summary']['time']);
        
        foreach ($this->results['classes'] as $classResult) {
            $testsuite = $xml->createElement('testsuite');
            $testsuite->setAttribute('name', $classResult['class']);
            $testsuite->setAttribute('tests', $classResult['summary']['total']);
            $testsuite->setAttribute('failures', $classResult['summary']['failed']);
            
            foreach ($classResult['tests'] as $testName => $testResult) {
                $testcase = $xml->createElement('testcase');
                $testcase->setAttribute('name', $testName);
                $testcase->setAttribute('classname', $classResult['class']);
                
                if ($testResult['status'] === 'failed') {
                    $failure = $xml->createElement('failure');
                    $failure->setAttribute('message', $testResult['message'] ?? 'Test failed');
                    $failure->nodeValue = $testResult['trace'] ?? '';
                    $testcase->appendChild($failure);
                }
                
                $testsuite->appendChild($testcase);
            }
            
            $testsuites->appendChild($testsuite);
        }
        
        $xml->appendChild($testsuites);
        return $xml->saveXML();
    }

    /**
     * Generate HTML report
     */
    private function generateHtmlReport()
    {
        $summary = $this->results['summary'];
        $status = $summary['failed'] === 0 ? 'passed' : 'failed';
        
        $html = "<!DOCTYPE html>
<html>
<head>
    <title>Nexa Framework Test Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f5f5f5; padding: 20px; border-radius: 5px; }
        .summary { margin: 20px 0; }
        .passed { color: green; }
        .failed { color: red; }
        .test-class { margin: 20px 0; border: 1px solid #ddd; border-radius: 5px; }
        .test-class-header { background: #f9f9f9; padding: 10px; font-weight: bold; }
        .test-method { padding: 10px; border-bottom: 1px solid #eee; }
        .test-method:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>Nexa Framework Test Report</h1>
        <p>Generated on: " . date('Y-m-d H:i:s') . "</p>
    </div>
    
    <div class='summary'>
        <h2>Summary</h2>
        <p><strong>Status:</strong> <span class='$status'>" . ucfirst($status) . "</span></p>
        <p><strong>Classes:</strong> {$summary['total_classes']}</p>
        <p><strong>Tests:</strong> {$summary['total_tests']}</p>
        <p><strong>Passed:</strong> <span class='passed'>{$summary['passed']}</span></p>
        <p><strong>Failed:</strong> <span class='failed'>{$summary['failed']}</span></p>
        <p><strong>Assertions:</strong> {$summary['assertions']}</p>
        <p><strong>Time:</strong> {$summary['time']} seconds</p>
    </div>";
        
        foreach ($this->results['classes'] as $classResult) {
            $html .= "<div class='test-class'>
                <div class='test-class-header'>{$classResult['class']}</div>";
            
            foreach ($classResult['tests'] as $testName => $testResult) {
                $statusClass = $testResult['status'] === 'passed' ? 'passed' : 'failed';
                $statusIcon = $testResult['status'] === 'passed' ? '✓' : '✗';
                
                $html .= "<div class='test-method'>
                    <span class='$statusClass'>$statusIcon $testName</span>";
                
                if ($testResult['status'] === 'failed' && !empty($testResult['message'])) {
                    $html .= "<br><small>Error: " . htmlspecialchars($testResult['message']) . "</small>";
                }
                
                $html .= "</div>";
            }
            
            $html .= "</div>";
        }
        
        $html .= "</body></html>";
        
        return $html;
    }

    /**
     * Get test results
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Check if all tests passed
     */
    public function allTestsPassed()
    {
        return $this->results['summary']['failed'] === 0;
    }
}