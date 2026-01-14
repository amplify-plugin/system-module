<?php

namespace Amplify\System\Media\Services\ACLService;

use Amplify\System\Media\Services\ConfigService\ConfigRepository;
use Cache;
use Illuminate\Support\Arr;

class ACL
{
    /**
     * @var ACLRepository
     */
    public $aclRepository;

    /**
     * @var ConfigRepository
     */
    public $configRepository;

    /**
     * ACL constructor.
     */
    public function __construct(
        ACLRepository $aclRepository,
        ConfigRepository $configRepository
    ) {
        $this->aclRepository = $aclRepository;
        $this->configRepository = $configRepository;
    }

    /**
     * Get access level for selected path
     *
     * @param  string  $path
     * @return int
     */
    public function getAccessLevel($disk, $path = '/')
    {
        // get rules list
        $rules = $this->rulesForDisk($disk);

        // find the first rule where the paths are equal
        $firstRule = Arr::first($rules, function ($value) use ($path) {
            return fnmatch($value['path'], $path);
        });

        if ($firstRule) {
            return $firstRule['access'];
        }

        // blacklist or whitelist (ACL strategy)
        return $this->configRepository->getAclStrategy() === 'blacklist' ? 2 : 0;
    }

    /**
     * Select rules for disk
     *
     *
     * @return array
     */
    protected function rulesForDisk($disk)
    {
        return Arr::where($this->rulesList(),
            function ($value) use ($disk) {
                return $value['disk'] === $disk;
            });
    }

    /**
     * Get rules list from ACL Repository
     *
     * @return array|mixed
     */
    protected function rulesList()
    {
        // if cache on
        if ($minutes = $this->configRepository->getAclRulesCache()) {
            $cacheName = get_class($this->aclRepository).'_'.$this->aclRepository->getUserID();

            return Cache::remember($cacheName, $minutes, function () {
                return $this->aclRepository->getRules();
            });
        }

        return $this->aclRepository->getRules();
    }
}
