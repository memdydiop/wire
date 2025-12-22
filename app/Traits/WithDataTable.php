<?php

namespace App\Traits;

use Livewire\WithPagination;
use Livewire\Attributes\Url;

trait WithDataTable
{
    use WithPagination;

    #[Url(as: 'q')]
    public $search = '';

    #[Url]
    public $perPage = 10;

    #[Url]
    public $sortBy = 'created_at';

    #[Url]
    public $sortDirection = 'desc';

    public $selected = [];
    public $selectAll = false;

    // Abstract-like method requirement: The consuming component MUST implement this
    abstract public function getQuery(); 

    public function updatedSearch()
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function sortByColumn($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            // Efficiently pluck IDs based on current filters
            $this->selected = $this->getQuery()
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function clearSelection()
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    // Helper to check bulk action status
    public function getSelectedCountProperty()
    {
        return count($this->selected);
    }
}