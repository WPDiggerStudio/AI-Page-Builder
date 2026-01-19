<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * TestSeeder Seeder
 *
 * Seeds the database with initial data.
 *
 * @package Database\Seeders
 */
class TestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This method is called when the seeder is executed.
     * Use it to insert initial data into the database.
     *
     * @return void
     */
    public function run(): void
    {
        // Example: Insert sample data
        // DB::table('your_table')->insert([
        //     'name' => 'Sample Name',
        //     'email' => 'sample@example.com',
        //     'is_active' => true,
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);

        // Example: Create models using Eloquent
        // \App\Models\YourModel::create([
        //     'name' => 'Sample Name',
        //     'email' => 'sample@example.com',
        // ]);

        // Example: Call other seeders
        // $this->call([
        //     OtherSeeder::class,
        // ]);
    }

    /**
     * Truncate a table before seeding.
     *
     * @param string $table Table name.
     * @return void
     */
    protected function truncate(string $table): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table($table)->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
