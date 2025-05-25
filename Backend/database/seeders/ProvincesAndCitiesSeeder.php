<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProvincesAndCitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = File::get(base_path('city.json'));
        $data = json_decode($json);
        
        if (!is_object($data) || !isset($data->provinces) || !isset($data->cities)) {
            $this->command->error('Invalid JSON structure.');
            return;
        }
        
        $provinces = $data->provinces;
        $cities = $data->cities;
        
        try {
            Schema::disableForeignKeyConstraints();
            
            City::truncate();
            Province::truncate();
            
            $this->command->info('Importing provinces...');
            foreach ($provinces as $provinceData) {
                Province::create([
                    'id' => $provinceData->id,
                    'name' => $provinceData->name,
                    'slug' => $provinceData->slug,
                    'tel_prefix' => $provinceData->tel_prefix,
                    'latitude' => $provinceData->location->latitude,
                    'longitude' => $provinceData->location->longitude,
                ]);
            }
            
            $this->command->info('Importing cities...');
            foreach ($cities as $cityData) {
                City::create([
                    'id' => $cityData->id,
                    'name' => $cityData->name,
                    'slug' => $cityData->slug,
                    'province_id' => $cityData->province_id,
                    'latitude' => $cityData->location->latitude,
                    'longitude' => $cityData->location->longitude,
                ]);
            }
            
            Schema::enableForeignKeyConstraints();
            
            $this->command->info('Provinces and cities imported successfully.');
                } catch (\Exception $e) {
            Schema::enableForeignKeyConstraints();
            
            $this->command->error('Error importing data:' . $e->getMessage());
        }
    }
}