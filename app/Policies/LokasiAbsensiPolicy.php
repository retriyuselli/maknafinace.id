<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LokasiAbsensi;
use Illuminate\Auth\Access\HandlesAuthorization;

class LokasiAbsensiPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LokasiAbsensi');
    }

    public function view(AuthUser $authUser, LokasiAbsensi $lokasiAbsensi): bool
    {
        return $authUser->can('View:LokasiAbsensi');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LokasiAbsensi');
    }

    public function update(AuthUser $authUser, LokasiAbsensi $lokasiAbsensi): bool
    {
        return $authUser->can('Update:LokasiAbsensi');
    }

    public function delete(AuthUser $authUser, LokasiAbsensi $lokasiAbsensi): bool
    {
        return $authUser->can('Delete:LokasiAbsensi');
    }

    public function restore(AuthUser $authUser, LokasiAbsensi $lokasiAbsensi): bool
    {
        return $authUser->can('Restore:LokasiAbsensi');
    }

    public function forceDelete(AuthUser $authUser, LokasiAbsensi $lokasiAbsensi): bool
    {
        return $authUser->can('ForceDelete:LokasiAbsensi');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LokasiAbsensi');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LokasiAbsensi');
    }

    public function replicate(AuthUser $authUser, LokasiAbsensi $lokasiAbsensi): bool
    {
        return $authUser->can('Replicate:LokasiAbsensi');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LokasiAbsensi');
    }

}