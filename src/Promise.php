<?php declare(strict_types = 1);

namespace JimboLoop;

interface Promise
{
    function when(callable $callback);
}
