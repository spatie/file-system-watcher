<?php

namespace Spatie\Watcher;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class Watcher
{
    protected array $paths = [];

    /** @var callable[] */
    protected array $onFileCreated = [];

    /** @var callable[] */
    protected array $onFileUpdated = [];

    /** @var callable[] */
    protected array $onFileDeleted = [];

    /** @var callable[] */
    protected array $onDirectoryAdded = [];

    /** @var callable[] */
    protected array $onDirectoryDeleted = [];

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

    public function onDirectoryAdded(callable $onDirectoryAdded): self
    {
        $this->onDirectoryAdded[] = $onDirectoryAdded;

        return $this;
    }

    public function onDirectoryDeleted(callable $onDirectoryDeleted): self
    {
        $this->onDirectoryDeleted[] = $onDirectoryDeleted;

        return $this;
    }

    public function onAny(callable $callable): self
    {
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

            ray($type, $path);
        }
    }
}
