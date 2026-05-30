<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = [
            ['id_main_province' => 1, 'id_sub_province' => 1, 'main_name_province' => 'ภาคเหนือ', 'sub_name_province' => 'เขต 1'],
            ['id_main_province' => 1, 'id_sub_province' => 2, 'main_name_province' => 'ภาคเหนือ', 'sub_name_province' => 'เขต 2'],
            ['id_main_province' => 2, 'id_sub_province' => 1, 'main_name_province' => 'ภาคกลาง', 'sub_name_province' => 'เขต 1'],
            ['id_main_province' => 2, 'id_sub_province' => 2, 'main_name_province' => 'ภาคกลาง', 'sub_name_province' => 'เขต 2'],
            ['id_main_province' => 2, 'id_sub_province' => 3, 'main_name_province' => 'ภาคกลาง', 'sub_name_province' => 'เขต 3'],
            ['id_main_province' => 3, 'id_sub_province' => 1, 'main_name_province' => 'ภาคตะวันออกเฉียงเหนือ', 'sub_name_province' => 'เขต 1'],
            ['id_main_province' => 3, 'id_sub_province' => 2, 'main_name_province' => 'ภาคตะวันออกเฉียงเหนือ', 'sub_name_province' => 'เขต 2'],
            ['id_main_province' => 3, 'id_sub_province' => 3, 'main_name_province' => 'ภาคตะวันออกเฉียงเหนือ', 'sub_name_province' => 'เขต 3'],
            ['id_main_province' => 4, 'id_sub_province' => 1, 'main_name_province' => 'ภาคใต้', 'sub_name_province' => 'เขต 1'],
            ['id_main_province' => 4, 'id_sub_province' => 2, 'main_name_province' => 'ภาคใต้', 'sub_name_province' => 'เขต 2'],
        ];

        DB::table('provinces_dla')->insert($provinces);
    }
}
