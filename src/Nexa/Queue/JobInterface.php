<?php

namespace Nexa\Queue;

interface JobInterface
{
    /**
     * Execute the job
     *
     * @return void
     */
    public function handle();

    /**
     * Get the job name/identifier
     *
     * @return string
     */
    public function getName();

    /**
     * Get the unique job ID
     *
     * @return string
     */
    public function getId();

    /**
     * Get the job data
     *
     * @return array
     */
    public function getData();

    /**
     * Get the number of times the job may be attempted
     *
     * @return int
     */
    public function getMaxAttempts();

    /**
     * Get the number of seconds the job can run before timing out
     *
     * @return int
     */
    public function getTimeout();

    /**
     * Get the delay before the job should be processed
     *
     * @return int
     */
    public function getDelay();

    /**
     * Get the queue name this job should be processed on
     *
     * @return string
     */
    public function getQueue();

    /**
     * Set the delay before the job should be processed
     *
     * @param int $delay
     * @return $this
     */
    public function setDelay($delay);

    /**
     * Set the queue name this job should be processed on
     *
     * @param string $queue
     * @return $this
     */
    public function setQueue($queue);

    /**
     * Get current attempt number
     *
     * @return int
     */
    public function getAttempts();

    /**
     * Increment attempt counter
     *
     * @return $this
     */
    public function incrementAttempts();

    /**
     * Handle a job failure
     *
     * @param \Exception $exception
     * @return void
     */
    public function failed(\Exception $exception);

    /**
     * Determine if the job should be retried
     *
     * @param \Exception $exception
     * @return bool
     */
    public function shouldRetry(\Exception $exception);

    /**
     * Retry a failed job
     *
     * @param string $jobId
     * @return bool
     */
    public function retry($jobId);

    /**
     * Serialize job to JSON
     *
     * @return string
     */
    public function toJson();

 
}


