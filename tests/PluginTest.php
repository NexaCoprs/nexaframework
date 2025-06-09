<?php

namespace Tests;

require_once __DIR__ . '/../src/Nexa/Testing/TestCase.php';
require_once __DIR__ . '/../src/Plugins/Plugin.php';
require_once __DIR__ . '/../src/Plugins/PluginManager.php';

use Nexa\Testing\TestCase;
use Nexa\Plugins\Plugin;
use Nexa\Plugins\PluginManager;
use Exception;

class PluginTest extends TestCase
{
    private $pluginManager;
    private $testPluginPath;
    private $testClassName;
    
    public function setUp()
    {
        parent::setUp();
        
        $this->pluginManager = new \Nexa\Plugins\PluginManager($this->app);
        
        // Create a unique test plugin directory in temp
        $uniqueId = uniqid();
        $this->testPluginPath = sys_get_temp_dir() . '/nexa_test_plugin_' . $uniqueId;
        $this->testClassName = 'TestPlugin' . $uniqueId;
        
        if (!is_dir($this->testPluginPath)) {
            mkdir($this->testPluginPath, 0755, true);
        }
        
        $this->createTestPlugin();
    }
    
    public function tearDown()
    {
        parent::tearDown();
        
        // Clean up test plugin
        try {
            if (file_exists($this->testPluginPath . '/' . $this->testClassName . '.php')) {
                unlink($this->testPluginPath . '/' . $this->testClassName . '.php');
            }
            if (is_dir($this->testPluginPath)) {
                rmdir($this->testPluginPath);
            }
        } catch (Exception $e) {
            // Ignore cleanup errors
        }
    }
    
    private function createTestPlugin()
    {
        $pluginCode = '<?php

use Nexa\Plugins\Plugin;

class ' . $this->testClassName . ' extends Plugin
{
    protected $name = "Test Plugin";
    protected $version = "1.0.0";
    protected $description = "A test plugin for unit testing";
    protected $author = "Test Author";
    
    public function register(): void
    {
        // Register plugin services
    }
    
    public function boot(): void
    {
        // Boot plugin functionality
    }
}';
        
        try {
            file_put_contents($this->testPluginPath . '/' . $this->testClassName . '.php', $pluginCode);
        } catch (Exception $e) {
            // Skip plugin tests if we can't create the test file
            $this->markTestSkipped('Cannot create test plugin file: ' . $e->getMessage());
        }
    }
    
    public function testPluginManagerInitialization()
    {
        $this->assertInstanceOf(PluginManager::class, $this->pluginManager);
        $this->assertIsArray($this->pluginManager->getLoadedPlugins());
        $this->assertEmpty($this->pluginManager->getLoadedPlugins());
    }
    
    public function testPluginDiscovery()
    {
        $plugins = $this->pluginManager->scanPlugins($this->testPluginPath);
        
        $this->assertIsArray($plugins);
        $this->assertNotEmpty($plugins);
        $this->assertArrayHasKey($this->testClassName, $plugins);
    }
    
    public function testPluginLoading()
    {
        // Discover and load plugins
        $this->pluginManager->scanPlugins($this->testPluginPath);
        $this->pluginManager->loadPlugins();
        
        $loadedPlugins = $this->pluginManager->getLoadedPlugins();
        
        $this->assertNotEmpty($loadedPlugins);
        $this->assertArrayHasKey('TestPlugin', $loadedPlugins);
        $this->assertInstanceOf(Plugin::class, $loadedPlugins['TestPlugin']);
    }
    
    public function testPluginActivation()
    {
        // Load plugin first
        $this->pluginManager->scanPlugins($this->testPluginPath);
        $this->pluginManager->loadPlugins();
        
        // Activate plugin
        $result = $this->pluginManager->activatePlugin('TestPlugin');
        
        $this->assertTrue($result);
        $this->assertTrue($this->pluginManager->isPluginEnabled('TestPlugin'));
    }
    
    public function testPluginDeactivation()
    {
        // Load and activate plugin first
        $this->pluginManager->scanPlugins($this->testPluginPath);
        $this->pluginManager->loadPlugins();
        $this->pluginManager->activatePlugin('TestPlugin');
        
        // Deactivate plugin
        $result = $this->pluginManager->deactivatePlugin('TestPlugin');
        
        $this->assertTrue($result);
        $this->assertFalse($this->pluginManager->isPluginEnabled('TestPlugin'));
    }
    
    public function testPluginInfo()
    {
        $this->pluginManager->scanPlugins($this->testPluginPath);
        $this->pluginManager->loadPlugins();
        
        $plugin = $this->pluginManager->getPlugin($this->testClassName);
        
        $this->assertEquals('Test Plugin', $plugin->getName());
        $this->assertEquals('1.0.0', $plugin->getVersion());
        $this->assertEquals('A test plugin for unit testing', $plugin->getDescription());
        $this->assertEquals('Test Author', $plugin->getAuthor());
    }
    
    public function testPluginDependencyCheck()
    {
        $this->pluginManager->scanPlugins($this->testPluginPath);
        $this->pluginManager->loadPlugins();
        
        $plugin = $this->pluginManager->getPlugin($this->testClassName);
        
        // Test plugin has no dependencies
        $this->assertTrue($plugin->checkDependencies());
    }
    
    public function testPluginCompatibility()
    {
        $this->pluginManager->scanPlugins($this->testPluginPath);
        $this->pluginManager->loadPlugins();
        
        $plugin = $this->pluginManager->getPlugin($this->testClassName);
        
        // Test compatibility with current framework version
        $this->assertTrue($plugin->isCompatible());
    }
    
    public function testInvalidPluginHandling()
    {
        $this->expectException(Exception::class);
        
        // Try to activate non-existent plugin
        $this->pluginManager->activatePlugin('NonExistentPlugin');
    }
    
    public function testPluginHooks()
    {
        $this->pluginManager->scanPlugins($this->testPluginPath);
        $this->pluginManager->loadPlugins();
        $this->pluginManager->activatePlugin($this->testClassName);
        
        // Test that plugin hooks are registered
        $activePlugins = $this->pluginManager->getActivePlugins();
        
        $this->assertArrayHasKey($this->testClassName, $activePlugins);
        $this->assertTrue($activePlugins[$this->testClassName]->isEnabled());
    }
    
    public function testPluginUninstall()
    {
        $this->pluginManager->scanPlugins($this->testPluginPath);
        $this->pluginManager->loadPlugins();
        $this->pluginManager->activatePlugin($this->testClassName);
        
        // Uninstall plugin
        $result = $this->pluginManager->uninstallPlugin($this->testClassName);
        
        $this->assertTrue($result);
        $this->assertFalse($this->pluginManager->isPluginEnabled($this->testClassName));
        $this->assertNull($this->pluginManager->getPlugin($this->testClassName));
    }
    
    public function testPluginUpdate()
    {
        $this->pluginManager->scanPlugins($this->testPluginPath);
        $this->pluginManager->loadPlugins();
        
        $plugin = $this->pluginManager->getPlugin($this->testClassName);
        $oldVersion = $plugin->getVersion();
        
        // Simulate plugin update
        $result = $this->pluginManager->updatePlugin($this->testClassName, '1.1.0');
        
        $this->assertTrue($result);
    }
}