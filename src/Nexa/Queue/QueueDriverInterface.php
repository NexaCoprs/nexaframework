<?php

namespace Nexa\Queue;

interface QueueDriverInterface
{
    /**
     * Push a job onto the queue
     *
     * @param JobInterface $job
     * @param string $queue
     * @return string Job ID
     */
    public function push(JobInterface $job, $queue);

    /**
     * Pop a job from the queue
     *
     * @param string $queue
     * @return JobInterface|null
     */
    public function pop($queue);

    /**
     * Delete a job from the queue
     *
     * @param JobInterface $job
     * @param string $queue
     * @return bool
     */
    public function delete(JobInterface $job, $queue);

    /**
     * Mark a job as failed
     *
     * @param JobInterface $job
     * @param string $queue
     * @param \Exception $exception
     * @return bool
     */
    public function fail(JobInterface $job, $queue, \Exception $exception);

    /**
     * Get the size of the queue
     *
     * @param string $queue
     * @return int
     */
    public function size($queue);

    /**
     * Clear all jobs from the queue
     *
     * @param string $queue
     * @return int Number of jobs cleared
     */
    public function clear($queue);

    /**
     * Get failed jobs
     *
     * @param string|null $queue
     * @return array
     */
    public function getFailedJobs($queue = null);

    /**
     * Retry a failed job
     *
     * @param string $jobId
     * @return bool
     */
    public function retry($jobId);
}