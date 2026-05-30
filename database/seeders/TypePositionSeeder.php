<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypePositionSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['id' => 1, 'name' => 'ทั่วไป', 'type_position' => 'ปฏิบัติงาน'],
            ['id' => 2, 'name' => 'วิชาการ', 'type_position' => 'ปฏิบัติการ'],
            ['id' => 3, 'name' => 'ครูผู้ช่วย', 'type_position' => null],
        ];

        DB::table('type_positions_dla')->insert($types);
    }
}
