<?php

namespace Nexa\Queue;

// use Nexa\Database\Database; // Database class doesn't exist yet
// use Nexa\Logging\Logger; // Logger class doesn't exist yet
use PDO;

class DatabaseQueueDriver implements QueueDriverInterface
{
    /**
     * Driver configuration
     */
    private $config;

    /**
     * Database connection
     */
    private $db;

    /**
     * Logger instance
     */
    private $logger;

    /**
     * Table name for jobs
     */
    private $table;

    /**
     * Table name for failed jobs
     */
    private $failedTable;

    public function __construct($config = [], $logger = null)
    {
        $this->config = array_merge([
            'table' => 'jobs',
            'failed_table' => 'failed_jobs',
            'queue' => 'default',
            'retry_after' => 90
        ], $config ?: []);

        $this->table = $this->config['table'];
        $this->failedTable = $this->config['failed_table'];
        $this->logger = $logger;
        
        try {
            // $this->db = Database::getInstance(); // Database class doesn't exist yet
            $this->db = new PDO('sqlite::memory:'); // Temporary placeholder
        } catch (\Exception $e) {
            // Fallback for testing
            $this->db = null;
        }

        $this->createTablesIfNotExists();
    }

    /**
     * Create tables if they don't exist
     */
    private function createTablesIfNotExists()
    {
        if (!$this->db) {
            return; // Skip table creation if no database connection
        }
        
        // Create jobs table
        $jobsTableSql = "
            CREATE TABLE IF NOT EXISTS {$this->table} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                job_id VARCHAR(255) UNIQUE NOT NULL,
                queue VARCHAR(255) NOT NULL DEFAULT 'default',
                payload TEXT NOT NULL,
                attempts INTEGER NOT NULL DEFAULT 0,
                reserved_at INTEGER NULL,
                available_at INTEGER NOT NULL,
                created_at INTEGER NOT NULL
            )
        ";

        // Create failed jobs table
        $failedJobsTableSql = "
            CREATE TABLE IF NOT EXISTS {$this->failedTable} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                job_id VARCHAR(255) NOT NULL,
                queue VARCHAR(255) NOT NULL,
                payload TEXT NOT NULL,
                exception TEXT NOT NULL,
                failed_at INTEGER NOT NULL
            )
        ";

        try {
            $this->db->exec($jobsTableSql);
            $this->db->exec($failedJobsTableSql);

            // Create indexes
            $this->db->exec("CREATE INDEX IF NOT EXISTS idx_jobs_queue_available ON {$this->table} (queue, available_at)");
            $this->db->exec("CREATE INDEX IF NOT EXISTS idx_jobs_reserved ON {$this->table} (reserved_at)");
            $this->db->exec("CREATE INDEX IF NOT EXISTS idx_failed_jobs_queue ON {$this->failedTable} (queue)");

        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to create queue tables", [
                    'error' => $e->getMessage()
                ]);
            }
            throw $e;
        }
    }

    /**
     * Push a job onto the queue
     *
     * @param JobInterface $job
     * @param string $queue
     * @return string Job ID
     */
    public function push(JobInterface $job, $queue)
    {
        $now = time();
        $availableAt = $now + $job->getDelay();
        
        $sql = "
            INSERT INTO {$this->table} (job_id, queue, payload, attempts, available_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $job->getId(),
                $queue,
                $job->toJson(),
                $job->getAttempts(),
                $availableAt,
                $now
            ]);

            if ($this->logger) {
                $this->logger->debug("Job pushed to database queue", [
                    'job_id' => $job->getId(),
                    'queue' => $queue,
                    'available_at' => date('Y-m-d H:i:s', $availableAt)
                ]);
            }

            return $job->getId();

        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to push job to database queue", [
                    'job_id' => $job->getId(),
                    'error' => $e->getMessage()
                ]);
            }
            throw $e;
        }
    }

    /**
     * Pop a job from the queue
     *
     * @param string $queue
     * @return JobInterface|null
     */
    public function pop($queue)
    {
        $now = time();
        $retryAfter = $this->config['retry_after'];

        // First, release any jobs that have been reserved too long
        $this->releaseReservedJobs($retryAfter);

        // Get the next available job
        $sql = "
            SELECT * FROM {$this->table}
            WHERE queue = ? AND available_at <= ? AND reserved_at IS NULL
            ORDER BY available_at ASC
            LIMIT 1
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$queue, $now]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            // Reserve the job
            $reserveSql = "
                UPDATE {$this->table}
                SET reserved_at = ?
                WHERE id = ? AND reserved_at IS NULL
            ";

            $reserveStmt = $this->db->prepare($reserveSql);
            $reserveStmt->execute([$now, $row['id']]);

            // Check if we successfully reserved the job
            if ($reserveStmt->rowCount() === 0) {
                // Job was reserved by another worker
                return $this->pop($queue); // Try again
            }

            // Create job instance from payload
            $job = Job::fromJson($row['payload']);

            if ($this->logger) {
                $this->logger->debug("Job popped from database queue", [
                    'job_id' => $job->getId(),
                    'queue' => $queue,
                    'attempts' => $job->getAttempts()
                ]);
            }

            return $job;

        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to pop job from database queue", [
                    'queue' => $queue,
                    'error' => $e->getMessage()
                ]);
            }
            return null;
        }
    }

    /**
     * Delete a job from the queue
     *
     * @param JobInterface $job
     * @param string $queue
     * @return bool
     */
    public function delete(JobInterface $job, $queue)
    {
        $sql = "DELETE FROM {$this->table} WHERE job_id = ?";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$job->getId()]);

            if ($this->logger) {
                $this->logger->debug("Job deleted from database queue", [
                    'job_id' => $job->getId(),
                    'queue' => $queue
                ]);
            }

            return $stmt->rowCount() > 0;

        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to delete job from database queue", [
                    'job_id' => $job->getId(),
                    'error' => $e->getMessage()
                ]);
            }
            return false;
        }
    }

    /**
     * Mark a job as failed
     *
     * @param JobInterface $job
     * @param string $queue
     * @param \Exception $exception
     * @return bool
     */
    public function fail(JobInterface $job, $queue, \Exception $exception)
    {
        $now = time();

        // Insert into failed jobs table
        $insertSql = "
            INSERT INTO {$this->failedTable} (job_id, queue, payload, exception, failed_at)
            VALUES (?, ?, ?, ?, ?)
        ";

        // Delete from jobs table
        $deleteSql = "DELETE FROM {$this->table} WHERE job_id = ?";

        try {
            $this->db->beginTransaction();

            // Insert failed job
            $insertStmt = $this->db->prepare($insertSql);
            $insertStmt->execute([
                $job->getId(),
                $queue,
                $job->toJson(),
                $exception->getMessage() . "\n" . $exception->getTraceAsString(),
                $now
            ]);

            // Delete from jobs table
            $deleteStmt = $this->db->prepare($deleteSql);
            $deleteStmt->execute([$job->getId()]);

            $this->db->commit();

            if ($this->logger) {
                $this->logger->debug("Job marked as failed in database", [
                    'job_id' => $job->getId(),
                    'queue' => $queue,
                    'error' => $exception->getMessage()
                ]);
            }

            return true;

        } catch (\Exception $e) {
            $this->db->rollBack();

            if ($this->logger) {
                $this->logger->error("Failed to mark job as failed in database", [
                    'job_id' => $job->getId(),
                    'error' => $e->getMessage()
                ]);
            }

            return false;
        }
    }

    /**
     * Get the size of the queue
     *
     * @param string $queue
     * @return int
     */
    public function size($queue)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE queue = ? AND reserved_at IS NULL";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$queue]);
            return (int) $stmt->fetchColumn();

        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to get queue size", [
                    'queue' => $queue,
                    'error' => $e->getMessage()
                ]);
            }
            return 0;
        }
    }

    /**
     * Clear all jobs from the queue
     *
     * @param string $queue
     * @return int Number of jobs cleared
     */
    public function clear($queue)
    {
        $sql = "DELETE FROM {$this->table} WHERE queue = ?";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$queue]);
            $count = $stmt->rowCount();

            if ($this->logger) {
                $this->logger->info("Queue cleared", [
                    'queue' => $queue,
                    'jobs_cleared' => $count
                ]);
            }

            return $count;

        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to clear queue", [
                    'queue' => $queue,
                    'error' => $e->getMessage()
                ]);
            }
            return 0;
        }
    }

    /**
     * Release jobs that have been reserved too long
     *
     * @param int $retryAfter
     * @return int Number of jobs released
     */
    private function releaseReservedJobs($retryAfter)
    {
        $expiredTime = time() - $retryAfter;
        $sql = "
            UPDATE {$this->table}
            SET reserved_at = NULL, attempts = attempts + 1
            WHERE reserved_at IS NOT NULL AND reserved_at < ?
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$expiredTime]);
            $count = $stmt->rowCount();

            if ($count > 0 && $this->logger) {
                $this->logger->info("Released expired reserved jobs", [
                    'count' => $count,
                    'retry_after' => $retryAfter
                ]);
            }

            return $count;

        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to release reserved jobs", [
                    'error' => $e->getMessage()
                ]);
            }
            return 0;
        }
    }

    /**
     * Get failed jobs
     *
     * @param string|null $queue
     * @return array
     */
    public function getFailedJobs($queue = null)
    {
        if ($queue) {
            $sql = "SELECT * FROM {$this->failedTable} WHERE queue = ? ORDER BY failed_at DESC";
        } else {
            $sql = "SELECT * FROM {$this->failedTable} ORDER BY failed_at DESC";
        }

        try {
            $stmt = $this->db->prepare($sql);
            if ($queue) {
                $stmt->execute([$queue]);
            } else {
                $stmt->execute();
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to get failed jobs", [
                    'error' => $e->getMessage()
                ]);
            }
            return [];
        }
    }

    /**
     * Retry a failed job
     *
     * @param string $jobId
     * @return bool
     */
    public function retry($jobId)
    {
        // Get failed job
        $selectSql = "SELECT * FROM {$this->failedTable} WHERE job_id = ?";
        
        try {
            $stmt = $this->db->prepare($selectSql);
            $stmt->execute([$jobId]);
            $failedJob = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$failedJob) {
                return false;
            }

            $this->db->beginTransaction();

            // Insert back into jobs table
            $insertSql = "
                INSERT INTO {$this->table} (job_id, queue, payload, attempts, available_at, created_at)
                VALUES (?, ?, ?, 0, ?, ?)
            ";

            $now = time();
            $insertStmt = $this->db->prepare($insertSql);
            $insertStmt->execute([
                $failedJob['job_id'],
                $failedJob['queue'],
                $failedJob['payload'],
                $now,
                $now
            ]);

            // Remove from failed jobs table
            $deleteSql = "DELETE FROM {$this->failedTable} WHERE job_id = ?";
            $deleteStmt = $this->db->prepare($deleteSql);
            $deleteStmt->execute([$jobId]);

            $this->db->commit();

            if ($this->logger) {
                $this->logger->info("Failed job retried", [
                    'job_id' => $jobId,
                    'queue' => $failedJob['queue']
                ]);
            }

            return true;

        } catch (\Exception $e) {
            $this->db->rollBack();

            if ($this->logger) {
                $this->logger->error("Failed to retry job", [
                    'job_id' => $jobId,
                    'error' => $e->getMessage()
                ]);
            }

            return false;
        }
    }
}