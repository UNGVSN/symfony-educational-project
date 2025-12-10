<?php

declare(strict_types=1);

namespace Console\Style;

use Console\Helper\ProgressBar;
use Console\Helper\Table;
use Console\Input\InputInterface;
use Console\Output\OutputInterface;

/**
 * Symfony Style
 *
 * High-level API for beautiful console output inspired by Symfony's SymfonyStyle.
 * Provides convenient methods for common output patterns.
 */
class SymfonyStyle
{
    private InputInterface $input;
    private OutputInterface $output;
    private ?ProgressBar $progressBar = null;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Display a title
     */
    public function title(string $message): void
    {
        $this->output->writeln('');
        $this->output->writeln(sprintf('<fg=white;bg=blue> %s </>', $message));
        $this->output->writeln(str_repeat('=', strlen($message) + 2));
        $this->output->writeln('');
    }

    /**
     * Display a section
     */
    public function section(string $message): void
    {
        $this->output->writeln('');
        $this->output->writeln(sprintf('<comment>%s</comment>', $message));
        $this->output->writeln(str_repeat('-', strlen($message)));
        $this->output->writeln('');
    }

    /**
     * Display a success message
     */
    public function success(string|array $message): void
    {
        $this->block($message, 'OK', 'success', ' ', true);
    }

    /**
     * Display an error message
     */
    public function error(string|array $message): void
    {
        $this->block($message, 'ERROR', 'error', ' ', true);
    }

    /**
     * Display a warning message
     */
    public function warning(string|array $message): void
    {
        $this->block($message, 'WARNING', 'warning', ' ', true);
    }

    /**
     * Display a note message
     */
    public function note(string|array $message): void
    {
        $this->block($message, 'NOTE', 'info', ' ! ', false);
    }

    /**
     * Display an info message
     */
    public function info(string|array $message): void
    {
        $this->block($message, 'INFO', 'info', ' ', false);
    }

    /**
     * Display a list
     */
    public function listing(array $elements): void
    {
        foreach ($elements as $element) {
            $this->output->writeln(sprintf(' * %s', $element));
        }
        $this->output->writeln('');
    }

    /**
     * Display text
     */
    public function text(string|array $message): void
    {
        $messages = is_array($message) ? $message : [$message];

        foreach ($messages as $msg) {
            $this->output->writeln(sprintf(' %s', $msg));
        }
    }

    /**
     * Display a table
     */
    public function table(array $headers, array $rows): void
    {
        $table = new Table($this->output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
    }

    /**
     * Start a progress bar
     */
    public function progressStart(int $max = 0): void
    {
        $this->progressBar = new ProgressBar($this->output, $max);
        $this->progressBar->start();
    }

    /**
     * Advance the progress bar
     */
    public function progressAdvance(int $step = 1): void
    {
        if ($this->progressBar) {
            $this->progressBar->advance($step);
        }
    }

    /**
     * Finish the progress bar
     */
    public function progressFinish(): void
    {
        if ($this->progressBar) {
            $this->progressBar->finish();
            $this->progressBar = null;
            $this->output->writeln('');
        }
    }

    /**
     * Ask a question
     */
    public function ask(string $question, ?string $default = null): string
    {
        $message = sprintf(' <question>%s</question>', $question);

        if ($default !== null) {
            $message .= sprintf(' [<comment>%s</comment>]', $default);
        }

        $message .= ': ';

        $this->output->write($message);

        $answer = trim(fgets(STDIN) ?: '');

        return $answer ?: ($default ?? '');
    }

    /**
     * Ask for a hidden answer (like password)
     */
    public function askHidden(string $question): string
    {
        $this->output->write(sprintf(' <question>%s</question>: ', $question));

        // Disable echo
        if (DIRECTORY_SEPARATOR === '\\') {
            // Windows
            $answer = trim(fgets(STDIN) ?: '');
        } else {
            // Unix-like
            system('stty -echo');
            $answer = trim(fgets(STDIN) ?: '');
            system('stty echo');
        }

        $this->output->writeln('');

        return $answer;
    }

    /**
     * Ask for confirmation
     */
    public function confirm(string $question, bool $default = true): bool
    {
        $defaultText = $default ? 'yes' : 'no';
        $message = sprintf(' <question>%s (yes/no)</question> [<comment>%s</comment>]: ', $question, $defaultText);

        $this->output->write($message);

        $answer = trim(strtolower(fgets(STDIN) ?: ''));

        if ($answer === '') {
            return $default;
        }

        return in_array($answer, ['y', 'yes']);
    }

    /**
     * Ask for a choice
     */
    public function choice(string $question, array $choices, mixed $default = null): string
    {
        $this->output->writeln(sprintf(' <question>%s</question>', $question));

        foreach ($choices as $index => $choice) {
            $this->output->writeln(sprintf('  [<comment>%d</comment>] %s', $index, $choice));
        }

        $message = ' > ';
        if ($default !== null) {
            $message = sprintf(' > [<comment>%s</comment>]: ', $default);
        }

        $this->output->write($message);

        $answer = trim(fgets(STDIN) ?: '');

        if ($answer === '' && $default !== null) {
            return $default;
        }

        if (is_numeric($answer) && isset($choices[$answer])) {
            return $choices[$answer];
        }

        if (in_array($answer, $choices)) {
            return $answer;
        }

        throw new \InvalidArgumentException('Invalid choice');
    }

    /**
     * Create a new line
     */
    public function newLine(int $count = 1): void
    {
        $this->output->write(str_repeat(PHP_EOL, $count));
    }

    /**
     * Display a block message
     */
    private function block(
        string|array $messages,
        string $type,
        string $style,
        string $prefix = ' ',
        bool $padding = false
    ): void {
        $messages = is_array($messages) ? $messages : [$messages];

        $this->output->writeln('');

        if ($padding) {
            $this->output->writeln(sprintf('<%s>%s</%s>', $style, str_repeat(' ', 80), $style));
        }

        foreach ($messages as $message) {
            $this->output->writeln(sprintf('<%s>%s[%s] %s</%s>', $style, $prefix, $type, $message, $style));
        }

        if ($padding) {
            $this->output->writeln(sprintf('<%s>%s</%s>', $style, str_repeat(' ', 80), $style));
        }

        $this->output->writeln('');
    }
}
