<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ItemPurchaseCode;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ItemPurchaseCodePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ItemPurchaseCode');
    }

    public function view(AuthUser $authUser, ItemPurchaseCode $itemPurchaseCode): bool
    {
        return $authUser->can('View:ItemPurchaseCode');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ItemPurchaseCode');
    }

    public function update(AuthUser $authUser, ItemPurchaseCode $itemPurchaseCode): bool
    {
        return $authUser->can('Update:ItemPurchaseCode');
    }

    public function delete(AuthUser $authUser, ItemPurchaseCode $itemPurchaseCode): bool
    {
        return $authUser->can('Delete:ItemPurchaseCode');
    }

    public function restore(AuthUser $authUser, ItemPurchaseCode $itemPurchaseCode): bool
    {
        return $authUser->can('Restore:ItemPurchaseCode');
    }

    public function forceDelete(AuthUser $authUser, ItemPurchaseCode $itemPurchaseCode): bool
    {
        return $authUser->can('ForceDelete:ItemPurchaseCode');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ItemPurchaseCode');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ItemPurchaseCode');
    }

    public function replicate(AuthUser $authUser, ItemPurchaseCode $itemPurchaseCode): bool
    {
        return $authUser->can('Replicate:ItemPurchaseCode');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ItemPurchaseCode');
    }
}
