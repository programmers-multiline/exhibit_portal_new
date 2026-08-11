<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// SIGURADUHING I-IMPORT ITO:
use Illuminate\Support\Facades\Http; 

class ClientList extends Controller
{
    //
  /*       public function getExternalData()
    {
        // I-hit ang external API link
        $response = Http::get('https://spr.multi-linegroupofcompanies.com/5ms/customer_list_json.php');

        // Suriin kung matagumpay ang request (Status 200)
        if ($response->successful()) {
            $data = $response->json(); // Gagawin itong PHP Array
            return response()->json($data);
        }

        return response()->json(['error' => 'Hindi makakuha ng data'], 500);
    } */

        public function getExternalData()
        {
            $response = Http::get('http://www.spr.multi-linegroupofcompanies.com/5ms/customer_list_json.php');

            if ($response->successful()) {
                $customers = $response->json(); // Ito ay magiging PHP Array
                
                // Ipapasa natin ang array sa blade view na may pangalang 'customers'
               // return view('customers', compact('customers'));
                 return response()->json($data); 
            }

            return abort(500, 'Hindi makakuha ng data mula sa external API.');
        }


}
