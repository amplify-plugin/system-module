<?php

namespace Amplify\System\Checks\ApiLog;

use Amplify\System\Backend\Models\SystemConfiguration;
use Amplify\System\Helpers\UtilityHelper;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class EasyAskApiLogCheck extends Check
{
    protected bool $expected = false;

    public function expectedToBe(bool $bool): self
    {
        $this->expected = $bool;

        return $this;
    }

    public function run(): Result
    {
        $fileConfig = config('amplify.developer.log_search', false);

        $dbConfig = null;

        if($entry = SystemConfiguration::whereName('developer')->whereOption('log_search')->first()) {
            $dbConfig = UtilityHelper::typeCast($entry->value, $entry->type);
        }

        $actual = $dbConfig !== null ? $dbConfig : $fileConfig;

        $result = Result::make()
            ->meta([
                'actual' => $actual,
                'expected' => $this->expected,
            ])
            ->shortSummary($this->convertToWord($actual));

        return $this->expected === $actual
            ? $result->ok()
            : $result->failed("The easyask search api log was expected to be `{$this->convertToWord((bool) $this->expected)}`, but actually was `{$this->convertToWord((bool) $actual)}`");
    }

    protected function convertToWord(bool $boolean): string
    {
        return $boolean ? 'true' : 'false';
    }
}
