<?php

use Spatie\TemporaryDirectory\TemporaryDirectory;
use Spatie\Watcher\Watch;

beforeEach(function () {
    ray()->clearScreen();

    $this->testDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'testDirectory';

    (new TemporaryDirectory($this->testDirectory))->empty();

    $this->recordedEvents = [];

    $this->i = 0;
});

it('can detect when files get created', function () {
    Watch::path($this->testDirectory)
        ->onFileCreated(function (string $path) {
            $this->modifiedPath = $path;
        })
        ->onAnyChange(function (string $type, string $path) {
            $this->recordedEvents[] = [$type, $path];
        })
        ->shouldContinue(function () {
            if ($this->i === 5) {
                touch($this->testDirectory . DIRECTORY_SEPARATOR . 'test.txt');
            }

            $this->i++;

            return $this->i <= 12;
        })
        ->start();

    expect($this->recordedEvents)->toHaveCount(1)
        ->and($this->recordedEvents[0])->toEqual([
            'fileCreated',
            $this->testDirectory . DIRECTORY_SEPARATOR . 'test.txt',
        ]);

    expect($this->modifiedPath)->toEqual($this->recordedEvents[0][1]);
});

it('can detect when files get updated', function () {
    $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'test.txt';

    touch($testFile);

    Watch::path($this->testDirectory)
        ->onFileUpdated(function (string $path) {
            $this->modifiedPath = $path;
        })
        ->onAnyChange(function (string $type, string $path) {
            $this->recordedEvents[] = [$type, $path];
        })
        ->shouldContinue(function () use ($testFile) {
            if ($this->i === 5) {
                file_put_contents($testFile, 'updated');
            }

            $this->i++;

            return $this->i <= 12;
        })
        ->start();

    expect($this->recordedEvents)->toHaveCount(1)
        ->and($this->recordedEvents[0])->toEqual([
            'fileUpdated',
            $testFile,
        ]);

    expect($this->modifiedPath)->toEqual($testFile);
});

it('can detect when files get deleted', function () {
    $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'test.txt';

    touch($testFile);

    Watch::path($this->testDirectory)
        ->onFileDeleted(function (string $path) {
            $this->modifiedPath = $path;
        })
        ->onAnyChange(function (string $type, string $path) {
            $this->recordedEvents[] = [$type, $path];
        })
        ->shouldContinue(function () use ($testFile) {
            if ($this->i === 5) {
                unlink($testFile);
            }

            $this->i++;

            return $this->i <= 12;
        })
        ->start();

    expect($this->recordedEvents)->toHaveCount(1)
        ->and($this->recordedEvents[0])->toEqual([
            'fileDeleted',
            $testFile,
        ]);

    expect($this->modifiedPath)->toEqual($testFile);
});

it('can detect when a directory gets created', function () {
    $newDirectoryPath = $this->testDirectory . DIRECTORY_SEPARATOR . 'new';

    Watch::path($this->testDirectory)
        ->onDirectoryCreated(function (string $path) {
            $this->modifiedPath = $path;
        })
        ->onAnyChange(function (string $type, string $path) {
            ray($type, $path);
            $this->recordedEvents[] = [$type, $path];
        })
        ->shouldContinue(function () use ($newDirectoryPath) {
            if ($this->i === 5) {
                mkdir($newDirectoryPath);
            }

            $this->i++;

            return $this->i <= 12;
        })
        ->start();

    expect($this->recordedEvents)->toHaveCount(1)
        ->and($this->recordedEvents[0])->toEqual([
            'directoryCreated',
            $newDirectoryPath,
        ]);

    expect($this->modifiedPath)->toEqual($newDirectoryPath);
});

it('can detect when a directory gets deleted', function () {
    $directoryPath = $this->testDirectory . DIRECTORY_SEPARATOR . 'new';

    $directory = (new TemporaryDirectory($directoryPath))->empty();

    Watch::path($this->testDirectory)
        ->onDirectoryDeleted(function (string $path) {
            $this->modifiedPath = $path;
        })
        ->onAnyChange(function (string $type, string $path) {
            $this->recordedEvents[] = [$type, $path];
        })
        ->shouldContinue(function () use ($directory) {
            if ($this->i === 5) {
                $directory->delete();
            }

            $this->i++;

            return $this->i <= 12;
        })
        ->start();

    expect($this->recordedEvents)->toHaveCount(1)
        ->and($this->recordedEvents[0])->toEqual([
            'directoryDeleted',
            $directoryPath,
        ]);

    expect($this->modifiedPath)->toEqual($directoryPath);
});
