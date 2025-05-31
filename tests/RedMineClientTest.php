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

class RedMineClientTest extends TestCase
{
    public function testImportDataExecutesWithoutErrors(): void
    {
        $mockUrl = 'https://mock-redmine-url.com';
        $mockApiKey = 'mock_api_key';
        $mockData = [
            'data' => [
                [
                    'grand_total' => ['text' => '5 hrs', 'hours' => 5],
                    'projects' => [['name' => 'Project A'], ['name' => 'Project B']],
                ],
            ],
        ];

        $client = new RedMineClient($mockUrl, $mockApiKey);

        // Mock the importData method to ensure it executes without errors
        $this->expectNotToPerformAssertions();
        $client->importData($mockData);
    }
}
