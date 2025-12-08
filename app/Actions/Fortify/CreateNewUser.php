<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'emp_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique(User::class),
            ],
            'emp_name' => ['required', 'string', 'max:255'],
            'password' => $this->passwordRules(),
            'position' => ['nullable', 'string', 'max:255'],
            'title_class' => ['nullable', 'string', 'max:255'],
            'rank' => ['nullable', 'string', 'max:255'],
            'hr_job_name' => ['nullable', 'string', 'max:255'],
            'job_assigned' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return User::create([
            'emp_no' => $input['emp_no'],
            'emp_name' => $input['emp_name'],
            'password' => $input['password'],
            'role' => UserRole::User,
            'position' => $input['position'] ?? null,
            'title_class' => $input['title_class'] ?? null,
            'rank' => $input['rank'] ?? null,
            'hr_job_name' => $input['hr_job_name'] ?? null,
            'job_assigned' => $input['job_assigned'] ?? null,
            'emp_verified_at' => null, // Explicitly set to null - requires admin verification
        ]);
    }
}
