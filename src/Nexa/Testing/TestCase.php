<?php

namespace Nexa\Testing;

use Nexa\Core\Application;
use Nexa\Database\Database;
use Nexa\Http\Request;
use Nexa\Http\Response;
use Nexa\Logging\Logger;

abstract class TestCase
{
    protected $app;
    protected $db;
    protected $logger;
    protected $assertions = 0;
    protected $failures = [];
    protected $testName;
    protected $expectedExceptionClass;
    protected $expectedExceptionMessage;
    
    public function __construct()
    {
        // Déterminer le chemin de base du framework
        $basePath = dirname(dirname(dirname(__DIR__)));
        $this->app = new Application($basePath);
        // $this->db = Database::getInstance(); // Commenté car Database n'existe pas encore
        // $this->logger = new Logger(); // Commenté car Logger n'existe pas encore
    }

    /**
     * Set up before each test
     */
    public function setUp()
    {
        // Override in child classes
    }

    /**
     * Clean up after each test
     */
    public function tearDown()
    {
        // Override in child classes
    }

    /**
     * Set up before all tests in the class
     */
    public static function setUpBeforeClass()
    {
        // Override in child classes
    }

    /**
     * Clean up after all tests in the class
     */
    public static function tearDownAfterClass()
    {
        // Override in child classes
    }

    /**
     * Run a specific test method
     */
    public function runTest($methodName)
    {
        $this->testName = $methodName;
        $this->assertions = 0;
        $this->failures = [];

        try {
            $this->setUp();
            $this->$methodName();
            $this->tearDown();
            
            return [
                'status' => 'passed',
                'assertions' => $this->assertions,
                'failures' => $this->failures,
                'message' => null
            ];
        } catch (\Exception $e) {
            $this->tearDown();
            
            return [
                'status' => 'failed',
                'assertions' => $this->assertions,
                'failures' => $this->failures,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    /**
     * Get all test methods in the class
     */
    public function getTestMethods()
    {
        $reflection = new \ReflectionClass($this);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        $testMethods = [];
        foreach ($methods as $method) {
            if (strpos($method->getName(), 'test') === 0) {
                $testMethods[] = $method->getName();
            }
        }
        
        return $testMethods;
    }

    // Assertion methods

    /**
     * Assert that a condition is true
     */
    protected function assertTrue($condition, $message = '')
    {
        $this->assertions++;
        if (!$condition) {
            $this->fail($message ?: 'Failed asserting that condition is true');
        }
    }

    /**
     * Assert that a condition is false
     */
    protected function assertFalse($condition, $message = '')
    {
        $this->assertions++;
        if ($condition) {
            $this->fail($message ?: 'Failed asserting that condition is false');
        }
    }

    /**
     * Assert that two values are equal
     */
    protected function assertEquals($expected, $actual, $message = '')
    {
        $this->assertions++;
        if ($expected !== $actual) {
            $this->fail($message ?: "Failed asserting that '" . var_export($actual, true) . "' equals '" . var_export($expected, true) . "'");
        }
    }

    /**
     * Assert that two values are not equal
     */
    protected function assertNotEquals($expected, $actual, $message = '')
    {
        $this->assertions++;
        if ($expected === $actual) {
            $this->fail($message ?: "Failed asserting that '" . var_export($actual, true) . "' does not equal '" . var_export($expected, true) . "'");
        }
    }

    /**
     * Assert that a value is null
     */
    protected function assertNull($actual, $message = '')
    {
        $this->assertions++;
        if ($actual !== null) {
            $this->fail($message ?: "Failed asserting that '" . var_export($actual, true) . "' is null");
        }
    }

    /**
     * Assert that a value is not null
     */
    protected function assertNotNull($actual, $message = '')
    {
        $this->assertions++;
        if ($actual === null) {
            $this->fail($message ?: 'Failed asserting that value is not null');
        }
    }

    /**
     * Assert that an array has a specific key
     */
    protected function assertArrayHasKey($key, $array, $message = '')
    {
        $this->assertions++;
        if (!is_array($array) || !array_key_exists($key, $array)) {
            $this->fail($message ?: "Failed asserting that array has key '$key'");
        }
    }

    /**
     * Assert that a string contains a substring
     */
    protected function assertStringContains($needle, $haystack, $message = '')
    {
        $this->assertions++;
        if (strpos($haystack, $needle) === false) {
            $this->fail($message ?: "Failed asserting that '$haystack' contains '$needle'");
        }
    }

    /**
     * Assert that a value is an array
     */
    protected function assertIsArray($actual, $message = '')
    {
        $this->assertions++;
        if (!is_array($actual)) {
            $this->fail($message ?: "Failed asserting that '" . var_export($actual, true) . "' is an array");
        }
    }

    /**
     * Assert that a value is empty
     */
    protected function assertEmpty($actual, $message = '')
    {
        $this->assertions++;
        if (!empty($actual)) {
            $this->fail($message ?: "Failed asserting that '" . var_export($actual, true) . "' is empty");
        }
    }

    /**
     * Assert that a value is not empty
     */
    protected function assertNotEmpty($actual, $message = '')
    {
        $this->assertions++;
        if (empty($actual)) {
            $this->fail($message ?: "Failed asserting that value is not empty");
        }
    }

    /**
     * Assert that a variable is of a given type
     */
    protected function assertInstanceOf($expected, $actual, $message = '')
    {
        $this->assertions++;
        if (!($actual instanceof $expected)) {
            $actualType = is_object($actual) ? get_class($actual) : gettype($actual);
            $this->fail($message ?: "Failed asserting that '$actualType' is an instance of '$expected'");
        }
    }

    /**
     * Assert that an array has a specific count
     */
    protected function assertCount($expectedCount, $array, $message = '')
    {
        $this->assertions++;
        $actualCount = is_countable($array) ? count($array) : 0;
        if ($actualCount !== $expectedCount) {
            $this->fail($message ?: "Failed asserting that array has count '$expectedCount', actual count is '$actualCount'");
        }
    }

    /**
     * Assert that an array contains a specific value
     */
    protected function assertContains($needle, $haystack, $message = '')
    {
        $this->assertions++;
        if (!in_array($needle, $haystack, true)) {
            $this->fail($message ?: "Failed asserting that array contains '" . var_export($needle, true) . "'");
        }
    }

    /**
     * Assert that an exception is thrown
     */
    protected function expectException($exceptionClass)
    {
        $this->expectedExceptionClass = $exceptionClass;
    }

    /**
     * Assert that a specific exception message is expected
     */
    protected function expectExceptionMessage($message)
    {
        $this->expectedExceptionMessage = $message;
    }

    /**
     * Fail the test with a message
     */
    protected function fail($message)
    {
        $this->failures[] = [
            'message' => $message,
            'test' => $this->testName,
            'line' => debug_backtrace()[1]['line'] ?? 'unknown'
        ];
        throw new \Exception($message);
    }

    // Helper methods for testing

    /**
     * Create a mock HTTP request
     */
    protected function createRequest($method = 'GET', $uri = '/', $data = [])
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        if ($method === 'POST') {
            $_POST = $data;
        } else {
            $_GET = $data;
        }
        
        return Request::capture();
    }

    /**
     * Create a test database transaction
     */
    protected function beginDatabaseTransaction()
    {
        $this->db->beginTransaction();
    }

    /**
     * Rollback test database transaction
     */
    protected function rollbackDatabaseTransaction()
    {
        if ($this->db->inTransaction()) {
            $this->db->rollback();
        }
    }

    /**
     * Create test data in database
     */
    protected function createTestData($table, $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        
        return $this->db->lastInsertId();
    }

    /**
     * Clean up test data
     */
    protected function cleanupTestData($table, $conditions = [])
    {
        if (empty($conditions)) {
            $sql = "DELETE FROM $table";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            $whereClause = [];
            foreach ($conditions as $column => $value) {
                $whereClause[] = "$column = :$column";
            }
            
            $sql = "DELETE FROM $table WHERE " . implode(' AND ', $whereClause);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($conditions);
        }
    }

    /**
     * Get test configuration
     */
    protected function getTestConfig($key = null)
    {
        $config = [
            'database' => [
                'host' => $_ENV['TEST_DB_HOST'] ?? 'localhost',
                'name' => $_ENV['TEST_DB_NAME'] ?? 'nexa_test',
                'user' => $_ENV['TEST_DB_USER'] ?? 'root',
                'password' => $_ENV['TEST_DB_PASSWORD'] ?? ''
            ],
            'app' => [
                'env' => 'testing',
                'debug' => true
            ]
        ];
        
        if ($key) {
            return $config[$key] ?? null;
        }
        
        return $config;
    }

    /**
     * Mark the current test as skipped
     */
    protected function markTestSkipped($message = '')
    {
        throw new \Exception('Test skipped: ' . $message);
    }
}