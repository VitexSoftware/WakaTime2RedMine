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

namespace VitexSoftware;

require_once '../vendor/autoload.php';

use VitexSoftware\WakaTime2RedMine\RedMineClient;
use VitexSoftware\WakaTime2RedMine\WakaTimeClient;

\define('APP_NAME', 'WakaTime2Redmine');

// Parse command line arguments
$options = getopt('s::e::o:', ['scope::', 'env::', 'output:']);
$exitcode = 0;
// Get the path to the .env file
$envfile = $options['env'] ?? '../.env';

$destination = \array_key_exists('output', $options) ? $options['output'] : \Ease\Shared::cfg('RESULT_FILE', 'php://stdout');

\Ease\Shared::init(['WAKATIME_TOKEN', 'REDMINE_URL', 'REDMINE_PROJECT', 'REDMINE_USERNAME'], $envfile);

// Initialize clients
$wakatimeClient = new WakaTimeClient(\Ease\Shared::cfg('WAKATIME_TOKEN'));
$redmineClient = new RedMineClient(\Ease\Shared::cfg('REDMINE_URL'), \Ease\Shared::cfg('REDMINE_PROJECT'), \Ease\Shared::cfg('REDMINE_USERNAME'), \Ease\Shared::cfg('REDMINE_PASSWORD', ''));

$engine = new WakaTime2RedMine\Importer();
$scope = $options['scope'] ?? \Ease\Shared::cfg('IMPORT_SCOPE', 'yesterday');
$engine->setScope($scope);
$engine->redmineClient($redmineClient);
$engine->wakatimeClient($wakatimeClient);

if (\Ease\Shared::cfg('APP_DEBUG', false)) {
    $engine->logBanner('Scope: '.$scope);
}

$data = $engine->import();

$report = [
    'scope' => $scope,
    'until' => $engine->getUntil()->format('Y-m-d'),
    'since' => $engine->getSince()->format('Y-m-d'),
    'data' => $data,
];

$written = file_put_contents($destination, json_encode($report, \Ease\Shared::cfg('DEBUG') ? \JSON_PRETTY_PRINT : 0));
$engine->addStatusMessage(sprintf(_('Saving result to %s'), $destination), $written ? 'success' : 'error');

exit($exitcode);
