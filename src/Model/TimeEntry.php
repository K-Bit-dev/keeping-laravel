<?php

namespace KBit\LaravelKeeping\Model;

use Jenssegers\Model\Model;

class TimeEntry extends Model
{
  protected $fillable = [
    'id',
    'user_id',
    'date',
    'purpose',
    'project_id',
    'task_id',
    'note',
    'external_references',
    'start',
    'end',
    'hours',
    'ongoing',
    'locked'
  ];

  protected $casts = [
    'start' => 'datetime',
    'end' => 'datetime',
  ];
}
