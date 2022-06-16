<?php

namespace Spatie\Watcher;

use Closure;
use Spatie\Watcher\Exceptions\CouldNotStartWatcher;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class Watch
{
    const EVENT_TYPE_FILE_CREATED = 'fileCreated';
    const EVENT_TYPE_FILE_UPDATED = 'fileUpdated';
    const EVENT_TYPE_FILE_DELETED = 'fileDeleted';
    const EVENT_TYPE_DIRECTORY_CREATED = 'directoryCreated';
    const EVENT_TYPE_DIRECTORY_DELETED = 'directoryDeleted';
    protected int $interval = 500 * 1000;

    protected array $paths = [];

    /** @var callable[] */
    protected array $onFileCreated = [];

    /** @var callable[] */
    protected array $onFileUpdated = [];

    /** @var callable[] */
    protected array $onFileDeleted = [];

    /** @var callable[] */
    protected array $onDirectoryCreated = [];

    /** @var callable[] */
    protected array $onDirectoryDeleted = [];

    /** @var callable[] */
    protected array $onAny = [];

    protected Closure $shouldContinue;

    public static function path(string $path): self
    {
        return (new self())->setPaths($path);
    }

    public static function paths(...$paths): self
    {
        return (new self())->setPaths($paths);
    }

    public function __construct()
    {
        $this->shouldContinue = fn () => true;
    }

    public function setPaths(string | array $paths): self
    {
        if (is_string($paths)) {
            $paths = func_get_args();
        }

        $this->paths = $paths;

        return $this;
    }

    public function onFileCreated(callable $onFileCreated): self
    {
        $this->onFileCreated[] = $onFileCreated;

        return $this;
    }

    public function onFileUpdated(callable $onFileUpdated): self
    {
        $this->onFileUpdated[] = $onFileUpdated;

        return $this;
    }

    public function onFileDeleted(callable $onFileDeleted): self
    {
        $this->onFileDeleted[] = $onFileDeleted;

        return $this;
    }

    public function onDirectoryCreated(callable $onDirectoryCreated): self
    {
        $this->onDirectoryCreated[] = $onDirectoryCreated;

        return $this;
    }

    public function onDirectoryDeleted(callable $onDirectoryDeleted): self
    {
        $this->onDirectoryDeleted[] = $onDirectoryDeleted;

        return $this;
    }

    public function onAnyChange(callable $callable): self
    {
        $this->onAny[] = $callable;

        return $this;
    }
    
    public function setIntervalTime(int $interval): self
    {
        $this->interval = $interval;

        return $this;
    }

    public function shouldContinue(Closure $shouldContinue): self
    {
        $this->shouldContinue = $shouldContinue;

        return $this;
    }

    public function start(): void
    {
        $watcher = $this->getWatchProcess();

        while (true) {
            if (! $watcher->isRunning()) {
                throw CouldNotStartWatcher::make($watcher);
            }

            if ($output = $watcher->getIncrementalOutput()) {
                $this->actOnOutput($output);
            }

            if (! ($this->shouldContinue)()) {
                break;
            }

            usleep($this->interval);
        }
    }

    protected function getWatchProcess(): Process
    {
        $command = [
            (new ExecutableFinder)->find('node'),
            realpath(__DIR__ . '/../bin/file-watcher.js'),
            json_encode($this->paths),
        ];

        $process = new Process(
            command: $command,
            timeout: null,
        );

        $process->start();

        return $process;
    }

    protected function actOnOutput(string $output): void
    {
        $lines = explode(PHP_EOL, $output);

        $lines = array_filter($lines);

        foreach ($lines as $line) {
            [$type, $path] = explode(' - ', $line, 2);

            $path = trim($path);

            match ($type) {
                static::EVENT_TYPE_FILE_CREATED => $this->callAll($this->onFileCreated, $path),
                static::EVENT_TYPE_FILE_UPDATED => $this->callAll($this->onFileUpdated, $path),
                static::EVENT_TYPE_FILE_DELETED => $this->callAll($this->onFileDeleted, $path),
                static::EVENT_TYPE_DIRECTORY_CREATED => $this->callAll($this->onDirectoryCreated, $path),
                static::EVENT_TYPE_DIRECTORY_DELETED => $this->callAll($this->onDirectoryDeleted, $path),
            };

            foreach ($this->onAny as $onAnyCallable) {
                $onAnyCallable($type, $path);
            }
        }
    }

    protected function callAll(array $callables, string $path): void
    {
        foreach ($callables as $callable) {
            $callable($path);
        }
    }
}
