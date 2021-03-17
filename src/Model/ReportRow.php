<?php

namespace KBit\LaravelKeeping\Model;

use Jenssegers\Model\Model;

class ReportRow extends Model
{
  protected $fillable = [
    'id',
    'description',
    'url',
    'hours',
    'direct_hours',
  ];
}
