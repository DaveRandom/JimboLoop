<?php declare(strict_types = 1);

namespace JimboLoop;

class Deferred implements Promisor, Promise
{
    private $result;
    private $error;
    private $isResolved = false;
    private $callbacks = [];

    public function getPromise(): Promise
    {
        return $this;
    }

    private function resolve()
    {
        $this->isResolved = true;

        foreach ($this->callbacks as $callback) {
            $callback($this->error, $this->result);
        }
    }

    public function succeed($result = null)
    {
        $this->result = $result;
        $this->resolve();
    }

    public function fail(\Throwable $error)
    {
        $this->error = $error;
        $this->resolve();
    }

    public function when(callable $callback)
    {
        if ($this->isResolved) {
            $callback($this->error, $this->result);
        } else {
            $this->callbacks[] = $callback;
        }
    }
}
