<?php

namespace App;

use Illuminate\Support\Collection;

class SudokuPuzzle
{
    private Collection $lines;
    private Collection $columns;
    private Collection $boxes;
    private Collection $cells;

    private int $passes = 0;

    public function __construct(array $puzzle)
    {
        $this->lines = new Collection();
        $this->columns = new Collection();
        $this->boxes = new Collection();
        $this->cells = new Collection();

        for ($i = 0; $i < 9; $i += 1) {
            $this->lines->add(new SudokuLine($i));
            $this->columns->add(new SudokuColumn($i));
            $this->boxes->add(new SudokuBox($i));
        }

        foreach ($puzzle as $index => $value) {
            $line = (int)($index / 9);
            $column = $index % 9;
            $box = floor($column / 3) + (floor($line / 3) * 3);

            $cell = new SudokuCell($index, $value);
            $cell->setSudokuLine($this->lines->get($line))
                ->setSudokuColumn($this->columns->get($column))
                ->setSudokuBox($this->boxes->get($box));

            $this->cells->add($cell);

//            echo "Box $box ($line, $column): $value \n";

        }
    }

    public function print()
    {
        echo "-------------------\n";
        $this->cells->each(function ($cell) {
            echo "|" . $cell->getPrintValue();
            if ($cell->getSudokuColumn()->getIndex() === 8) {
                echo "|\n";
            }
        });
        echo "-------------------\n";
    }

    public function solve(): SudokuPuzzle
    {
        while (!$this->isSolved() && $this->passes < 1000) {
            $this->unsolvedCellPass();
            if (!$this->isSolved()) {
                $this->solvedCellPass();
            }
            $this->passes += 1;
        }

        echo $this->isSolved()
            ? "Solved in $this->passes passes.\n"
            : "Could not solve in $this->passes passes.\n";
        return $this;
    }

    private function unsolvedCellPass()
    {
        $this->cells->filter(function (SudokuCell $cell) {
            return !$cell->isSolved();
        })->each(function (SudokuCell $cell) {
            $cell->updateCannotBe();
            $cell->checkCanBeSolved();
        });

    }

    private function solvedCellPass()
    {
        $this->cells->filter(function (SudokuCell $cell) {
            return $cell->isSolved();
        })->each(function (SudokuCell $cell) {
            $cell->solveFor($this->getSiblings($this->lines, $cell->getSudokuLine()->getIndex()));
            $cell->solveFor($this->getSiblings($this->columns, $cell->getSudokuColumn()->getIndex()));
            $cell->solveFor($this->getSiblings($this->boxes, $cell->getSudokuBox()->getIndex()));
        });
    }

    private function isSolved(): bool
    {
        return $this->cells->every(function ($cell) {
            return $cell->isSolved();
        });
    }

    private function getSiblings(Collection $containers, int $lineIndex): Collection
    {
        $startIndex = floor($lineIndex/3) * 3;
        return $containers->slice($startIndex, 3)->filter(function ($container) use ($lineIndex) {
            return $container->getIndex() !== $lineIndex;
        });
    }
}
