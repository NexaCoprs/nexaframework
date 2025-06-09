<?php

namespace Nexa\Queue;

// use Nexa\Logging\Logger; // Commented out as Logger class doesn't exist yet

class SyncQueueDriver implements QueueDriverInterface
{
    /**
     * Driver configuration
     */
    private $config;

    /**
     * Logger instance
     */
    private $logger;

    public function __construct($config = [], $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Push a job onto the queue (execute immediately)
     *
     * @param JobInterface $job
     * @param string $queue
     * @return string Job ID
     */
    public function push(JobInterface $job, $queue)
    {
        $startTime = microtime(true);
        
        try {
            // Log job execution start
            if ($this->logger) {
                $this->logger->info("Sync job started: {$job->getName()}", [
                    'job_id' => $job->getId(),
                    'queue' => $queue
                ]);
            }

            // Execute job immediately
            $job->handle();

            $executionTime = microtime(true) - $startTime;

            // Log successful execution
            if ($this->logger) {
                $this->logger->info("Sync job completed: {$job->getName()}", [
                    'job_id' => $job->getId(),
                    'execution_time' => $executionTime
                ]);
            }

            return $job->getId();

        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;

            // Log job failure
            if ($this->logger) {
                $this->logger->error("Sync job failed: {$job->getName()}", [
                    'job_id' => $job->getId(),
                    'error' => $e->getMessage(),
                    'execution_time' => $executionTime,
                    'trace' => $e->getTraceAsString()
                ]);
            }

            // Call job's failed method
            $job->failed($e);

            // Re-throw exception
            throw $e;
        }
    }

    /**
     * Pop a job from the queue (not applicable for sync driver)
     *
     * @param string $queue
     * @return JobInterface|null
     */
    public function pop($queue)
    {
        // Sync driver doesn't queue jobs, so nothing to pop
        return null;
    }

    /**
     * Delete a job from the queue (not applicable for sync driver)
     *
     * @param JobInterface $job
     * @param string $queue
     * @return bool
     */
    public function delete(JobInterface $job, $queue)
    {
        // Jobs are executed immediately, so nothing to delete
        return true;
    }

    /**
     * Mark a job as failed (not applicable for sync driver)
     *
     * @param JobInterface $job
     * @param string $queue
     * @param \Exception $exception
     * @return bool
     */
    public function fail(JobInterface $job, $queue, \Exception $exception)
    {
        // Sync driver handles failures immediately
        return true;
    }

    /**
     * Get the size of the queue (always 0 for sync driver)
     *
     * @param string $queue
     * @return int
     */
    public function size($queue)
    {
        // Sync driver doesn't queue jobs
        return 0;
    }

    /**
     * Clear all jobs from the queue (not applicable for sync driver)
     *
     * @param string $queue
     * @return int Number of jobs cleared
     */
    public function clear($queue)
    {
        // Sync driver doesn't queue jobs
        return 0;
    }

    /**
     * Get failed jobs (not applicable for sync driver)
     *
     * @param string|null $queue
     * @return array
     */
    public function getFailedJobs($queue = null)
    {
        // Sync driver doesn't store failed jobs
        return [];
    }

    /**
     * Retry a failed job (not applicable for sync driver)
     *
     * @param string $jobId
     * @return bool
     */
    public function retry($jobId)
    {
        // Sync driver doesn't store failed jobs, so nothing to retry
        return false;
    }
}