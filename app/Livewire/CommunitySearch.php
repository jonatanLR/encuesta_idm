<?php

namespace App\Livewire;

use App\Models\Community;
use App\Models\Municipality;
use App\Services\CommunitySearchService;
use Livewire\Component;

class CommunitySearch extends Component
{
    public string $search = '';

    public ?int $selectedCommunityId = null;

    public ?string $selectedCommunityName = null;

    public int $municipalityId;

    public function mount(): void
    {
        $municipality = Municipality::where(
            'name',
            'Distrito Central'
        )->firstOrFail();

        $this->municipalityId = $municipality->id;
    }

    public function selectCommunity(
        int $communityId
    ): void {
        $community = Community::where(
            'municipality_id',
            $this->municipalityId
        )
            ->where('active', true)
            ->findOrFail($communityId);

        $this->selectedCommunityId = $community->id;
        $this->selectedCommunityName = $community->name;
        $this->search = $community->name;
    }

    public function clearSelection(): void
    {
        $this->selectedCommunityId = null;
        $this->selectedCommunityName = null;
        $this->search = '';
    }

    public function render(
        CommunitySearchService $communitySearch
    ) {
        $communities = $communitySearch->search(
            $this->search,
            $this->municipalityId
        );

        return view(
            'livewire.community-search',
            compact('communities')
        );
    }
}