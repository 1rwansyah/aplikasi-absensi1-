<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanyProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CompanyProfile::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Nama Perusahaan',
                'logo' => null,
                'address' => 'Alamat kantor',
                'email' => 'company@example.com',
                'phone' => '0210000000',
                'website' => null,
                'description' => null,
            ]
        );
    }
}
