<?php

namespace Tests;

class SimplePluginTest
{
    private $testResults = [];
    
    public function runAllTests()
    {
        echo "\n--- Plugin System Tests ---\n";
        
        $tests = [
            'testPluginClassExists',
            'testPluginManagerExists',
            'testPluginConfigExists'
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
        
        echo "\nPlugin Tests: {$passed}/{$total} passed\n";
        return $passed === $total;
    }
    
    public function testPluginClassExists()
    {
        $pluginFile = __DIR__ . '/../src/Plugins/Plugin.php';
        if (!file_exists($pluginFile)) {
            throw new Exception('Plugin.php file not found');
        }
        
        require_once $pluginFile;
        
        if (!class_exists('Nexa\\Plugins\\Plugin')) {
            throw new Exception('Plugin class not found');
        }
    }
    
    public function testPluginManagerExists()
    {
        $managerFile = __DIR__ . '/../src/Plugins/PluginManager.php';
        if (!file_exists($managerFile)) {
            throw new Exception('PluginManager.php file not found');
        }
        
        require_once $managerFile;
        
        if (!class_exists('Nexa\\Plugins\\PluginManager')) {
            throw new Exception('PluginManager class not found');
        }
    }
    
    public function testPluginConfigExists()
    {
        $configFile = __DIR__ . '/../config/plugins.php';
        if (!file_exists($configFile)) {
            throw new Exception('plugins.php config file not found');
        }
        
        $config = require $configFile;
        if (!is_array($config)) {
            throw new Exception('Plugin config should return an array');
        }
    }
}