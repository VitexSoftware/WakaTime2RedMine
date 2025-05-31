<?php

declare(strict_types=1);

/**
 * This file is part of the AbraflexiContractor package
 *
 * https://github.com/VitexSoftware/WakaTime2RedMine
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once '../vendor/autoload.php';
\define('APP_NAME', 'WakaTime2RedmineRedirect');

\Ease\Shared::init(['WAKATIME_APP_ID', 'WAKATIME_APP_SECRET', 'WAKATIME_REDIRECT_URI'], '../.env');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$request = Request::createFromGlobals();

$clientId = \Ease\Shared::cfg('WAKATIME_APP_ID');
$clientSecret = \Ease\Shared::cfg('WAKATIME_APP_SECRET');
$redirectUri = \Ease\Shared::cfg('WAKATIME_REDIRECT_URI');

if ($request->query->has('code')) {
    $code = $request->query->get('code');

    $url = 'https://wakatime.com/oauth/token';
    $data = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code',
        'code' => $code,
    ];

    $ch = curl_init();
    curl_setopt($ch, \CURLOPT_URL, $url);
    curl_setopt($ch, \CURLOPT_POST, true);
    curl_setopt($ch, \CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, \CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        echo 'Error: Failed to exchange authorization code for access token.';

        exit;
    }

    /*
     * The response looks like:

access_token=waka_tok_F3CM8WUJr7LmBfDWz4kmd4DNv8crsme1BNVX2P45CemFxXmlMoLJkaKddXvNf6lGt4m3aoQOkFQ9Na88&refresh_token=waka_ref_cyPml2ka5mZfnrpi33j5z0TzmSrRY4mRajRcsD4X5EpZaBRMDUedZtsNOeqXJpdoSgPOMziU1GAHn4QG&uid=5abba9ca-813e-43ac-9b5f-b1cfdf3dc1c7&token_type=bearer&expires_at=2026-05-31T13%3A31%3A18Z&expires_in=31536000&scope=email+read_stats

     */

    $decodedResponse = [];
    parse_str($response, $decodedResponse);

    if (isset($decodedResponse['access_token'])) {
        $accessToken = $decodedResponse['access_token'];
        $refreshToken = $decodedResponse['refresh_token'] ?? null;
        $expiresAt = $decodedResponse['expires_at'] ?? null;
        $expiresIn = $decodedResponse['expires_in'] ?? null;

        // Calculate expiration details
        $expirationDateTime = $expiresAt ? (new DateTime($expiresAt))->format('Y-m-d H:i:s') : 'Unknown';
        $daysToExpire = $expiresIn ? floor($expiresIn / 86400) : 'Unknown';

        // Display tokens and expiration details on the page with a copy widget
        echo '<h3>Access Token</h3>';
        echo '<input type="text" value="'.htmlspecialchars($accessToken, \ENT_QUOTES, 'UTF-8').'" readonly id="accessToken">';
        echo '<button onclick="navigator.clipboard.writeText(document.getElementById(\'accessToken\').value)">Copy</button>';

        if ($refreshToken) {
            echo '<h3>Refresh Token</h3>';
            echo '<input type="text" value="'.htmlspecialchars($refreshToken, \ENT_QUOTES, 'UTF-8').'" readonly id="refreshToken">';
            echo '<button onclick="navigator.clipboard.writeText(document.getElementById(\'refreshToken\').value)">Copy</button>';
        }

        echo '<h3>Expiration Details</h3>';
        echo '<p>Expires At: '.htmlspecialchars($expirationDateTime, \ENT_QUOTES, 'UTF-8').'</p>';
        echo '<p>Days to Expire: '.htmlspecialchars((string) $daysToExpire, \ENT_QUOTES, 'UTF-8').'</p>';
    } else {
        echo 'Error: Invalid response while exchanging authorization code.';
    }
} else {
    echo 'Error: Authorization code not found in the request.';
}
