<?php

namespace Amplify\System\Seeders;

use Amplify\System\Marketing\Models\MerchandisingZone;
use Illuminate\Database\Seeder;

class MerchandisingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $merchandising_zones = [
            ['name' => 'Best Sellers', 'description' => 'Best Selling products as defined by business...', 'easyask_key' => 'best-sellers', 'created_at' => '2022-03-30 14:33:58', 'updated_at' => '2022-03-30 14:33:58'],
            ['name' => 'Featured Products', 'description' => 'Featured Products', 'easyask_key' => 'featured-products', 'created_at' => '2022-04-11 12:47:12', 'updated_at' => '2022-04-11 12:47:29'],
            ['name' => 'New Arrivals', 'description' => 'New Arrivals', 'easyask_key' => 'new-arrivals', 'created_at' => '2022-04-11 12:47:46', 'updated_at' => '2022-04-11 12:47:46'],
            ['name' => 'Managers Choice', 'description' => 'Managers Choice', 'easyask_key' => 'managers-choice', 'created_at' => '2022-04-11 12:48:22', 'updated_at' => '2022-04-11 12:48:22'],
        ];

        MerchandisingZone::insert($merchandising_zones);
    }
}
