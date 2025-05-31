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

namespace VitexSoftware\WakaTime2RedMine;

/**
 * Description of WakaTimeClient.
 *
 * @author Vitex <info@vitexsoftware.cz>
 */
class WakaTimeClient
{
    private string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Fetch data from WakaTime API using OAuth 2.0.
     *
     * @return array<string, mixed>
     */
    public function fetchData(string $startDate, string $endDate): array
    {
        $url = "https://wakatime.com/api/v1/users/current/summaries?start={$startDate}&end={$endDate}";
        $headers = [
            "Authorization: Bearer {$this->token}",
        ];

        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_URL, $url);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Failed to fetch data from WakaTime API.');
        }

        return json_decode($response, true);
    }

    /**
     * Refresh OAuth 2.0 access token.
     */
    public function refreshAccessToken(string $refreshToken, string $clientId, string $clientSecret, string $redirectUri): string
    {
        $url = 'https://wakatime.com/oauth/token';
        $data = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
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
            throw new \RuntimeException('Failed to refresh access token.');
        }

        $decodedResponse = json_decode($response, true);

        if (!isset($decodedResponse['access_token'])) {
            throw new \RuntimeException('Invalid response while refreshing access token.');
        }

        return $decodedResponse['access_token'];
    }

    /**
     * Fetch durations for a specific date from WakaTime API.
     *
     * @param string $date the date in YYYY-MM-DD format
     *
     * @return array<string, mixed>
     */
    public function fetchDurations(string $date): array
    {
        $url = "https://wakatime.com/api/v1/users/current/durations?date={$date}";
        $headers = [
            "Authorization: Bearer {$this->token}",
        ];

        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_URL, $url);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Failed to fetch durations from WakaTime API.');
        }

        return json_decode($response, true);
    }

    /**
     * Fetch all registered projects from WakaTime API.
     *
     * @return array<string> List of project names
     */
    public function fetchProjects(): array
    {
        $url = "https://wakatime.com/api/v1/users/current/projects";
        $headers = [
            "Authorization: Bearer {$this->token}",
        ];

        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_URL, $url);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Failed to fetch projects from WakaTime API.');
        }

        $decodedResponse = json_decode($response, true);

        if (!isset($decodedResponse['data'])) {
            throw new \RuntimeException('Invalid response while fetching projects.');
        }

        return $decodedResponse['data'];
    }
}
