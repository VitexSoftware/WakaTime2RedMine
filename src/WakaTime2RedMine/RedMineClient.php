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

class RedMineClient
{
    private string $url;
    private ?string $username;
    private ?string $password;
    private ?string $apiKey;
    private ?string $projectName;

    public function __construct(string $url, string $projectName, string $username, string $password = '')
    {
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;

        if ($password === '') {
            $this->apiKey = $username; // Use username as API key if password is empty
            $this->username = null; // Clear username to avoid confusion
        } else {
            $this->apiKey = null; // Clear API key if username and password are provided
        }

        if (empty($this->url) || !filter_var($this->url, \FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid RedMine URL provided.');
        }
    }

    public function importData(array $records): void
    {
        foreach ($records as $record) {
            $issueData = [
                'project_id' => 1, // Replace with your project ID
                'subject' => 'Work record: '.$record['grand_total']['text'],
                'description' => 'Worked on: '.implode(', ', array_column($record['projects'], 'name')),
                'spent_hours' => $record['grand_total']['hours'],
            ];

            $this->createIssue($issueData);

            echo 'Imported record: '.$record['grand_total']['text']."\n";
        }
    }

    /**
     * Set RedMine Project.
     */
    public function setProject(string $projectName): void
    {
        $this->projectName = $projectName;
    }

    /**
     * Get RedMine Project.
     */
    public function getProject(): ?string
    {
        return $this->projectName ?? null;
    }

    /**
     * Obtain RedMine Projects listing.
     *
     * @param array<string, mixed> $params conditions
     *
     * @return null|array<int, mixed>
     */
    public function getProjects(array $params = []): ?array
    {
        $result = null;
        $response = $this->performRequest(\Ease\Functions::addUrlParams(
            'projects.json',
            $params,
        ), 'GET');

        if ($this->lastResponseCode === 200) {
            $response = \Ease\Functions::reindexArrayBy($response['projects'], 'id');
        }

        return $response;
    }

    /**
     * Create Issue in RedMine Project.
     *
     * @param array<string, mixed> $issueData
     *
     * @return null|array<string, mixed>
     */
    public function createIssue(array $issueData): ?array
    {
        $url = $this->url.'/issues.json';

        $headers = $this->getAuthHeaders();
        $issueData['project_id'] = $this->getProject();

        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_URL, $url);
        curl_setopt($ch, \CURLOPT_POST, true);
        curl_setopt($ch, \CURLOPT_POSTFIELDS, json_encode(['issue' => $issueData]));
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_HTTPHEADER, array_merge($headers, ['Content-Type: application/json']));

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Failed to create issue in RedMine.');
        }

        return json_decode($response, true);
    }

    /**
     * Obtain Redmine Issues List.
     *
     * @param array<string, mixed> $conditions conditions
     *
     * @return null|array<int, mixed>
     */
    public function getIssues(array $conditions): ?array
    {
        $url = $this->url.'/issues.json';
        $queryParams = http_build_query($conditions);
        $fullUrl = $url.'?'.$queryParams;

        $headers = $this->getAuthHeaders();

        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_URL, $fullUrl);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Failed to fetch issues from RedMine.');
        }

        $decodedResponse = json_decode($response, true);

        if (isset($decodedResponse['issues'])) {
            return $decodedResponse['issues'];
        }

        return null;
    }

    /**
     * Obtain Issue Info.
     *
     * @return null|array<int, mixed>
     */
    public function getIssueInfo(int $issueId): ?array
    {
        $issues = $this->getIssues(['issue_id' => $issueId, 'status_id' => '*']);

        return $issues[$issueId] ?? null;
    }

    /**
     * Add Issue names to time entries.
     *
     * @param array<int, mixed> $timeEntries
     *
     * @return array<int, mixed>
     */
    public function addIssueNames(array $timeEntries): array
    {
        $result = [];
        $issues = [];

        foreach ($timeEntries as $timeEntryID => $timeEntry) {
            if (isset($timeEntry['issue'])) {
                $issues[$timeEntry['issue']['id']] = $timeEntry['issue']['id'];
            }

            $result[$timeEntryID] = [
                'project' => $timeEntry['project']['name'],
                'hours' => $timeEntry['hours'],
                'issue' => $timeEntry['issue']['id'] ?? 0,
                'comments' => $timeEntry['comments'] ?? '',
            ];
        }

        if (\count($issues)) {
            $issueInfo = $this->getNameForIssues($issues);

            foreach ($result as $timeEntryID => $timeEntry) {
                if (isset($timeEntry['issue'])) {
                    $issueID = $timeEntry['issue'];

                    $timeEntry['issue'] = $issueInfo[$issueID] ?? $issueID;
                }

                $result[$timeEntryID] = $timeEntry;
            }
        }

        return $result;
    }

    /**
     * Obtain Issue name by IssueID.
     *
     * @param array<int> $issuesID
     *
     * @return array<int, string>
     */
    public function getNameForIssues(array $issuesID): array
    {
        $url = $this->url.'/issues.json';
        $queryParams = http_build_query(['status_id' => '*', 'issue_id' => implode(',', $issuesID)]);
        $fullUrl = $url.'?'.$queryParams;

        $headers = $this->getAuthHeaders();

        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_URL, $fullUrl);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Failed to fetch issue names from RedMine.');
        }

        $decodedResponse = json_decode($response, true);

        $result = [];

        if (isset($decodedResponse['issues'])) {
            foreach ($decodedResponse['issues'] as $issue) {
                $result[$issue['id']] = $issue['subject'];
            }
        }

        return $result;
    }

    /**
     * Add URL parameters.
     *
     * @param array<string, mixed> $params
     */
    private function addUrlParams(string $url, array $params): string
    {
        return $url.'?'.http_build_query($params);
    }

    private function getAuthHeaders(): array
    {
        if ($this->apiKey) {
            return ['X-Redmine-API-Key: '.$this->apiKey];
        }

        if ($this->username && $this->password) {
            return ['Authorization: Basic '.base64_encode("{$this->username}:{$this->password}")];
        }

        throw new \InvalidArgumentException('Authentication method not provided.');
    }
}
