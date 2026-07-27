<?php
/**
 * Response — Structured JSON response system.
 *
 * Every API/backend response includes:
 *   success    bool
 *   message    string
 *   code       string
 *   data       mixed
 *   errors     array
 *   timestamp  int
 *   request_id string
 *
 * Usage:
 *   // Success
 *   Response::success('User created successfully', $userData);
 *
 *   // Error
 *   Response::error('Validation failed', 'validation_error', $validator->errors());
 *
 *   // Redirect (for form submissions)
 *   Response::redirect('../views/users.php', 'success', 'User created.');
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/helpers.php';

class Response
{
    /**
     * Send a success response.
     *
     * @param string $message
     * @param mixed $data
     * @param int $httpCode
     * @return void
     */
    public static function success(string $message = '', $data = null, int $httpCode = 200): void
    {
        self::send(true, $message, 'success', $data, [], $httpCode);
    }

    /**
     * Send an error response.
     *
     * @param string $message
     * @param string $code
     * @param mixed $errors
     * @param int $httpCode
     * @return void
     */
    public static function error(string $message = '', string $code = 'error', $errors = null, int $httpCode = 400): void
    {
        self::send(false, $message, $code, null, $errors, $httpCode);
    }

    /**
     * Send a validation error response.
     *
     * @param mixed $errors
     * @param string $message
     * @return void
     */
    public static function validationError($errors, string $message = 'داده‌های ورودی نامعتبر هستند.'): void
    {
        self::send(false, $message, 'validation_error', null, $errors, 422);
    }

    /**
     * Send an unauthorized response.
     *
     * @param string $message
     * @return void
     */
    public static function unauthorized(string $message = 'دسترسی غیرمجاز.'): void
    {
        self::send(false, $message, 'unauthorized', null, null, 401);
    }

    /**
     * Send a forbidden response.
     *
     * @param string $message
     * @return void
     */
    public static function forbidden(string $message = 'دسترسی شما به این بخش محدود شده است.'): void
    {
        self::send(false, $message, 'forbidden', null, null, 403);
    }

    /**
     * Send a not found response.
     *
     * @param string $message
     * @return void
     */
    public static function notFound(string $message = 'منبع یافت نشد.'): void
    {
        self::send(false, $message, 'not_found', null, null, 404);
    }

    /**
     * Send a rate limited response.
     *
     * @param int $retryAfter
     * @param string $message
     * @return void
     */
    public static function rateLimited(int $retryAfter = 60, string $message = 'درخواست بیش از حد. لطفاً دوباره تلاش کنید.'): void
    {
        header('Retry-After: ' . $retryAfter);
        self::send(false, $message, 'rate_limited', null, null, 429);
    }

    /**
     * Send a server error response.
     *
     * @param string $message
     * @param mixed $errors
     * @return void
     */
    public static function serverError(string $message = 'خطای سرور.', $errors = null): void
    {
        self::send(false, $message, 'server_error', null, $errors, 500);
    }

    /**
     * Redirect with a flash message (for form submissions).
     *
     * @param string $url
     * @param string $type
     * @param string $message
     * @return void
     */
    public static function redirect(string $url, string $type = 'success', string $message = ''): void
    {
        redirect_with_flash($url, $type, $message);
    }

    /**
     * Send the structured response.
     *
     * @param bool $success
     * @param string $message
     * @param string $code
     * @param mixed $data
     * @param mixed $errors
     * @param int $httpCode
     * @return void
     */
    private static function send(
        bool $success,
        string $message,
        string $code,
        $data = null,
        $errors = null,
        int $httpCode = 200
    ): void {
        // If this is an AJAX request, send JSON
        if (self::isAjaxRequest()) {
            http_response_code($httpCode);
            header('Content-Type: application/json; charset=utf-8');

            $response = [
                'success'    => $success,
                'message'    => $message,
                'code'       => $code,
                'data'       => $data,
                'errors'     => $errors,
                'timestamp'  => time(),
                'request_id' => request_id(),
            ];

            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit();
        }

        // For non-AJAX requests, use flash messages and redirect
        if ($success) {
            redirect_with_flash($_SERVER['HTTP_REFERER'] ?? 'dashboard.php', 'success', $message);
        } else {
            redirect_with_flash($_SERVER['HTTP_REFERER'] ?? 'dashboard.php', 'error', $message);
        }
    }

    /**
     * Check if the current request is an AJAX request.
     *
     * @return bool
     */
    private static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

