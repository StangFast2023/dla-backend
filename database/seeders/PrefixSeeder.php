<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrefixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prefixes = [
            ['id' => 1, 'name' => 'นักวิชาการ'],
            ['id' => 2, 'name' => 'เจ้าพนักงาน'],
            ['id' => 3, 'name' => ' '],
            ['id' => 4, 'name' => 'นายช่าง'],
            ['id' => 5, 'name' => 'นักจัดการงาน'],
            ['id' => 6, 'name' => 'นัก'],
            ['id' => 7, 'name' => 'นักพัฒนา'],
            ['id' => 8, 'name' => 'ครูผู้ช่วย'],
        ];

        // บันทึกข้อมูลลงตาราง prefixes_dla
        DB::table('prefixes_dla')->insert($prefixes);
    }
}
