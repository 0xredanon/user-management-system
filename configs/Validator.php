<?php
/**
 * Validator — Centralized input validation.
 *
 * Usage:
 *   $v = new Validator($_POST);
 *   $v->required('username', 'نام کاربری الزامی است.')
 *     ->min('username', 3, 'نام کاربری باید حداقل ۳ کاراکتر باشد.')
 *     ->max('username', 50, 'نام کاربری نمی‌تواند بیش از ۵۰ کاراکتر باشد.')
 *     ->regex('username', '/^[a-zA-Z0-9_]+$/u', 'نام کاربری فقط شامر حروف، عدد و _ می‌تواند باشد.');
 *   $v->required('email')
 *     ->email('ایمیل معتبر نیست.');
 *   $v->required('password')
 *     ->min('password', 8, 'رمز عبور باید حداقل ۸ کاراکتر باشد.')
 *     ->passwordStrength('رمز عبور باید شامر حروف بزرگ، کوچک، عدد و نماد باشد.');
 *
 *   if (!$v->passes()) {
 *       $errors = $v->errors();
 *   }
 */

require_once __DIR__ . '/env.php';

class Validator
{
    /** @var array */
    private $data;

    /** @var array<string, array<string>> */
    private $errors = [];

    /**
     * Constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Validate that a field is present and not empty.
     *
     * @param string $field
     * @param string $message
     * @return $this
     */
    public function required(string $field, string $message = ''): self
    {
        $value = $this->data[$field] ?? null;

        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, $message ?: "فیلد {$field} الزامی است.");
        }

        return $this;
    }

    /**
     * Validate that a field is a valid email.
     *
     * @param string $field
     * @param string $message
     * @return $this
     */
    public function email(string $field, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';

        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $message ?: 'ایمیل وارد شده معتبر نیست.');
        }

        return $this;
    }

    /**
     * Validate minimum length.
     *
     * @param string $field
     * @param int $min
     * @param string $message
     * @return $this
     */
    public function min(string $field, int $min, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';

        if ($value !== '' && mb_strlen((string) $value) < $min) {
            $this->addError($field, $message ?: "فیلد {$field} باید حداقل {$min} کاراکتر باشد.");
        }

        return $this;
    }

    /**
     * Validate maximum length.
     *
     * @param string $field
     * @param int $max
     * @param string $message
     * @return $this
     */
    public function max(string $field, int $max, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';

        if ($value !== '' && mb_strlen((string) $value) > $max) {
            $this->addError($field, $message ?: "فیلد {$field} نمی‌تواند بیش از {$max} کاراکتر باشد.");
        }

        return $this;
    }

    /**
     * Validate against a regex pattern.
     *
     * @param string $field
     * @param string $pattern
     * @param string $message
     * @return $this
     */
    public function regex(string $field, string $pattern, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';

        if ($value !== '' && !preg_match($pattern, (string) $value)) {
            $this->addError($field, $message ?: "فیلد {$field} فرمت نامعتبر دارد.");
        }

        return $this;
    }

    /**
     * Validate that a field matches another field (e.g. password confirmation).
     *
     * @param string $field
     * @param string $other
     * @param string $message
     * @return $this
     */
    public function matches(string $field, string $other, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';
        $otherValue = $this->data[$other] ?? '';

        if ($value !== '' && $value !== $otherValue) {
            $this->addError($field, $message ?: "فیلد {$field} با {$other} مطابقت ندارد.");
        }

        return $this;
    }

    /**
     * Validate password strength.
     * Requires at least 8 chars, one uppercase, one lowercase, one digit, one special char.
     *
     * @param string $field
     * @param string $message
     * @return $this
     */
    public function passwordStrength(string $field, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';

        if ($value !== '') {
            if (mb_strlen($value) < 8) {
                $this->addError($field, $message ?: 'رمز عبور باید حداقل ۸ کاراکتر باشد.');
            }
            if (!preg_match('/[A-Z]/', $value)) {
                $this->addError($field, $message ?: 'رمز عبور باید شامل حروف بزرگ باشد.');
            }
            if (!preg_match('/[a-z]/', $value)) {
                $this->addError($field, $message ?: 'رمز عبور باید شامر حروف کوچک باشد.');
            }
            if (!preg_match('/[0-9]/', $value)) {
                $this->addError($field, $message ?: 'رمز عبور باید شامر عدد باشد.');
            }
            if (!preg_match('/[^A-Za-z0-9]/', $value)) {
                $this->addError($field, $message ?: 'رمز عبور باید شامر نماد باشد.');
            }
        }

        return $this;
    }

    /**
     * Validate that a field is a valid integer.
     *
     * @param string $field
     * @param string $message
     * @return $this
     */
    public function integer(string $field, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';

        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, $message ?: "فیلد {$field} باید عدد باشد.");
        }

        return $this;
    }

    /**
     * Validate that a field is a valid date.
     *
     * @param string $field
     * @param string $format
     * @param string $message
     * @return $this
     */
    public function date(string $field, string $format = 'Y-m-d', string $message = ''): self
    {
        $value = $this->data[$field] ?? '';

        if ($value !== '') {
            $d = DateTime::createFromFormat($format, (string) $value);
            if (!$d || $d->format($format) !== $value) {
                $this->addError($field, $message ?: "فیلد {$field} تاریخ نامعتبر دارد.");
            }
        }

        return $this;
    }

    /**
     * Validate that a field value is in a set of allowed values.
     *
     * @param string $field
     * @param array $allowed
     * @param string $message
     * @return $this
     */
    public function in(string $field, array $allowed, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';

        if ($value !== '' && !in_array($value, $allowed, true)) {
            $this->addError($field, $message ?: "فیلد {$field} مقدار نامعتبر دارد.");
        }

        return $this;
    }

    /**
     * Validate an uploaded image file.
     *
     * @param string $field
     * @param string $message
     * @return $this
     */
    public function image(string $field, string $message = ''): self
    {
        $file = $_FILES[$field] ?? null;

        if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->addError($field, $message ?: 'خطا در آپلود فایل.');
                return $this;
            }

            // Check file size
            if ($file['size'] > MAX_UPLOAD_SIZE) {
                $this->addError($field, $message ?: 'اندازه فایل بیش از حد مجاز است.');
            }

            // Check MIME type using getimagesize (always available, no extension needed)
            $imgInfo = @getimagesize($file['tmp_name']);
            if ($imgInfo === false) {
                $this->addError($field, $message ?: 'فقط فایل‌های تصویری مجاز هستند.');
            } else {
                $mime = $imgInfo['mime'];
                if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
                    $this->addError($field, $message ?: 'فقط فایل‌های تصویری مجاز هستند.');
                }
            }

            // Check extension
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_IMAGE_EXTENSIONS, true)) {
                $this->addError($field, $message ?: 'فقط فایل‌های تصویری مجاز هستند.');
            }
        }

        return $this;
    }

    /**
     * Check if validation passes.
     *
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation fails.
     *
     * @return bool
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Get all errors.
     *
     * @return array<string, array<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get the first error for a field.
     *
     * @param string $field
     * @return string|null
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Get all errors as a flat array.
     *
     * @return array<string>
     */
    public function allErrors(): array
    {
        $all = [];
        foreach ($this->errors as $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $all[] = $error;
            }
        }
        return $all;
    }

    /**
     * Get validated data (only fields that were present in input).
     *
     * @return array
     */
    public function validated(): array
    {
        return $this->data;
    }

    /**
     * Get a specific validated field.
     *
     * @param string $field
     * @param mixed $default
     * @return mixed
     */
    public function get(string $field, $default = null)
    {
        return $this->data[$field] ?? $default;
    }

    /**
     * Add an error for a field.
     *
     * @param string $field
     * @param string $message
     * @return void
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
}

