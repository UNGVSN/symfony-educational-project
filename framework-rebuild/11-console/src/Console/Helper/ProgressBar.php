<?php

declare(strict_types=1);

namespace Console\Helper;

use Console\Output\OutputInterface;

/**
 * Progress Bar
 *
 * Displays a progress bar for long-running operations.
 *
 * Example:
 *   $progress = new ProgressBar($output, 100);
 *   $progress->start();
 *   foreach ($items as $item) {
 *       // Process item
 *       $progress->advance();
 *   }
 *   $progress->finish();
 */
class ProgressBar
{
    private OutputInterface $output;
    private int $max;
    private int $current = 0;
    private int $startTime;
    private int $barWidth = 28;
    private string $barChar = '=';
    private string $emptyBarChar = '-';
    private string $progressChar = '>';

    private string $format = ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%';

    public function __construct(OutputInterface $output, int $max = 0)
    {
        $this->output = $output;
        $this->max = max(0, $max);
    }

    /**
     * Start the progress bar
     */
    public function start(int $max = null): void
    {
        if ($max !== null) {
            $this->max = $max;
        }

        $this->startTime = time();
        $this->current = 0;

        $this->display();
    }

    /**
     * Advance the progress bar
     */
    public function advance(int $step = 1): void
    {
        $this->setProgress($this->current + $step);
    }

    /**
     * Set progress to a specific value
     */
    public function setProgress(int $current): void
    {
        $this->current = min($current, $this->max);
        $this->display();
    }

    /**
     * Finish the progress bar
     */
    public function finish(): void
    {
        if ($this->current < $this->max) {
            $this->current = $this->max;
        }

        $this->display();
        $this->output->write("\n");
    }

    /**
     * Display the progress bar
     */
    private function display(): void
    {
        // Move cursor to beginning of line
        $this->output->write("\r");

        // Clear line
        $this->output->write(str_repeat(' ', 80));
        $this->output->write("\r");

        // Generate progress bar
        $message = $this->generate();

        $this->output->write($message);
    }

    /**
     * Generate the progress bar string
     */
    private function generate(): string
    {
        $percent = 0.0;
        if ($this->max > 0) {
            $percent = (float) $this->current / $this->max;
        }

        $completeBars = (int) floor($percent * $this->barWidth);
        $emptyBars = $this->barWidth - $completeBars;

        $bar = str_repeat($this->barChar, $completeBars);
        if ($completeBars < $this->barWidth) {
            $bar .= $this->progressChar;
            $emptyBars--;
        }
        $bar .= str_repeat($this->emptyBarChar, max(0, $emptyBars));

        $elapsed = time() - $this->startTime;
        $estimated = '---';

        if ($this->current > 0 && $elapsed > 0) {
            $rate = $this->current / $elapsed;
            $remaining = $this->max - $this->current;
            $estimated = (int) ceil($remaining / $rate);
        }

        $replacements = [
            '%current%' => str_pad((string) $this->current, strlen((string) $this->max), ' ', STR_PAD_LEFT),
            '%max%' => $this->max,
            '%bar%' => $bar,
            '%percent%' => floor($percent * 100),
            '%elapsed%' => $this->formatTime($elapsed),
            '%estimated%' => is_numeric($estimated) ? $this->formatTime($estimated) : $estimated,
            '%memory%' => $this->formatMemory(memory_get_usage(true)),
        ];

        return strtr($this->format, $replacements);
    }

    /**
     * Format time in seconds to human-readable format
     */
    private function formatTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Format memory usage
     */
    private function formatMemory(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return sprintf('%.1f%s', $bytes, $units[$i]);
    }

    /**
     * Set custom format
     */
    public function setFormat(string $format): void
    {
        $this->format = $format;
    }

    /**
     * Set bar width
     */
    public function setBarWidth(int $width): void
    {
        $this->barWidth = max(1, $width);
    }

    /**
     * Set progress character
     */
    public function setProgressChar(string $char): void
    {
        $this->progressChar = $char;
    }

    /**
     * Set bar character
     */
    public function setBarChar(string $char): void
    {
        $this->barChar = $char;
    }

    /**
     * Set empty bar character
     */
    public function setEmptyBarChar(string $char): void
    {
        $this->emptyBarChar = $char;
    }
}
