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
\define('APP_NAME', 'WakaTime2RedmineOAuth');

\Ease\Shared::init(['WAKATIME_APP_ID', 'WAKATIME_REDIRECT_URI'], '../.env');

$clientId = \Ease\Shared::cfg('WAKATIME_APP_ID');
$redirectUri = \Ease\Shared::cfg('WAKATIME_REDIRECT_URI');
$scope = 'read_heartbeats'; // Updated scope to include read_summaries
$state = bin2hex(random_bytes(16)); // Generate a random state for security

$authUrl = sprintf(
    'https://wakatime.com/oauth/authorize?client_id=%s&response_type=code&redirect_uri=%s&scope=%s&state=%s',
    urlencode($clientId),
    urlencode($redirectUri),
    urlencode($scope),
    urlencode($state),
);

header('Location: '.$authUrl);

exit;
