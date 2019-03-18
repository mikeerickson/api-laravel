<?php

use App\Models\Widget;
use Illuminate\Database\Seeder;

class WidgetsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Widget::class, 50)->create();
    }
}
