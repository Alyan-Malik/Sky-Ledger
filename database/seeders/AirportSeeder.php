<?php

namespace Database\Seeders;

use App\Models\Airport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // <-- ADD THIS LINE

class AirportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->command->info('Importing airports from CSV...');
        
        // Path to your CSV file
        $csvPath = storage_path('app/seed-data/airports.csv');
        
        if (!file_exists($csvPath)) {
            $this->command->warn('CSV file not found at: ' . $csvPath . '. Seeding sample data instead.');
            $this->seedSampleData();
            return;
        }

        // Disable query log to prevent PHP out-of-memory crashes on large files
        DB::connection()->disableQueryLog();
        
        // Truncate the table before seeding to prevent key conflicts with old attempts
        Schema::disableForeignKeyConstraints();
        Airport::truncate();
        Schema::enableForeignKeyConstraints();
        
        $file = fopen($csvPath, 'r');
        $headers = fgetcsv($file);
        
        // Clean up headers string (removes accidental hidden BOM bytes or spaces)
        $headers = array_map(function($header) {
            return trim(strtolower($header));
        }, $headers);

        $batchSize = 500;
        $airports = [];
        $count = 0;
        
        while (($row = fgetcsv($file)) !== false) {
            // Skip empty rows
            if (empty($row) || current($row) === null) {
                continue;
            }

            // Ensure row element counts match header schema counts to prevent offset crashes
            if (count($headers) !== count($row)) {
                continue;
            }

            $data = array_combine($headers, $row);
            
            // Map the correct fallbacks for open-source DataHub/OurAirports fields
            $iata = trim($data['iata_code'] ?? $data['iata'] ?? '');
            $icao = trim($data['icao_code'] ?? $data['icao'] ?? '');
            $name = trim($data['name'] ?? $data['airport_name'] ?? $data['airport name'] ?? '');
            $city = trim($data['municipality'] ?? $data['city'] ?? 'Unknown');
            $country = trim($data['iso_country'] ?? $data['country'] ?? '');

            // RULE 1: If IATA is empty, missing, or unavailable, skip it. 
            // Users cannot search "From" / "To" flights for closed airfields or helipads.
            if (empty($iata) || $iata === '-73.7781' || $iata === '\N' || strlen($iata) !== 3) {
                continue;
            }

            // Parse coordinates cleanly if they are grouped inside a single column string (e.g. "40.64, -73.77")
            $lat = null;
            $lon = null;
            if (isset($data['coordinates']) && !empty($data['coordinates'])) {
                $coords = explode(',', $data['coordinates']);
                $lat = isset($coords[0]) ? (float)trim($coords[0]) : null;
                $lon = isset($coords[1]) ? (float)trim($coords[1]) : null;
            } else {
                $lat = isset($data['latitude']) ? (float)$data['latitude'] : null;
                $lon = isset($data['longitude']) ? (float)$data['longitude'] : null;
            }
            
            $airports[$iata] = [
                'iata_code'    => $iata,
                'icao_code'    => !empty($icao) && $icao !== '\N' ? $icao : null,
                'airport_name' => $name,
                'city'         => $city,
                'country'      => $country,
                'latitude'     => $lat,
                'longitude'    => $lon,
                'timezone'     => $data['timezone'] ?? null,
                'active'       => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
            
            $count++;
            
            if (count($airports) >= $batchSize) {
                Airport::insert(array_values($airports));
                $airports = [];
                $this->command->info("Processed {$count} lines...");
            }
        }
        
        // Insert remaining records
        if (!empty($airports)) {
            Airport::insert(array_values($airports));
        }
        
        fclose($file);
        
        $insertedCount = Airport::count();
        $this->command->info("Total active commercial airports imported successfully: {$insertedCount}");
    }
    
    /**
     * Seed sample data for development
     */
    private function seedSampleData(): void
    {
        $sampleAirports = [
            ['JFK', 'KJFK', 'John F Kennedy International Airport', 'New York', 'United States', 40.6413, -73.7781, 'America/New_York'],
            ['LAX', 'KLAX', 'Los Angeles International Airport', 'Los Angeles', 'United States', 33.9416, -118.4085, 'America/Los_Angeles'],
            ['LHR', 'EGLL', 'London Heathrow Airport', 'London', 'United Kingdom', 51.4700, -0.4543, 'Europe/London'],
            ['DXB', 'OMDB', 'Dubai International Airport', 'Dubai', 'United Arab Emirates', 25.2532, 55.3657, 'Asia/Dubai'],
            ['SIN', 'WSSS', 'Singapore Changi Airport', 'Singapore', 'Singapore', 1.3644, 103.9915, 'Asia/Singapore'],
            ['HND', 'RJTT', 'Tokyo Haneda Airport', 'Tokyo', 'Japan', 35.5494, 139.7798, 'Asia/Tokyo'],
            ['CDG', 'LFPG', 'Charles de Gaulle Airport', 'Paris', 'France', 49.0097, 2.5479, 'Europe/Paris'],
            ['LHE', 'OPLA', 'Allama Iqbal International Airport', 'Lahore', 'Pakistan', 31.5216, 74.4036, 'Asia/Karachi'],
            ['KHI', 'OPKC', 'Jinnah International Airport', 'Karachi', 'Pakistan', 24.9060, 67.1608, 'Asia/Karachi'],
            ['ISB', 'OPIS', 'Islamabad International Airport', 'Islamabad', 'Pakistan', 33.5491, 72.8257, 'Asia/Karachi'],
        ];
        
        foreach ($sampleAirports as $airport) {
            Airport::updateOrCreate(
                ['iata_code' => $airport[0]],
                [
                    'icao_code'    => $airport[1],
                    'airport_name' => $airport[2],
                    'city'         => $airport[3],
                    'country'      => $airport[4],
                    'latitude'     => $airport[5],
                    'longitude'    => $airport[6],
                    'timezone'     => $airport[7],
                    'active'       => true,
                ]
            );
        }
    }
}
