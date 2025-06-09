<?php

namespace Tests;

use Nexa\Testing\TestCase;
use Nexa\GraphQL\GraphQLManager;
use Nexa\GraphQL\Type;
use Nexa\GraphQL\Query;
use Nexa\GraphQL\Mutation;
use Nexa\Http\Request;
use Exception;

class GraphQLTest extends TestCase
{
    private $graphqlManager;
    private $testSchema;
    
    public function setUp()
    {
        parent::setUp();
        
        $this->graphqlManager = new GraphQLManager();
        $this->createTestSchema();
    }
    
    public function tearDown()
    {
        parent::tearDown();
    }
    
    private function createTestSchema()
    {
        // Create test type
        $this->testSchema = [
            'types' => [
                'User' => new TestUserType(),
                'Post' => new TestPostType()
            ],
            'queries' => [
                'user' => new TestUserQuery(),
                'users' => new TestUsersQuery(),
                'post' => new TestPostQuery()
            ],
            'mutations' => [
                'createUser' => new TestCreateUserMutation(),
                'updateUser' => new TestUpdateUserMutation(),
                'deleteUser' => new TestDeleteUserMutation()
            ]
        ];
    }
    
    public function testGraphQLManagerInitialization()
    {
        $this->assertInstanceOf(GraphQLManager::class, $this->graphqlManager);
    }
    
    public function testSchemaLoading()
    {
        $this->graphqlManager->loadSchema($this->testSchema);
        
        $schema = $this->graphqlManager->getSchema();
        $this->assertNotNull($schema);
        $this->assertTrue(is_array($schema));
    }
    
    public function testTypeRegistration()
    {
        $userType = new TestUserType();
        $this->graphqlManager->registerType('User', $userType);
        
        $registeredType = $this->graphqlManager->getType('User');
        $this->assertInstanceOf(Type::class, $registeredType);
        $this->assertEquals('User', $registeredType->getName());
    }
    
    public function testQueryRegistration()
    {
        $userQuery = new TestUserQuery();
        $this->graphqlManager->registerQuery('user', $userQuery);
        
        $registeredQuery = $this->graphqlManager->getQuery('user');
        $this->assertInstanceOf(Query::class, $registeredQuery);
        $this->assertEquals('user', $registeredQuery->getName());
    }
    
    public function testMutationRegistration()
    {
        $createUserMutation = new TestCreateUserMutation();
        $this->graphqlManager->registerMutation('createUser', $createUserMutation);
        
        $registeredMutation = $this->graphqlManager->getMutation('createUser');
        $this->assertInstanceOf(Mutation::class, $registeredMutation);
        $this->assertEquals('createUser', $registeredMutation->getName());
    }
    
    public function testSimpleQuery()
    {
        $this->graphqlManager->loadSchema($this->testSchema);
        
        $query = '{
            user(id: 1) {
                id
                name
                email
            }
        }';
        
        $result = $this->graphqlManager->executeQuery($query);
        
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('user', $result['data']);
    }
    
    public function testQueryWithArguments()
    {
        $this->graphqlManager->loadSchema($this->testSchema);
        
        $query = '{
            users(limit: 5, offset: 0) {
                id
                name
                email
            }
        }';
        
        $result = $this->graphqlManager->executeQuery($query);
        
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('users', $result['data']);
        $this->assertTrue(is_array($result['data']['users']));
    }
    
    public function testMutation()
    {
        $this->graphqlManager->loadSchema($this->testSchema);
        
        $mutation = 'mutation {
            createUser(input: {
                name: "John Doe"
                email: "john@example.com"
            }) {
                id
                name
                email
            }
        }';
        
        $result = $this->graphqlManager->executeQuery($mutation);
        
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('createUser', $result['data']);
    }
    
    public function testQueryValidation()
    {
        $this->graphqlManager->loadSchema($this->testSchema);
        
        // Invalid query syntax
        $invalidQuery = '{
            user(id: 1 {
                id
                name
            }
        }';
        
        $result = $this->graphqlManager->executeQuery($invalidQuery);
        
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('errors', $result);
    }
    
    public function testQueryComplexityLimit()
    {
        $this->graphqlManager->loadSchema($this->testSchema);
        $this->graphqlManager->setMaxQueryComplexity(5);
        
        // Complex nested query
        $complexQuery = '{
            users {
                id
                name
                posts {
                    id
                    title
                    author {
                        id
                        name
                        posts {
                            id
                            title
                        }
                    }
                }
            }
        }';
        
        $result = $this->graphqlManager->executeQuery($complexQuery);
        
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('errors', $result);
    }
    
    public function testQueryDepthLimit()
    {
        $this->graphqlManager->loadSchema($this->testSchema);
        $this->graphqlManager->setMaxQueryDepth(3);
        
        // Deep nested query
        $deepQuery = '{
            user(id: 1) {
                posts {
                    author {
                        posts {
                            author {
                                name
                            }
                        }
                    }
                }
            }
        }';
        
        $result = $this->graphqlManager->executeQuery($deepQuery);
        
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('errors', $result);
    }
    
    public function testMiddleware()
    {
        $this->graphqlManager->loadSchema($this->testSchema);
        
        // Add test middleware
        $this->graphqlManager->addMiddleware(function($query, $next) {
            // Add custom header to test middleware execution
            $result = $next($query);
            $result['middleware_executed'] = true;
            return $result;
        });
        
        $query = '{ user(id: 1) { id name } }';
        $result = $this->graphqlManager->executeQuery($query);
        
        $this->assertArrayHasKey('middleware_executed', $result);
        $this->assertTrue($result['middleware_executed']);
    }
    
    public function testErrorHandling()
    {
        $this->graphqlManager->loadSchema($this->testSchema);
        
        // Query non-existent field
        $query = '{ user(id: 1) { nonExistentField } }';
        $result = $this->graphqlManager->executeQuery($query);
        
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('errors', $result);
        $this->assertNotEmpty($result['errors']);
    }
    
    public function testIntrospection()
    {
        $this->graphqlManager->loadSchema($this->testSchema);
        
        $introspectionQuery = '{
            __schema {
                types {
                    name
                }
            }
        }';
        
        $result = $this->graphqlManager->executeQuery($introspectionQuery);
        
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('__schema', $result['data']);
    }
    
    public function testGraphiQLInterface()
    {
        $graphiql = $this->graphqlManager->getGraphiQLInterface();
        
        $this->assertTrue(is_string($graphiql));
        $this->assertTrue(strpos($graphiql, 'GraphiQL') !== false);
        $this->assertTrue(strpos($graphiql, '<!DOCTYPE html>') !== false);
    }
    
    public function testRequestParsing()
    {
        $requestData = [
            'query' => '{ user(id: 1) { id name } }',
            'variables' => ['id' => 1],
            'operationName' => null
        ];
        
        // Create a mock request with JSON content
        $request = new MockRequest();
        $request->setContent(json_encode($requestData));
        $request->setMethod('POST');
        
        $parsedRequest = $this->graphqlManager->parseRequest($request);
        
        $this->assertTrue(is_array($parsedRequest));
        $this->assertArrayHasKey('query', $parsedRequest);
        $this->assertArrayHasKey('variables', $parsedRequest);
        $this->assertEquals($requestData['query'], $parsedRequest['query']);
    }
}

// Test classes for GraphQL testing
class MockRequest
{
    private $content = '';
    private $method = 'GET';
    private $queryParams = [];
    
    public function setContent($content)
    {
        $this->content = $content;
    }
    
    public function setMethod($method)
    {
        $this->method = $method;
    }
    
    public function setQueryParams($params)
    {
        $this->queryParams = $params;
    }
    
    public function isMethod($method)
    {
        return strtoupper($this->method) === strtoupper($method);
    }
    
    public function json($assoc = true)
    {
        return json_decode($this->content, $assoc);
    }
    
    public function query()
    {
        return $this->queryParams;
    }
}
class TestUserType extends Type
{
    protected $name = 'User';
    protected $description = 'User type for testing';
    
    protected $fields = [
        'id' => 'Int!',
        'name' => 'String!',
        'email' => 'String!',
        'posts' => '[Post]'
    ];
    
    public function resolveField($fieldName, $args = [], $context = null)
    {
        switch ($fieldName) {
            case 'posts':
                return [
                    ['id' => 1, 'title' => 'Test Post', 'author_id' => 1]
                ];
            default:
                return null;
        }
    }
}

class TestPostType extends Type
{
    protected $name = 'Post';
    protected $description = 'Post type for testing';
    
    protected $fields = [
        'id' => 'Int!',
        'title' => 'String!',
        'content' => 'String',
        'author' => 'User'
    ];
    
    public function resolveField($fieldName, $args = [], $context = null)
    {
        switch ($fieldName) {
            case 'author':
                return ['id' => 1, 'name' => 'Test User', 'email' => 'test@example.com'];
            default:
                return null;
        }
    }
}

class TestUserQuery extends Query
{
    protected $name = 'user';
    protected $description = 'Get user by ID';
    protected $type = 'User';
    protected $args = [
        'id' => 'Int!'
    ];
    
    public function resolve($root, array $args, $context, array $info)
    {
        return [
            'id' => $args['id'] ?? 1,
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];
    }
}

class TestUsersQuery extends Query
{
    protected $name = 'users';
    protected $description = 'Get list of users';
    protected $type = '[User]';
    protected $args = [
        'limit' => 'Int',
        'offset' => 'Int'
    ];
    
    public function resolve($root, array $args, $context, array $info)
    {
        return [
            ['id' => 1, 'name' => 'User 1', 'email' => 'user1@example.com'],
            ['id' => 2, 'name' => 'User 2', 'email' => 'user2@example.com']
        ];
    }
}

class TestPostQuery extends Query
{
    protected $name = 'post';
    protected $description = 'Get post by ID';
    protected $type = 'Post';
    protected $args = [
        'id' => 'Int!'
    ];
    
    public function resolve($root, array $args, $context, array $info)
    {
        return [
            'id' => $args['id'] ?? 1,
            'title' => 'Test Post',
            'content' => 'Test content'
        ];
    }
}

class TestCreateUserMutation extends Mutation
{
    protected $name = 'createUser';
    protected $description = 'Create a new user';
    protected $type = 'User';
    protected $args = [
        'input' => 'UserInput!'
    ];
    
    public function resolve($root, array $args, $context, array $info)
    {
        $input = $args['input'] ?? [];
        return [
            'id' => rand(1, 1000),
            'name' => $input['name'] ?? 'New User',
            'email' => $input['email'] ?? 'new@example.com'
        ];
    }
}

class TestUpdateUserMutation extends Mutation
{
    protected $name = 'updateUser';
    protected $description = 'Update an existing user';
    protected $type = 'User';
    protected $args = [
        'id' => 'Int!',
        'input' => 'UserInput!'
    ];
    
    public function resolve($root, array $args, $context, array $info)
    {
        $input = $args['input'] ?? [];
        return [
            'id' => $args['id'] ?? 1,
            'name' => $input['name'] ?? 'Updated User',
            'email' => $input['email'] ?? 'updated@example.com'
        ];
    }
}

class TestDeleteUserMutation extends Mutation
{
    protected $name = 'deleteUser';
    protected $description = 'Delete a user';
    protected $type = 'Boolean';
    protected $args = [
        'id' => 'Int!'
    ];
    
    public function resolve($root, array $args, $context, array $info)
    {
        return true;
    }
}