<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Auth\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ([
            [
                $user = new User(),
                
                $user -> name = "SuperAdmin",
                $user -> email = "superadmin@gmail.com",
                $user -> password = bcrypt('12'),
                $user -> akses = "admin",
                $user -> foto_user = "foto.jpg",
                $user -> save(),
            ],
            [
                $user = new User(),
                $user -> name = "User",
                $user -> email = "user@gmail.com",
                $user -> password = bcrypt('123456'),
                $user -> akses = "karyawan",
                $user -> foto_user = "foto2.jpg",
                $user -> save(),
            ],
        ]);
    }
}
