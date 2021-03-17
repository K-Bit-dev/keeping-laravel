<?php

namespace KBit\LaravelKeeping\Exceptions;

use Exception;
use Illuminate\Http\Response;

class KeepingException extends Exception
{
  protected $message = 'The request to keeping did not succeed';
  protected $code = Response::HTTP_BAD_REQUEST;

  public function report ()
  {
    // TODO: implement if desired
  }
}
