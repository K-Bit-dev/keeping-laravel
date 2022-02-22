<?php

namespace KBit\LaravelKeeping;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Support\Collection;
use KBit\LaravelKeeping\Exceptions\KeepingException;
use KBit\LaravelKeeping\Exceptions\KeepingOrganisationIdMissingException;
use KBit\LaravelKeeping\Exceptions\NoOrganisationException;
use KBit\LaravelKeeping\Model\Client;
use KBit\LaravelKeeping\Model\Organisation;
use KBit\LaravelKeeping\Model\Project;
use KBit\LaravelKeeping\Model\Report;
use KBit\LaravelKeeping\Model\ReportQuery;
use KBit\LaravelKeeping\Model\ReportRow;
use KBit\LaravelKeeping\Model\Task;
use KBit\LaravelKeeping\Model\TimeEntry;
use KBit\LaravelKeeping\Model\User;

class KeepingClient
{
  const API_URL = "https://api.keeping.nl/v1";

  /** @var \GuzzleHttp\Client  */
  private $client;

  /** @var string  */
  private $personalAccessToken;

  /** @var int */
  public $maxRetries = 2;

  /** @var int */
  public $retryDelay = 500;

  /** @var Organisation */
  public $organisation;

  /**
   * KeepingClient constructor.
   * @param string $personalAccessToken
   */
  public function __construct(string $personalAccessToken)
  {
    $this->personalAccessToken = $personalAccessToken;

    $this->client = new \GuzzleHttp\Client([
      'handler' => $this->createGuzzleHandler(),
    ]);

    $this->headers = [
      'Authorization' => 'Bearer '.$this->personalAccessToken,
    ];

    $organisation = $this
      ->getOrganisations()
      ->where('id', env('KEEPING_ORGANISATION_ID'))
      ->first();

    if (!$organisation instanceof Organisation) {
      throw new KeepingOrganisationIdMissingException();
    }

    $this->setOrganisation($organisation);
  }

  /**
   * @return \Illuminate\Support\HigherOrderTapProxy|mixed
   */
  private function createGuzzleHandler()
  {
    return tap(HandlerStack::create(new CurlHandler()), function (HandlerStack $handlerStack) {
      $handlerStack->push(Middleware::retry(function ($retries, Psr7Request $request, Psr7Response $response = null, RequestException $exception = null) {
        if ($retries >= $this->maxRetries) {
          return false;
        }

        if ($exception instanceof ConnectException) {
          return true;
        }

        if ($response && $response->getStatusCode() >= 500) {
          return true;
        }

        return false;
      }), $this->retryDelay);
    });
  }

  /**
   * @param string $endpoint
   * @param array $query
   * @return array|bool|float|int|mixed|object|string|null
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function get(string $endpoint, array $query = [])
  {
    try {
      $response = $this->client
        ->get(self::API_URL . $endpoint, [
          'headers' => $this->headers,
          'query' => $query,
        ])
        ->getBody();
    } catch (\Exception $exception) {
      throw new KeepingException();
    }

    return  \GuzzleHttp\json_decode($response);
  }

  /**
   * @param string $endpoint
   * @param array $data
   * @return array|bool|float|int|mixed|object|string|null
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function post(string $endpoint, array $data)
  {
    try {
      $response = $this->client
        ->post(self::API_URL . $endpoint, [
          'headers' => $this->headers,
          'json' => $data,
        ])
        ->getBody();
    } catch (\Exception $exception) {
      throw new KeepingException();
    }

    return \GuzzleHttp\json_decode($response);
  }

  /**
   * @param string $endpoint
   * @param array $data
   * @return array|bool|float|int|mixed|object|string|null
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function patch(string $endpoint, array $data)
  {
    try {
      $response = $this->client
        ->patch(self::API_URL . $endpoint, [
          'headers' => $this->headers,
          'json' => $data,
        ])
        ->getBody();
    } catch (\Exception $exception) {
      throw new KeepingException();
    }

    return \GuzzleHttp\json_decode($response);
  }

  /**
   * @param string $endpoint
   * @param array $data
   * @return array|bool|float|int|mixed|object|string|null
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function delete(string $endpoint)
  {
    try {
       $this->client
        ->delete(self::API_URL . $endpoint, [
          'headers' => $this->headers,
        ])
        ->getBody();
    } catch (\Exception $exception) {
      throw new KeepingException();
    }

    return true;
  }

  /**
   * @throws NoOrganisationException
   */
  public function requireOrganisation()
  {
    if (!$this->organisation instanceof Organisation) {
      throw new NoOrganisationException();
    }
  }

  /**
   * @return Collection
   */
  public function getOrganisations() : Collection
  {
    $organisations = new Collection();
    $response = $this->get('/organisations');

    foreach ($response->organisations as $organisationData) {
      $organisation = new Organisation((array) $organisationData);
      $organisations->add($organisation);
    }

    return $organisations;
  }

  /**
   * @param Organisation $organisation
   * @return $this
   */
  public function setOrganisation(Organisation $organisation) : KeepingClient
  {
    $this->organisation = $organisation;

    return $this;
  }

  /**
   * @param User $user
   * @param \DateTime|null $dateTime
   * @return Collection
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function getUserTimesheet(User $user, \DateTime $dateTime = null) : Collection
  {
    $this->requireOrganisation();

    $timeEntries = new Collection();
    $dateTime = $dateTime ?? new \DateTime();

    $response = $this->get('/time-entries', [
      'user_id' => $user->id,
      'date' => $dateTime->format('Y-m-d'),
    ]);

    foreach ($response->time_entries as $entryData) {
      $timeEntry = new TimeEntry((array) $entryData);
      $timeEntries->add($timeEntry);
    }

    return $timeEntries;
  }

  /**
   * @param User $user
   * @throws NoOrganisationException
   */
  public function getLastTimeEntry(User $user) : TimeEntry
  {
    $this->requireOrganisation();

    $response = $this->get('/'.$this->organisation->id.'/time-entries/last', [
      'user_id' => $user->id,
    ]);

    return new TimeEntry((array) $response->time_entry);
  }

  /**
   * @param int $timeEntryId
   * @return TimeEntry
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function getTimeEntry(int $timeEntryId) : TimeEntry
  {
    $this->requireOrganisation();

    $response = $this->get('/'.$this->organisation->id.'/time-entries/'.$timeEntryId);

    return new TimeEntry((array) $response->time_entry);
  }

  /**
   * @param TimeEntry $timeEntry
   * @throws NoOrganisationException
   */
  public function postTimeEntry(TimeEntry $timeEntry)
  {
    $this->requireOrganisation();

    $endpoint = '/'.$this->organisation->id.'/time-entries';
    $response = $this->post($endpoint, $timeEntry->toArray());

    return new TimeEntry((array) $response->time_entry);
  }

  /**
   * @param TimeEntry $timeEntry
   * @return TimeEntry
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function patchTimeEntry(TimeEntry $timeEntry)
  {
    $this->requireOrganisation();

    $data = [
      'project_id' => $timeEntry->project_id,
      'note' => $timeEntry->note,
      'hours' => $timeEntry->hours,
    ];

    if ($timeEntry->task_id) {
      $data['task_id'] = $timeEntry->task_id;
    }

    if ($timeEntry->task_id) {
      $data['start'] = $timeEntry->start;
    }

    if ($timeEntry->task_id) {
      $data['end'] = $timeEntry->end;
    }

    $endpoint = '/'.$this->organisation->id.'/time-entries/'.$timeEntry->id;
    $response = $this->patch($endpoint, $data);

    return new TimeEntry((array) $response->time_entry);
  }

  /**
   * @param TimeEntry $timeEntry
   * @return bool
   * @throws NoOrganisationException
   */
  public function deleteTimeEntry(TimeEntry $timeEntry) : bool
  {
    $this->requireOrganisation();

    $endpoint = '/'.$this->organisation->id.'/time-entries/'.$timeEntry->id;

    return $this->delete($endpoint);
  }

  /**
   * @param TimeEntry $timeEntry
   * @return TimeEntry
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function resumeTimeEntry(TimeEntry $timeEntry) : TimeEntry
  {
    $this->requireOrganisation();

    $endpoint = '/'.$this->organisation->id.'/time-entries/'.$timeEntry->id.'/resume';
    $response = $this->post($endpoint, []);

    return new TimeEntry((array) $response->time_entry);
  }

  /**
   * @param Organisation $organisation
   * @param TimeEntry $timeEntry
   * @return TimeEntry
   * @throws NoOrganisationException
   */
  public function stopTimeEntry(TimeEntry $timeEntry) : TimeEntry
  {
    $this->requireOrganisation();

    $endpoint = '/'.$this->organisation->id.'/time-entries/'.$timeEntry->id.'/stop';
    $response = $this->patch($endpoint, []);

    return new TimeEntry((array) $response->time_entry);
  }

  /**
   * @param array $query
   * @return Report
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function getReportTimeEntries(array $query = []) : Collection
  {
    $this->requireOrganisation();

    $endpoint = '/'.$this->organisation->id.'/report/time-entries';
    $response = $this->get($endpoint, $query);

    $timeEntries = new Collection();

    foreach ($response->time_entries as $timeEntry) {
      $entry = new TimeEntry((array) $timeEntry);
      $timeEntries->add($entry);
    }

    return $timeEntries;
  }

  /**
   * @param array $query
   * @return Collection
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function getReport(array $query = []) : Report
  {
    $this->requireOrganisation();

    $endpoint = '/'.$this->organisation->id.'/report';
    $response = $this->get($endpoint, $query);

    $reportRows = new Collection();

    foreach ($response->report->rows as $row) {
      $reportRow = new ReportRow((array) $row);
      $reportRows->add($reportRow);
    }

    $report = new Report((array) $response->report);
    $report->rows = $reportRows;

    return $report;
  }

  /**
   * @param array $query
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function getUsers(array $query = []) : array
  {
    $this->requireOrganisation();

    $users = new Collection();
    $response = $this->get('/'.$this->organisation->id.'/users', $query);

    foreach ($response->users as $userData) {
      $user = new User((array) $userData);
      $users->add($user);
    }

    return [
      'users' => $users,
      'meta' => $response->meta,
    ];
  }

  /**
   * @param int|null $userId
   * @return User
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function getUser(int $userId = null) : User
  {
    $this->requireOrganisation();

    $response = $this->get('/'.$this->organisation->id.'/users/'.($userId ?? 'me'));

    return new User((array) $response->user);
  }

  /**
   * @param array $query
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function getProjects(array $query = [])
  {
    $this->requireOrganisation();

    $projects = new Collection();
    $response = $this->get('/'.$this->organisation->id.'/projects', $query);

    foreach ($response->projects as $projectData) {
      $project = new Project((array) $projectData);
      $projects->add($project);
    }

    return [
      'projects' => $projects,
      'meta' => $response->meta,
    ];
  }

  /**
   * @param int $projectId
   * @return Project
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function getProject(int $projectId) : Project
  {
    $this->requireOrganisation();

    $response = $this->get('/'.$this->organisation->id.'/projects/'.$projectId);

    return new Project((array) $response->project);
  }

  /**
   * @param Project $project
   * @return Project
   * @throws NoOrganisationException
   */
  public function postProject(Project $project) : Project
  {
    $this->requireOrganisation();

    $response = $this->post('/'.$this->organisation->id.'/projects', $project->toArray());

    return new Project((array) $response->project);
  }

  /**
   * @param Project $project
   * @return Project
   * @throws NoOrganisationException
   */
  public function patchProject(Project $project) : Project
  {
    $this->requireOrganisation();

    $data = [
      'name' => $project->name,
    ];

    if ($project->client_id) {
      $data['client_id'] = $project->client_id;
    }

    if ($project->code) {
      $data['code'] = $project->code;
    }

    if ($project->direct) {
      $data['direct'] = $project->direct;
    }

    $endpoint = '/'.$this->organisation->id.'/projects/'.$project->id;
    $response = $this->patch($endpoint, $data);

    return new Project((array) $response->project);
  }

  /**
   * @param Project $project
   * @return bool
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function deleteProject(Project $project) : bool
  {
    $this->requireOrganisation();

    $endpoint = '/'.$this->organisation->id.'/projects/'.$project->id;

    return $this->delete($endpoint);
  }

  /**
   * @param Project $project
   * @return Project
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function archiveProject(Project $project) : Project
  {
    $this->requireOrganisation();

    $endpoint = '/'.$this->organisation->id.'/projects/'.$project->id.'/archive';
    $response = $this->patch($endpoint, []);

    return new Project((array) $response->project);
  }

  /**
   * @param Project $project
   * @return Project
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function restoreProject(Project $project) : Project
  {
    $this->requireOrganisation();

    $endpoint = '/'.$this->organisation->id.'/projects/'.$project->id.'/restore';
    $response = $this->patch($endpoint, []);

    return new Project((array) $response->project);
  }

  /**
   * @param array $query
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function getTasks(array $query = [])
  {
    $this->requireOrganisation();

    $tasks = new Collection();
    $response = $this->get('/'.$this->organisation->id.'/tasks', $query);

    foreach ($response->tasks as $taskData) {
      $task = new Task((array) $taskData);
      $tasks->add($task);
    }

    return [
      'tasks' => $tasks,
      'meta' => $response->meta,
    ];
  }

  /**
   * @param int $taskId
   * @return Task
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function getTask(int $taskId) : Task
  {
    $this->requireOrganisation();

    $response = $this->get('/'.$this->organisation->id.'/tasks/'.$taskId);

    return new Task((array) $response->task);
  }

  /**
   * @param Task $task
   * @return Task
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function postTask(Task $task) : Task
  {
    $this->requireOrganisation();

    $response = $this->post('/'.$this->organisation->id.'/tasks', $task->toArray());

    return new Task((array) $response->task);
  }

  /**
   * @param Task $task
   * @return Task
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function patchTask(Task $task) : Task
  {
    $this->requireOrganisation();

    $data = [
      'name' => $task->name,
    ];

    if ($task->code) {
      $data['code'] = $task->code;
    }

    if ($task->direct) {
      $data['direct'] = $task->direct;
    }

    $endpoint = '/'.$this->organisation->id.'/tasks/'.$task->id;
    $response = $this->patch($endpoint, $data);

    return new task((array) $response->task);
  }

  /**
   * @param Task $task
   * @return bool
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function deleteTask(Task $task) : bool
  {
    $this->requireOrganisation();

    $endpoint = '/'.$this->organisation->id.'/tasks/'.$task->id;

    return $this->delete($endpoint);
  }

  /**
   * @param Task $task
   * @return Task
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function archiveTask(Task $task) : Task
  {
    $this->requireOrganisation();

    $endpoint = '/'.$this->organisation->id.'/tasks/'.$task->id.'/archive';
    $response = $this->patch($endpoint, []);

    return new Task((array) $response->task);
  }

  /**
   * @param Task $task
   * @return Task
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function restoreTask(Task $task) : Task
  {
    $this->requireOrganisation();

    $endpoint = '/'.$this->organisation->id.'/tasks/'.$task->id.'/restore';
    $response = $this->patch($endpoint, []);

    return new Task((array) $response->task);
  }

  /**
   * @param array $query
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function getClients(array $query = [])
  {
    $this->requireOrganisation();

    $clients = new Collection();
    $response = $this->get('/'.$this->organisation->id.'/clients', $query);

    foreach ($response->clients as $clientData) {
      $client = new Client((array) $clientData);
      $clients->add($client);
    }

    return [
      'clients' => $clients,
      'meta' => $response->meta,
    ];
  }

  /**
   * @param int $clientId
   * @return Client
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function getClient(int $clientId)
  {
    $this->requireOrganisation();

    $response = $this->get('/'.$this->organisation->id.'/clients/'.$clientId);

    return new Client((array) $response->client);
  }

  /**
   * @param Client $client
   * @return Client
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws NoOrganisationException
   */
  public function postClient(Client $client) : Client
  {
    $this->requireOrganisation();

    $response = $this->post('/'.$this->organisation->id.'/clients', $client->toArray());

    return new Client((array) $response->client);
  }

  /**
   * @param Client $client
   * @return Client
   * @throws NoOrganisationException
   */
  public function patchClient(Client $client) : Client
  {
    $this->requireOrganisation();

    $data = [
      'name' => $client->name,
    ];

    if ($client->code) {
      $data['code'] = $client->code;
    }

    $endpoint = '/'.$this->organisation->id.'/clients/'.$client->id;
    $response = $this->patch($endpoint, $data);

    return new Client((array) $response->client);
  }

  /**
   * @param Client $client
   * @return bool
   * @throws NoOrganisationException
   */
  public function deleteClient(Client $client) : bool
  {
    $this->requireOrganisation();

    $endpoint = '/'.$this->organisation->id.'/clients/'.$client->id;

    return $this->delete($endpoint);
  }
}
