<?php

namespace Amplify\System\Checks\CpuLoad;

class CpuLoad
{
    /**
     * @throws CouldNotMeasureException
     */
    public static function measure(): self
    {
        $result = false;

        if (function_exists('sys_getloadavg')) {
            $result = sys_getloadavg();
        }

        if (! $result) {
            throw CouldNotMeasureException::make();
        }

        $result = array_map(fn ($n) => round($n, 2), $result);

        return new self(...$result);
    }

    public function __construct(
        public float $lastMinute,
        public float $last5Minutes,
        public float $last15Minutes,
    ) {
    }
}
