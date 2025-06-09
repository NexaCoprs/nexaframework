<?php

namespace Nexa\Validation;

trait ValidatesRequests
{
    /**
     * Valide les données de la requête
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @return array
     * @throws ValidationException
     */
    protected function validate(array $data, array $rules, array $messages = [])
    {
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $data;
    }

    /**
     * Valide les données de la requête et retourne le validateur
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @return Validator
     */
    protected function validator(array $data, array $rules, array $messages = [])
    {
        return Validator::make($data, $rules, $messages);
    }
}