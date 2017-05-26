<?php declare(strict_types = 1);

namespace JimboLoop;

class Loop
{
    private $timers = [];
    private $readWatchers = [];
    private $writeWatchers = [];

    private function getAbsoluteTime(int $timeout): float
    {
        // timeout is in milliseconds, microtime() works in seconds so need to convert
        return microtime(true) + ($timeout / 1000);
    }

    private function havePendingTimers(): bool
    {
        return !empty($this->timers);
    }

    private function haveIoToWatch(): bool
    {
        return !empty($this->readWatchers) || !empty($this->writeWatchers);
    }

    private function haveIoToWatchOrPendingTimers(): bool
    {
        return $this->haveIoToWatch() || $this->havePendingTimers();
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

    private function watchForIo(int $timeout): array
    {
        // get bare arrays of just the read/write streams
        $readStreams = array_column($this->readWatchers, 0);
        $writeStreams = array_column($this->writeWatchers, 0);
        $oobStreams = null; // ignore this, working around PHP stupidity, see comment below

        if ($timeout > 0) {
            // the timeout in in microseconds but stream_select() needs a seconds component
            $timeoutSecs = (int)($timeout / 1000000);
            $timeoutUsecs = $timeout % 1000000;
        } else {
            // normalise potential negative values to zero
            $timeoutSecs = $timeoutUsecs = 0;
        }

        // stream_select() takes 3 arrays of stream resources. These are passed by reference and modified by the call,
        // so the only streams left in the arrays after the call are the ones where there was some activity. You can
        // ignore OOB streams for the purposes of this exercise, they are only relevant when you are doing some quite
        // niche networking related stuff (even amp doesn't support this right now, I think)
        // stream_select() is a *blocking* call, it returns only when there has been some activity on a stream *or*
        // when the specified timeout expires - it's possible that no streams will be present in any output array.
        stream_select($readStreams, $writeStreams, $oobStreams, $timeoutSecs, $timeoutUsecs);

        return [$readStreams, $writeStreams];
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

    private function processPendingIo(array $pendingReads, array $pendingWrites)
    {
        // note: casting a stream to integer reliably gives a unique ID for a stream.

        foreach ($pendingReads as $stream) {
            $this->readWatchers[(int)$stream][1]($stream); // invoke callbacks for readable streams
        }

        foreach ($pendingWrites as $stream) {
            $this->writeWatchers[(int)$stream][1]($stream); // invoke callbacks for writable streams
        }
    }

    public function setTimeout(int $timeout, callable $callback)
    {
        $this->timers[] = [$this->getAbsoluteTime($timeout), $callback];
    }

    public function onReadable($stream, callable $callback)
    {
        $this->readWatchers[(int)$stream] = [$stream, $callback];
    }

    public function onWritable($stream, callable $callback)
    {
        $this->writeWatchers[(int)$stream] = [$stream, $callback];
    }

    public function cancelReadable($stream)
    {
        unset($this->readWatchers[(int)$stream]);
    }

    public function cancelWritable($stream)
    {
        unset($this->writeWatchers[(int)$stream]);
    }

    public function run()
    {
        while ($this->haveIoToWatchOrPendingTimers()) {
            $timeout = $this->getMicrosecondsUntilNextTimer();
            $pendingReads = $pendingWrites = [];

            if ($this->haveIoToWatch()) {
                list($pendingReads, $pendingWrites) = $this->watchForIo($timeout);
            } else if ($timeout > 0) {
                usleep($timeout);
            }

            $this->processPendingIo($pendingReads, $pendingWrites);
            $this->processReadyTimers();
        }
    }
}
