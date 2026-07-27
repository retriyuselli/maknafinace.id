<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\JadwalKerja;
use Illuminate\Auth\Access\HandlesAuthorization;

class JadwalKerjaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:JadwalKerja');
    }

    public function view(AuthUser $authUser, JadwalKerja $jadwalKerja): bool
    {
        return $authUser->can('View:JadwalKerja');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:JadwalKerja');
    }

    public function update(AuthUser $authUser, JadwalKerja $jadwalKerja): bool
    {
        return $authUser->can('Update:JadwalKerja');
    }

    public function delete(AuthUser $authUser, JadwalKerja $jadwalKerja): bool
    {
        return $authUser->can('Delete:JadwalKerja');
    }

    public function restore(AuthUser $authUser, JadwalKerja $jadwalKerja): bool
    {
        return $authUser->can('Restore:JadwalKerja');
    }

    public function forceDelete(AuthUser $authUser, JadwalKerja $jadwalKerja): bool
    {
        return $authUser->can('ForceDelete:JadwalKerja');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:JadwalKerja');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:JadwalKerja');
    }

    public function replicate(AuthUser $authUser, JadwalKerja $jadwalKerja): bool
    {
        return $authUser->can('Replicate:JadwalKerja');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:JadwalKerja');
    }

}