# Summary

This is a simple library which can be used to communicate with timemanagement tool Keeping.

### Installation

```
composer require k-bit/laravel-keeping
```
Add the following items to your `.env` file:
```dotenv
KEEPING_TOKEN="XXXXX"
KEEPING_ORGANISATION_ID="1234"
```

---
### Example for creating a new time entry
The example below shows how you can create a new time entry in Keeping.

Import the required dependancies:
```php
use KBit\LaravelKeeping\KeepingClient;
use KBit\LaravelKeeping\Model\TimeEntry;
```

Setup a connection with Keeping:
```php

```

As a simple example, we now retrieve all the available projects,
grab the first of them, and create a time entry for that project.:

See:
https://developer.keeping.nl/#tag/reports/paths/~1{organisation_id}~1report/get

```php
$keepingClient = new KeepingClient();

// If you don't provide an ID to the getUser function as the first parameter,
// your own user (== the one who is attached to your authorisation token) will
// be returned as a default. 
$user = $keepingClient->getUser();
$project = $keepingClient->getProjects()->first();

// Create a time entry
$entry = new TimeEntry([
    'user_id' => $user->id,
    'project_id' => $project->id,
    'date' => (new \DateTime)->format('Y-m-d'),
    'purpose' => 'work',
    'note' => 'Creating a new entry with laravel-keeping',
    'hours' => 1,
]);

$keepingClient->postTimeEntry($entry);
```

---

### Example for getting a client-based report
The example below retrieves a report for a specific client.
Please refer to the Keeping API documentation for a complete list of possible query options.

Import the required dependancies:
```php
use KBit\LaravelKeeping\KeepingClient;
use KBit\LaravelKeeping\Model\Report;
```

Retrieve the report data:

```php
$clientId = 12345;

$reportQuery = [
  'from' => '2021-01-01',
  'to' => '2021-12-31',
  'row_type' => Report::REPORT_ROW_TYPE_MONTH,
  'client_ids' => [$clientId],
  'page' => 1,
  'per_page' => 100,
];

// Retrieve an overview of the hours made in each month
$summary = $keepingClient->getReport($reportQuery);

// Retrieves a Collection with TimeEntry models for all the entries that match the specified query variables.
$timeEntriesCollection = $keepingClient->getReportTimeEntries($reportQuery);
```
---
#### TODO:
This package supports all of the API endpoints that are currently provided by the Keeping. There are some improvements that can be made in the future:

* Adding support for Keeping's paginated responses
* Adding validation to the query parameters arrays for various endpoints
