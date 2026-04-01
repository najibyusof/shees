<?php

namespace App\Policies;

use App\Models\SiteAudit;
use App\Models\User;

class SiteAuditPolicy
{
    public function viewAny(User $user): bool
    {
        // view_audit is the canonical permission used in route middleware.
        // audits.view / audits.conduct are legacy names kept for web workflow.
        return $user->hasPermissionTo('view_audit')
            || $user->hasPermissionTo('audits.view')
            || $user->hasPermissionTo('audits.conduct')
            || $user->hasPermissionTo('create_audit')
            || $user->hasPermissionTo('edit_audit')
            || $user->hasPermissionTo('approve_audit');
    }

    public function view(User $user, SiteAudit $siteAudit): bool
    {
        return $this->viewAny($user) || $siteAudit->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_audit')
            || $user->hasPermissionTo('audits.conduct');
    }

    public function update(User $user, SiteAudit $siteAudit): bool
    {
        if (! $user->hasPermissionTo('edit_audit')
            && ! $user->hasPermissionTo('audits.conduct')) {
            return false;
        }

        if (! in_array($siteAudit->status, ['draft', 'scheduled', 'in_progress', 'rejected'], true)) {
            return false;
        }

        if ($siteAudit->created_by === $user->id) {
            return true;
        }

        // Permission-based fallback — no hardcoded role names.
        return $user->hasPermissionTo('edit_audit');
    }

    public function delete(User $user, SiteAudit $siteAudit): bool
    {
        return $user->hasPermissionTo('approve_audit')
            || $user->hasPermissionTo('audits.approve');
    }

    public function submit(User $user, SiteAudit $siteAudit): bool
    {
        return ($user->hasPermissionTo('create_audit') || $user->hasPermissionTo('audits.conduct'))
            && $siteAudit->created_by === $user->id
            && in_array($siteAudit->status, ['draft', 'scheduled', 'in_progress', 'rejected'], true);
    }

    public function approve(User $user, SiteAudit $siteAudit): bool
    {
        return ($user->hasPermissionTo('approve_audit') || $user->hasPermissionTo('audits.approve'))
            && in_array($siteAudit->status, ['submitted', 'under_review'], true)
            && $siteAudit->created_by !== $user->id;
    }

    public function reject(User $user, SiteAudit $siteAudit): bool
    {
        return $this->approve($user, $siteAudit);
    }

    public function manageNcr(User $user, SiteAudit $siteAudit): bool
    {
        return $this->view($user, $siteAudit)
            && (
                $user->hasPermissionTo('audits.ncr.manage')
                || ($siteAudit->created_by === $user->id && $user->hasPermissionTo('audits.conduct'))
            );
    }

    public function manageKpi(User $user, SiteAudit $siteAudit): bool
    {
        return $this->update($user, $siteAudit);
    }

    public function createNcr(User $user, SiteAudit $siteAudit): bool
    {
        return $this->manageNcr($user, $siteAudit);
    }

    public function updateNcr(User $user, SiteAudit $siteAudit): bool
    {
        return $this->manageNcr($user, $siteAudit);
    }

    public function createCorrectiveAction(User $user, SiteAudit $siteAudit): bool
    {
        return $this->manageNcr($user, $siteAudit);
    }

    public function updateCorrectiveAction(User $user, SiteAudit $siteAudit): bool
    {
        return $this->manageNcr($user, $siteAudit);
    }
}
