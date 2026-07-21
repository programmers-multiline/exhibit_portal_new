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
            ->whereNull('address') 
            ->orWhere('address', '')
            ->where('id',758)
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
                // Tinatawagan ang totoong Google API Maps server
                /* $response = Http::get('https://googleapis.com', [
                    'address' => trim($company->company_name),
                    'key'     => $apiKey
                ]); */

                // PALITAN ANG LUMANG HTTP::GET NG GANITONG BULK FORMAT:
$response = Http::baseUrl('https://maps.googleapis.com')
    ->get('/maps/api/geocode/json', [
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

                    DB::table('company_list')
                        ->where('id', $company->id) 
                        ->whereNull('address')
                        ->update(['address' => $formattedAddress]);

                    $this->info("✔ Tagumpay: {$formattedAddress}");
                } else {
                    $errorMessage = $data['error_message'] ?? 'Walang mensahe.';
                    $this->warn("❌ Bigo: {$googleStatus} - {$errorMessage}");
                }

            } catch (\Exception $e) {
                $this->error("💥 System Error: " . $e->getMessage());
            }

            usleep(200000); 
        }

        return Command::SUCCESS;
    }
}
