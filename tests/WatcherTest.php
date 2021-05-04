<?php

namespace Spatie\Watcher\Tests;

use PHPUnit\Framework\TestCase;
use Spatie\Watcher\Watcher;

class WatcherTest extends TestCase
{
    /** @test */
    public function it_can_watch_for_changes_in_the_file_system()
    {
        ray()->clearScreen();

        (new Watcher())
            ->paths([__DIR__ . '/testDirectory'])
            ->start();
    }
}
