<?php

namespace KBit\LaravelKeeping\Model;

use Jenssegers\Model\Model;

class OrganisationFeatures extends Model
{
  protected $fillable = [
    'timesheet',
    'projects',
    'tasks',
    'breaks'
  ];
}
