<?php

namespace Tests;

use Nexa\Testing\TestCase;
use Nexa\WebSockets\WebSocketServer;
use Nexa\WebSockets\WebSocketClient;
use Exception;

class WebSocketTest extends TestCase
{
    private $server;
    private $client;
    private $testPort;
    
    public function setUp()
    {
        parent::setUp();
        
        $this->testPort = 8081; // Use different port for testing
        $this->server = new WebSocketServer('127.0.0.1', $this->testPort);
        $this->client = new WebSocketClient();
        
        // Connect client to server for event testing
        $this->client->setMockServer($this->server);
    }
    
    public function tearDown()
    {
        parent::tearDown();
        
        // Clean up connections
        if ($this->client->isConnected()) {
            $this->client->disconnect();
        }
        
        if ($this->server->isRunning()) {
            $this->server->stop();
        }
    }
    
    public function testWebSocketServerInitialization()
    {
        $this->assertInstanceOf(WebSocketServer::class, $this->server);
        $this->assertEquals('127.0.0.1', $this->server->getHost());
        $this->assertEquals($this->testPort, $this->server->getPort());
        $this->assertFalse($this->server->isRunning());
    }
    
    public function testWebSocketClientInitialization()
    {
        $this->assertInstanceOf(WebSocketClient::class, $this->client);
        $this->assertFalse($this->client->isConnected());
    }
    
    public function testServerConfiguration()
    {
        $config = [
            'host' => '0.0.0.0',
            'port' => 8082,
            'ssl' => false,
            'heartbeat_interval' => 30,
            'max_connections' => 100
        ];
        
        $server = new WebSocketServer($config['host'], $config['port'], $config);
        
        $this->assertEquals($config['host'], $server->getHost());
        $this->assertEquals($config['port'], $server->getPort());
        $this->assertEquals($config['heartbeat_interval'], $server->getHeartbeatInterval());
        $this->assertEquals($config['max_connections'], $server->getMaxConnections());
    }
    
    public function testServerStart()
    {
        // Mock server start for testing
        $result = $this->server->mockStart();
        
        $this->assertTrue($result);
        $this->assertTrue($this->server->isRunning());
    }
    
    public function testServerStop()
    {
        $this->server->mockStart();
        $this->assertTrue($this->server->isRunning());
        
        $result = $this->server->stop();
        
        $this->assertTrue($result);
        $this->assertFalse($this->server->isRunning());
    }
    
    public function testClientConnection()
    {
        $this->server->mockStart();
        
        // Mock client connection
        $result = $this->client->mockConnect('ws://127.0.0.1:' . $this->testPort);
        
        $this->assertTrue($result);
        $this->assertTrue($this->client->isConnected());
    }
    
    public function testClientDisconnection()
    {
        $this->server->mockStart();
        $this->client->mockConnect('ws://127.0.0.1:' . $this->testPort);
        
        $result = $this->client->disconnect();
        
        $this->assertTrue($result);
        $this->assertFalse($this->client->isConnected());
    }
    
    public function testMessageSending()
    {
        $this->server->mockStart();
        $this->client->mockConnect('ws://127.0.0.1:' . $this->testPort);
        
        $message = 'Hello WebSocket!';
        $result = $this->client->send($message);
        
        $this->assertTrue($result);
    }
    
    public function testMessageReceiving()
    {
        $this->server->mockStart();
        $this->client->mockConnect('ws://127.0.0.1:' . $this->testPort);
        
        // Mock receiving a message
        $expectedMessage = 'Hello from server!';
        $this->client->mockReceive($expectedMessage);
        
        $receivedMessage = $this->client->receive();
        
        $this->assertEquals($expectedMessage, $receivedMessage);
    }
    
    public function testChannelJoining()
    {
        $this->server->mockStart();
        $this->client->mockConnect('ws://127.0.0.1:' . $this->testPort);
        
        $channel = 'test-channel';
        $result = $this->client->joinChannel($channel);
        
        $this->assertTrue($result);
        $this->assertTrue($this->client->isInChannel($channel));
    }
    
    public function testChannelLeaving()
    {
        $this->server->mockStart();
        $this->client->mockConnect('ws://127.0.0.1:' . $this->testPort);
        $this->client->joinChannel('test-channel');
        
        $result = $this->client->leaveChannel('test-channel');
        
        $this->assertTrue($result);
        $this->assertFalse($this->client->isInChannel('test-channel'));
    }
    
    public function testChannelBroadcast()
    {
        $this->server->mockStart();
        
        // Mock multiple clients
        $client1 = new WebSocketClient();
        $client2 = new WebSocketClient();
        
        $client1->mockConnect('ws://127.0.0.1:' . $this->testPort);
        $client2->mockConnect('ws://127.0.0.1:' . $this->testPort);
        
        $channel = 'broadcast-channel';
        $client1->joinChannel($channel);
        $client2->joinChannel($channel);
        
        $message = 'Broadcast message';
        $result = $this->server->broadcastToChannel($channel, $message);
        
        $this->assertTrue($result);
    }
    
    public function testAuthentication()
    {
        $this->server->mockStart();
        $this->client->mockConnect('ws://127.0.0.1:' . $this->testPort);
        
        $token = 'test-auth-token';
        $result = $this->client->authenticate($token);
        
        $this->assertTrue($result);
        $this->assertTrue($this->client->isAuthenticated());
    }
    
    public function testAuthenticationFailure()
    {
        $this->server->mockStart();
        $this->client->mockConnect('ws://127.0.0.1:' . $this->testPort);
        
        $invalidToken = 'invalid-token';
        $result = $this->client->authenticate($invalidToken);
        
        $this->assertFalse($result);
        $this->assertFalse($this->client->isAuthenticated());
    }
    
    public function testHeartbeat()
    {
        $this->server->mockStart();
        $this->client->mockConnect('ws://127.0.0.1:' . $this->testPort);
        
        $result = $this->client->ping();
        
        $this->assertTrue($result);
        
        // Mock pong response
        $pongReceived = $this->client->waitForPong(1000); // 1 second timeout
        $this->assertTrue($pongReceived);
    }
    
    public function testConnectionLimit()
    {
        $server = new WebSocketServer('127.0.0.1', 8083, ['max_connections' => 2]);
        $server->mockStart();
        
        $client1 = new WebSocketClient();
        $client1->setMockServer($server);
        $client2 = new WebSocketClient();
        $client2->setMockServer($server);
        $client3 = new WebSocketClient();
        $client3->setMockServer($server);
        
        $this->assertTrue($client1->mockConnect('ws://127.0.0.1:8083'));
        $this->assertTrue($client2->mockConnect('ws://127.0.0.1:8083'));
        $this->assertFalse($client3->mockConnect('ws://127.0.0.1:8083')); // Should fail
    }
    
    public function testEventHandling()
    {
        $this->server->mockStart();
        
        $eventFired = false;
        $this->server->on('connection', function($connection) use (&$eventFired) {
            $eventFired = true;
        });
        
        $this->client->mockConnect('ws://127.0.0.1:' . $this->testPort);
        
        $this->assertTrue($eventFired);
    }
    
    public function testMessageValidation()
    {
        $this->server->mockStart();
        $this->client->mockConnect('ws://127.0.0.1:' . $this->testPort);
        
        // Test invalid JSON message
        $invalidMessage = 'invalid json {';
        $result = $this->client->send($invalidMessage);
        
        $this->assertFalse($result);
    }
    
    public function testPrivateChannels()
    {
        $this->server->mockStart();
        $this->client->mockConnect('ws://127.0.0.1:' . $this->testPort);
        $this->client->authenticate('valid-token');
        
        $privateChannel = 'private-user-123';
        $result = $this->client->joinChannel($privateChannel);
        
        $this->assertTrue($result);
        $this->assertTrue($this->client->isInChannel($privateChannel));
    }
    
    public function testPresenceChannels()
    {
        $this->server->mockStart();
        $this->client->mockConnect('ws://127.0.0.1:' . $this->testPort);
        $this->client->authenticate('valid-token');
        
        $presenceChannel = 'presence-chat-room';
        $result = $this->client->joinChannel($presenceChannel);
        
        $this->assertTrue($result);
        
        // Test getting channel members
        $members = $this->server->getChannelMembers($presenceChannel);
        $this->assertIsArray($members);
        $this->assertNotEmpty($members);
    }
    
    public function testConnectionMetrics()
    {
        $this->server->mockStart();
        
        $metrics = $this->server->getMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_connections', $metrics);
        $this->assertArrayHasKey('active_connections', $metrics);
        $this->assertArrayHasKey('messages_sent', $metrics);
        $this->assertArrayHasKey('messages_received', $metrics);
    }
    
    public function testErrorHandling()
    {
        $this->server->mockStart();
        
        // Test connection to invalid URL
        $result = $this->client->mockConnect('ws://invalid-host:9999');
        
        $this->assertFalse($result);
        $this->assertFalse($this->client->isConnected());
    }
    
    public function testSSLConnection()
    {
        $sslServer = new WebSocketServer('127.0.0.1', 8084, ['ssl' => true]);
        $sslClient = new WebSocketClient();
        
        // Mock SSL connection
        $result = $sslClient->mockConnect('wss://127.0.0.1:8084');
        
        $this->assertTrue($result);
        $this->assertTrue($sslClient->isConnected());
    }
    
    public function testMessageCompression()
    {
        $server = new WebSocketServer('127.0.0.1', 8085, ['compression' => true]);
        $server->mockStart();
        
        $this->client->mockConnect('ws://127.0.0.1:8085');
        
        $largeMessage = str_repeat('This is a test message for compression. ', 100);
        $result = $this->client->send($largeMessage);
        
        $this->assertTrue($result);
    }
    
    public function testCORSHandling()
    {
        $corsConfig = [
            'allowed_origins' => ['http://localhost:3000', 'https://example.com'],
            'allowed_methods' => ['GET', 'POST'],
            'allowed_headers' => ['Content-Type', 'Authorization']
        ];
        
        $server = new WebSocketServer('127.0.0.1', 8086, ['cors' => $corsConfig]);
        $server->mockStart();
        
        // Test CORS preflight
        $corsResult = $server->handleCORSPreflight('http://localhost:3000');
        
        $this->assertTrue($corsResult);
    }
}