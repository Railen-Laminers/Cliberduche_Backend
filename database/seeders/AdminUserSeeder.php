<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user if not exists
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'firstname' => 'Administrator',
                'lastname' => 'Admin',
                'contact_number' => 'N/A',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'account_status' => 'approved',
                'is_active' => true,
            ]
        );
        
        // Ensure role is properly set
        $admin = User::where('email', 'admin@cliberduche.com')->first();
        if ($admin) {
            $admin->role = 'admin';
            $admin->account_status = 'approved';
            $admin->save();
        }
    }
}

