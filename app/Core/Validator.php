<?php
/**
 * XooPress Validator
 * 
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Validator
{
    /**
     * Data to validate
     * 
     * @var array
     */
    protected array $data;
    
    /**
     * Validation rules
     * 
     * @var array
     */
    protected array $rules;
    
    /**
     * Custom error messages
     * 
     * @var array
     */
    protected array $messages;
    
    /**
     * Validation errors
     * 
     * @var array
     */
    protected array $errors = [];
    
    /**
     * Validated data
     * 
     * @var array
     */
    protected array $validated = [];
    
    /**
     * Available validation rules
     * 
     * @var array
     */
    protected array $availableRules = [
        'required', 'email', 'min', 'max', 'numeric', 'integer',
        'alpha', 'alphanumeric', 'url', 'ip', 'date', 'boolean',
        'array', 'string', 'confirmed', 'in', 'not_in', 'regex',
        'unique', 'exists', 'between', 'different', 'same',
    ];
    
    /**
     * Default error messages
     * 
     * @var array
     */
    protected array $defaultMessages = [
        'required' => 'The :field field is required.',
        'email' => 'The :field must be a valid email address.',
        'min' => 'The :field must be at least :param characters.',
        'max' => 'The :field must not exceed :param characters.',
        'numeric' => 'The :field must be a number.',
        'integer' => 'The :field must be an integer.',
        'alpha' => 'The :field may only contain letters.',
        'alphanumeric' => 'The :field may only contain letters and numbers.',
        'url' => 'The :field must be a valid URL.',
        'ip' => 'The :field must be a valid IP address.',
        'date' => 'The :field must be a valid date.',
        'boolean' => 'The :field must be a boolean value.',
        'array' => 'The :field must be an array.',
        'string' => 'The :field must be a string.',
        'confirmed' => 'The :field confirmation does not match.',
        'in' => 'The selected :field is invalid.',
        'not_in' => 'The selected :field is invalid.',
        'regex' => 'The :field format is invalid.',
        'unique' => 'The :field has already been taken.',
        'exists' => 'The selected :field is invalid.',
        'between' => 'The :field must be between :param1 and :param2.',
        'different' => 'The :field and :param must be different.',
        'same' => 'The :field and :param must match.',
    ];
    
    /**
     * Constructor
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @param array $messages Custom error messages
     */
    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = array_merge($this->defaultMessages, $messages);
    }
    
    /**
     * Perform validation
     * 
     * @return bool
     */
    public function validate(): bool
    {
        $this->errors = [];
        $this->validated = [];
        
        foreach ($this->rules as $field => $ruleString) {
            $rules = $this->parseRules($ruleString);
            $value = $this->data[$field] ?? null;
            
            foreach ($rules as $rule) {
                $ruleName = $rule['name'];
                $parameters = $rule['parameters'];
                
                if (!$this->validateRule($field, $value, $ruleName, $parameters)) {
                    $this->addError($field, $ruleName, $parameters);
                    break;
                }
            }
            
            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Parse rule string into array of rules
     * 
     * @param string $ruleString
     * @return array
     */
    protected function parseRules(string $ruleString): array
    {
        $rules = [];
        $ruleParts = explode('|', $ruleString);
        
        foreach ($ruleParts as $part) {
            $parts = explode(':', $part, 2);
            $ruleName = $parts[0];
            $parameters = [];
            
            if (isset($parts[1])) {
                $parameters = explode(',', $parts[1]);
            }
            
            if (in_array($ruleName, $this->availableRules)) {
                $rules[] = [
                    'name' => $ruleName,
                    'parameters' => $parameters,
                ];
            }
        }
        
        return $rules;
    }
    
    /**
     * Validate a single rule
     * 
     * @param string $field
     * @param mixed $value
     * @param string $rule
     * @param array $parameters
     * @return bool
     */
    protected function validateRule(string $field, mixed $value, string $rule, array $parameters): bool
    {
        $methodName = 'validate' . ucfirst($rule);
        
        if (method_exists($this, $methodName)) {
            return $this->$methodName($field, $value, $parameters);
        }
        
        return true;
    }
    
    /**
     * Add an error message
     * 
     * @param string $field
     * @param string $rule
     * @param array $parameters
     * @return void
     */
    protected function addError(string $field, string $rule, array $parameters): void
    {
        $message = $this->messages[$rule] ?? $this->defaultMessages[$rule] ?? 'Validation failed.';
        
        $message = str_replace(':field', $field, $message);
        $message = str_replace(':param', $parameters[0] ?? '', $message);
        $message = str_replace(':param1', $parameters[0] ?? '', $message);
        $message = str_replace(':param2', $parameters[1] ?? '', $message);
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * Validate required field
     */
    protected function validateRequired(string $field, mixed $value, array $parameters): bool
    {
        if (is_null($value)) return false;
        if (is_string($value) && trim($value) === '') return false;
        if (is_array($value) && empty($value)) return false;
        return true;
    }
    
    /**
     * Validate email field
     */
    protected function validateEmail(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate minimum length/value
     */
    protected function validateMin(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        $min = (int) ($parameters[0] ?? 0);
        if (is_string($value)) return mb_strlen($value) >= $min;
        if (is_numeric($value)) return $value >= $min;
        if (is_array($value)) return count($value) >= $min;
        return true;
    }
    
    /**
     * Validate maximum length/value
     */
    protected function validateMax(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        $max = (int) ($parameters[0] ?? 0);
        if (is_string($value)) return mb_strlen($value) <= $max;
        if (is_numeric($value)) return $value <= $max;
        if (is_array($value)) return count($value) <= $max;
        return true;
    }
    
    /**
     * Validate numeric field
     */
    protected function validateNumeric(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        return is_numeric($value);
    }
    
    /**
     * Validate integer field
     */
    protected function validateInteger(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    /**
     * Validate alpha field
     */
    protected function validateAlpha(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        return ctype_alpha($value);
    }
    
    /**
     * Validate alphanumeric field
     */
    protected function validateAlphanumeric(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        return ctype_alnum($value);
    }
    
    /**
     * Validate URL field
     */
    protected function validateUrl(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validate IP address field
     */
    protected function validateIp(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
    
    /**
     * Validate date field
     */
    protected function validateDate(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        $format = $parameters[0] ?? 'Y-m-d';
        $date = \DateTime::createFromFormat($format, $value);
        return $date && $date->format($format) === $value;
    }
    
    /**
     * Validate boolean field
     */
    protected function validateBoolean(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        $acceptable = [true, false, 0, 1, '0', '1', 'true', 'false', 'on', 'off', 'yes', 'no'];
        return in_array($value, $acceptable, true);
    }
    
    /**
     * Validate array field
     */
    protected function validateArray(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        return is_array($value);
    }
    
    /**
     * Validate string field
     */
    protected function validateString(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        return is_string($value);
    }
    
    /**
     * Validate confirmed field
     */
    protected function validateConfirmed(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        $confirmationField = $field . '_confirmation';
        return isset($this->data[$confirmationField]) && $this->data[$confirmationField] === $value;
    }
    
    /**
     * Validate in list
     */
    protected function validateIn(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        return in_array((string) $value, $parameters);
    }
    
    /**
     * Validate not in list
     */
    protected function validateNotIn(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        return !in_array((string) $value, $parameters);
    }
    
    /**
     * Validate regex pattern
     */
    protected function validateRegex(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        $pattern = $parameters[0] ?? '';
        if (empty($pattern)) return true;
        return preg_match($pattern, $value) === 1;
    }
    
    /**
     * Validate between
     */
    protected function validateBetween(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        $min = (int) ($parameters[0] ?? 0);
        $max = (int) ($parameters[1] ?? 0);
        if (is_string($value)) {
            $length = mb_strlen($value);
            return $length >= $min && $length <= $max;
        }
        if (is_numeric($value)) return $value >= $min && $value <= $max;
        return true;
    }
    
    /**
     * Validate different fields
     */
    protected function validateDifferent(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        $otherField = $parameters[0] ?? '';
        if (!isset($this->data[$otherField])) return true;
        return $value !== $this->data[$otherField];
    }
    
    /**
     * Validate same fields
     */
    protected function validateSame(string $field, mixed $value, array $parameters): bool
    {
        if (empty($value)) return true;
        $otherField = $parameters[0] ?? '';
        if (!isset($this->data[$otherField])) return false;
        return $value === $this->data[$otherField];
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get the first error message for a field
     * 
     * @param string $field
     * @return string|null
     */
    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
    
    /**
     * Get validated data
     * 
     * @return array
     */
    public function getValidated(): array
    {
        return $this->validated;
    }
    
    /**
     * Check if a field has errors
     * 
     * @param string $field
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }
}