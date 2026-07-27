<?php
/**
 * Error Handler — Centralized error & exception handling.
 *
 * Errors are:
 *   - Consistent (structured JSON for APIs, flash messages for web)
 *   - Human-friendly (clear messages)
 *   - Developer-friendly (detailed in debug mode)
 *   - Localized-ready (messages can be translated)
 *   - Easy to debug (logged with context)
 *
 * Usage:
 *   require_once 'ErrorHandler.php';
 *   ErrorHandler::register();
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Response.php';

class ErrorHandler
{
    /** @var bool */
    private static $registered = false;

    /**
     * Register the error and exception handlers.
     *
     * @return void
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        // Set error handler
        set_error_handler([self::class, 'handleError']);

        // Set exception handler
        set_exception_handler([self::class, 'handleException']);

        // Register shutdown function to catch fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);

        // Set error reporting level
        if (APP_DEBUG) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
            ini_set('display_errors', '0');
        }
    }

    /**
     * Handle PHP errors.
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return bool
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        // Ignore suppressed errors
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $error = [
            'type'    => self::errorType($errno),
            'message' => $errstr,
            'file'    => $errfile,
            'line'    => $errline,
        ];

        self::logError($error);

        if (APP_DEBUG) {
            self::renderErrorPage($error);
        } else {
            // In production, show a generic error
            self::renderErrorPage([
                'type'    => 'خطا',
                'message' => 'یک خطا رخ داده است. لطفاً دوباره تلاش کنید.',
                'file'    => '',
                'line'    => 0,
            ]);
        }

        return true;
    }

    /**
     * Handle uncaught exceptions.
     *
     * @param Throwable $e
     * @return void
     */
    public static function handleException(Throwable $e): void
    {
        $error = [
            'type'    => get_class($e),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ];

        self::logError($error);

        // If it's an AJAX request, send JSON
        if (self::isAjax()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success'    => false,
                'message'    => APP_DEBUG ? $e->getMessage() : 'خطای سرور.',
                'code'       => 'server_error',
                'errors'     => APP_DEBUG ? ['exception' => get_class($e), 'trace' => $e->getTraceAsString()] : null,
                'timestamp'  => time(),
                'request_id' => request_id(),
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit();
        }

        // For web requests, render error page
        self::renderErrorPage($error);
    }

    /**
     * Handle fatal errors (shutdown).
     *
     * @return void
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $formatted = [
                'type'    => self::errorType($error['type']),
                'message' => $error['message'],
                'file'    => $error['file'],
                'line'    => $error['line'],
            ];

            self::logError($formatted);
            self::renderErrorPage($formatted);
        }
    }

    /**
     * Log an error to the log file.
     *
     * @param array $error
     * @return void
     */
    private static function logError(array $error): void
    {
        $logEntry = sprintf(
            "[%s] %s: %s in %s on line %d\n",
            date('Y-m-d H:i:s'),
            $error['type'],
            $error['message'],
            $error['file'] ?? 'unknown',
            $error['line'] ?? 0
        );

        if (isset($error['trace'])) {
            $logEntry .= "Stack trace:\n" . $error['trace'] . "\n";
        }

        $logEntry .= "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
        $logEntry .= "Client IP: " . get_client_ip() . "\n";
        $logEntry .= "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "\n";
        $logEntry .= str_repeat('-', 80) . "\n";

        // Ensure logs directory exists
        if (!is_dir(LOGS_PATH)) {
            @mkdir(LOGS_PATH, 0755, true);
        }

        error_log($logEntry, 3, LOGS_PATH . '/errors.log');
    }

    /**
     * Render an error page.
     *
     * @param array $error
     * @return void
     */
    private static function renderErrorPage(array $error): void
    {
        // If headers already sent, just output
        if (headers_sent()) {
            echo '<!DOCTYPE html><html><head><title>Error</title></head><body>';
            echo '<h1>' . e($error['type']) . '</h1>';
            echo '<p>' . e($error['message']) . '</p>';
            if (APP_DEBUG && !empty($error['file'])) {
                echo '<p>File: ' . e($error['file']) . ' on line ' . (int) $error['line'] . '</p>';
            }
            echo '</body></html>';
            return;
        }

        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>خطا — <?= e(APP_NAME) ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Inter', sans-serif;
                    background: #1c1815;
                    color: #f2ead9;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 24px;
                }
                .error-container {
                    max-width: 560px;
                    width: 100%;
                    background: rgba(36, 30, 24, 0.72);
                    border: 1px solid #3a332c;
                    border-radius: 16px;
                    padding: 32px;
                    backdrop-filter: blur(18px);
                    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
                    text-align: center;
                }
                .error-icon {
                    width: 72px;
                    height: 72px;
                    margin: 0 auto 24px;
                    border-radius: 50%;
                    background: rgba(214, 91, 91, 0.18);
                    border: 1px solid rgba(214, 91, 91, 0.4);
                    color: #d65b5b;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 32px;
                    font-weight: 700;
                }
                .error-container h1 {
                    font-size: 1.5rem;
                    margin-bottom: 12px;
                    color: #f2ead9;
                }
                .error-container p {
                    color: #a89a86;
                    line-height: 1.6;
                    margin-bottom: 20px;
                }
                .error-container .error-detail {
                    background: rgba(0, 0, 0, 0.2);
                    border-radius: 8px;
                    padding: 16px;
                    margin-top: 20px;
                    text-align: left;
                    font-size: 0.85rem;
                    color: #a89a86;
                    white-space: pre-wrap;
                    word-break: break-word;
                }
                .back-btn {
                    display: inline-block;
                    margin-top: 24px;
                    padding: 12px 28px;
                    background: #e8a659;
                    color: #241a0e;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: 600;
                    transition: background 0.25s;
                }
                .back-btn:hover {
                    background: #f2b96e;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon">!</div>
                <h1><?= e($error['type']) ?></h1>
                <p><?= e($error['message']) ?></p>
                <?php if (APP_DEBUG && !empty($error['file'])): ?>
                    <div class="error-detail">
                        <strong>فایل:</strong> <?= e($error['file']) ?>
                        <strong>خط:</strong> <?= (int) $error['line'] ?>
                        <?php if (!empty($error['trace'])): ?>
                            <strong>استک:</strong> <?= e($error['trace']) ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <a href="javascript:history.back()" class="back-btn">بازگشت</a>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Get a human-readable error type name.
     *
     * @param int $errno
     * @return string
     */
    private static function errorType(int $errno): string
    {
        $types = [
            E_ERROR             => 'Fatal Error',
            E_WARNING           => 'Warning',
            E_PARSE             => 'Parse Error',
            E_NOTICE            => 'Notice',
            E_CORE_ERROR        => 'Core Error',
            E_CORE_WARNING      => 'Core Warning',
            E_COMPILE_ERROR     => 'Compile Error',
            E_COMPILE_WARNING   => 'Compile Warning',
            E_USER_ERROR        => 'User Error',
            E_USER_WARNING      => 'User Warning',
            E_USER_NOTICE       => 'User Notice',
            E_STRICT            => 'Runtime Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED        => 'Deprecated',
            E_USER_DEPRECATED   => 'User Deprecated',
        ];

        return $types[$errno] ?? 'Unknown Error';
    }

    /**
     * Check if the current request is an AJAX request.
     *
     * @return bool
     */
    private static function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

