<?php
/**
 * Rate Limiter — File-based rate limiting system.
 *
 * Uses the database (rate_limits table) for tracking.
 * Falls back to file-based storage if DB is unavailable.
 *
 * Usage:
 *   $limiter = new RateLimiter('login:' . get_client_ip(), 5, 300);
 *   if (!$limiter->attempt()) {
 *       // Rate limit exceeded
 *       http_response_code(429);
 *       exit();
 *   }
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';

class RateLimiter
{
    /** @var string */
    private $key;

    /** @var int */
    private $maxAttempts;

    /** @var int */
    private $windowSeconds;

    /** @var string|null */
    private $storagePath;

    /**
     * Constructor.
     *
     * @param string $key           Unique key for this rate limit (e.g. "login:192.168.1.1")
     * @param int    $maxAttempts   Maximum allowed attempts in the window
     * @param int    $windowSeconds Window size in seconds
     */
    public function __construct(string $key, int $maxAttempts, int $windowSeconds)
    {
        $this->key = $key;
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
        $this->storagePath = BASE_PATH . '/storage/rate_limits';
    }

    /**
     * Attempt a rate-limited action.
     * Returns true if allowed, false if rate limit exceeded.
     *
     * @return bool
     */
    public function attempt(): bool
    {
        if (!RATE_LIMIT_ENABLED) {
            return true;
        }

        $current = $this->getAttempts();

        if ($current >= $this->maxAttempts) {
            return false;
        }

        $this->increment();
        return true;
    }

    /**
     * Check if the rate limit has been exceeded.
     *
     * @return bool
     */
    public function isExceeded(): bool
    {
        return $this->getAttempts() >= $this->maxAttempts;
    }

    /**
     * Get the number of remaining attempts.
     *
     * @return int
     */
    public function remaining(): int
    {
        return max(0, $this->maxAttempts - $this->getAttempts());
    }

    /**
     * Get the number of seconds until the window resets.
     *
     * @return int
     */
    public function retryAfter(): int
    {
        $expiresAt = $this->getExpiresAt();
        return max(0, $expiresAt - time());
    }

    /**
     * Clear the rate limit counter (e.g. on successful login).
     *
     * @return void
     */
    public function clear(): void
    {
        $this->deleteFromDatabase();
        $this->deleteFromFile();
    }

    /**
     * Get the current number of attempts.
     *
     * @return int
     */
    private function getAttempts(): int
    {
        // Try database first
        $attempts = $this->getFromDatabase();
        if ($attempts !== null) {
            return $attempts;
        }

        // Fall back to file-based storage
        return $this->getFromFile() ?? 0;
    }

    /**
     * Get the expiry timestamp.
     *
     * @return int
     */
    private function getExpiresAt(): int
    {
        $expiresAt = $this->getExpiresFromDatabase();
        if ($expiresAt !== null) {
            return $expiresAt;
        }

        $expiresAt = $this->getExpiresFromFile();
        return $expiresAt ?? (time() + $this->windowSeconds);
    }

    /**
     * Increment the attempt counter.
     *
     * @return void
     */
    private function increment(): void
    {
        // Try database first
        if ($this->incrementDatabase()) {
            return;
        }

        // Fall back to file-based storage
        $this->incrementFile();
    }

    // ─── Database Storage ────────────────────────────────────

    /**
     * Get attempts from the database.
     *
     * @return int|null  Returns null if not found or DB error
     */
    private function getFromDatabase(): ?int
    {
        try {
            $db = Database::getInstance();
            $row = $db->fetch(
                "SELECT count, expires_at FROM rate_limits WHERE `key` = ?",
                [$this->key]
            );

            if (!$row) {
                return null;
            }

            // Check if expired
            if (time() > strtotime($row['expires_at'])) {
                $this->deleteFromDatabase();
                return null;
            }

            return (int) $row['count'];
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Get expiry from the database.
     *
     * @return int|null
     */
    private function getExpiresFromDatabase(): ?int
    {
        try {
            $db = Database::getInstance();
            $row = $db->fetch(
                "SELECT expires_at FROM rate_limits WHERE `key` = ?",
                [$this->key]
            );

            if (!$row) {
                return null;
            }

            return strtotime($row['expires_at']);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Increment the counter in the database.
     *
     * @return bool
     */
    private function incrementDatabase(): bool
    {
        try {
            $db = Database::getInstance();
            $expiresAt = date('Y-m-d H:i:s', time() + $this->windowSeconds);

            // Try to insert (first attempt)
            $result = $db->prepare(
                "INSERT INTO rate_limits (`key`, count, expires_at)
                 VALUES (?, 1, ?)
                 ON DUPLICATE KEY UPDATE count = count + 1"
            )->execute([$this->key, $expiresAt]);

            return $result;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Delete the rate limit record from the database.
     *
     * @return void
     */
    private function deleteFromDatabase(): void
    {
        try {
            $db = Database::getInstance();
            $db->prepare("DELETE FROM rate_limits WHERE `key` = ?")->execute([$this->key]);
        } catch (Throwable $e) {
            // Ignore
        }
    }

    // ─── File Storage (fallback) ─────────────────────────────

    /**
     * Get the file path for this rate limit key.
     *
     * @return string
     */
    private function getFilePath(): string
    {
        // Sanitize key for use as filename
        $safeKey = md5($this->key);
        return $this->storagePath . '/' . $safeKey . '.json';
    }

    /**
     * Get attempts from file.
     *
     * @return int|null
     */
    private function getFromFile(): ?int
    {
        $path = $this->getFilePath();

        if (!file_exists($path)) {
            return null;
        }

        $data = json_decode(file_get_contents($path), true);

        if (!$data || !isset($data['expires_at'], $data['count'])) {
            return null;
        }

        // Check if expired
        if (time() > $data['expires_at']) {
            $this->deleteFromFile();
            return null;
        }

        return (int) $data['count'];
    }

    /**
     * Get expiry from file.
     *
     * @return int|null
     */
    private function getExpiresFromFile(): ?int
    {
        $path = $this->getFilePath();

        if (!file_exists($path)) {
            return null;
        }

        $data = json_decode(file_get_contents($path), true);

        if (!$data || !isset($data['expires_at'])) {
            return null;
        }

        return (int) $data['expires_at'];
    }

    /**
     * Increment the counter in a file.
     *
     * @return void
     */
    private function incrementFile(): void
    {
        $path = $this->getFilePath();

        if (!is_dir($this->storagePath)) {
            @mkdir($this->storagePath, 0755, true);
        }

        $count = $this->getFromFile() ?? 0;
        $expiresAt = $count > 0
            ? $this->getExpiresFromFile() ?? (time() + $this->windowSeconds)
            : time() + $this->windowSeconds;

        $data = [
            'count'      => $count + 1,
            'expires_at' => $expiresAt,
        ];

        file_put_contents($path, json_encode($data), LOCK_EX);
    }

    /**
     * Delete the rate limit file.
     *
     * @return void
     */
    private function deleteFromFile(): void
    {
        $path = $this->getFilePath();
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}

/**
 * Helper function to check rate limit for login attempts.
 *
 * @return RateLimiter
 */
function login_rate_limiter(): RateLimiter
{
    return new RateLimiter(
        'login:' . get_client_ip(),
        RATE_LIMIT_LOGIN_MAX,
        RATE_LIMIT_LOGIN_WINDOW
    );
}

/**
 * Helper function to check rate limit for registration attempts.
 *
 * @return RateLimiter
 */
function register_rate_limiter(): RateLimiter
{
    return new RateLimiter(
        'register:' . get_client_ip(),
        RATE_LIMIT_REGISTER_MAX,
        RATE_LIMIT_REGISTER_WINDOW
    );
}

/**
 * Helper function to check rate limit for API requests.
 *
 * @return RateLimiter
 */
function api_rate_limiter(): RateLimiter
{
    return new RateLimiter(
        'api:' . get_client_ip(),
        RATE_LIMIT_API_MAX,
        RATE_LIMIT_API_WINDOW
    );
}

/**
 * Helper function to check rate limit for profile updates.
 *
 * @return RateLimiter
 */
function profile_rate_limiter(): RateLimiter
{
    return new RateLimiter(
        'profile:' . get_client_ip(),
        RATE_LIMIT_PROFILE_MAX,
        RATE_LIMIT_PROFILE_WINDOW
    );
}

