<?php

namespace Nexa\Events;

/**
 * User Registration Event
 */
class UserRegistered extends Event
{
    public function __construct($user, $additionalData = [])
    {
        parent::__construct(array_merge([
            'user' => $user,
            'action' => 'registered'
        ], $additionalData));
    }

    public function getUser()
    {
        return $this->get('user');
    }

    public function getUserId()
    {
        $user = $this->getUser();
        return is_array($user) ? ($user['id'] ?? null) : ($user->id ?? null);
    }

    public function getUserEmail()
    {
        $user = $this->getUser();
        return is_array($user) ? ($user['email'] ?? null) : ($user->email ?? null);
    }

    public function getUserName()
    {
        $user = $this->getUser();
        return is_array($user) ? ($user['name'] ?? null) : ($user->name ?? null);
    }

    public function getUserData()
    {
        return $this->getUser();
    }
}