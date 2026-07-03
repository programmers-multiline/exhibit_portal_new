<?php
// =========================================================================
// STANDALONE NATIVE PHP RECIPIENTS SYNCER (Framework and Router Free)
// =========================================================================

// 1. IPASOK ANG CONFIGURATION NG IYONG LOCAL MYSQL DATABASE DITO
$db_host = '127.0.0.1';
$db_user = 'root';        // Palitan kung may iba kang username sa local
$db_pass = '';            // Ilagay ang password ng mysql mo kung mayroon
$db_name = 'exhibit_portal'; // PALITAN NG PANGALAN NG DATABASE MO

// 2. ANG IYONG BREVO API KEY AT TARGET CAMPAIGN DETALYE
$apiKey       = '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'; // PALITAN NG API KEY MO
$campaignId   = 808;
$campaignName = 'msc_philcons_2026_ty';
$listIds      = [284, 221, 346, 345]; // Ang apat na nakuha nating active List IDs

try {
    // Kumonekta sa Database gamit ang PDO native driver
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $totalInserted = 0;
    echo "<h2>Simula ng Brevo Local PC Sync Process...</h2>";

    // 3. I-loop ang bawat List ID gamit ang Pagination Offset Loop
    foreach ($listIds as $listId) {
        $limit = 500;
        $offset = 0;
        $hasMore = true;

        echo "Processing List ID: <strong>$listId</strong>...<br>";

        while ($hasMore) {
            // Absolute native hardcoded path string para sa Contacts endpoint
           // $url = "https://brevo.com" . $listId . "/contacts?limit=" . $limit . "&offset=" . $offset;
// Ginamit ang %d at %s para ihiwalay nang husto ang numero sa domain name upang iwas typo
//$url = sprintf("https://brevo.com", $listId, $limit, $offset);
// Tahasang binuo ang URL string gamit ang purong concatenation upang masigurong walang typo sa endpoint
$url = "https://brevo.com" . intval($listId) . "/contacts?limit=" . intval($limit) . "&offset=" . intval($offset);


            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            // LOCAL HANDSHAKE BYPASS: Siguradong mawawala ang status 0 error sa localhost
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
    // Kuhanin ang totoong mensahe mula sa operating system cURL engine
    $curl_error_message = curl_error($ch); 
    
    echo " <span style='color:red;'>Pumalya ang koneksyon para sa offset $offset (Status: $statusCode)</span><br>";
    echo " <span style='color:darkorange; font-size:13px;'>Tunay na Dahilan: <strong>" . ($curl_error_message ?: 'Unknown Network Block') . "</strong></span><br>";
    break;
}


            $data = json_decode($response, true);
            $contacts = $data['contacts'] ?? [];

            if (empty($contacts)) {
                $hasMore = false;
                break;
            }

            // 4. I-insert at I-update ang bawat email sa database gamit ang MySQL Dual Query
            foreach ($contacts as $contact) {
                $email = $contact['email'] ?? null;
                if (!$email) continue;

                $blacklisted = $contact['emailBlacklisted'] ?? false;
                $delivered = $blacklisted ? 'No' : 'Yes';
                $unsubscribed = $blacklisted ? 'Yes' : 'No';
                $statusLabel = $blacklisted ? 'unsubscribed' : 'delivered';
                $now = date('Y-m-d H:i:s');

                // SQL Execution Logic (updateOrInsert equivalent sa native PHP)
                $sql = "INSERT INTO campaign_recipients 
                        (campaign_id, campaign_name, from_list_id, email, delivered, unsubscribed, status, action_at, created_at, updated_at) 
                        VALUES (:camp_id, :camp_name, :list_id, :email, :del, :unsub, :status, :act, :created, :updated)
                        ON DUPLICATE KEY UPDATE 
                        campaign_name = VALUES(campaign_name),
                        from_list_id = VALUES(from_list_id),
                        delivered = VALUES(delivered),
                        unsubscribed = VALUES(unsubscribed),
                        status = VALUES(status),
                        updated_at = VALUES(updated_at)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':camp_id'   => strval($campaignId),
                    ':camp_name' => $campaignName,
                    ':list_id'   => $listId,
                    ':email'     => $email,
                    ':del'       => $delivered,
                    ':unsub'     => $unsubscribed,
                    ':status'    => $statusLabel,
                    ':act'       => $now,
                    ':created'   => $now,
                    ':updated'   => $now
                ]);

                $totalInserted++;
            }

            $offset += $limit;

            if (count($contacts) < $limit) {
                $hasMore = false;
            }
        }
        echo " <span style='color:green;'>Tapos na ang List $listId!</span><br><br>";
    }

    echo "<h3>🎉 Tagumpay! Kabuuang <strong>$totalInserted</strong> records ang pumasok at na-update sa iyong table!</h3>";

} catch (Exception $e) {
    echo "<h3 style='color:red;'>Error sa pagtakbo: " . $e->getMessage() . "</h3>";
}
