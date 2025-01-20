<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            ['name' => 'Comedy'],
            ['name' => 'Romance'],
            ['name' => 'Horror'],
            ['name' => 'Adventure'],
            ['name' => 'Fiction'],
            ['name' => 'Fantasy'],
            ['name' => 'Drama'],
            ['name' => 'Heartfelt'],
            ['name' => 'Mystery'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
