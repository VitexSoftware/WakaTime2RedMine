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

use PHPUnit\Framework\TestCase;
use VitexSoftware\WakaTime2RedMine\RedMineClient;
use VitexSoftware\WakaTime2RedMine\WakaTimeClient;

class WakaTime2RedMineTest extends TestCase
{
    public function testClientsInitialization(): void
    {
        $mockWakaTimeApiKey = 'mock_WAKATIME_TOKEN';
        $mockRedMineUrl = 'https://mock-redmine-url.com';
        $mockRedMineApiKey = 'mock_redmine_api_key';

        $wakatimeClient = new WakaTimeClient($mockWakaTimeApiKey);
        $redmineClient = new RedMineClient($mockRedMineUrl, $mockRedMineApiKey);

        $this->assertInstanceOf(WakaTimeClient::class, $wakatimeClient);
        $this->assertInstanceOf(RedMineClient::class, $redmineClient);
    }

    public function testMainScriptExecution(): void
    {
        // Mock the main script logic
        $this->expectNotToPerformAssertions();

        // Simulate script execution
        $mockWakaTimeApiKey = 'mock_WAKATIME_TOKEN';
        $mockRedMineUrl = 'https://mock-redmine-url.com';
        $mockRedMineApiKey = 'mock_redmine_api_key';

        $wakatimeClient = new WakaTimeClient($mockWakaTimeApiKey);
        $redmineClient = new RedMineClient($mockRedMineUrl, $mockRedMineApiKey);

        $mockData = $wakatimeClient->fetchData('2025-05-01', '2025-05-31');
        $redmineClient->importData($mockData);
    }
}
