<?php

namespace App\Policies;

use App\Models\BankReconciliationItem;
use Illuminate\Foundation\Auth\User as AuthUser;

class BankReconciliationItemPolicy
{
    public function viewAny(AuthUser $user): bool
    {
        return $user->can('ViewAny:BankStatement') || $user->hasRole('super_admin');
    }

    public function view(AuthUser $user, BankReconciliationItem $bankReconciliationItem): bool
    {
        return $this->viewAny($user);
    }

    public function update(AuthUser $user, BankReconciliationItem $bankReconciliationItem): bool
    {
        return $user->can('Update:BankStatement') || $user->hasRole('super_admin');
    }

    public function delete(AuthUser $user, BankReconciliationItem $bankReconciliationItem): bool
    {
        return $this->update($user, $bankReconciliationItem);
    }

    public function restore(AuthUser $user, BankReconciliationItem $bankReconciliationItem): bool
    {
        return $this->update($user, $bankReconciliationItem);
    }

    public function forceDelete(AuthUser $user, BankReconciliationItem $bankReconciliationItem): bool
    {
        return $this->update($user, $bankReconciliationItem);
    }
}
