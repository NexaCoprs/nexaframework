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

/**
 * User Logout Event
 */
class UserLoggedOut extends Event
{
    public function __construct($user, $additionalData = [])
    {
        parent::__construct(array_merge([
            'user' => $user,
            'action' => 'logged_out',
            'logout_time' => date('Y-m-d H:i:s')
        ], $additionalData));
    }

    public function getUser()
    {
        return $this->get('user');
    }

    public function getLogoutTime()
    {
        return $this->get('logout_time');
    }
}

/**
 * User Profile Updated Event
 */
class UserProfileUpdated extends Event
{
    public function __construct($user, $oldData, $newData, $additionalData = [])
    {
        parent::__construct(array_merge([
            'user' => $user,
            'old_data' => $oldData,
            'new_data' => $newData,
            'action' => 'profile_updated',
            'updated_at' => date('Y-m-d H:i:s')
        ], $additionalData));
    }

    public function getUser()
    {
        return $this->get('user');
    }

    public function getOldData()
    {
        return $this->get('old_data');
    }

    public function getNewData()
    {
        return $this->get('new_data');
    }

    public function getChangedFields()
    {
        $oldData = $this->getOldData();
        $newData = $this->getNewData();
        $changed = [];

        foreach ($newData as $key => $value) {
            if (!isset($oldData[$key]) || $oldData[$key] !== $value) {
                $changed[$key] = [
                    'old' => $oldData[$key] ?? null,
                    'new' => $value
                ];
            }
        }

        return $changed;
    }
}

/**
 * Password Changed Event
 */
class UserPasswordChanged extends Event
{
    public function __construct($user, $additionalData = [])
    {
        parent::__construct(array_merge([
            'user' => $user,
            'action' => 'password_changed',
            'changed_at' => date('Y-m-d H:i:s')
        ], $additionalData));
    }

    public function getUser()
    {
        return $this->get('user');
    }

    public function getChangedAt()
    {
        return $this->get('changed_at');
    }
}

/**
 * User Deleted Event
 */
class UserDeleted extends Event
{
    public function __construct($user, $additionalData = [])
    {
        parent::__construct(array_merge([
            'user' => $user,
            'action' => 'deleted',
            'deleted_at' => date('Y-m-d H:i:s')
        ], $additionalData));
    }

    public function getUser()
    {
        return $this->get('user');
    }

    public function getDeletedAt()
    {
        return $this->get('deleted_at');
    }
}