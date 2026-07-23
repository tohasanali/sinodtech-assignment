<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserBranchSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Assign employees to branches. The named demo employee gets two branches
     * (exercises the frontend branch-switcher UI); the rest get exactly one
     * (exercises the silent auto-select path). Admins get no rows.
     */
    public function run(): void
    {
        $branches = Branch::orderBy('id')->get();
        $employees = User::where('role', UserRole::Employee)->orderBy('id')->get();

        $namedEmployee = $employees->firstWhere('email', 'employee@sinodtech.test');
        $namedEmployee?->branches()->attach($branches->take(2)->pluck('id'));

        foreach ($employees->reject(fn (User $employee) => $namedEmployee && $employee->is($namedEmployee)) as $index => $employee) {
            $employee->branches()->attach($branches[$index % $branches->count()]->id);
        }
    }
}
