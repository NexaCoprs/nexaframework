<?php

namespace Tests;

require_once __DIR__ . '/SimplePluginTest.php';

class SimpleTestSuite
{
    private $testResults = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    
    public function runAllTests()
    {
        echo "\n=== NEXA FRAMEWORK PHASE 3 TEST SUITE ===\n";
        echo "Running simplified tests for all Phase 3 features...\n\n";
        
        $testClasses = [
            'SimplePluginTest' => 'Plugin System Tests',
            'SimpleModuleTest' => 'Module System Tests',
            'SimpleGraphQLTest' => 'GraphQL System Tests',
            'SimpleWebSocketTest' => 'WebSocket System Tests',
            'SimpleMicroserviceTest' => 'Microservice System Tests'
        ];
        
        foreach ($testClasses as $className => $description) {
            $this->runTestClass($className, $description);
        }
        
        $this->displaySummary();
        
        return $this->passedTests === $this->totalTests;
    }
    
    private function runTestClass($className, $description)
    {
        $fullClassName = "Tests\\{$className}";
        
        if (!class_exists($fullClassName)) {
            echo "\n--- {$description} ---\n";
            echo "❌ Test class {$fullClassName} not found\n";
            return;
        }
        
        $testInstance = new $fullClassName();
        
        if (method_exists($testInstance, 'runAllTests')) {
            $result = $testInstance->runAllTests();
            if ($result) {
                $this->passedTests++;
            } else {
                $this->failedTests++;
            }
            $this->totalTests++;
        }
    }
    
    private function displaySummary()
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "TEST SUITE SUMMARY\n";
        echo str_repeat("=", 50) . "\n";
        
        echo "Total Test Suites: {$this->totalTests}\n";
        echo "Passed: {$this->passedTests}\n";
        echo "Failed: {$this->failedTests}\n";
        
        $percentage = $this->totalTests > 0 ? round(($this->passedTests / $this->totalTests) * 100, 2) : 0;
        echo "Success Rate: {$percentage}%\n";
        
        if ($this->passedTests === $this->totalTests) {
            echo "\n🎉 All test suites passed!\n";
        } else {
            echo "\n❌ Some test suites failed.\n";
        }
    }
    
    public function getTestResults()
    {
        return [
            'total' => $this->totalTests,
            'passed' => $this->passedTests,
            'failed' => $this->failedTests,
            'results' => $this->testResults,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

class SimpleModuleTest
{
    public function runAllTests()
    {
        echo "\n--- Module System Tests ---\n";
        
        $tests = [
            'testModuleClassExists',
            'testModuleManagerExists',
            'testModuleConfigExists'
        ];
        
        $passed = 0;
        $total = count($tests);
        
        foreach ($tests as $test) {
            try {
                $this->$test();
                echo "✅ {$test}\n";
                $passed++;
            } catch (Exception $e) {
                echo "❌ {$test}: {$e->getMessage()}\n";
            }
        }
        
        echo "\nModule Tests: {$passed}/{$total} passed\n";
        return $passed === $total;
    }
    
    public function testModuleClassExists()
    {
        $moduleFile = __DIR__ . '/../src/Modules/Module.php';
        if (!file_exists($moduleFile)) {
            throw new Exception('Module.php file not found');
        }
    }
    
    public function testModuleManagerExists()
    {
        $managerFile = __DIR__ . '/../src/Modules/ModuleManager.php';
        if (!file_exists($managerFile)) {
            throw new Exception('ModuleManager.php file not found');
        }
    }
    
    public function testModuleConfigExists()
    {
        $configFile = __DIR__ . '/../config/modules.php';
        if (!file_exists($configFile)) {
            throw new Exception('modules.php config file not found');
        }
    }
}

class SimpleGraphQLTest
{
    public function runAllTests()
    {
        echo "\n--- GraphQL System Tests ---\n";
        
        $tests = [
            'testGraphQLManagerExists',
            'testGraphQLTypeExists',
            'testGraphQLQueryExists',
            'testGraphQLMutationExists',
            'testGraphQLConfigExists'
        ];
        
        $passed = 0;
        $total = count($tests);
        
        foreach ($tests as $test) {
            try {
                $this->$test();
                echo "✅ {$test}\n";
                $passed++;
            } catch (Exception $e) {
                echo "❌ {$test}: {$e->getMessage()}\n";
            }
        }
        
        echo "\nGraphQL Tests: {$passed}/{$total} passed\n";
        return $passed === $total;
    }
    
    public function testGraphQLManagerExists()
    {
        $file = __DIR__ . '/../src/GraphQL/GraphQLManager.php';
        if (!file_exists($file)) {
            throw new Exception('GraphQLManager.php file not found');
        }
    }
    
    public function testGraphQLTypeExists()
    {
        $file = __DIR__ . '/../src/GraphQL/Type.php';
        if (!file_exists($file)) {
            throw new Exception('Type.php file not found');
        }
    }
    
    public function testGraphQLQueryExists()
    {
        $file = __DIR__ . '/../src/GraphQL/Query.php';
        if (!file_exists($file)) {
            throw new Exception('Query.php file not found');
        }
    }
    
    public function testGraphQLMutationExists()
    {
        $file = __DIR__ . '/../src/GraphQL/Mutation.php';
        if (!file_exists($file)) {
            throw new Exception('Mutation.php file not found');
        }
    }
    
    public function testGraphQLConfigExists()
    {
        $configFile = __DIR__ . '/../config/graphql.php';
        if (!file_exists($configFile)) {
            throw new Exception('graphql.php config file not found');
        }
    }
}

class SimpleWebSocketTest
{
    public function runAllTests()
    {
        echo "\n--- WebSocket System Tests ---\n";
        
        $tests = [
            'testWebSocketServerExists',
            'testWebSocketClientExists',
            'testWebSocketConfigExists'
        ];
        
        $passed = 0;
        $total = count($tests);
        
        foreach ($tests as $test) {
            try {
                $this->$test();
                echo "✅ {$test}\n";
                $passed++;
            } catch (Exception $e) {
                echo "❌ {$test}: {$e->getMessage()}\n";
            }
        }
        
        echo "\nWebSocket Tests: {$passed}/{$total} passed\n";
        return $passed === $total;
    }
    
    public function testWebSocketServerExists()
    {
        $file = __DIR__ . '/../src/WebSockets/WebSocketServer.php';
        if (!file_exists($file)) {
            throw new Exception('WebSocketServer.php file not found');
        }
    }
    
    public function testWebSocketClientExists()
    {
        $file = __DIR__ . '/../src/WebSockets/WebSocketClient.php';
        if (!file_exists($file)) {
            throw new Exception('WebSocketClient.php file not found');
        }
    }
    
    public function testWebSocketConfigExists()
    {
        $configFile = __DIR__ . '/../config/websockets.php';
        if (!file_exists($configFile)) {
            throw new Exception('websockets.php config file not found');
        }
    }
}

class SimpleMicroserviceTest
{
    public function runAllTests()
    {
        echo "\n--- Microservice System Tests ---\n";
        
        $tests = [
            'testServiceRegistryExists',
            'testServiceClientExists',
            'testMicroserviceConfigExists'
        ];
        
        $passed = 0;
        $total = count($tests);
        
        foreach ($tests as $test) {
            try {
                $this->$test();
                echo "✅ {$test}\n";
                $passed++;
            } catch (Exception $e) {
                echo "❌ {$test}: {$e->getMessage()}\n";
            }
        }
        
        echo "\nMicroservice Tests: {$passed}/{$total} passed\n";
        return $passed === $total;
    }
    
    public function testServiceRegistryExists()
    {
        $file = __DIR__ . '/../src/Microservices/ServiceRegistry.php';
        if (!file_exists($file)) {
            throw new Exception('ServiceRegistry.php file not found');
        }
    }
    
    public function testServiceClientExists()
    {
        $file = __DIR__ . '/../src/Microservices/ServiceClient.php';
        if (!file_exists($file)) {
            throw new Exception('ServiceClient.php file not found');
        }
    }
    
    public function testMicroserviceConfigExists()
    {
        $configFile = __DIR__ . '/../config/microservices.php';
        if (!file_exists($configFile)) {
            throw new Exception('microservices.php config file not found');
        }
    }
}