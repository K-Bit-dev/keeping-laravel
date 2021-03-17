<?php

namespace KBit\LaravelKeeping\Model;

use Jenssegers\Model\Model;

class Organisation extends Model
{
  protected $fillable = [
    'id',
    'name',
    'url',
    'current_plan',
    'features',
    'time_zone',
    'currency',
  ];
}
