<?php

namespace Database\Seeders;

use App\Models\AcademicProgram;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@nemsu.edu.ph'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('ChangeMe123!'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'faculty@nemsu.edu.ph'],
            [
                'name' => 'Faculty Viewer',
                'password' => Hash::make('ChangeMe123!'),
                'role' => 'faculty',
                'is_active' => true,
            ]
        );

        AcademicProgram::firstOrCreate(
            ['code' => 'BSCS'],
            ['name' => 'BS Computer Science', 'college' => 'College of Information Technology Education']
        );

        SchoolYear::firstOrCreate(
            ['label' => '2025-2026'],
            ['is_current' => true]
        );
    }
}
