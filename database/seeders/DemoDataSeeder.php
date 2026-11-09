<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $farmer = User::firstOrCreate(
            ['email' => 'jonas@ukis.lt'],
            [
                'name' => 'Jonas Ūkininkas',
                'password' => bcrypt('password'),
            ]
        );

        $buyer = User::firstOrCreate(
            ['email' => 'petras@gmail.com'],
            [
                'name' => 'Petras Pirkėjas',
                'password' => bcrypt('password'),
            ]
        );

        $data = [
            [
                'category' => [
                    'title' => 'Pieno produktai',
                    'description' => 'Švieži kaimiški pieno gaminiai.',
                ],
                'product' => [
                    'title' => 'Naminis sūris',
                    'price' => 4.50,
                    'stock' => 15,
                    'description' => 'Natūralus karvės pieno sūris, gaminamas ūkyje.',
                ],
                'review' => [
                    'rating' => 5,
                    'comment' => 'Labai skanus ir šviežias sūris.',
                ],
            ],
            [
                'category' => [
                    'title' => 'Daržovės',
                    'description' => 'Sezoninės daržovės, užaugintos Lietuvos ūkiuose.',
                ],
                'product' => [
                    'title' => 'Šviežios bulvės',
                    'price' => 1.20,
                    'stock' => 50,
                    'description' => 'Kaimiškos bulvės, tinkamos virti ir kepti.',
                ],
                'review' => [
                    'rating' => 4,
                    'comment' => 'Bulvės skanios ir gerai laikosi.',
                ],
            ],
            [
                'category' => [
                    'title' => 'Vaisiai',
                    'description' => 'Natūraliai sunokę vaisiai iš vietinių sodų.',
                ],
                'product' => [
                    'title' => 'Sodo obuoliai',
                    'price' => 2.00,
                    'stock' => 30,
                    'description' => 'Traškūs ir saldūs lietuviški obuoliai.',
                ],
                'review' => [
                    'rating' => 5,
                    'comment' => 'Obuoliai labai švieži ir saldūs.',
                ],
            ],
            [
                'category' => [
                    'title' => 'Medus',
                    'description' => 'Natūralus medus iš Lietuvos bitynų.',
                ],
                'product' => [
                    'title' => 'Liepų žiedų medus',
                    'price' => 8.50,
                    'stock' => 12,
                    'description' => 'Kvapnus ir natūralus liepų žiedų medus.',
                ],
                'review' => [
                    'rating' => 5,
                    'comment' => 'Puikus skonis ir malonus aromatas.',
                ],
            ],
            [
                'category' => [
                    'title' => 'Kiaušiniai',
                    'description' => 'Švieži laisvai laikomų vištų kiaušiniai.',
                ],
                'product' => [
                    'title' => 'Kaimiški kiaušiniai',
                    'price' => 3.80,
                    'stock' => 25,
                    'description' => 'Švieži kiaušiniai iš laisvai laikomų vištų.',
                ],
                'review' => [
                    'rating' => 4,
                    'comment' => 'Kiaušiniai švieži, trynys ryškus.',
                ],
            ],
        ];

        foreach ($data as $item) {
            $category = Category::create($item['category']);

            $product = Product::create([
                ...$item['product'],
                'category_id' => $category->id,
                'user_id' => $farmer->id,
            ]);

            Review::create([
                ...$item['review'],
                'product_id' => $product->id,
                'user_id' => $buyer->id,
            ]);
        }
    }
}
