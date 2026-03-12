<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Tests\Traits;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

/** @mixin TestCase */
trait TracksMemory
{
    protected int $_memStart = 0;

    protected static int $_memMax = 0;

    private const int MB = 1048576;

    private const int KB = 1024;

    #[Before]
    protected function startMemoryTracking(): void
    {
        memory_reset_peak_usage();
        $this->_memStart = memory_get_usage();
    }

    #[After]
    protected function reportMemoryUsage(): void
    {
        $test = $this->valueObjectForEvents();
        $peak = memory_get_peak_usage(true);
        $peaked = $peak > static::$_memMax;
        if ($peaked) {
            static::$_memMax = $peak;
        }
        $current = memory_get_usage();
        $delta = $current - $this->_memStart;
        $peakDelta = $peak - $this->_memStart;

        // $prettyClass = $test->testDox()->prettifiedClassName();
        // $class = substr($prettyClass, strrpos($prettyClass, '/') + 1);
        $name = $test->testDox()->prettifiedMethodName(true);

        fwrite(STDERR, sprintf(
            PHP_EOL.'  %7s  %7s  %7s  %7s  %s',
            $this->formatBytes($current),
            ($delta >= 0 ? '+' : '').$this->formatBytes($delta),
            $this->formatBytes($peak),
            ($peakDelta >= 0 ? '+' : '').$this->formatBytes($peakDelta),
            // $class,
            $name,
        ));
    }

    protected function reportMemoryUsageOld(): void
    {
        $test = $this->valueObjectForEvents();
        $peak = memory_get_peak_usage(true);
        $delta = memory_get_usage() - $this->_memStart;

        // $prettyClass = $test->testDox()->prettifiedClassName();
        // $class = substr($prettyClass, strrpos($prettyClass, '/') + 1);
        $name = $test->testDox()->prettifiedMethodName(true);

        $deltaStr = ($delta >= 0 ? '+' : '').$this->formatBytes($delta);

        fwrite(STDERR, sprintf(
            PHP_EOL.'  %7s  %7s  %s',
            $deltaStr,
            $this->formatBytes($peak),
            // $class,
            $name,
        ));
    }

    protected function formatBytes(int $bytes): string
    {
        $abs = abs($bytes);

        return match (true) {
            $abs >= (self::MB * 0.1) => sprintf('%.1fMB', $bytes / self::MB),
            $abs >= self::KB => sprintf('%.0fKB', $bytes / self::KB),
            default => sprintf('%.1fKB', $bytes / self::KB),
        };
    }
}
