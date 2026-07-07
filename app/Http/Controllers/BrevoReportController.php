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
     * Brevo API Base URL
     */
    private string $baseUrl = 'https://api.brevo.com/v3';

    /**
     * Kunin at i-display ang mga email na nag-Open at nag-Click mula sa Events Log.
     * 
     * URL: http://127.0.0{campaignId}/display
     */
      /**
     * Kunin at i-display ang mga email na nag-Open at nag-Click mula sa Events Log.
     * 
     * URL: http://127.0.0{campaignId}/display
     */
       /**
     * Kunin at i-display ang mga email na nag-Open at nag-Click sa browser.
     * 
     * URL: http://127.0.0{campaignId}/display
     */
       /**
     * Kunin at i-display ang mga email na nag-Open at nag-Click na may Pagination.
     * 
     * URL format: http://127.0.0{campaignId}/display?page=1
     */
       /**
     * Kunin at i-display ang mga email na nag-Open at nag-Click na may Pagination (Solution B).
     * 
     * URL format: https://exhibit_portal.app/brevo/campaign/{campaignId}/display?page=1
     */
    public function showCampaignEngagement(Request $request, $campaignId)
    {
        $apiKey = trim(env('BREVO_API_KEY'));
        $cleanCampaignId = intval(trim($campaignId));

        // Kunin ang kasalukuyang page mula sa URL query string (Default ay Page 1)
        $currentPage = intval($request->query('page', 1));
        if ($currentPage < 1) $currentPage = 1;

        // Bilang ng contacts na ipoproseso kada request (Huwag lakihan para hindi mag-timeout)
        $perPage = 50; 

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'BREVO_API_KEY is not configured in your environment.'
            ], 500);
        }

        try {
            // HAKBANG 1: Kunin ang lahat ng contacts mula sa iyong umiiral na listahan ng campaign
            $contactsResponse = $this->getEmailsByCampaign($cleanCampaignId);
            $contactsData = $contactsResponse->getData(true);

            if (!$contactsData['success'] || empty($contactsData['data'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Walang contacts na nakuha para sa campaign na ito.'
                ], 404);
            }

            $allCampaignContacts = $contactsData['data'];
            $totalContacts = count($allCampaignContacts);

            // HAKBANG 2: Pag-kalkula para sa Pagination Offset
            $offset = ($currentPage - 1) * $perPage;
            $totalPages = ceil($totalContacts / $perPage);

            // Kumuha lamang ng piraso (slice) ng contacts para sa kasalukuyang page
            $paginatedContacts = array_slice($allCampaignContacts, $offset, $perPage);

            $openedEmails = [];
            $clickedEmails = [];

            // HAKBANG 3: I-loop ang contacts para sa page na ito (SOLUTION B)
            foreach ($paginatedContacts as $contact) {
                $email = $contact['email'] ?? null;
                if (!$email || $email === 'N/A') continue;

                $statsUrl = "{$this->baseUrl}/contacts/" . urlencode($email) . "/campaignStats";
                $response = $this->makeRequest($statsUrl, $apiKey);

                if ($response['success']) {
                    $campaignsStats = $response['data']['campaignStats'] ?? [];
                    
                    foreach ($campaignsStats as $stat) {
                        if (intval($stat['campaignId'] ?? 0) === $cleanCampaignId) {
                            
                            $openedCount = $stat['opened'] ?? 0;
                            if ($openedCount > 0) {
                                $openedEmails[] = [
                                    'email' => $email,
                                    'status' => 'Opened',
                                    'count' => $openedCount
                                ];
                            }

                            $clickedCount = $stat['clicked'] ?? 0;
                            if ($clickedCount > 0) {
                                $clickedEmails[] = [
                                    'email' => $email,
                                    'status' => 'Clicked',
                                    'count' => $clickedCount
                                ];
                            }
                        }
                    }
                }
                
                usleep(50000); // 0.05 seconds upang maiwasan ang rate limits
            }

            // KONTROL: Kung walang nahanap na engagement sa page na ito, magbigay ng tip sa user
            $message = (empty($openedEmails) && empty($clickedEmails)) 
                ? "Walang nag-open o nag-click sa batch na ito. Subukan ang susunod na pahina (?page=" . ($currentPage + 1) . ")." 
                : "May nahanap na engagement sa pahinang ito.";

            return response()->json([
                'success'       => true,
                'campaign_id'   => $cleanCampaignId,
                'message'       => $message,
                'pagination'    => [
                    'current_page' => $currentPage,
                    'per_page'     => $perPage,
                    'total_pages'  => $totalPages,
                    'total_contacts_in_campaign' => $totalContacts,
                    'next_page_url' => $currentPage < $totalPages ? url("/brevo/campaign/{$cleanCampaignId}/display?page=" . ($currentPage + 1)) : null,
                    'prev_page_url' => $currentPage > 1 ? url("/brevo/campaign/{$cleanCampaignId}/display?page=" . ($currentPage - 1)) : null,
                ],
                'results_for_this_page' => [
                    'opened'  => $openedEmails,
                    'clicked' => $clickedEmails
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error("Brevo Pagination Loop Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'May naganap na error habang pinoproseso ang pahina.',
                'error_debug' => $e->getMessage()
            ], 500);
        }
    }





    /**
     * Kunin ang lahat ng email contacts mula sa mga listahan ng isang partikular na campaign.
     */
   /*  public function getEmailsByCampaign($campaignId)
    {
        $apiKey = trim(env('BREVO_API_KEY'));
        $cleanCampaignId = intval(trim($campaignId));

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'BREVO_API_KEY is not configured in your environment configuration.'
            ], 500);
        }

        try {
            $campaignUrl = "{$this->baseUrl}/emailCampaigns/{$cleanCampaignId}";
            $campaignResponse = $this->makeRequest($campaignUrl, $apiKey);

            if (!$campaignResponse['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $campaignResponse['error']
                ], $campaignResponse['status'] ?? 502);
            }

            $campaignData = $campaignResponse['data'];
            $listIds      = $campaignData['recipients']['lists'] ?? [];
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

            foreach ($listIds as $listId) {
                $cleanListId     = intval($listId);
                $limit           = 500;
                $offset          = 0;
                $hasMoreContacts = true;

                while ($hasMoreContacts) {
                    $url = "{$this->baseUrl}/contacts/lists/{$cleanListId}/contacts?limit={$limit}&offset={$offset}";
                    $contactsResponse = $this->makeRequest($url, $apiKey);

                    if (!$contactsResponse['success']) {
                        Log::error("Brevo API Error for List ID {$cleanListId}: " . $contactsResponse['error']);
                        $hasMoreContacts = false;
                        break;
                    }

                    $contacts = $contactsResponse['data']['contacts'] ?? [];

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

                    if (count($contacts) < $limit) {
                        $hasMoreContacts = false;
                    } else {
                        $offset += $limit;
                    }
                }
            }

            $uniqueEmails = $allEmails->unique('email')->values();

            return response()->json([
                'success' => true,
                'data' => $uniqueEmails
            ]);

        } catch (Exception $e) {
            Log::error("Brevo Get Emails Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    } */

    /**
     * Isang pangkalahatang Helper function para sa cURL GET requests.
     */
    /* private function makeRequest($url, $apiKey)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'api-key: ' . $apiKey,
            'accept: application/json'
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status >= 200 && $status < 300) {
            return ['success' => true, 'data' => json_decode($response, true)];
        }

        return ['success' => false, 'status' => $status, 'error' => $response];
    } */

        public function getEmailsByCampaign($campaignId)
{
    $apiKey = trim(env('BREVO_API_KEY'));
    $cleanCampaignId = intval(trim($campaignId));

    if (empty($apiKey)) {
        return response()->json([
            'success' => false,
            'message' => 'BREVO_API_KEY is not configured in your environment configuration.'
        ], 500);
    }

    try {
        $campaignUrl = "{$this->baseUrl}/emailCampaigns/{$cleanCampaignId}";
        $campaignResponse = $this->makeRequest($campaignUrl, $apiKey);

        if (!$campaignResponse['success']) {
            return response()->json([
                'success' => false,
                'message' => $campaignResponse['error']
            ], $campaignResponse['status'] ?? 502);
        }

        $campaignData = $campaignResponse['data'];
        $listIds      = $campaignData['recipients']['lists'] ?? [];
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

        foreach ($listIds as $listId) {
            $cleanListId     = intval($listId);
            $limit           = 500;
            $offset          = 0;
            $hasMoreContacts = true;

            while ($hasMoreContacts) {
                $url = "{$this->baseUrl}/contacts/lists/{$cleanListId}/contacts?limit={$limit}&offset={$offset}";
                $contactsResponse = $this->makeRequest($url, $apiKey);

                if (!$contactsResponse['success']) {
                    Log::error("Brevo API Error for List ID {$cleanListId}: " . $contactsResponse['error']);
                    $hasMoreContacts = false;
                    break;
                }

                $contacts = $contactsResponse['data']['contacts'] ?? [];

                if (empty($contacts)) {
                    $hasMoreContacts = false;
                    break;
                }

                foreach ($contacts as $contact) {
                    $email = $contact['email'] ?? null;
                    
                    // Laktawan kung walang valid email na nakuha
                    if (!$email || $email === 'N/A') {
                        continue;
                    }

                    $blacklisted = $contact['emailBlacklisted'] ?? false;
                    $deliveredVal = $blacklisted ? 'No' : 'Yes';
                    $unsubscribedVal = $blacklisted ? 'Yes' : 'No';

                    // 1. I-save o I-insert sa database (campaign_recipients table)
                    // Gumamit ng check upang maiwasan ang duplicate entry error sa database
                    $exists = DB::table('campaign_recipients')
                        ->where('campaign_id', $cleanCampaignId)
                        ->where('email', $email)
                        ->exists();

                    if (!$exists) {
                        DB::table('campaign_recipients')->insert([
                            'campaign_id'   => $cleanCampaignId,
                            'campaign_name' => $campaignName,
                            'from_list_id'  => $cleanListId,
                            'delivered'     => $deliveredVal,
                            'unsubscribed'  => $unsubscribedVal,
                            'email'         => $email,
                            'status'        => 'sent', // Default value base sa iyong db schema
                            'created_at'    => now(),
                            'updated_at'    => now()
                        ]);
                    }

                    // 2. I-push sa koleksyon para sa API return format mo
                    $allEmails->push([
                        'campaign_id'   => $cleanCampaignId,
                        'campaign_name' => $campaignName,
                        'from_list_id'  => $cleanListId,
                        'email'         => $email,
                        'id'            => $contact['id'] ?? 'N/A',
                        'delivered'     => $deliveredVal,
                        'unsubscribed'  => $unsubscribedVal
                    ]);
                }

                if (count($contacts) < $limit) {
                    $hasMoreContacts = false;
                } else {
                    $offset += $limit;
                }
            }
        }

        $uniqueEmails = $allEmails->unique('email')->values();

        return response()->json([
            'success' => true,
            'data' => $uniqueEmails
        ]);

    } catch (Exception $e) {
        Log::error("Brevo Get Emails Error: " . $e->getMessage());
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

/**
 * Isang pangkalahatang Helper function para sa cURL GET requests.
 */
private function makeRequest($url, $apiKey)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $apiKey,
        'accept: application/json'
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status >= 200 && $status < 300) {
        return ['success' => true, 'data' => json_decode($response, true)];
    }

    return ['success' => false, 'status' => $status, 'error' => $response];
}


/* public function getEmailEngagement($email)
{
    $apiKey = trim(env('BREVO_API_KEY'));

    if (empty($apiKey)) {
        return response()->json([
            'success' => false,
            'message' => 'BREVO_API_KEY is not configured.'
        ], 500);
    }

    // I-encode ang email sakaling may kakaibang characters
    $encodedEmail = urlencode(trim($email));

    try {
        // Brevo API endpoint para sa campaign stats ng isang contact
        $url = "{$this->baseUrl}/contacts/{$encodedEmail}/campaignStats";
        $response = $this->makeRequest($url, $apiKey);

        if (!$response['success']) {
            return response()->json([
                'success' => false,
                'message' => $response['error']
            ], $response['status'] ?? 502);
        }

        // Kunin ang statistics data mula sa response
        $statsData = $response['data']['campaignStats'] ?? [];

        return response()->json([
            'success' => true,
            'email'   => $email,
            'data'    => $statsData
        ]);

    } catch (Exception $e) {
        Log::error("Brevo Engagement Error para sa {$email}: " . $e->getMessage());
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
} */
public function updateAllEmailsEngagement()
{
    $apiKey = trim(env('BREVO_API_KEY'));

    if (empty($apiKey)) {
        return response()->json([
            'success' => false,
            'message' => 'BREVO_API_KEY is not configured.'
        ], 500);
    }

    $updatedCount = 0;

    try {
        // Kunin ang mga email mula sa iyong database table gamit ang chunk (100 kada batch)
        DB::table('campaign_recipients')
            ->select('email', 'campaign_id')
            ->orderBy('id')
            ->chunk(100, function ($recipients) use ($apiKey, &$updatedCount) {
                
                foreach ($recipients as $recipient) {
                    $email = $recipient->email;
                    $campaignId = $recipient->campaign_id;

                    if (empty($email) || $email === 'N/A') {
                        continue;
                    }

                    // 1. Tawagin ang Brevo API para sa bawat partikular na email
                    $encodedEmail = urlencode(trim($email));
                    $url = "{$this->baseUrl}/contacts/{$encodedEmail}/campaignStats";
                    $response = $this->makeRequest($url, $apiKey);

                    if (!$response['success']) {
                        Log::warning("Hindi nakuha ang stats para sa {$email}: " . $response['error']);
                        continue; // Laktawan at ituloy sa susunod na email kung mag-error ang API
                    }

                    $statsList = $response['data']['campaignStats'] ?? [];

                    // 2. Hanapin ang statistics na tugma sa campaign_id ng email na ito
                    foreach ($statsList as $stats) {
                        if (isset($stats['campaignId']) && $stats['campaignId'] == $campaignId) {
                            
                            $openedCount = $stats['opened'] ?? 0;
                            $clicksCount = $stats['clicks'] ?? 0;

                            // Alamin ang status base sa engagement (Maaari mong baguhin ang lohika rito)
                            $newStatus = 'sent';
                            if ($clicksCount > 0) {
                                $newStatus = 'clicked';
                            } elseif ($openedCount > 0) {
                                $newStatus = 'opened';
                            }

                            // 3. I-update ang status at metrics sa iyong local database table
                            DB::table('campaign_recipients')
                                ->where('campaign_id', $campaignId)
                                ->where('email', $email)
                                ->update([
                                    'status'     => $newStatus,
                                    'updated_at' => now(),
                                    // Tandaan: Kung may 'opened' o 'clicked' columns ka sa table, maaari mo ring i-save dito.
                                ]);

                            $updatedCount++;
                            break; // Nahanap na ang tamang campaign, lumabas na sa panloob na loop
                        }
                    }

                    // Proteksyon: Mag-pause ng 100 milliseconds (0.1 segundo) sa bawat request
                    // para hindi ma-block ang iyong API key dahil sa Rate Limiting ng Brevo
                    usleep(100000); 
                }
            });

        return response()->json([
            'success' => true,
            'message' => "Matagumpay na natapos ang pag-loop. Na-update ang {$updatedCount} na recipients.",
        ]);

    } catch (Exception $e) {
        Log::error("Bulk Engagement Loop Error: " . $e->getMessage());
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

}
