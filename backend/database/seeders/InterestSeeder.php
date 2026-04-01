<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Interest;
use Illuminate\Support\Str;

class InterestSeeder extends Seeder
{
    public function run(): void
    {
        $interests = [
            'Business',
            'Technology',
            'Sports',
            'Music',
            'Film',
            'Gaming',
            'Travel',
            'Food',
            'Health',
            'Education',
        ];

        foreach ($interests as $name) {
            Interest::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}