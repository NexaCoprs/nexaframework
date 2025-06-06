<?php

namespace Nexa\Events;

/**
 * User Login Event
 */
class UserLoggedIn extends Event
{
    public function __construct($user, $ipAddress = null, $userAgent = null, $additionalData = [])
    {
        parent::__construct(array_merge([
            'user' => $user,
            'action' => 'logged_in',
            'login_time' => date('Y-m-d H:i:s'),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ], $additionalData));
    }

    public function getUser()
    {
        return $this->get('user');
    }

    public function getLoginTime()
    {
        return $this->get('login_time');
    }

    public function getUserId()
    {
        $user = $this->getUser();
        return is_array($user) ? ($user['id'] ?? null) : ($user->id ?? null);
    }

    public function getIpAddress()
    {
        return $this->get('ip_address');
    }

    public function getUserAgent()
    {
        return $this->get('user_agent');
    }
}