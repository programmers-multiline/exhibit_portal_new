<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;

class BrevoReportController extends Controller
{
    /**
     * 1. ILISTA ANG MGA CAMPAIGN AT KANILANG RECIPIENT LISTS
     * URL: https://exhibit_portal.app/brevo-v5/campaigns
     */
    public function campaigns()
    {
        $apiKey = trim(env('BREVO_API_KEY'));

        // Native PHP cURL setup para direktang tumawag nang walang framework abstraction layer
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.brevo.com/v3/emailCampaigns');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'api-key: ' . $apiKey,
            'accept: application/json'
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode === 200) {
            $data = json_decode($response, true);
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

        return response()->json([
            'success' => false,
            'message' => 'API Error mula sa Brevo.',
            'status_code' => $statusCode,
            'raw_response' => json_decode($response, true)
        ], $statusCode);
    }

    /**
     * 2. AUTOMATED CAMPAIGN RECIPIENTS REPORT
     * URL: https://exhibit_portal.app/brevo-v5/campaign-report/808
     */
    public function getEmailsByCampaign($campaignId)
    {
        $apiKey = trim(env('BREVO_API_KEY'));
        $cleanCampaignId = intval(trim($campaignId));

        try {
            // Hakbang A: Kunin ang Campaign Details gamit ang Native PHP cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.brevo.com/v3/emailCampaigns/' . $cleanCampaignId);
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
                    'message' => 'Hindi mahanap ang Campaign ID ' . $cleanCampaignId
                ], $campaignStatus);
            }

            $campaignData = json_decode($campaignResponse, true);
            $listIds = $campaignData['recipients']['lists'] ?? [];

            if (empty($listIds)) {
                return response()->json([
                    'success' => true,
                    'campaign_name' => $campaignData['name'] ?? 'N/A',
                    'message' => 'Walang contact lists na nakakabit sa campaign na ito.',
                    'emails' => []
                ]);
            }

            $allEmails = collect();

            // Hakbang B: I-loop ang bawat List ID gamit ang Native PHP cURL para kunin ang mga emails
            foreach ($listIds as $listId) {
                $cleanListId = intval(trim($listId));

                $chList = curl_init();
                curl_setopt($chList, CURLOPT_URL, 'https://api.brevo.com/v3/contacts/lists/' . $cleanListId . '/contacts');
                curl_setopt($chList, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chList, CURLOPT_HTTPHEADER, [
                    'api-key: ' . $apiKey,
                    'accept: application/json'
                ]);

                $contactsResponse = curl_exec($chList);
                curl_close($chList);

                $contactsData = json_decode($contactsResponse, true);
                $contacts = $contactsData['contacts'] ?? [];

                foreach ($contacts as $contact) {
                    $blacklisted = $contact['emailBlacklisted'] ?? false;
                    $allEmails->push([
                        'campaign_id'   => $cleanCampaignId,
                        'campaign_name' => $campaignData['name'] ?? 'N/A',
                        'from_list_id'  => $cleanListId,
                        'email'         => $contact['email'] ?? 'N/A',
                        'id'            => $contact['id'] ?? 'N/A',
                        'delivered'     => $blacklisted ? 'No (Bounced/Blocked)' : 'Yes',
                        'unsubscribed'  => $blacklisted ? 'Yes' : 'No'
                    ]);
                }
            }

            // Alisin ang mga duplicate rows kung may contact na kasapi sa magkaibang grupo
            $uniqueEmails = $allEmails->unique('email')->values();

            return response()->json([
                'success'       => true,
                'campaign_id'   => $cleanCampaignId,
                'campaign_name' => $campaignData['name'] ?? 'N/A',
                'total_emails'  => count($uniqueEmails),
                'emails'        => $uniqueEmails
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
