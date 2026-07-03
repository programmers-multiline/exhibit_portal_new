<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Exception;

class BrevoReportController extends Controller
{
    /**
     * Kunin ang lahat ng email contacts mula sa mga listahan ng isang partikular na campaign.
     *
     * @param  mixed  $campaignId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEmailsByCampaign($campaignId)
    {
        $apiKey = trim(env('BREVO_API_KEY'));
        $cleanCampaignId = intval(trim($campaignId));

        // Siguraduhing may API key na nakalagay sa .env file
        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'BREVO_API_KEY is not configured in your environment configuration.'
            ], 500);
        }

        try {
            // =========================================================================
            // HAKBANG A: KUNIN ANG CAMPAIGN DETAILS GAMIT ANG NATIVE PHP cURL
            // =========================================================================
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://brevo.com' . $cleanCampaignId);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'api-key: ' . $apiKey,
                'accept: application/json'
            ]);
            
            $campaignResponse = curl_exec($ch);
            $campaignStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($campaignStatus !== 200) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hindi mahanap ang Campaign ID ' . $cleanCampaignId . ' o may error sa Brevo API.'
                ], $campaignStatus);
            }

            $campaignData = json_decode($campaignResponse, true);
            $listIds = $campaignData['recipients']['lists'] ?? [];
            $campaignName = $campaignData['name'] ?? 'N/A';

            if (empty($listIds)) {
                return response()->json([
                    'success'       => true,
                    'campaign_name' => $campaignName,
                    'message'       => 'Walang contact lists na nakakabit sa campaign na ito.',
                    'emails'        => []
                ]);
            }

            $allEmails = collect();

            // =========================================================================
            // HAKBANG B: I-LOOP ANG BAWAT LIST ID AT GAMITAN NG PAGINATION (LIMIT/OFFSET)
            // =========================================================================
            foreach ($listIds as $listId) {
                $cleanListId = intval(trim($listId));
                
                $limit = 500; // Pinakamataas na limit na pinapayagan ng Brevo kada request
                $offset = 0;
                $hasMoreContacts = true;

                while ($hasMoreContacts) {
                    // Idagdag ang pagination limits sa dulo ng URL query string
                    $url = "https://brevo.com{$cleanListId}/contacts?limit={$limit}&offset={$offset}";

                    $chList = curl_init();
                    curl_setopt($chList, CURLOPT_URL, $url);
                    curl_setopt($chList, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chList, CURLOPT_HTTPHEADER, [
                        'api-key: ' . $apiKey,
                        'accept: application/json'
                    ]);

                    $contactsResponse = curl_exec($chList);
                    $contactsStatus = curl_getinfo($chList, CURLINFO_HTTP_CODE);
                    curl_close($chList);

                    // Kung nagkaroon ng error ang request sa contacts, ihinto ang kasalukuyang listahan
                    if ($contactsStatus !== 200) {
                        Log::error("Brevo API Error for List ID {$cleanListId}: Status {$contactsStatus}");
                        $hasMoreContacts = false;
                        break;
                    }

                    $contactsData = json_decode($contactsResponse, true);
                    $contacts = $contactsData['contacts'] ?? [];

                    // Kung blanko o walang ibinalik na contacts, tapos na ang listahan
                    if (empty($contacts)) {
                        $hasMoreContacts = false;
                        break;
                    }

                    foreach ($contacts as $contact) {
                        $blacklisted = $contact['emailBlacklisted'] ?? false;
                        $allEmails->push([
                            'campaign_id'   => $cleanCampaignId,
                            'campaign_name' => $campaignName,
                            'from_list_id'  => $cleanListId,
                            'email'         => $contact['email'] ?? 'N/A',
                            'id'            => $contact['id'] ?? 'N/A',
                            'delivered'     => $blacklisted ? 'No' : 'Yes',
                            'unsubscribed'  => $blacklisted ? 'Yes' : 'No'
                        ]);
                    }

                    // Kung mas mababa sa 500 ang nakuha natin, ibig sabihin ito na ang huling pahina
                    if (count($contacts) < $limit) {
                        $hasMoreContacts = false;
                    } else {
                        // I-adjust ang offset para sa susunod na batch (halimbawa: 0 -> 500 -> 1000)
                        $offset += $limit;
                    }
                }
            }

            // Alisin ang mga duplicate rows kung may contact na kasapi sa magkaibang listahan
            $uniqueEmails = $allEmails->unique('email')->values();

            // =========================================================================
            // HAKBANG C: DIREKTANG PAG-INSERT AT UPDATE SA DATABASE TABLE
            // =========================================================================
            foreach ($uniqueEmails as $item) {
                // Tukuyin ang standard status batay sa unsubscribe status
                $finalStatus = ($item['unsubscribed'] === 'Yes') ? 'unsubscribed' : 'delivered';

                DB::table('campaign_recipients')->updateOrInsert(
                    [
                        // UNIQUE FIELDS (Dito binebase kung mag-a-update o mag-i-insert ng bago)
                        'campaign_id' => strval($item['campaign_id']),
                        'email'       => $item['email']
                    ],
                    [
                        // MAPUPUNAN NA ANG MGA DATI AY NULL FIELDS:
                        'campaign_name' => $item['campaign_name'],
                        'from_list_id'  => $item['from_list_id'],
                        'delivered'     => $item['delivered'],
                        'unsubscribed'  => $item['unsubscribed'],
                        
                        // Iba pang kinakailangang database values
                        'status'        => $finalStatus,
                        'action_at'     => now(),
                        'created_at'    => now(),
                        'updated_at'    => now()
                    ]
                );
            }

            return response()->json([
                'success'       => true,
                'message'       => 'Matagumpay na nakuha, nai-save, at nai-update ang mga records sa database.',
                'campaign_id'   => $cleanCampaignId,
                'campaign_name' => $campaignName,
                'total_emails'  => count($uniqueEmails),
                'emails'        => $uniqueEmails
            ]);

        } catch (Exception $e) {
            // I-log ang error para madaling i-debug sakaling magkaproblema
            Log::error("Error in getEmailsByCampaign: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
