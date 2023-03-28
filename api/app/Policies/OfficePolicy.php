<?php

namespace App\Policies;

use App\Models\Office;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfficePolicy
{
    use HandlesAuthorization;

    public function update(User $user, Office $office): bool
    {
        return $user->id === $office->user_id;
    }

    public function delete(User $user, Office $office): bool
    {
        return $this->update($user, $office);
    }
}
