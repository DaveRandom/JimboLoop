<?php declare(strict_types = 1);

namespace JimboLoop;

class Loop
{
    private $timers = [];

    private function getAbsoluteTime(int $timeout): float
    {
        // timeout is in milliseconds, microtime() works in seconds so need to convert
        return microtime(true) + ($timeout / 1000);
    }

    private function havePendingTimers(): bool
    {
        return !empty($this->timers);
    }

    private function getMicrosecondsUntilNextTimer(): int
    {
        $min = PHP_INT_MAX;

        // find the lowest value of $when in the pending timers
        foreach ($this->timers as list($when, $callback)) {
            if ($when < $min) {
                $min = $when;
            }
        }

        // $min is now an absolute time, expressed in seconds as a float
        // first, calculate the time difference between now and then
        $diff = $min - microtime(true);

        // now convert it to microseconds and return it
        return (int)($diff * 1000000);
    }

    private function processReadyTimers()
    {
        $now = microtime(true);

        // Execute and remove any timers that have expired
        foreach ($this->timers as $key => list($when, $callback)) {
            if ($when <= $now) {
                $callback();
                unset($this->timers[$key]);
            }
        }
    }

    public function setTimeout(int $timeout, callable $callback)
    {
        $this->timers[] = [$this->getAbsoluteTime($timeout), $callback];
    }

    public function run()
    {
        while ($this->havePendingTimers()) {
            $timeout = $this->getMicrosecondsUntilNextTimer();

            if ($timeout > 0) {
                usleep($timeout);
            }

            $this->processReadyTimers();
        }
    }
}
