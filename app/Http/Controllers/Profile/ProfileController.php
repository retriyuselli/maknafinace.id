<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    private function profileViewData(): array
    {
        $user = Auth::user();

        $performanceData = [
            'projects_completed' => 23,
            'client_satisfaction' => 97,
            'revenue_generated' => 125000,
        ];

        $hrData = [
            'monthly_salary' => 8500000,
            'annual_salary' => 102000000,
            'total_annual_leave' => 24,
            'used_leave' => 6,
            'remaining_leave' => 18,
            'sick_leave_taken' => 2,
            'emergency_leave_taken' => 1,
            'last_salary_review' => '2024-01-01',
            'next_salary_review' => '2025-01-01',
            'annual_summary' => [
                'year' => 2024,
                'total_days_worked' => 240,
                'total_leave_taken' => 6,
                'overtime_hours' => 48,
                'bonus_earned' => 2500000,
                'training_hours' => 32,
                'projects_completed' => 12,
                'performance_rating' => 4.2,
            ],
        ];

        $upcomingEvents = [
            [
                'title' => 'Team Meeting',
                'date' => 'Today, 2:00 PM',
                'color' => 'red',
            ],
            [
                'title' => 'Client Consultation',
                'date' => 'Tomorrow, 10:00 AM',
                'color' => 'blue',
            ],
            [
                'title' => 'Wedding Event',
                'date' => 'March 15, 2024',
                'color' => 'green',
            ],
        ];

        $benefits = [
            'health_insurance' => 'Active',
            'annual_leave' => $hrData['remaining_leave'].' days left',
            'performance_bonus' => 'Eligible',
            'training_budget' => '$2,500',
        ];

        return compact('user', 'performanceData', 'upcomingEvents', 'benefits', 'hrData');
    }

    /**
     * Show the user's profile.
     */
    public function show()
    {
        return $this->overview();
    }

    public function overview()
    {
        return view('profile.show', $this->profileViewData());
    }

    public function compensation()
    {
        return view('profile.compensation', $this->profileViewData());
    }

    public function schedule()
    {
        return view('profile.schedule', $this->profileViewData());
    }

    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        $user = Auth::user();

        return view('profile.edit', compact('user'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'hire_date' => ['nullable', 'date'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'signature_url' => ['nullable', 'image', 'mimes:png', 'max:1024'],
        ];

        // Add password validation if password field is filled
        if ($request->filled('password')) {
            $rules['current_password'] = ['required', 'current_password'];
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        $request->validate($rules);

        // Debugging: Log request
        Log::info('Profile Update Request', [
            'method' => $request->method(),
            'has_avatar' => $request->hasFile('avatar'),
            'avatar_error' => $request->hasFile('avatar') ? $request->file('avatar')->getError() : null,
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar_url && Storage::disk('public')->exists($user->avatar_url)) {
                Storage::disk('public')->delete($user->avatar_url);
            }

            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_url = $avatarPath;
        }

        // Handle signature upload
        if ($request->hasFile('signature')) {
            // Delete old signature if exists
            if ($user->signature_url && Storage::disk('public')->exists($user->signature_url)) {
                Storage::disk('public')->delete($user->signature_url);
            }

            // Store new signature
            $signaturePath = $request->file('signature')->store('signatures', 'public');
            $user->signature_url = $signaturePath;
        }

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'hire_date' => $request->hire_date,
            'emergency_contact' => $request->emergency_contact,
            'avatar_url' => $user->avatar_url,
            'signature_url' => $user->signature_url,
            'updated_at' => now(),
        ];

        // Update password if filled
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // Update user data using DB
        DB::table('users')
            ->where('id', $user->id)
            ->update($updateData);

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = Auth::user();
        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'password' => Hash::make($request->password),
                'updated_at' => now(),
            ]);

        return redirect()->route('profile')->with('success', 'Password updated successfully!');
    }

    /**
     * Generate user performance report.
     */
    public function generateReport()
    {
        $user = Auth::user();

        $reportData = [
            'user' => $user,
            'period' => now()->format('F Y'),
            'projects_completed' => 23,
            'client_satisfaction' => 97,
            'revenue_generated' => 125000,
            'performance_score' => 'Excellent',
            'goals_achieved' => 15,
            'total_goals' => 18,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Report generated successfully',
            'data' => $reportData,
        ]);
    }

    /**
     * Get user's upcoming events.
     */
    public function getEvents()
    {
        $events = [
            [
                'id' => 1,
                'title' => 'Team Meeting',
                'date' => now()->format('Y-m-d H:i:s'),
                'type' => 'meeting',
                'status' => 'upcoming',
            ],
            [
                'id' => 2,
                'title' => 'Client Consultation',
                'date' => now()->addDay()->format('Y-m-d H:i:s'),
                'type' => 'consultation',
                'status' => 'scheduled',
            ],
            [
                'id' => 3,
                'title' => 'Wedding Event',
                'date' => now()->addDays(20)->format('Y-m-d H:i:s'),
                'type' => 'event',
                'status' => 'confirmed',
            ],
        ];

        return response()->json($events);
    }

    /**
     * Get user's HR benefits information.
     */
    public function getBenefits()
    {
        $user = Auth::user();

        $benefits = [
            'health_insurance' => [
                'status' => 'Active',
                'provider' => 'Corporate Health Plus',
                'coverage' => 'Full Coverage',
                'expiry' => now()->addYear()->format('F d, Y'),
            ],
            'annual_leave' => [
                'total_days' => 24,
                'used_days' => 6,
                'remaining_days' => 18,
                'pending_requests' => 0,
            ],
            'performance_bonus' => [
                'eligibility' => 'Eligible',
                'last_bonus' => '$5,000',
                'next_review' => 'June 2024',
                'performance_score' => 97,
            ],
            'training_budget' => [
                'annual_budget' => 5000,
                'used_budget' => 2500,
                'remaining_budget' => 2500,
                'last_training' => 'Advanced Wedding Planning',
            ],
        ];

        return response()->json($benefits);
    }
}
