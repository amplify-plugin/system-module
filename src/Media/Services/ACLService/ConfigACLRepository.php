<?php

namespace Amplify\System\Media\Services\ACLService;

/**
 * Class ConfigACLRepository
 *
 * Get rules from file-manager config file - aclRules
 */
class ConfigACLRepository implements ACLRepository
{
    /**
     * Get user ID
     *
     * @return mixed
     */
    public function getUserID()
    {
        return \Auth::id();
    }

    /**
     * Get rules from file-manger.php config file
     */
    public function getRules(): array
    {
        return config('file-manager.aclRules')[$this->getUserID()] ?? [];
    }
}
