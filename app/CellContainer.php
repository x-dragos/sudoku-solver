<?php

namespace App;

use Illuminate\Support\Collection;

class CellContainer
{

    private int $index;
    private Collection $cells;

    public function __construct(int $index)
    {
        $this->index = $index;
        $this->cells = new Collection();
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    public function addCell(SudokuCell $cell)
    {
        $this->cells->add($cell);
    }

    public function getValues(): Collection
    {
        return $this->cells->map(function ($cell) {
            return $cell->getValue();
        });
    }

    public function hasCellWithValue(int $value): bool
    {
        return (bool)$this->cells->first(function (SudokuCell $cell) use ($value) {
            return $cell->getValue() === $value;
        });
    }

    public function getCells(): Collection
    {
        return $this->cells;
    }
}
