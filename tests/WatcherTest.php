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
    public function it_can_watch_for_changes_in_the_file_system()
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
}
