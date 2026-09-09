<?php

namespace App\Services;

use App\Models\HouseholdMember;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class HouseholdMemberService
{
    public function startCapture(
        SurveyResponse $response,
        HouseholdMember $householdMember
    ): HouseholdMember {
        if ($householdMember->trashed()) {
            throw new InvalidArgumentException(
                'Un miembro eliminado no puede iniciar una captura.'
            );
        }

        $belongsToResponse = $response->household()
            ->whereHas('householdMembers', function (Builder $query) use ($householdMember): void {
                $query->whereKey($householdMember->getKey());
            })
            ->exists();

        if (! $belongsToResponse) {
            throw new InvalidArgumentException(
                'El miembro no pertenece a la respuesta de encuesta.'
            );
        }

        HouseholdMember::query()
            ->whereKey($householdMember->getKey())
            ->whereNull('capture_started_at')
            ->update([
                'capture_started_at' => now(),
            ]);

        return $householdMember->refresh();
    }
}
