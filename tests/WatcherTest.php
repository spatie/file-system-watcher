<?php

namespace Spatie\Watcher\Tests;

use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Spatie\Watcher\Watcher;

class WatcherTest extends TestCase
{
    protected string $testDirectory;

    protected int $i = 0;

    protected array $recordedEvents = [];

    public function setUp(): void
    {
        parent::setUp();

        ray()->clearScreen();

        $this->testDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'testDirectory';

        (new TemporaryDirectory($this->testDirectory))->empty();

        $this->i = 0;
    }

    /** @test */
    public function it_can_detect_when_files_get_created()
    {
        (new Watcher())
            ->paths([$this->testDirectory])
            ->onFileCreated(function (string $path) {
                $this->modifiedPath = $path;
            })
            ->onAnyEvent(function (string $type, string $path) {
                $this->recordedEvents[] = [$type, $path];
            })
            ->shouldContinue(function () {
                if ($this->i === 5) {
                    touch($this->testDirectory . DIRECTORY_SEPARATOR . 'test.txt');
                }

                $this->i++;

                return $this->i <= 7;
            })
            ->start();

        $this->assertCount(1, $this->recordedEvents);
        $this->assertEquals([
            'fileCreated',
            $this->testDirectory . DIRECTORY_SEPARATOR . 'test.txt',
        ], $this->recordedEvents[0]);

        $this->assertEquals($this->recordedEvents[0][1], $this->modifiedPath);
    }

    /** @test */
    public function it_can_detect_when_files_get_updated()
    {
        $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'test.txt';

        touch($testFile);

        (new Watcher())
            ->paths([$this->testDirectory])
            ->onFileUpdated(function (string $path) {
                $this->modifiedPath = $path;
            })
            ->onAnyEvent(function (string $type, string $path) {
                $this->recordedEvents[] = [$type, $path];
            })
            ->shouldContinue(function () use ($testFile) {
                if ($this->i === 5) {
                    file_put_contents($testFile, 'updated');
                }

                $this->i++;

                return $this->i <= 7;
            })
            ->start();

        $this->assertCount(1, $this->recordedEvents);
        $this->assertEquals([
            'fileUpdated',
            $testFile,
        ], $this->recordedEvents[0]);

        $this->assertEquals($testFile, $this->modifiedPath);
    }

    /** @test */
    public function it_can_detect_when_files_get_deleted()
    {
        $testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'test.txt';

        touch($testFile);

        (new Watcher())
            ->paths([$this->testDirectory])
            ->onFileDeleted(function (string $path) {
                $this->modifiedPath = $path;
            })
            ->onAnyEvent(function (string $type, string $path) {
                $this->recordedEvents[] = [$type, $path];
            })
            ->shouldContinue(function () use ($testFile) {
                if ($this->i === 5) {
                    unlink($testFile);
                }

                $this->i++;

                return $this->i <= 7;
            })
            ->start();

        $this->assertCount(1, $this->recordedEvents);
        $this->assertEquals([
            'fileDeleted',
            $testFile,
        ], $this->recordedEvents[0]);

        $this->assertEquals($testFile, $this->modifiedPath);
    }

    /** @test */
    public function it_can_detect_when_a_directory_gets_created()
    {
        $newDirectoryPath = $this->testDirectory . DIRECTORY_SEPARATOR . 'new';

        (new Watcher())
            ->paths([$this->testDirectory])
            ->onDirectoryCreated(function (string $path) {
                $this->modifiedPath = $path;
            })
            ->onAnyEvent(function (string $type, string $path) {
                $this->recordedEvents[] = [$type, $path];
            })
            ->shouldContinue(function () use ($newDirectoryPath) {
                if ($this->i === 5) {
                    mkdir($newDirectoryPath);
                }

                $this->i++;

                return $this->i <= 7;
            })
            ->start();

        $this->assertCount(1, $this->recordedEvents);
        $this->assertEquals([
            'directoryCreated',
            $newDirectoryPath,
        ], $this->recordedEvents[0]);

        $this->assertEquals($newDirectoryPath, $this->modifiedPath);
    }
}
