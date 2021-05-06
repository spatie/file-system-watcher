<?php

namespace Spatie\Watcher\Exceptions;

use Exception;
use Symfony\Component\Process\Process;

class CouldNotStartWatcher extends Exception
{
    public static function make(Process $watcher): self
    {
        return new self("Could not start watcher. Make sure you have required chokidar. Error output: " . $watcher->getErrorOutput());
    }
}
