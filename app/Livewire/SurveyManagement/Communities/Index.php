<?php

namespace App\Livewire\SurveyManagement\Communities;

use App\Models\Community;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $communities = Community::query()
            ->when(
                trim($this->search) !== '',
                function ($query) {
                    $search = trim($this->search);

                    $query->where(function ($query) use ($search) {
                        $query->where(
                            'name',
                            'like',
                            '%' . $search . '%'
                        )->orWhere(
                            'source_code',
                            'like',
                            '%' . $search . '%'
                        );
                    });
                }
            )
            ->orderBy('name')
            ->paginate(20);

        return view(
            'livewire.survey-management.communities.index',
            compact('communities')
        );
    }
}
