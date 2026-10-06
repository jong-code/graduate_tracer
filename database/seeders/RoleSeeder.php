<?php

namespace Database\Seeders;

use App\Models\AcademicProgram;
use App\Models\Department;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // email is encrypted (see User::$casts) - firstOrCreate's
        // ['email' => ...] search array would run a plain WHERE against
        // ciphertext and never match, re-creating the account (and
        // failing the email_hash unique constraint) on every reseed.
        // Look up via email_hash instead, same as AuthController does.
        // Seeded, admin-provisioned accounts - not part of the
        // self-registration/email-verification flow (see AuthController
        // and EmailVerificationController), so they're marked verified
        // right after creation rather than left null. email_verified_at is
        // deliberately left out of User::$fillable (so it can never be set
        // via a normal mass-assigned request), so it's set here with
        // forceFill() instead of through firstOrCreate()'s attributes.
        if (app()->environment(['local', 'testing'])) {
            $admin = User::firstOrCreate(
                ['email_hash' => User::hashEmail('admin@nemsu.edu.ph')],
                [
                    'name' => 'System Administrator',
                    'email' => 'admin@nemsu.edu.ph',
                    'password' => Hash::make(Str::random(64)),
                    'role' => 'admin',
                    'is_active' => true,
                ]
            );
            if (! $admin->email_verified_at) {
                $admin->forceFill(['email_verified_at' => now()])->save();
            }

            $faculty = User::firstOrCreate(
                ['email_hash' => User::hashEmail('faculty@nemsu.edu.ph')],
                [
                    'name' => 'Faculty Viewer',
                    'email' => 'faculty@nemsu.edu.ph',
                    'password' => Hash::make(Str::random(64)),
                    'role' => 'faculty',
                    'is_active' => true,
                ]
            );
            if (! $faculty->email_verified_at) {
                $faculty->forceFill(['email_verified_at' => now()])->save();
            }
        }

        $cite = Department::firstOrCreate(
            ['code' => 'CITE'],
            ['name' => 'College of Information Technology Education']
        );

        AcademicProgram::firstOrCreate(
            ['code' => 'BSCS'],
            [
                'name' => 'BS Computer Science',
                'department_id' => $cite->id,
            ]
        );

        SchoolYear::firstOrCreate(
            ['label' => '2025-2026'],
            ['is_current' => true]
        );
    }
}
