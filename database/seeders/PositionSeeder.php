<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                "id_position" => 101,
                "name" => "ธุรการ",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 102,
                "name" => "ทะเบียน",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 103,
                "name" => "การเงินและบัญชี",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 104,
                "name" => "การคลัง",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 105,
                "name" => "พัสดุ",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 106,
                "name" => "จัดเก็บรายได้",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 107,
                "name" => "ประชาสัมพันธ์",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 108,
                "name" => "ส่งเสริมการท่องเที่ยว",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 109,
                "name" => "การเกษตร",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 110,
                "name" => "สัตวบาล",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 111,
                "name" => "สวนสาธารณะ",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 112,
                "name" => "สาธารณสุข",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 113,
                "name" => "สุขาภิบาล",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 114,
                "name" => "ทันตสาธารณสุข",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 115,
                "name" => "สัตวแพทย์",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 116,
                "name" => "ฉุกเฉินการแพทย์",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 117,
                "name" => "โยธา",
                "id_prefix" => 4,
                "id_type" => 1
            ],
            [
                "id_position" => 118,
                "name" => "เขียนแบบ",
                "id_prefix" => 4,
                "id_type" => 1
            ],
            [
                "id_position" => 119,
                "name" => "สำรวจ",
                "id_prefix" => 4,
                "id_type" => 1
            ],
            [
                "id_position" => 120,
                "name" => "ผังเมือง",
                "id_prefix" => 4,
                "id_type" => 1
            ],
            [
                "id_position" => 121,
                "name" => "เครื่องกล",
                "id_prefix" => 4,
                "id_type" => 1
            ],
            [
                "id_position" => 122,
                "name" => "ไฟฟ้า",
                "id_prefix" => 4,
                "id_type" => 1
            ],
            [
                "id_position" => 123,
                "name" => "ประปา",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 124,
                "name" => "ศิลป์",
                "id_prefix" => 4,
                "id_type" => 1
            ],
            [
                "id_position" => 125,
                "name" => "พัฒนาชุมชน",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 126,
                "name" => "ศูนย์เยาวชน",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 127,
                "name" => "เทศกิจ",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 128,
                "name" => "ป้องกันและบรรเทาสาธารณภัย",
                "id_prefix" => 2,
                "id_type" => 1
            ],
            [
                "id_position" => 201,
                "name" => "ทั่วไป",
                "id_prefix" => 5,
                "id_type" => 2
            ],
            [
                "id_position" => 202,
                "name" => "ทรัพยากรบุคคล",
                "id_prefix" => 6,
                "id_type" => 2
            ],
            [
                "id_position" => 203,
                "name" => "วิเคราะห์นโยบายและแผน",
                "id_prefix" => 6,
                "id_type" => 2
            ],
            [
                "id_position" => 204,
                "name" => "นิติกร",
                "id_prefix" => 3,
                "id_type" => 2
            ],
            [
                "id_position" => 205,
                "name" => "คอมพิวเตอร์",
                "id_prefix" => 1,
                "id_type" => 2
            ],
            [
                "id_position" => 206,
                "name" => "การเงินและบัญชี",
                "id_prefix" => 1,
                "id_type" => 2
            ],
            [
                "id_position" => 207,
                "name" => "การคลัง",
                "id_prefix" => 1,
                "id_type" => 2
            ],
            [
                "id_position" => 208,
                "name" => "จัดเก็บรายได้",
                "id_prefix" => 1,
                "id_type" => 2
            ],
            [
                "id_position" => 209,
                "name" => "พัสดุ",
                "id_prefix" => 1,
                "id_type" => 2
            ],
            [
                "id_position" => 210,
                "name" => "ตรวจสอบภายใน",
                "id_prefix" => 1,
                "id_type" => 2
            ],
            [
                "id_position" => 211,
                "name" => "พาณิชย์",
                "id_prefix" => 1,
                "id_type" => 2
            ],
            [
                "id_position" => 212,
                "name" => "ประชาสัมพันธ์",
                "id_prefix" => 1,
                "id_type" => 2
            ],
            [
                "id_position" => 213,
                "name" => "การเกษตร",
                "id_prefix" => 1,
                "id_type" => 2
            ],
            [
                "id_position" => 214,
                "name" => "สวนสาธารณะ",
                "id_prefix" => 1,
                "id_type" => 2
            ],
            [
                "id_position" => 215,
                "name" => "สาธารณสุข",
                "id_prefix" => 1,
                "id_type" => 2
            ],
            [
                "id_position" => 216,
                "name" => "พยาบาลวิชาชีพ",
                "id_prefix" => 3,
                "id_type" => 2
            ],
            [
                "id_position" => 217,
                "name" => "กายภาพบำบัด",
                "id_prefix" => 6,
                "id_type" => 2
            ],
            [
                "id_position" => 218,
                "name" => "สุขาภิบาล",
                "id_prefix" => 1,
                "id_type" => 2
            ],
            [
                "id_position" => 219,
                "name" => "นายสัตว์แพทย์",
                "id_prefix" => 3,
                "id_type" => 2
            ],
            [
                "id_position" => 220,
                "name" => "ฉุกเฉินการแพทย์",
                "id_prefix" => 6,
                "id_type" => 2
            ],
            [
                "id_position" => 221,
                "name" => "วิศวกรโยธา",
                "id_prefix" => 3,
                "id_type" => 2
            ],
            [
                "id_position" => 222,
                "name" => "สถาปนิก",
                "id_prefix" => 3,
                "id_type" => 2
            ],
            [
                "id_position" => 223,
                "name" => "ผังเมือง",
                "id_prefix" => 6,
                "id_type" => 2
            ],
            [
                "id_position" => 224,
                "name" => "วิศวกรเครื่องกล",
                "id_prefix" => 3,
                "id_type" => 2
            ],
            [
                "id_position" => 225,
                "name" => "วิศวกรไฟฟ้า",
                "id_prefix" => 3,
                "id_type" => 2
            ],
            [
                "id_position" => 226,
                "name" => "วิศวกรสุขาภิบาล",
                "id_prefix" => 3,
                "id_type" => 2
            ],
            [
                "id_position" => 227,
                "name" => "ช่าง",
                "id_prefix" => 5,
                "id_type" => 2
            ],
            [
                "id_position" => 228,
                "name" => "ชุมชน",
                "id_prefix" => 7,
                "id_type" => 2
            ],
            [
                "id_position" => 229,
                "name" => "สังคมสงเคราะห์",
                "id_prefix" => 6,
                "id_type" => 2
            ],
            [
                "id_position" => 230,
                "name" => "การศึกษา",
                "id_prefix" => 1,
                "id_type" => 2
            ],
            [
                "id_position" => 231,
                "name" => "บรรณารักษ์",
                "id_prefix" => 3,
                "id_type" => 2
            ],
            [
                "id_position" => 232,
                "name" => "วัฒนธรรม",
                "id_prefix" => 1,
                "id_type" => 2
            ],
            [
                "id_position" => 233,
                "name" => "สันทนาการ",
                "id_prefix" => 6,
                "id_type" => 2
            ],
            [
                "id_position" => 234,
                "name" => "ภัณฑารักษ์",
                "id_prefix" => 3,
                "id_type" => 2
            ],
            [
                "id_position" => 235,
                "name" => "เทศกิจ",
                "id_prefix" => 5,
                "id_type" => 2
            ],
            [
                "id_position" => 236,
                "name" => "ป้องกันและบรรเทาสาธารณภัย",
                "id_prefix" => 6,
                "id_type" => 2
            ],
            [
                "id_position" => 237,
                "name" => "ทะเบียนและบัตร",
                "id_prefix" => 5,
                "id_type" => 2
            ],
            [
                "id_position" => 238,
                "name" => "การท่องเที่ยว",
                "id_prefix" => 7,
                "id_type" => 2
            ],
            [
                "id_position" => 239,
                "name" => "สิ่งแวดล้อม",
                "id_prefix" => 1,
                "id_type" => 2
            ],
            [
                "id_position" => 240,
                "name" => "การกีฬา",
                "id_prefix" => 7,
                "id_type" => 2
            ],
            [
                "id_position" => 242,
                "name" => "วิทยาศาสตร์",
                "id_prefix" => 6,
                "id_type" => 2
            ],
            [
                "id_position" => 243,
                "name" => "สาธารสุข",
                "id_prefix" => 6,
                "id_type" => 2
            ],
            [
                "id_position" => 244,
                "name" => "โภชนาการ",
                "id_prefix" => 6,
                "id_type" => 2
            ],
            [
                "id_position" => 301,
                "name" => "กลุ่มวิชาการวัดผลและประเมินผล",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 303,
                "name" => "กลุ่มวิชาเกษตร",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 304,
                "name" => "กลุ่มวิชาภาษาไทย",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 305,
                "name" => "กลุ่มวิชาภาษาอังกฤษ",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 306,
                "name" => "กลุ่มวิชาคณิตศาสตร์",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 307,
                "name" => "กลุ่มวิชาคหกรรม",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 308,
                "name" => "กลุ่มวิชาชีววิทยา",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 309,
                "name" => "กลุ่มวิชาฟิสิกส์",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 310,
                "name" => "กลุ่มวิชาสังคมศึกษา",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 311,
                "name" => "กลุ่มวิชาบรรณารักษ์",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 312,
                "name" => "กลุ่มวิชาศิลปะ",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 313,
                "name" => "กลุ่มวิชานาฎศิลป์",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 314,
                "name" => "กลุ่มวิชาดนตรี",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 315,
                "name" => "กลุ่มวิชาดนตรีไทย",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 316,
                "name" => "กลุ่มวิชาดนตรีสากล",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 317,
                "name" => "กลุ่มวิชาปฐมวัยฯ (การศึกษาปฐมวัย)",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 318,
                "name" => "กลุ่มวิชาประถมศึกษา",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 319,
                "name" => "กลุ่มวิชาภาษาจีน",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 320,
                "name" => "กลุ่มวิชาเทคโนโลยีการศึกษา",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 321,
                "name" => "กลุ่มวิชาการตลาด",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 322,
                "name" => "กลุ่มวิชาการโรงแรม",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 324,
                "name" => "กลุ่มวิชาแนะแนว",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 325,
                "name" => "กลุ่มวิชาการบัญชี",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 326,
                "name" => "กลุ่มวิชาการงานอาชีพและเทคโนโลยี",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 327,
                "name" => "กลุ่มวิชาปฐมวัยฯ (ศูนย์พัฒนาเด็กเล็ก)",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 328,
                "name" => "กลุ่มวิชาเคมี",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 329,
                "name" => "กลุ่มวิชาอุตสาหกรรมศิลป์",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 330,
                "name" => "กลุ่มวิชาวิทยาศาสตร์",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 331,
                "name" => "กลุ่มวิชาพลศึกษา",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 332,
                "name" => "กลุ่มวิชาสุขศึกษาและพลศึกษา",
                "id_prefix" => 8,
                "id_type" => 3
            ],
            [
                "id_position" => 333,
                "name" => "กลุ่มวิชาคอมพิวเตอร์",
                "id_prefix" => 8,
                "id_type" => 3
            ]
        ];
        DB::table('positions_dla')->insert($positions);
    }
}
