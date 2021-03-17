<?php

namespace KBit\LaravelKeeping\Exceptions;

use Exception;
use Illuminate\Http\Response;

class NoOrganisationException extends Exception
{
  protected $message = 'No organisation set';
  protected $code = Response::HTTP_INTERNAL_SERVER_ERROR;

  public function report ()
  {

  }
}
