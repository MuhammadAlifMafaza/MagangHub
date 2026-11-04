<?php

namespace App\Policies;

use App\Enums\GuidanceBookingStatus;
use App\Enums\UserRole;
use App\Models\GuidanceBooking;
use App\Models\User;

class GuidanceBookingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Student, UserRole::Lecturer, UserRole::Admin], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GuidanceBooking $guidanceBooking): bool
    {
        return match ($user->role) {
            UserRole::Student => $guidanceBooking->student->user_id === $user->id,
            UserRole::Lecturer => $guidanceBooking->slot->lecturer->user_id === $user->id,
            UserRole::Admin => true,
        };
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Student && $user->student()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GuidanceBooking $guidanceBooking): bool
    {
        return $user->role === UserRole::Student && $guidanceBooking->student->user_id === $user->id;
    }

    public function cancelByLecturer(User $user, GuidanceBooking $guidanceBooking): bool
    {
        return $user->role === UserRole::Lecturer && $guidanceBooking->slot->lecturer->user_id === $user->id;
    }

    public function cancelByAdmin(User $user, GuidanceBooking $guidanceBooking): bool
    {
        return $user->role === UserRole::Admin && $guidanceBooking->status === GuidanceBookingStatus::Booked;
    }

    public function recordOutcome(User $user, GuidanceBooking $guidanceBooking): bool
    {
        return $user->role === UserRole::Lecturer && $guidanceBooking->slot->lecturer->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GuidanceBooking $guidanceBooking): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GuidanceBooking $guidanceBooking): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GuidanceBooking $guidanceBooking): bool
    {
        return false;
    }
}
