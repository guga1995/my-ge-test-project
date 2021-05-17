<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\ProductGroupItem;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->faker = FakerFactory::Create();
        
        $users = User::factory()
            ->count(100)
            ->create();

        $users->each(function ($user) {
            $products = Product::factory()->count(10)->create([
                'user_id' => $user->id
            ]);
            $productGroups = ProductGroup::factory()->count(3)->create([
                'user_id' => $user->id
            ]);

            $products->each(function ($product) use ($productGroups, $user) {
                ProductGroupItem::factory()->create([
                    'product_id' => $product,
                    'product_group_id' => $this->faker->randomElement($productGroups->map->id->toArray())
                ]);
                Cart::factory()->create([
                    'user_id' => $user->id,
                    'product_id' => $product->id
                ]);
            });

        });
    }
}
