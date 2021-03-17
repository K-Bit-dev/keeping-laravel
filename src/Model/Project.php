<?php

namespace KBit\LaravelKeeping\Model;

use Jenssegers\Model\Model;

class Project extends Model
{
  protected $fillable = [
    'id',
    'client',
    'client_id',
    'name',
    'code',
    'direct',
    'state'
  ];
}
