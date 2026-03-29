<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncidentWorkflowSettingsController extends Controller
{
    private const SETTING_KEY = 'incident_workflow.unresolved_critical_comments';

    private const COMMENT_TYPE_OPTIONS = [
        'general' => 'General',
        'clarification' => 'Clarification',
        'action_required' => 'Action Required',
        'action' => 'Action',
        'review' => 'Review',
        'investigation' => 'Investigation',
    ];

    public function __construct()
    {
        $this->middleware('role:Admin');
    }

    public function show(): View
    {
        $base = (array) config('incident_workflow.unresolved_critical_comments', []);
        $db = AppSetting::get(self::SETTING_KEY);
        $current = is_array($db) ? array_merge($base, $db) : $base;

        $roles = Role::orderBy('name')->get();

        return view('admin.settings.incident-workflow', [
            'current' => $current,
            'roles' => $roles,
            'commentTypeOptions' => self::COMMENT_TYPE_OPTIONS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'critical_comment_types' => ['nullable', 'array'],
            'critical_comment_types.*' => ['string', 'in:general,clarification,action_required,action,review,investigation'],
            'role_rules' => ['nullable', 'array'],
            'role_rules.*' => ['boolean'],
        ]);

        $roleRules = collect($validated['role_rules'] ?? [])
            ->mapWithKeys(fn ($enforce, $roleName) => [$roleName => ['enforce' => (bool) $enforce]])
            ->all();

        $settingValue = [
            'enabled' => (bool) $validated['enabled'],
            'critical_comment_types' => $validated['critical_comment_types'] ?? [],
            'role_rules' => $roleRules,
        ];

        AppSetting::set(self::SETTING_KEY, $settingValue, 'incident_workflow');

        return back()->with('success', 'Incident workflow settings saved successfully.');
    }
}
