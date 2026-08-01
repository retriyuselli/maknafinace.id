<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\KoreksiAbsensi;
use Illuminate\Auth\Access\HandlesAuthorization;

class KoreksiAbsensiPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:KoreksiAbsensi');
    }

    public function view(AuthUser $authUser, KoreksiAbsensi $koreksiAbsensi): bool
    {
        return $authUser->can('View:KoreksiAbsensi');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:KoreksiAbsensi');
    }

    public function update(AuthUser $authUser, KoreksiAbsensi $koreksiAbsensi): bool
    {
        return $authUser->can('Update:KoreksiAbsensi');
    }

    public function delete(AuthUser $authUser, KoreksiAbsensi $koreksiAbsensi): bool
    {
        return $authUser->can('Delete:KoreksiAbsensi');
    }

    public function restore(AuthUser $authUser, KoreksiAbsensi $koreksiAbsensi): bool
    {
        return $authUser->can('Restore:KoreksiAbsensi');
    }

    public function forceDelete(AuthUser $authUser, KoreksiAbsensi $koreksiAbsensi): bool
    {
        return $authUser->can('ForceDelete:KoreksiAbsensi');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:KoreksiAbsensi');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:KoreksiAbsensi');
    }

    public function replicate(AuthUser $authUser, KoreksiAbsensi $koreksiAbsensi): bool
    {
        return $authUser->can('Replicate:KoreksiAbsensi');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:KoreksiAbsensi');
    }

}