<?php

namespace App\Policies;

use App\Models\SurveyResponse;
use App\Models\User;

class SurveyResponsePolicy
{
    public function view(User $user, SurveyResponse $surveyResponse): bool
    {
        return $user->hasPermission('survey.view');
    }

    public function update(User $user, SurveyResponse $surveyResponse): bool
    {
        return $this->canModify($user, $surveyResponse, 'survey.update');
    }

    public function complete(User $user, SurveyResponse $surveyResponse): bool
    {
        return $this->canModify($user, $surveyResponse, 'survey.complete');
    }

    public function cancel(User $user, SurveyResponse $surveyResponse): bool
    {
        return $this->canModify($user, $surveyResponse, 'survey.cancel');
    }

    private function canModify(User $user, SurveyResponse $surveyResponse, string $permission): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('survey-admin')) {
            return false;
        }

        return $user->hasPermission($permission)
            && $surveyResponse->created_by === $user->getKey();
    }
}
