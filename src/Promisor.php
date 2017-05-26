<?php declare(strict_types = 1);

namespace JimboLoop;

interface Promisor
{
    function getPromise(): Promise;
    function succeed($value = null);
    function fail(\Throwable $e);
}
