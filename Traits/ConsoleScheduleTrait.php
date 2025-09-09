<?php

namespace Amplify\System\Traits;

use Illuminate\Console\Scheduling\Schedule;

trait ConsoleScheduleTrait
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $commands = config('amplify.schedule.commands', []);

        $timeZone = config('amplify.schedule.timezone', 'UTC');

        foreach ($this->getEnabledCommands($commands) as $command) {
            $this->scheduleCommand(
                $schedule,
                $command['command'],
                $timeZone,
                $command['interval'],
                $command['time'],
                $command['variables'] ?? []
            );
        }
    }


    private function getEnabledCommands(array $commands): array
    {
        return collect($commands)
            ->where('enabled', true)
            ->sortBy('priority')
            ->all();
    }

    private function scheduleCommand($schedule, $command, $timeZone, $interval, $time, $variables): void
    {
        $schedule->command($command, $this->prepareVariables($variables))
            ->timezone($timeZone)
            ->{$interval}($this->getScheduleTime($interval, $time))
            ->appendOutputTo(storage_path('logs/scheduler.log'));
    }

    private function getScheduleTime($interval, $time): string
    {
        if ($interval === 'cron') {
            return sprintf(
                '%s %s %s %s %s',
                $time['minute'] ?? '0',
                $time['hour'] ?? '*',
                $time['day'] ?? '*',
                $time['month'] ?? '*',
                $time['weekday'] ?? '*'
            );
        }

        return '';
    }

    private function prepareVariables(array $variables): array
    {
        return collect($variables)->mapWithKeys(function ($value, $key) use ($variables) {
            $index = array_search($key, array_keys($variables));

            return $value === '--no-arg-val--' ? [$index => $key] : [$key => $value];
        })->all();
    }
}
