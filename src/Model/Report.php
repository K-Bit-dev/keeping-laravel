<?php

namespace KBit\LaravelKeeping\Model;

use Jenssegers\Model\Model;

class Report extends Model
{
  const REPORT_ROW_TYPE_Project = 'project';
  const REPORT_ROW_TYPE_TASK = 'task';
  const REPORT_ROW_TYPE_CLIENT = 'client';
  const REPORT_ROW_TYPE_USER = 'user';
  const REPORT_ROW_TYPE_EXTERNAL_REFERENCE = 'external_reference';
  const REPORT_ROW_TYPE_EXTERNAL_REFERENCE_TYPE = 'external_reference_type';
  const REPORT_ROW_TYPE_DAY = 'day';
  const REPORT_ROW_TYPE_WEEK = 'week';
  const REPORT_ROW_TYPE_MONTH = 'month';
  const REPORT_ROW_TYPE_QUARTER = 'quarter';
  const REPORT_ROW_TYPE_YEAR = 'year';

  protected $fillable = [
    'from',
    'to',
    'row_type',
    'rows',
    'total_hours',
    'total_direct_hours',
  ];
}
