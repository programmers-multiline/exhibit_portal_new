<?php

namespace App\Console\Commands;

use Illuminate\Console\Command; // Dito tinatawag ang core Command file na binalik mo sa dati
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class UpdateCompanyAddresses extends Command
{
    protected $signature = 'company:update-addresses';
    protected $description = 'Kukuha ng address sa Google Maps gamit ang company_list table';

    public function handle()
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY');

        if (!$apiKey) {
            $this->error('Pakilagay muna ang GOOGLE_MAPS_API_KEY sa iyong .env file!');
            return Command::FAILURE;
        }

        $companies = DB::table('company_list')
            ->orWhere('address', '')
            ->get();

        if ($companies->isEmpty()) {
            $this->info('Lahat ng kumpanya ay may address na.');
            return Command::SUCCESS;
        }

        $this->info('May nakitang ' . $companies->count() . ' na kumpanya...');

              foreach ($companies as $company) {
            $this->line("--------------------------------------------------");
            $this->line("Sinusuri ang kumpanya: {$company->company_name}...");

            try {
                    $response = Http::baseUrl('https://maps.googleapis.com')
                    ->get('maps/api/geocode/json', [ // <--- Ganito dapat
                        'address' => trim($company->company_name),
                        'key'     => $apiKey
                    ]);

                if (!$response->successful()) {
                    $this->error("❌ HTTP Error! Status Code: " . $response->status());
                    continue;
                }

                $data = $response->json();
                $googleStatus = $data['status'] ?? 'UNKNOWN';
                $this->line("Google API Status: [{$googleStatus}]");

                if ($googleStatus === 'OK' && !empty($data['results'])) {
                    $formattedAddress = $data['results'][0]['formatted_address'];

                    // DEBUG: I-check kung ano ang ia-update
                    $this->line("Subukang i-update ang ID {$company->id} gamit ang: {$formattedAddress}");

                    // TINANGGAL ANG whereNull PARA GUMANA SA EMPTY STRINGS
                    $affectedRows = DB::table('company_list')
                        ->where('id', $company->id)
                        ->update(['address' => $formattedAddress]);

                    // DEBUG DATABASE: I-verify kung may nabago sa DB
                    if ($affectedRows > 0) {
                        $this->info("✔ Tagumpay: Naka-update ng ($affectedRows) row/s sa database.");
                    } else {
                        $this->warn("⚠ Babala: OK ang API pero may isyu sa DB (Baka pareho lang ang address o mali ang ID).");
                    }
                } else {
                    $errorMessage = $data['error_message'] ?? 'Walang mensahe.';
                    $this->warn("❌ Bigo ang Google API: {$googleStatus} - {$errorMessage}");
                    
                    // DEBUG API: I-print ang buong JSON response para makita ang error details
                    $this->line("DEBUG API RESPONSE: " . json_encode($data));
                }

            } catch (\Exception $e) {
                $this->error("💥 System Error: " . $e->getMessage());
            }

            usleep(200000); 
        }


        return Command::SUCCESS;
    }
}
