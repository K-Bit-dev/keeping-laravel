<?php

namespace KBit\LaravelKeeping\Model;

use Jenssegers\Model\Model;

class User extends Model
{
  protected $fillable = [
    'id',
    'first_name',
    'sur_name',
    'code',
    'role',
    'state',
  ];
}
