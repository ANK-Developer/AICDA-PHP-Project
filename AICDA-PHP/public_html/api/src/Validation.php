<?php
declare(strict_types=1);

// Joi-style validation (same rules and messages as AICDA-Backend/src/validation).
// Rule keys: type (string|number|date), required, allow, valid, ref, trim, min,
// max, length, email, pattern, integer, iso, messages. Unknown body keys are
// rejected like Joi.object() does by default.
final class Schema
{
    public static function validate(array $schema, array $body): array
    {
        $errors = [];

        foreach ($schema as $key => $rule) {
            $present = array_key_exists($key, $body);
            foreach (self::check($key, $rule, $present, $present ? $body[$key] : null, $body) as $message) {
                $errors[] = $message;
            }
        }

        foreach (array_keys($body) as $key) {
            if (!array_key_exists($key, $schema)) {
                $errors[] = "\"$key\" is not allowed";
            }
        }

        return $errors;
    }

    // Middleware equivalent of middlewares/validate.js.
    public static function middleware(array $schema): callable
    {
        return function (Request $request) use ($schema): ?array {
            $errors = self::validate($schema, $request->body);
            return $errors
                ? json_reply(400, ['success' => false, 'message' => 'Validation failed', 'errors' => $errors])
                : null;
        };
    }

    private static function check(string $key, array $rule, bool $present, $value, array $body): array
    {
        $label = "\"$key\"";
        $msg = fn (string $code, string $default) => $rule['messages'][$code] ?? $default;

        if (!$present) {
            return !empty($rule['required']) ? [$msg('any.required', "$label is required")] : [];
        }

        if (isset($rule['allow']) && in_array($value, $rule['allow'], true)) {
            return [];
        }

        if (isset($rule['valid'])) {
            return in_array($value, $rule['valid'], true)
                ? []
                : [$msg('any.only', "$label must be one of [" . implode(', ', $rule['valid']) . ']')];
        }

        if (isset($rule['ref'])) {
            return array_key_exists($rule['ref'], $body) && $body[$rule['ref']] === $value
                ? []
                : [$msg('any.only', "$label must be [ref:{$rule['ref']}]")];
        }

        $errors = [];

        switch ($rule['type']) {
            case 'string':
                if (!is_string($value)) {
                    return [$msg('string.base', "$label must be a string")];
                }
                $text = !empty($rule['trim']) ? trim($value) : $value;
                if ($text === '') {
                    return in_array('', $rule['allow'] ?? [], true) ? [] : [$msg('string.empty', "$label is not allowed to be empty")];
                }
                $length = mb_strlen($text);
                if (isset($rule['min']) && $length < $rule['min']) {
                    $errors[] = $msg('string.min', "$label length must be at least {$rule['min']} characters long");
                }
                if (isset($rule['max']) && $length > $rule['max']) {
                    $errors[] = $msg('string.max', "$label length must be less than or equal to {$rule['max']} characters long");
                }
                if (isset($rule['length']) && $length !== $rule['length']) {
                    $errors[] = $msg('string.length', "$label length must be {$rule['length']} characters long");
                }
                if (!empty($rule['email']) && !self::isEmail($text)) {
                    $errors[] = $msg('string.email', "$label must be a valid email");
                }
                if (isset($rule['pattern']) && !preg_match($rule['pattern'], $text)) {
                    $errors[] = $msg('string.pattern.base', "$label with value \"$text\" fails to match the required pattern: {$rule['pattern']}");
                }
                return $errors;

            case 'number':
                if (is_int($value) || is_float($value)) {
                    $number = $value;
                } elseif (is_string($value) && trim($value) !== '' && is_numeric(trim($value))) {
                    $number = trim($value) + 0;
                } else {
                    return [$msg('number.base', "$label must be a number")];
                }
                if (!empty($rule['integer']) && floor($number) != $number) {
                    $errors[] = $msg('number.integer', "$label must be an integer");
                }
                if (isset($rule['min']) && $number < $rule['min']) {
                    $errors[] = $msg('number.min', "$label must be greater than or equal to {$rule['min']}");
                }
                return $errors;

            case 'date':
                if (is_string($value)) {
                    if (!empty($rule['iso']) && !Dates::isIso8601($value)) {
                        return [$msg('date.format', "$label must be in ISO 8601 date format")];
                    }
                    if (Dates::parse($value) === null) {
                        return [$msg('date.base', "$label must be a valid date")];
                    }
                    return [];
                }
                return [$msg('date.base', "$label must be a valid date")];
        }

        return [];
    }

    private static function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false
            && (bool) preg_match('/\.[A-Za-z]{2,}$/', $value);
    }
}

// express-validator style checks used by member.validation.js. Errors have the
// same shape: { type, value, msg, path, location }.
final class FieldCheck
{
    private array $errors = [];

    public function __construct(private Request $request)
    {
    }

    private function text($value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        return is_scalar($value) ? (string) $value : '';
    }

    private function fail(string $path, string $msg): void
    {
        $error = ['type' => 'field'];
        if ($this->request->has($path)) {
            $error['value'] = $this->request->body[$path];
        }
        $this->errors[] = $error + ['msg' => $msg, 'path' => $path, 'location' => 'body'];
    }

    // optional(): skips the chain when the field was not sent.
    // optional({ values: "falsy" }): also skips "", 0, false, null.
    public function skip(string $path, bool $falsy = false): bool
    {
        if (!$this->request->has($path)) {
            return true;
        }
        return $falsy && !$this->request->body[$path];
    }

    public function notEmpty(string $path, string $msg): void
    {
        if ($this->text($this->request->input($path)) === '') {
            $this->fail($path, $msg);
        }
    }

    public function isInt(string $path, int $min, int $max, string $msg): void
    {
        $value = $this->text($this->request->input($path));
        if (!preg_match('/^[-+]?[0-9]+$/', $value) || (int) $value < $min || (int) $value > $max) {
            $this->fail($path, $msg);
        }
    }

    public function isFloatMin(string $path, float $min, string $msg): void
    {
        $value = $this->text($this->request->input($path));
        if (!is_numeric($value) || (float) $value < $min) {
            $this->fail($path, $msg);
        }
    }

    public function matches(string $path, string $pattern, string $msg): void
    {
        if (!preg_match($pattern, $this->text($this->request->input($path)))) {
            $this->fail($path, $msg);
        }
    }

    public function isISO8601(string $path, string $msg): void
    {
        if (!Dates::isIso8601($this->text($this->request->input($path)))) {
            $this->fail($path, $msg);
        }
    }

    public function length(string $path, ?int $min, ?int $max, string $msg): void
    {
        $length = mb_strlen($this->text($this->request->input($path)));
        if (($min !== null && $length < $min) || ($max !== null && $length > $max)) {
            $this->fail($path, $msg);
        }
    }

    public function trim(string $path): void
    {
        $this->request->body[$path] = trim($this->text($this->request->input($path)));
    }

    public function reply(): ?array
    {
        return $this->errors ? json_reply(400, ['success' => false, 'errors' => $this->errors]) : null;
    }
}

final class Rules
{
    public static function enquiry(): array
    {
        return [
            'requestType' => ['type' => 'string', 'required' => true, 'messages' => [
                'any.required' => 'Request type is required', 'string.empty' => 'Request type is required',
            ]],
            'fullName' => ['type' => 'string', 'required' => true, 'min' => 2, 'max' => 100, 'messages' => [
                'any.required' => 'Full name is required', 'string.empty' => 'Full name is required',
            ]],
            'mobile' => ['type' => 'string', 'required' => true, 'pattern' => '/^[0-9]{10}$/', 'messages' => [
                'any.required' => 'Mobile number is required', 'string.pattern.base' => 'Mobile number must be 10 digits',
            ]],
            'email' => ['type' => 'string', 'email' => true, 'allow' => ['']],
            'companyName' => ['type' => 'string', 'max' => 150, 'allow' => ['']],
            'city' => ['type' => 'string', 'max' => 100, 'allow' => ['']],
            'state' => ['type' => 'string', 'max' => 100, 'allow' => ['']],
            'message' => ['type' => 'string', 'max' => 1000, 'allow' => ['']],
        ];
    }

    public static function enquiryStatus(): array
    {
        return [
            'status' => ['type' => 'string', 'required' => true, 'valid' => ['NEW', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'], 'messages' => [
                'any.required' => 'Status is required',
                'any.only' => 'Status must be NEW, IN_PROGRESS, RESOLVED or CLOSED',
            ]],
        ];
    }

    public static function specialDate(): array
    {
        return [
            'date' => ['type' => 'date', 'iso' => true, 'required' => true, 'messages' => [
                'any.required' => 'Date is required', 'date.base' => 'Invalid date', 'date.format' => 'Invalid date',
            ]],
            'note' => ['type' => 'string', 'trim' => true, 'max' => 191, 'allow' => ['', null], 'messages' => [
                'string.max' => 'Note must be 191 characters or fewer',
            ]],
        ];
    }

    public static function createPartner(bool $update = false): array
    {
        $text = fn (array $extra = []) => ['type' => 'string', 'allow' => ['']] + $extra;
        $date = ['type' => 'date', 'iso' => true, 'allow' => ['']];

        return [
            // Member IDs are text so leading zeros stay (0560 and 560 are different members).
            'memberId' => ['type' => 'string', 'pattern' => '/^(?!0+$)\d{1,6}$/', 'required' => !$update, 'messages' => [
                'any.required' => 'Member ID is required', 'string.empty' => 'Member ID is required',
                'string.base' => 'Member ID must be a number', 'string.pattern.base' => 'Member ID must be a number',
            ]],
            'partnerName' => ['type' => 'string', 'min' => 2, 'max' => 150, 'required' => !$update, 'messages' => [
                'any.required' => 'Partner Name is required', 'string.empty' => 'Partner Name is required',
            ]],
            'fatherName' => $text(['max' => 150]),
            'dateOfBirth' => $date,
            'specialDates' => $text(),
            'residentialAddress' => $text(),
            'mobile' => $text(['pattern' => '/^[0-9]{10}$/', 'messages' => ['string.pattern.base' => 'Mobile number must be exactly 10 digits']]),
            'residentialTelephone' => $text(),
            'panCardNo' => $text(['pattern' => '/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', 'messages' => ['string.pattern.base' => 'Invalid PAN Card']]),
            'aadharNo' => $text(['length' => 12, 'messages' => ['string.length' => 'Aadhar must contain 12 digits']]),
            'designation' => $text(['max' => 100]),
            'companyName' => $text(['max' => 150]),
            'companyAddress' => $text(),
            'companyTelephone' => $text(),
            'packetNo' => $text(),
            'state' => $text(['max' => 100]),
            'district' => $text(['max' => 100]),
            'city' => $text(['max' => 100]),
            'dateOfJoining' => $date,
            'validityFrom' => $date,
            'validityTo' => $date,
            'amount' => ['type' => 'number', 'min' => 0],
            'note' => $text(),
        ];
    }

    public static function renewPartner(): array
    {
        return [
            'validityTo' => ['type' => 'date', 'iso' => true, 'required' => true, 'messages' => ['any.required' => 'Validity To date is required']],
            'validityFrom' => ['type' => 'date', 'iso' => true, 'allow' => [''], 'messages' => ['date.format' => 'Invalid Valid From date']],
            'amount' => ['type' => 'number', 'min' => 0],
            'note' => ['type' => 'string', 'max' => 300, 'allow' => ['']],
        ];
    }

    private static function phone(): array
    {
        return ['type' => 'string', 'pattern' => '/^[0-9]{10}$/', 'allow' => [''], 'messages' => [
            'string.pattern.base' => 'Phone number must be 10 digits',
        ]];
    }

    public static function createSuperAdmin(): array
    {
        return [
            'firstName' => ['type' => 'string', 'min' => 2, 'max' => 50, 'required' => true, 'messages' => [
                'any.required' => 'First name is required', 'string.empty' => 'First name is required',
            ]],
            'lastName' => ['type' => 'string', 'min' => 2, 'max' => 50, 'allow' => ['']],
            'email' => ['type' => 'string', 'email' => true, 'required' => true, 'messages' => [
                'any.required' => 'Email is required', 'string.email' => 'Invalid email address',
            ]],
            'phone' => self::phone(),
            'password' => ['type' => 'string', 'min' => 6, 'required' => true, 'messages' => [
                'any.required' => 'Password is required', 'string.min' => 'Password must be at least 6 characters long',
            ]],
        ];
    }

    public static function updateSuperAdmin(): array
    {
        return [
            'firstName' => ['type' => 'string', 'min' => 2, 'max' => 50],
            'lastName' => ['type' => 'string', 'min' => 2, 'max' => 50, 'allow' => ['']],
            'email' => ['type' => 'string', 'email' => true, 'messages' => ['string.email' => 'Invalid email address']],
            'phone' => self::phone(),
        ];
    }

    public static function resetPassword(): array
    {
        return [
            'newPassword' => ['type' => 'string', 'min' => 6, 'required' => true, 'messages' => [
                'any.required' => 'New password is required',
                'string.min' => 'New password must be at least 6 characters long',
            ]],
            'confirmPassword' => ['type' => 'string', 'ref' => 'newPassword', 'required' => true, 'messages' => [
                'any.required' => 'Confirm password is required',
                'any.only' => 'New password and confirm password do not match',
            ]],
        ];
    }

    // member.validation.js — validateMember
    public static function member(Request $request): ?array
    {
        $check = new FieldCheck($request);

        $check->notEmpty('memberId', 'Member ID is required');
        // Text, so leading zeros are kept: 0560 and 560 are different members.
        $check->matches('memberId', '/^(?!0+$)\d{1,6}$/', 'Member ID must be a number up to 6 digits');
        $check->notEmpty('memberName', 'Member Name is required');

        if (!$check->skip('mobile')) {
            $check->matches('mobile', '/^[0-9]{10}$/', 'Mobile number must be exactly 10 digits');
        }
        if (!$check->skip('dateOfBirth')) {
            $check->isISO8601('dateOfBirth', 'Invalid Date of Birth');
        }
        if (!$check->skip('aadharNo')) {
            $check->length('aadharNo', 12, 12, 'Aadhar must contain 12 digits');
        }
        foreach (['state', 'district', 'city'] as $field) {
            if (!$check->skip($field)) {
                $check->trim($field);
                $check->length($field, null, 100, 'State, district and city must be 100 characters or fewer');
            }
        }
        if (!$check->skip('validityFrom', true)) {
            $check->isISO8601('validityFrom', 'Invalid Valid From date');
        }
        if (!$check->skip('validityTo')) {
            $check->isISO8601('validityTo', 'Invalid Validity To date');
        }
        if (!$check->skip('amount')) {
            $check->isFloatMin('amount', 0, 'Amount must be a positive number');
        }

        return $check->reply();
    }

    // member.validation.js — validateMemberRenew
    public static function memberRenew(Request $request): ?array
    {
        $check = new FieldCheck($request);

        $check->notEmpty('validityTo', 'Validity To date is required');
        $check->isISO8601('validityTo', 'Invalid Validity To date');

        if (!$check->skip('validityFrom', true)) {
            $check->isISO8601('validityFrom', 'Invalid Valid From date');
        }
        if (!$check->skip('amount')) {
            $check->isFloatMin('amount', 0, 'Amount must be a positive number');
        }
        if (!$check->skip('note')) {
            $check->trim('note');
            $check->length('note', null, 300, 'Note must be 300 characters or fewer');
        }

        return $check->reply();
    }
}
