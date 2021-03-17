<?php

namespace KBit\LaravelKeeping\Model;

use Jenssegers\Model\Model;

class Client extends Model
{
  protected $fillable = [
    'id',
    'name',
    'code',
    'state',
  ];
}
