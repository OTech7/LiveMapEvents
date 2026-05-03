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
            'Startups',
            'Entrepreneurship',
            'Marketing',
            'Finance',
            'Investing',
            'Real Estate',
            'Sports',
            'Football',
            'Basketball',
            'Running',
            'Cycling',
            'Yoga',
            'Fitness',
            'Music',
            'Podcasts',
            'Concerts',
            'DJ',
            'Dance',
            'Film',
            'Photography',
            'Art',
            'Design',
            'Fashion',
            'Gaming',
            'Esports',
            'Board Games',
            'Travel',
            'Road Trips',
            'Camping',
            'Hiking',
            'Adventure',
            'Food',
            'Coffee',
            'Cooking',
            'Baking',
            'Health',
            'Wellness',
            'Mental Health',
            'Education',
            'Science',
            'Programming',
            'AI',
            'Books',
            'Reading',
            'Writing',
            'Languages',
            'Networking',
            'Volunteering',
            'Community',
            'Culture',
            'History',
            'Environment',
            'Pets',
        ];

        foreach ($interests as $name) {
            Interest::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
