<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Brevo\Client\Configuration;
use Brevo\Client\Api\EmailCampaignsApi;
use GuzzleHttp\Client;
use Exception;

class BrevoCampaignController extends Controller
{
    public function getCampaignStatus($campaignId)
    {
        // 1. I-setup ang configuration gamit ang backslash para masigurong galing ito sa vendor root
        $config = \Brevo\Client\Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', env('BREVO_API_KEY'));

        // 2. I-initialize ang EmailCampaignsApi direct gamit ang Guzzle Client
        $apiInstance = new EmailCampaignsApi(
            new Client(),
            $config
        );

        try {
            // 3. Kunin ang campaign data
            $campaign = $apiInstance->getEmailCampaign($campaignId);

            return response()->json([
                'success'       => true,
                'campaign_id'   => $campaign->getId(),
                'campaign_name' => $campaign->getName(),
                'subject'       => $campaign->getSubject(),
                'status'        => $campaign->getStatus(),
                'statistics'    => [
                    'sent'      => $campaign->getStatistics()->getGlobalStats()->getSent(),
                    'delivered' => $campaign->getStatistics()->getGlobalStats()->getDelivered(),
                    'opens'     => $campaign->getStatistics()->getGlobalStats()->getUniqueOpens(),
                    'clicks'    => $campaign->getStatistics()->getGlobalStats()->getUniqueClicks(),
                    'bounces'   => $campaign->getStatistics()->getGlobalStats()->getBounces(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'May error sa pagkuha ng status: ' . $e->getMessage()
            ], 500);
        }
    }
}
