<?php

namespace KBit\LaravelKeeping\Model;

use Jenssegers\Model\Model;

class Task extends Model
{
  protected $fillable = [
    'id',
    'name',
    'code',
    'direct',
    'state',
  ];
}
