<?php

namespace Nexa\Queue;

abstract class Job implements JobInterface
{
    /**
     * Job data
     */
    protected $data = [];

    /**
     * Maximum number of attempts
     */
    protected $maxAttempts = 3;

    /**
     * Job timeout in seconds
     */
    protected $timeout = 60;

    /**
     * Delay before processing in seconds
     */
    protected $delay = 0;

    /**
     * Queue name
     */
    protected $queue = 'default';

    /**
     * Job name/identifier
     */
    protected $name;

    /**
     * Current attempt number
     */
    protected $attempts = 0;

    /**
     * Job ID
     */
    protected $id;

    /**
     * Job creation timestamp
     */
    protected $createdAt;

    public function __construct($data = [])
    {
        $this->data = $data;
        $this->name = basename(str_replace('\\', '/', static::class));
        $this->id = uniqid('job_', true);
        $this->createdAt = microtime(true);
    }

    /**
     * Execute the job - must be implemented by child classes
     *
     * @return void
     */
    abstract public function handle();

    /**
     * Get the job name/identifier
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the job data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set job data
     *
     * @param array $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get specific data by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set specific data by key
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Get the number of times the job may be attempted
     *
     * @return int
     */
    public function getMaxAttempts()
    {
        return $this->maxAttempts;
    }

    /**
     * Set the maximum number of attempts
     *
     * @param int $attempts
     * @return $this
     */
    public function setMaxAttempts($attempts)
    {
        $this->maxAttempts = $attempts;
        return $this;
    }

    /**
     * Get the number of seconds the job can run before timing out
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Set the job timeout
     *
     * @param int $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Get the delay before the job should be processed
     *
     * @return int
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * Set the job delay
     *
     * @param int $delay
     * @return $this
     */
    public function setDelay($delay)
    {
        $this->delay = $delay;
        return $this;
    }

    /**
     * Get the queue name this job should be processed on
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Set the queue name
     *
     * @param string $queue
     * @return $this
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * Get job ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get current attempt number
     *
     * @return int
     */
    public function getAttempts()
    {
        return $this->attempts;
    }

    /**
     * Increment attempt counter
     *
     * @return $this
     */
    public function incrementAttempts()
    {
        $this->attempts++;
        return $this;
    }

    /**
     * Get job creation timestamp
     *
     * @return float
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Handle a job failure
     *
     * @param \Exception $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        // Default implementation - can be overridden
        error_log("Job {$this->getName()} failed: " . $exception->getMessage());
    }

    /**
     * Determine if the job should be retried
     *
     * @param \Exception $exception
     * @return bool
     */
    public function shouldRetry(\Exception $exception)
    {
        return $this->attempts < $this->maxAttempts;
    }

    /**
     * Convert job to array for serialization
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'class' => static::class,
            'data' => $this->data,
            'queue' => $this->queue,
            'delay' => $this->delay,
            'timeout' => $this->timeout,
            'max_attempts' => $this->maxAttempts,
            'attempts' => $this->attempts,
            'created_at' => $this->createdAt
        ];
    }

    /**
     * Create job from array
     *
     * @param array $data
     * @return static
     */
    public static function fromArray($data)
    {
        $class = $data['class'];
        $job = new $class($data['data']);
        $job->id = $data['id'];
        $job->name = $data['name'];
        $job->queue = $data['queue'];
        $job->delay = $data['delay'];
        $job->timeout = $data['timeout'];
        $job->maxAttempts = $data['max_attempts'];
        $job->attempts = $data['attempts'];
        $job->createdAt = $data['created_at'];
        
        return $job;
    }

    /**
     * Serialize job to JSON
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Create job from JSON
     *
     * @param string $json
     * @return static
     */
    public static function fromJson($json)
    {
        $data = json_decode($json, true);
        return static::fromArray($data);
    }
}