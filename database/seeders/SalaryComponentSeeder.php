<?php

namespace Database\Seeders;

use App\Models\SalaryComponent;
use Illuminate\Database\Seeder;

class SalaryComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            ['name' => 'Jabatan', 'type' => 'allowance', 'default_amount' => 0],
            ['name' => 'Transport', 'type' => 'allowance', 'default_amount' => 0],
            ['name' => 'Keluarga', 'type' => 'allowance', 'default_amount' => 0],
            ['name' => 'Pulsa', 'type' => 'allowance', 'default_amount' => 0],
            ['name' => 'Tempat Tinggal', 'type' => 'allowance', 'default_amount' => 0],
            ['name' => 'BPJS Kesehatan', 'type' => 'allowance', 'default_amount' => 0],
            ['name' => 'BPJS TK', 'type' => 'allowance', 'default_amount' => 0],
            ['name' => 'Asuransi Pendidikan', 'type' => 'allowance', 'default_amount' => 0],
        ];

        foreach ($components as $component) {
            SalaryComponent::updateOrCreate(
                ['name' => $component['name']],
                [
                    'type' => $component['type'],
                    'default_amount' => $component['default_amount'],
                    'is_active' => true,
                ]
            );
        }
    }
}
