<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class SyncCustomerList extends Command
{
    // Ito ang itatawag mo sa terminal o scheduler
    protected $signature   = 'customer:sync';
    protected $description = 'Sync customer list from external API';

    public function handle()
    {
        $response = Http::get('http://122.49.215.36/5ms/SAP_API/customer_list_msc.php');

        if ($response->successful()) {
            $customers = $response->json();

            foreach ($customers as $customer) {
                // Direktang gagamit ng DB table para sa customer_list
                DB::table('customer_list')->updateOrInsert(
                    ['customer_code' => $customer['customer_code']],
                    [
                        'customer_name'    => $customer['customer_name'],
                        'tin_num'          => $customer['tin_num'],
                        'customer_address' => $customer['customer_address'],
                        'customer_street'  => $customer['customer_street'],
                        'contact_person'   => $customer['contact_person'],
                        'tel_num'          => $customer['tel_num'],
                        'mob_num'          => $customer['mob_num'],
                        'email'            => $customer['email'],
                        'currency'         => $customer['currency'],
                        'slpcode'          => $customer['slpcode'],
                        'psc_name'         => $customer['psc_name'],
                        'date_synced'       => now()
                    ]
                );
            }
            $this->info('Customer list sync completed successfully.');
        } else {
            $this->error('Failed to fetch data from API.');
        }
    }
}
