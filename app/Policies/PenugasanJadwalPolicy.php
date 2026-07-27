<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PenugasanJadwal;
use Illuminate\Auth\Access\HandlesAuthorization;

class PenugasanJadwalPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PenugasanJadwal');
    }

    public function view(AuthUser $authUser, PenugasanJadwal $penugasanJadwal): bool
    {
        return $authUser->can('View:PenugasanJadwal');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PenugasanJadwal');
    }

    public function update(AuthUser $authUser, PenugasanJadwal $penugasanJadwal): bool
    {
        return $authUser->can('Update:PenugasanJadwal');
    }

    public function delete(AuthUser $authUser, PenugasanJadwal $penugasanJadwal): bool
    {
        return $authUser->can('Delete:PenugasanJadwal');
    }

    public function restore(AuthUser $authUser, PenugasanJadwal $penugasanJadwal): bool
    {
        return $authUser->can('Restore:PenugasanJadwal');
    }

    public function forceDelete(AuthUser $authUser, PenugasanJadwal $penugasanJadwal): bool
    {
        return $authUser->can('ForceDelete:PenugasanJadwal');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PenugasanJadwal');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PenugasanJadwal');
    }

    public function replicate(AuthUser $authUser, PenugasanJadwal $penugasanJadwal): bool
    {
        return $authUser->can('Replicate:PenugasanJadwal');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PenugasanJadwal');
    }

}