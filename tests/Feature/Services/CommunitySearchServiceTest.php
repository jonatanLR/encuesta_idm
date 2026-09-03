<?php

use App\Models\Community;
use App\Models\Municipality;
use App\Services\CommunitySearchService;

/* it('returns an empty collection when the community search term is empty', function () {
    $service = app(CommunitySearchService::class);

    $results = $service->search('', 1);

    expect($results)
        ->toBeInstanceOf(Illuminate\Database\Eloquent\Collection::class)
        ->toBeEmpty();
}); */

//----------------------------------------------

it('returns an empty collection when the search term is empty', function () {
    $service = app(CommunitySearchService::class);

    $results = $service->search('', 1);

    expect($results)->toBeEmpty();
});

//------------------------------------------------------------

it('returns an empty collection when the search term contains only whitespace', function () {
    $service = app(CommunitySearchService::class);

    $results = $service->search('   ', 1);

    expect($results)->toBeEmpty();
});

//---------------------------------------------------------

it('returns only communities from the specified municipality', function () {
    $municipality = Municipality::factory()->create([
        'name' => 'Municipio de Prueba',
    ]);

    $otherMunicipality = Municipality::factory()->create([
        'name' => 'Otro Municipio',
    ]);

    $community = Community::factory()->create([
        'municipality_id' => $municipality->id,
        'name' => 'Comunidad Correcta',
        'search_name' => 'comunidad correcta',
        'active' => true,
    ]);

    Community::factory()->create([
        'municipality_id' => $otherMunicipality->id,
        'name' => 'Comunidad Otro Municipio',
        'search_name' => 'comunidad otro municipio',
        'active' => true,
    ]);

    $service = app(CommunitySearchService::class);

    $results = $service->search('Comunidad', $municipality->id);

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($community->id);
});
//-------------------------------------------------------------

it('excludes inactive communities from the results', function () {
    $municipality = Municipality::factory()->create();

    $activeCommunity = Community::factory()->create([
        'municipality_id' => $municipality->id,
        'name' => 'Comunidad Activa',
        'search_name' => 'comunidad activa',
        'active' => true,
    ]);

    Community::factory()->create([
        'municipality_id' => $municipality->id,
        'name' => 'Comunidad Inactiva',
        'search_name' => 'comunidad inactiva',
        'active' => false,
    ]);

    $service = app(CommunitySearchService::class);

    $results = $service->search('Comunidad', $municipality->id);

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($activeCommunity->id);
});

//-------------------------------------------------------------

it('searches using search_name and trims the search term', function () {
    $municipality = Municipality::factory()->create();

    $community = Community::factory()->create([
        'municipality_id' => $municipality->id,
        'name' => 'Colonia Las Flores',
        'search_name' => 'colonia las flores',
        'active' => true,
    ]);

    $service = app(CommunitySearchService::class);

    $results = $service->search('  las flores  ', $municipality->id);

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($community->id);
});

//-------------------------------------------------------------

it('returns communities ordered by name', function () {
    $municipality = Municipality::factory()->create();

    Community::factory()->create([
        'municipality_id' => $municipality->id,
        'name' => 'Zacate Blanco',
        'search_name' => 'zacate blanco',
        'active' => true,
    ]);

    Community::factory()->create([
        'municipality_id' => $municipality->id,
        'name' => 'Aldea Bonita',
        'search_name' => 'aldea bonita',
        'active' => true,
    ]);

    Community::factory()->create([
        'municipality_id' => $municipality->id,
        'name' => 'Barrio Central',
        'search_name' => 'barrio central',
        'active' => true,
    ]);

    $service = app(CommunitySearchService::class);

    $results = $service->search('a', $municipality->id);

    expect($results->pluck('name')->values()->all())
        ->toBe([
            'Aldea Bonita',
            'Barrio Central',
            'Zacate Blanco',
        ]);
});

//---------------------------------------------

it('respects the specified result limit', function () {
    $municipality = Municipality::factory()->create();

    foreach (['Comunidad A', 'Comunidad B', 'Comunidad C', 'Comunidad D'] as $name) {
        Community::factory()->create([
            'municipality_id' => $municipality->id,
            'name' => $name,
            'search_name' => strtolower($name),
            'active' => true,
        ]);
    }

    $service = app(CommunitySearchService::class);

    $results = $service->search('Comunidad', $municipality->id, 2);

    expect($results)->toHaveCount(2);
});

//--------------------------------------------------------------



//---------------------------------------------------------

