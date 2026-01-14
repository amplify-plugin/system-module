<?php

namespace Amplify\System\Media\Services\ACLService;

/**
 * Class DBACLRepository
 */
class DBACLRepository implements ACLRepository
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
     * Get ACL rules list for user
     */
    public function getRules(): array
    {
        return \DB::table('acl_rules')
            ->where('user_id', $this->getUserID())
            ->get(['disk', 'path', 'access'])
            ->map(function ($item) {
                return get_object_vars($item);
            })
            ->all();
    }
}
