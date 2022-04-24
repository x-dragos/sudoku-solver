<?php

namespace App;

use Illuminate\Support\Collection;

class SudokuCell
{
    private int $index;

    private bool $solved = false;
    private int $value = 0;

    private SudokuLine $sudokuLine;
    private SudokuColumn $sudokuColumn;
    private SudokuBox $sudokuBox;

    private Collection $cannotBe;

    const POSSIBLE_VALUES = [
        1, 2, 3, 4, 5, 6, 7, 8, 9
    ];

    /**
     * @param bool $solved
     * @return SudokuCell
     */
    public function setSolved(bool $solved): SudokuCell
    {
        $this->solved = $solved;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSolved(): bool
    {
        return $this->solved;
    }

    /**
     * @param SudokuLine $sudokuLine
     * @return SudokuCell
     */
    public function setSudokuLine(SudokuLine $sudokuLine): SudokuCell
    {
        $this->sudokuLine = $sudokuLine;
        $sudokuLine->addCell($this);
        return $this;
    }

    /**
     * @return SudokuLine
     */
    public function getSudokuLine(): SudokuLine
    {
        return $this->sudokuLine;
    }

    /**
     * @param SudokuColumn $sudokuColumn
     * @return SudokuCell
     */
    public function setSudokuColumn(SudokuColumn $sudokuColumn): SudokuCell
    {
        $this->sudokuColumn = $sudokuColumn;
        $sudokuColumn->addCell($this);
        return $this;
    }

    /**
     * @return SudokuColumn
     */
    public function getSudokuColumn(): SudokuColumn
    {
        return $this->sudokuColumn;
    }

    /**
     * @param SudokuBox $sudokuBox
     * @return SudokuCell
     */
    public function setSudokuBox(SudokuBox $sudokuBox): SudokuCell
    {
        $this->sudokuBox = $sudokuBox;
        $sudokuBox->addCell($this);
        return $this;
    }

    /**
     * @return SudokuBox
     */
    public function getSudokuBox(): SudokuBox
    {
        return $this->sudokuBox;
    }

    public function __construct(int $index, int $value = 0)
    {
        $this->index = $index;
        $this->value = $value;

        if ($value) {
            $this->solved = true;
        }

        $this->cannotBe = new Collection();
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    public function getPrintValue()
    {
        return $this->value ?: ' ';
    }

    public function checkCanBeSolved()
    {
        if ($this->solved) {
            return;
        }

        if ($this->cannotBe->count() === 8) {
            for ($i = 1; $i < 10; $i += 1) {
                if ($this->cannotBe->doesntContain($i)) {
                    $this->value = $i;
                    $this->solved = true;
                    return;
                }
            }
        } else {
            $possibleValues = new Collection(self::POSSIBLE_VALUES);
            $possibleValues = $possibleValues->diff($this->cannotBe);
            $possibleValues->each(function ($possibleValue) {
                $this->solveForContainer($this->getSudokuLine(), $possibleValue, $this);
                $this->solveForContainer($this->getSudokuColumn(), $possibleValue, $this);
                $this->solveForContainer($this->getSudokuBox(), $possibleValue, $this);
            });
        }
    }

    public function updateCannotBe()
    {
        $addCannotBe = function ($value) {
            $this->cannotBe->add($value);
        };

        $this->getSudokuLine()->getValues()->each($addCannotBe);
        $this->getSudokuColumn()->getValues()->each($addCannotBe);
        $this->getSudokuBox()->getValues()->each($addCannotBe);

        $this->cannotBe = $this->cannotBe->filter()->unique();
    }

    public function solveFor(Collection $containers)
    {
        $containersWithoutValue = $containers->filter(function ($container) {
            return !$container->hasCellWithValue($this->value);
        });

        if ($containersWithoutValue->count() !== 1) {
            return;
        }

        $container = $containersWithoutValue->first();

        $this->solveForContainer($container, $this->value);
    }

    public function canBe(int $value): bool
    {
        return !$this->isSolved() && $this->cannotBe->doesntContain($value);
    }

    public function solveWithValue(int $value)
    {
        if ($this->isSolved()) {
            return;
        }
        $this->value = $value;
        $this->solved = true;
    }

    /**
     * @param $container
     * @param int $value
     * @param SudokuCell|null $cellToSolve
     * @return void
     */
    private function solveForContainer($container, int $value, ?SudokuCell $cellToSolve = null): void
    {
        $possibleCells = $container->getCells()->filter(function (SudokuCell $cell) use ($value) {
            return $cell->canBe($value);
        });

        if ($possibleCells->count() === 1 && (!$cellToSolve || $possibleCells->contains($cellToSolve))) {
            $possibleCells->first()->solveWithValue($value);
        }
    }
}
