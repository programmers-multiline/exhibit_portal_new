<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Exception;

class BrevoReportController extends Controller
{
    /**
     * 1. ILISTA ANG MGA CAMPAIGN AT KANILANG DETALYE
     * URL: https://exhibit_portal.app/brevo-v4/campaigns
     */
    public function campaigns()
    {
        $apiKey = trim(env('BREVO_API_KEY'));
        try {
            $response = Http::withHeaders([
                'api-key' => $apiKey,
                'accept'  => 'application/json',
            ])->get("https://brevo.com", [
                'type'   => 'classic',
                'limit'  => 50,
                'offset' => 0,          
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'campaigns' => collect($data['campaigns'] ?? [])->map(function ($c) {
                        return [
                            'id' => $c['id'] ?? null,
                            'name' => $c['name'] ?? 'N/A',
                            'status' => $c['status'] ?? 'N/A',
                            'recipients_lists' => $c['recipients']['lists'] ?? []
                        ];
                    })
                ]);
            }
            return response()->json(['success' => false, 'message' => 'API Error'], $response->status());
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 2. KUNIN ANG MGA EMAILS MULA SA ISANG CONTACT LIST ID
     * URL: https://exhibit_portal.app/brevo-v4/list/284
     */
    public function emailsFromList($listId)
    {
        $apiKey = trim(env('BREVO_API_KEY'));
        try {
            // Gumamit ng secure text processing para hindi idikit ang variable sa dulo ng domain name
            $cleanListId = strval(trim($listId));
            $endpointUrl = 'https://api.brevo.com/v3/contacts/lists/' . $cleanListId . '/contacts';

            $response = Http::withHeaders([
                'api-key' => $apiKey,
                'accept'  => 'application/json',
            ])->get($endpointUrl, [
                'limit' => 100,
                'offset' => 0
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $contacts = $data['contacts'] ?? [];

                $emailList = collect($contacts)->map(function ($contact) {
                    $blacklisted = $contact['emailBlacklisted'] ?? false;
                    return [
                        'email'        => $contact['email'] ?? 'N/A',
                        'id'           => $contact['id'] ?? 'N/A',
                        'delivered'    => $blacklisted ? 'No (Bounced/Blocked)' : 'Yes',
                        'unsubscribed' => $blacklisted ? 'Yes' : 'No'
                    ];
                });

                return response()->json([
                    'success'      => true,
                    'list_id'      => $listId,
                    'total_emails' => count($emailList),
                    'emails'       => $emailList
                ]);
            }

            return response()->json([
                'success' => false, 
                'message' => 'Maling List ID o walang laman ang listahang ito.',
                'brevo_error' => $response->json()
            ], $response->status());

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
