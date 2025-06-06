<?php

namespace Nexa\Validation;

use Exception;

class ValidationException extends Exception
{
    protected $validator;
    protected $errors;

    public function __construct(Validator $validator, $message = 'The given data was invalid.', $code = 422)
    {
        parent::__construct($message, $code);
        
        $this->validator = $validator;
        $this->errors = $validator->getErrorMessages();
    }

    /**
     * Obtient le validateur qui a échoué
     *
     * @return Validator
     */
    public function validator()
    {
        return $this->validator;
    }

    /**
     * Obtient les erreurs de validation
     *
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Obtient les erreurs de validation (alias pour errors())
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Obtient la première erreur pour un champ donné
     *
     * @param string $field
     * @return string|null
     */
    public function getFirstError($field)
    {
        return isset($this->errors[$field]) ? $this->errors[$field][0] : null;
    }

    /**
     * Vérifie si un champ a des erreurs
     *
     * @param string $field
     * @return bool
     */
    public function hasError($field)
    {
        return isset($this->errors[$field]);
    }

    /**
     * Convertit les erreurs en format JSON
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode([
            'message' => $this->getMessage(),
            'errors' => $this->errors
        ]);
    }

    /**
     * Convertit les erreurs en tableau
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors
        ];
    }
}