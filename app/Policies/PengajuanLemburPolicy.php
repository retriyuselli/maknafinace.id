<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PengajuanLembur;
use Illuminate\Auth\Access\HandlesAuthorization;

class PengajuanLemburPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PengajuanLembur');
    }

    public function view(AuthUser $authUser, PengajuanLembur $pengajuanLembur): bool
    {
        return $authUser->can('View:PengajuanLembur');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PengajuanLembur');
    }

    public function update(AuthUser $authUser, PengajuanLembur $pengajuanLembur): bool
    {
        return $authUser->can('Update:PengajuanLembur');
    }

    public function delete(AuthUser $authUser, PengajuanLembur $pengajuanLembur): bool
    {
        return $authUser->can('Delete:PengajuanLembur');
    }

    public function restore(AuthUser $authUser, PengajuanLembur $pengajuanLembur): bool
    {
        return $authUser->can('Restore:PengajuanLembur');
    }

    public function forceDelete(AuthUser $authUser, PengajuanLembur $pengajuanLembur): bool
    {
        return $authUser->can('ForceDelete:PengajuanLembur');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PengajuanLembur');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PengajuanLembur');
    }

    public function replicate(AuthUser $authUser, PengajuanLembur $pengajuanLembur): bool
    {
        return $authUser->can('Replicate:PengajuanLembur');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PengajuanLembur');
    }

}