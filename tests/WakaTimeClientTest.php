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
use VitexSoftware\WakaTime2RedMine\WakaTimeClient;

class WakaTimeClientTest extends TestCase
{
    public function testFetchDataReturnsArray(): void
    {
        $mockApiKey = 'mock_api_key';
        $mockStartDate = '2025-05-01';
        $mockEndDate = '2025-05-31';

        $client = new WakaTimeClient($mockApiKey);

        // Mock the fetchData method to return a predefined response
        $this->assertIsArray($client->fetchData($mockStartDate, $mockEndDate));
    }
}
