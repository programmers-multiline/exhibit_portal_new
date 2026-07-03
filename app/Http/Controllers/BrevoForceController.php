<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class BrevoForceController extends Controller
{
    /**
     * Kuhanin ang buong 1,114+ records gamit ang purong List ID parameters
     * URL: http://localhost:8000/brevo-force-sync?list_id=284
     */
    public function syncListDirect(Request $request)
    {
        // 1. Hardcoded parameters para sa Campaign 808 ng system niyo
        $campaignId   = "808";
        $campaignName = "msc_philcons_2026_ty";
        
        $listId = $request->query('list_id');
        $apiKey = trim(env('BREVO_API_KEY'));

        if (!$listId) {
            return response()->json(['success' => false, 'message' => 'Pakilagay ang ?list_id= sa URL.'], 400);
        }

        try {
            $allContacts = collect();
            $limit = 500;  // Maximum na kapasidad na kayang ibigay ng Brevo
            $offset = 0;   
            $hasMore = true;

            // 2. Ang Pagination Loop gamit ang subok na gumaganang Contacts endpoint
            while ($hasMore) {
                $cleanListId = intval(trim($listId));
                
                // Tahasang binuo ang URL string na walang variable embedding para iwas cache parsing error
                $url = "https://api.brevo.com/v3/contacts/lists/" . $cleanListId . "/contacts?limit=" . $limit . "&offset=" . $offset;

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                
                // Patayin ang local SSL checking para iwas cURL error 0 o 6 sa local network niyo
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'api-key: ' . $apiKey,
                    'accept: application/json'
                ]);

                $response = curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($statusCode !== 200 || !$response) {
                    break; 
                }

                $data = json_decode($response, true);
                $contacts = $data['contacts'] ?? [];

                if (empty($contacts)) {
                    $hasMore = false;
                    break;
                }

                foreach ($contacts as $contact) {
                    $allContacts->push($contact);
                }

                $offset += $limit; // I-advance ang pagination target range

                if (count($contacts) < $limit) {
                    $hasMore = false;
                }
            }

            $insertedCount = 0;

            // 3. I-loop ang mga nakalap na contacts at isalpak sa MySQL database table mo
            foreach ($allContacts as $contact) {
                $email = $contact['email'] ?? null;
                if (!$email) continue;

                $blacklisted = $contact['emailBlacklisted'] ?? false;
                $delivered = $blacklisted ? 'No' : 'Yes';
                $unsubscribed = $blacklisted ? 'Yes' : 'No';
                $finalStatus = $blacklisted ? 'unsubscribed' : 'delivered';

                DB::table('campaign_recipients')->updateOrInsert(
                    [
                        'campaign_id' => strval($campaignId),
                        'email'       => $email
                    ],
                    [
                        // MA-I-INSERT AT MA-U-UPDATE NA NANG TAMA ANG LAHAT NG COLUMNS MO:
                        'campaign_name' => $campaignName,
                        'from_list_id'  => $cleanListId,
                        'delivered'     => $delivered,
                        'unsubscribed'  => $unsubscribed,
                        'status'        => $finalStatus,
                        'action_at'     => now(),
                        'created_at'    => now(),
                        'updated_at'    => now()
                    ]
                );

                $insertedCount++;
            }

            return response()->json([
                'success'        => true,
                'message'        => 'Matagumpay na na-sync ang buong List ' . $cleanListId,
                'total_inserted' => $insertedCount
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
