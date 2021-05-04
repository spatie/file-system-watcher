<?php

namespace Spatie\Watcher;

use Closure;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class Watcher
{
    const EVENT_TYPE_FILE_CREATED = 'fileCreated';
    const EVENT_TYPE_FILE_UPDATED = 'fileUpdated';
    const EVENT_TYPE_FILE_DELETED = 'fileDeleted';
    const EVENT_TYPE_DIRECTORY_CREATED = 'directoryAdded';
    const EVENT_TYPE_DIRECTORY_DELETED = 'directoryDeleted';

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

    public function __construct()
    {
        $this->shouldContinue = fn () => true;
    }

    public function paths(array $paths): self
    {
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

    public function onAnyEvent(callable $callable): self
    {
        $this->onAny[] = $callable;

        return $this;
    }

    public function shouldContinue(Closure $shouldContinue): self
    {
        $this->shouldContinue = $shouldContinue;

        return $this;
    }

    public function start()
    {
        $watcher = $this->getWatchProcess();

        while (true) {
            if (! $watcher->isRunning()) {
                break;
            }

            if ($output = $watcher->getIncrementalOutput()) {
                $this->actOnOutput($output);
            }

            if (! ($this->shouldContinue)()) {
                break;
            }

            usleep(500 * 1000);
        }
    }

    protected function getWatchProcess(): Process
    {
        $command = [
            (new ExecutableFinder)->find('node'),
            'file-watcher.js',
            json_encode($this->paths),
        ];

        $process = new Process(
            command: $command,
            cwd: realpath(__DIR__ . '/../bin'),
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
