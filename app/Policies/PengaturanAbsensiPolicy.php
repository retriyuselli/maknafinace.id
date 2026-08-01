<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PengaturanAbsensi;
use Illuminate\Auth\Access\HandlesAuthorization;

class PengaturanAbsensiPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PengaturanAbsensi');
    }

    public function view(AuthUser $authUser, PengaturanAbsensi $pengaturanAbsensi): bool
    {
        return $authUser->can('View:PengaturanAbsensi');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PengaturanAbsensi');
    }

    public function update(AuthUser $authUser, PengaturanAbsensi $pengaturanAbsensi): bool
    {
        return $authUser->can('Update:PengaturanAbsensi');
    }

    public function delete(AuthUser $authUser, PengaturanAbsensi $pengaturanAbsensi): bool
    {
        return $authUser->can('Delete:PengaturanAbsensi');
    }

    public function restore(AuthUser $authUser, PengaturanAbsensi $pengaturanAbsensi): bool
    {
        return $authUser->can('Restore:PengaturanAbsensi');
    }

    public function forceDelete(AuthUser $authUser, PengaturanAbsensi $pengaturanAbsensi): bool
    {
        return $authUser->can('ForceDelete:PengaturanAbsensi');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PengaturanAbsensi');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PengaturanAbsensi');
    }

    public function replicate(AuthUser $authUser, PengaturanAbsensi $pengaturanAbsensi): bool
    {
        return $authUser->can('Replicate:PengaturanAbsensi');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PengaturanAbsensi');
    }

}