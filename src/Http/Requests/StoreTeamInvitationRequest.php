<?php

namespace Coollabsio\LaravelSaas\Http\Requests;

use Coollabsio\LaravelSaas\Enums\TeamRole;
use Coollabsio\LaravelSaas\Support\Billing;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamInvitationRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(array_column(TeamRole::cases(), 'value'))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'An email address is required.',
            'role.required' => 'A role is required.',
            'role.in' => 'The selected role is invalid.',
        ];
    }

    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $teamParam = $this->route('team');
            $teamModel = Billing::teamModel();
            $team = $teamParam instanceof \Illuminate\Database\Eloquent\Model
                ? $teamParam
                : $teamModel::findOrFail($teamParam);

            if ($team->users()->where('email', $this->input('email'))->exists()) {
                $validator->errors()->add('email', 'This user is already a team member.');
            }

            if ($team->invitations()->where('email', $this->input('email'))->exists()) {
                $validator->errors()->add('email', 'This user has already been invited.');
            }
        });
    }
}
