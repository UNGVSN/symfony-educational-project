<?php

declare(strict_types=1);

namespace Console\Helper;

use Console\Output\OutputInterface;

/**
 * Table Helper
 *
 * Renders tables in the console.
 *
 * Example:
 *   $table = new Table($output);
 *   $table
 *       ->setHeaders(['ID', 'Name', 'Email'])
 *       ->setRows([
 *           [1, 'John', 'john@example.com'],
 *           [2, 'Jane', 'jane@example.com'],
 *       ])
 *       ->render();
 */
class Table
{
    private OutputInterface $output;
    private array $headers = [];
    private array $rows = [];
    private string $style = 'default';

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Set table headers
     */
    public function setHeaders(array $headers): static
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Set table rows
     */
    public function setRows(array $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    /**
     * Add a single row
     */
    public function addRow(array $row): static
    {
        $this->rows[] = $row;
        return $this;
    }

    /**
     * Set table style
     */
    public function setStyle(string $style): static
    {
        $this->style = $style;
        return $this;
    }

    /**
     * Render the table
     */
    public function render(): void
    {
        $columnWidths = $this->calculateColumnWidths();
        $style = $this->getStyleDefinition();

        // Render top border
        $this->renderSeparator($columnWidths, $style['top']);

        // Render headers
        if (!empty($this->headers)) {
            $this->renderRow($this->headers, $columnWidths, $style['header']);
            $this->renderSeparator($columnWidths, $style['middle']);
        }

        // Render rows
        foreach ($this->rows as $index => $row) {
            $this->renderRow($row, $columnWidths, $style['row']);

            // Render separator between rows (optional)
            if ($index < count($this->rows) - 1 && isset($style['row_separator'])) {
                $this->renderSeparator($columnWidths, $style['row_separator']);
            }
        }

        // Render bottom border
        $this->renderSeparator($columnWidths, $style['bottom']);
    }

    /**
     * Calculate column widths
     */
    private function calculateColumnWidths(): array
    {
        $widths = [];

        // Start with headers
        foreach ($this->headers as $index => $header) {
            $widths[$index] = strlen((string) $header);
        }

        // Check all rows
        foreach ($this->rows as $row) {
            foreach ($row as $index => $cell) {
                $length = strlen((string) $cell);
                $widths[$index] = max($widths[$index] ?? 0, $length);
            }
        }

        return $widths;
    }

    /**
     * Render a separator line
     */
    private function renderSeparator(array $widths, array $style): void
    {
        $line = $style['left'];

        foreach ($widths as $index => $width) {
            if ($index > 0) {
                $line .= $style['cross'];
            }
            $line .= str_repeat($style['horizontal'], $width + 2);
        }

        $line .= $style['right'];

        $this->output->writeln($line);
    }

    /**
     * Render a row
     */
    private function renderRow(array $row, array $widths, array $style): void
    {
        $line = $style['left'];

        foreach ($widths as $index => $width) {
            if ($index > 0) {
                $line .= $style['separator'];
            }

            $cell = $row[$index] ?? '';
            $line .= ' ' . str_pad((string) $cell, $width) . ' ';
        }

        $line .= $style['right'];

        $this->output->writeln($line);
    }

    /**
     * Get style definition
     */
    private function getStyleDefinition(): array
    {
        $styles = [
            'default' => [
                'top' => [
                    'left' => '+',
                    'horizontal' => '-',
                    'cross' => '+',
                    'right' => '+',
                ],
                'middle' => [
                    'left' => '+',
                    'horizontal' => '-',
                    'cross' => '+',
                    'right' => '+',
                ],
                'bottom' => [
                    'left' => '+',
                    'horizontal' => '-',
                    'cross' => '+',
                    'right' => '+',
                ],
                'header' => [
                    'left' => '|',
                    'separator' => '|',
                    'right' => '|',
                ],
                'row' => [
                    'left' => '|',
                    'separator' => '|',
                    'right' => '|',
                ],
            ],
            'compact' => [
                'top' => [
                    'left' => ' ',
                    'horizontal' => '-',
                    'cross' => '-',
                    'right' => ' ',
                ],
                'middle' => [
                    'left' => ' ',
                    'horizontal' => '-',
                    'cross' => '-',
                    'right' => ' ',
                ],
                'bottom' => [
                    'left' => ' ',
                    'horizontal' => '-',
                    'cross' => '-',
                    'right' => ' ',
                ],
                'header' => [
                    'left' => ' ',
                    'separator' => ' ',
                    'right' => ' ',
                ],
                'row' => [
                    'left' => ' ',
                    'separator' => ' ',
                    'right' => ' ',
                ],
            ],
            'box' => [
                'top' => [
                    'left' => '┌',
                    'horizontal' => '─',
                    'cross' => '┬',
                    'right' => '┐',
                ],
                'middle' => [
                    'left' => '├',
                    'horizontal' => '─',
                    'cross' => '┼',
                    'right' => '┤',
                ],
                'bottom' => [
                    'left' => '└',
                    'horizontal' => '─',
                    'cross' => '┴',
                    'right' => '┘',
                ],
                'header' => [
                    'left' => '│',
                    'separator' => '│',
                    'right' => '│',
                ],
                'row' => [
                    'left' => '│',
                    'separator' => '│',
                    'right' => '│',
                ],
            ],
        ];

        return $styles[$this->style] ?? $styles['default'];
    }
}
