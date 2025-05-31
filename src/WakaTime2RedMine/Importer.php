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
 * Description of Importer.
 *
 * @author Vitex <info@vitexsoftware.cz>
 */
class Importer extends \Ease\Sand
{
    private WakaTimeClient $wakatimeClient;
    private RedMineClient $redmineClient;
    private string $scope;
    private \DateTime $since;
    private \DateTime $until;

    public function __construct()
    {
        $this->setObjectName();
    }

    /**
     * Prepare processing interval.
     *
     * @throws \Exception
     */
    public function setScope(string $scope): \DatePeriod
    {
        switch ($scope) {
            case 'yesterday':
                $this->since = (new \DateTime('yesterday'))->setTime(0, 0);
                $this->until = (new \DateTime('yesterday'))->setTime(23, 59, 59, 999);

                break;
            case 'current_month':
                $this->since = (new \DateTime('first day of this month'))->setTime(0, 0);
                $this->until = (new \DateTime())->setTime(23, 59, 59, 999);

                break;
            case 'last_month':
                $this->since = (new \DateTime('first day of last month'))->setTime(0, 0);
                $this->until = (new \DateTime('last day of last month'))->setTime(23, 59, 59, 999);

                break;
            case 'last_week':
                $this->since = (new \DateTime('monday last week'))->setTime(0, 0);
                $this->until = (new \DateTime('sunday last week'))->setTime(23, 59, 59, 999);

                break;
            case 'last_two_months':
                $this->since = (new \DateTime('first day of -2 months'))->setTime(0, 0);
                $this->until = (new \DateTime('last day of last month'))->setTime(23, 59, 59, 999);

                break;
            case 'previous_month':
                $this->since = (new \DateTime('first day of -2 months'))->setTime(0, 0);
                $this->until = (new \DateTime('last day of -2 months'))->setTime(23, 59, 59, 999);

                break;
            case 'two_months_ago':
                $this->since = (new \DateTime('first day of -3 months'))->setTime(0, 0);
                $this->until = (new \DateTime('last day of -3 months'))->setTime(23, 59, 59, 999);

                break;
            case 'this_year':
                $this->since = (new \DateTime('first day of January '.date('Y')))->setTime(0, 0);
                $this->until = (new \DateTime('last day of December '.date('Y')))->setTime(23, 59, 59, 999);

                break;
            case 'January':
            case 'February':
            case 'March':
            case 'April':
            case 'May':
            case 'June':
            case 'July':
            case 'August':
            case 'September':
            case 'October':
            case 'November':
            case 'December':
                $this->since = (new \DateTime('first day of '.$scope.' '.date('Y')))->setTime(0, 0);
                $this->until = (new \DateTime('last day of '.$scope.' '.date('Y')))->setTime(23, 59, 59, 999);

                break;
            case 'auto':
                $this->addStatusMessage('Previous record for "auto since" not found. Defaulting to today\'s 00:00', 'warning');
                $this->since = (new \DateTime())->setTime(0, 0);
                $this->until = (new \DateTime())->setTime(23, 59, 59, 999);

                break;

            default:
                if (strstr($scope, '>')) {
                    [$begin, $end] = explode('>', $scope);
                    $this->since = (new \DateTime($begin))->setTime(0, 0);
                    $this->until = (new \DateTime($end))->setTime(23, 59, 59, 999);
                } else {
                    if (preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $scope)) {
                        $this->since = (new \DateTime($scope))->setTime(0, 0);
                        $this->until = (new \DateTime($scope))->setTime(23, 59, 59, 999);

                        break;
                    }

                    throw new \Exception('Unknown scope '.$scope);
                }

                break;
        }

        if ($scope !== 'auto' && $scope !== 'today' && $scope !== 'yesterday') {
            $this->since = $this->since->setTime(0, 0);
            $this->until = $this->until->setTime(23, 59, 59, 999);
        }

        $this->scope = $scope;

        return new \DatePeriod($this->since, new \DateInterval('P1D'), $this->until);
    }

    public function wakatimeClient(?WakaTimeClient $client): ?WakaTimeClient
    {
        if ($client) {
            $this->wakatimeClient = $client;
        }

        return $this->wakatimeClient;
    }

    public function redmineClient(?RedMineClient $client): ?RedMineClient
    {
        if ($client) {
            $this->redmineClient = $client;
        }

        return $this->redmineClient;
    }

    public function import(): array
    {
        $projects = $this->wakatimeClient->fetchProjects();
        
        $this->addStatusMessage('WakaTime Projects: ' . json_encode($projects), 'info');


        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $data = [];

        $period = new \DatePeriod(
            new \DateTime($startDate),
            new \DateInterval('P1D'),
            (new \DateTime($endDate))->modify('+1 day'),
        );

        foreach ($period as $date) {
            $spentTimeSeconds = 0;
            $dailyData = $this->wakatimeClient->fetchDurations($date->format('Y-m-d'));

            if (\array_key_exists('error', $dailyData)) {
                $this->addStatusMessage($date->format('Y-m-d').': '.$dailyData['error'], 'warning');

                continue;
            }

            foreach ($dailyData['data'] as $entry) {
                if (\is_array($entry)) {
                    $spentTimeSeconds += $entry['duration'] ?? 0;
                }
            }
            $spentTimeHours = round($spentTimeSeconds / 3600, 2); // Convert seconds to decimal hours
            $this->addStatusMessage($date->format('Y-m-d').' Time: '.$spentTimeHours.' hours', 'debug');

            $data = array_merge($data, $dailyData);
        }

        $groupedData = [];

        foreach ($data['data'] as $entry) {
            $project = $entry['project'] ?? 'Unknown';

            if (!isset($groupedData[$project])) {
                $groupedData[$project] = 0;
            }

            $groupedData[$project] += $entry['duration'];
        }

        $this->redmineClient->importData($groupedData);

        return ['groupedData' => $groupedData];
    }

    public function getUntil(): \DateTime
    {
        return $this->until;
    }

    public function getSince(): \DateTime
    {
        return $this->since;
    }

    private function getStartDate(): string
    {
        return $this->since->format('Y-m-d');
    }

    private function getEndDate(): string
    {
        return $this->until->format('Y-m-d');
    }
}
