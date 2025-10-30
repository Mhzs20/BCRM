<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Template;

class BirthdayTemplatesSeeder extends Seeder
{
    public function run()
    {
        Template::create([
            'name' => 'تبریک تولد ساده',
            'content' => 'تولدت مبارک! آرزوی بهترین‌ها برای شما.',
            'type' => 'birthday',
            'is_global' => true,
        ]);
        Template::create([
            'name' => 'تبریک تولد با تخفیف',
            'content' => 'تولدت مبارک! با کد تخفیف ویژه امروز به ما سر بزن.',
            'type' => 'birthday',
            'is_global' => true,
        ]);
        Template::create([
            'name' => 'تبریک تولد اختصاصی',
            'content' => 'تولدت مبارک عزیزم! منتظر دیدارت هستیم.',
            'type' => 'birthday',
            'is_global' => true,
        ]);
    }
}
