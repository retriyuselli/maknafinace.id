<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DocumentationCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentationCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DocumentationCategory');
    }

    public function view(AuthUser $authUser, DocumentationCategory $documentationCategory): bool
    {
        return $authUser->can('View:DocumentationCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DocumentationCategory');
    }

    public function update(AuthUser $authUser, DocumentationCategory $documentationCategory): bool
    {
        return $authUser->can('Update:DocumentationCategory');
    }

    public function delete(AuthUser $authUser, DocumentationCategory $documentationCategory): bool
    {
        return $authUser->can('Delete:DocumentationCategory');
    }

    public function restore(AuthUser $authUser, DocumentationCategory $documentationCategory): bool
    {
        return $authUser->can('Restore:DocumentationCategory');
    }

    public function forceDelete(AuthUser $authUser, DocumentationCategory $documentationCategory): bool
    {
        return $authUser->can('ForceDelete:DocumentationCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DocumentationCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DocumentationCategory');
    }

    public function replicate(AuthUser $authUser, DocumentationCategory $documentationCategory): bool
    {
        return $authUser->can('Replicate:DocumentationCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DocumentationCategory');
    }

}