<?php

namespace Nexa\Validation;

class Validator
{
    protected $data = [];
    protected $rules = [];
    protected $errors = [];
    protected $customMessages = [];

    public function __construct(array $data, array $rules, array $customMessages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
    }

    public static function make(array $data, array $rules, array $customMessages = [])
    {
        return new static($data, $rules, $customMessages);
    }

    public function validate()
    {
        foreach ($this->rules as $field => $rules) {
            $this->validateField($field, $rules);
        }

        return empty($this->errors);
    }

    public function fails()
    {
        return !$this->validate();
    }

    public function errors()
    {
        return $this->errors;
    }

    public function getErrorMessages()
    {
        $messages = [];
        foreach ($this->errors as $field => $fieldErrors) {
            $messages[$field] = $fieldErrors;
        }
        return $messages;
    }

    protected function validateField($field, $rules)
    {
        $rules = is_string($rules) ? explode('|', $rules) : $rules;
        $value = $this->getValue($field);

        foreach ($rules as $rule) {
            $this->applyRule($field, $value, $rule);
        }
    }

    protected function getValue($field)
    {
        return isset($this->data[$field]) ? $this->data[$field] : null;
    }

    protected function applyRule($field, $value, $rule)
    {
        if (strpos($rule, ':') !== false) {
            [$ruleName, $parameter] = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $parameter = null;
        }

        $method = 'validate' . ucfirst($ruleName);

        if (method_exists($this, $method)) {
            $result = $this->$method($field, $value, $parameter);
            if (!$result) {
                $this->addError($field, $ruleName, $parameter);
            }
        }
    }

    protected function addError($field, $rule, $parameter = null)
    {
        $message = $this->getErrorMessage($field, $rule, $parameter);
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }

    protected function getErrorMessage($field, $rule, $parameter = null)
    {
        $key = "{$field}.{$rule}";
        
        if (isset($this->customMessages[$key])) {
            return $this->customMessages[$key];
        }

        $messages = [
            'required' => "Le champ {$field} est requis.",
            'email' => "Le champ {$field} doit être une adresse email valide.",
            'min' => "Le champ {$field} doit contenir au moins {$parameter} caractères.",
            'max' => "Le champ {$field} ne peut pas dépasser {$parameter} caractères.",
            'numeric' => "Le champ {$field} doit être un nombre.",
            'integer' => "Le champ {$field} doit être un entier.",
            'string' => "Le champ {$field} doit être une chaîne de caractères.",
            'boolean' => "Le champ {$field} doit être un booléen.",
            'array' => "Le champ {$field} doit être un tableau.",
            'url' => "Le champ {$field} doit être une URL valide.",
            'confirmed' => "La confirmation du champ {$field} ne correspond pas.",
            'same' => "Le champ {$field} doit être identique à {$parameter}.",
            'different' => "Le champ {$field} doit être différent de {$parameter}.",
            'in' => "Le champ {$field} doit être l'une des valeurs suivantes: {$parameter}.",
            'not_in' => "Le champ {$field} ne peut pas être l'une des valeurs suivantes: {$parameter}.",
            'regex' => "Le format du champ {$field} est invalide.",
            'unique' => "La valeur du champ {$field} est déjà utilisée.",
            'exists' => "La valeur sélectionnée pour {$field} est invalide."
        ];

        return $messages[$rule] ?? "Le champ {$field} est invalide.";
    }

    // Validation Rules
    protected function validateRequired($field, $value, $parameter)
    {
        return !is_null($value) && $value !== '' && $value !== [];
    }

    protected function validateEmail($field, $value, $parameter)
    {
        return is_null($value) || filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateMin($field, $value, $parameter)
    {
        if (is_null($value)) return true;
        
        if (is_string($value)) {
            return strlen($value) >= (int)$parameter;
        }
        
        if (is_numeric($value)) {
            return $value >= (float)$parameter;
        }
        
        if (is_array($value)) {
            return count($value) >= (int)$parameter;
        }
        
        return false;
    }

    protected function validateMax($field, $value, $parameter)
    {
        if (is_null($value)) return true;
        
        if (is_string($value)) {
            return strlen($value) <= (int)$parameter;
        }
        
        if (is_numeric($value)) {
            return $value <= (float)$parameter;
        }
        
        if (is_array($value)) {
            return count($value) <= (int)$parameter;
        }
        
        return false;
    }

    protected function validateNumeric($field, $value, $parameter)
    {
        return is_null($value) || is_numeric($value);
    }

    protected function validateInteger($field, $value, $parameter)
    {
        return is_null($value) || filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    protected function validateString($field, $value, $parameter)
    {
        return is_null($value) || is_string($value);
    }

    protected function validateBoolean($field, $value, $parameter)
    {
        return is_null($value) || is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true);
    }

    protected function validateArray($field, $value, $parameter)
    {
        return is_null($value) || is_array($value);
    }

    protected function validateUrl($field, $value, $parameter)
    {
        return is_null($value) || filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    protected function validateConfirmed($field, $value, $parameter)
    {
        $confirmationField = $field . '_confirmation';
        return isset($this->data[$confirmationField]) && $value === $this->data[$confirmationField];
    }

    protected function validateSame($field, $value, $parameter)
    {
        return isset($this->data[$parameter]) && $value === $this->data[$parameter];
    }

    protected function validateDifferent($field, $value, $parameter)
    {
        return !isset($this->data[$parameter]) || $value !== $this->data[$parameter];
    }

    protected function validateIn($field, $value, $parameter)
    {
        if (is_null($value)) return true;
        
        $values = explode(',', $parameter);
        return in_array($value, $values, true);
    }

    protected function validateNotIn($field, $value, $parameter)
    {
        if (is_null($value)) return true;
        
        $values = explode(',', $parameter);
        return !in_array($value, $values, true);
    }

    protected function validateRegex($field, $value, $parameter)
    {
        if (is_null($value)) return true;
        
        return preg_match($parameter, $value) > 0;
    }

    protected function validateUnique($field, $value, $parameter)
    {
        // Cette méthode nécessiterait une connexion à la base de données
        // Pour l'instant, on retourne true
        return true;
    }

    protected function validateExists($field, $value, $parameter)
    {
        // Cette méthode nécessiterait une connexion à la base de données
        // Pour l'instant, on retourne true
        return true;
    }
}