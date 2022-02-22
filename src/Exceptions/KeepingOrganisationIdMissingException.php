<?php

namespace KBit\LaravelKeeping\Exceptions;

use Exception;
use Illuminate\Http\Response;

class KeepingOrganisationIdMissingException extends Exception
{
  protected $message = 'Could not find Keeping organisation. Please ensure that the KEEPING_ORGANISATION_ID key is set correctly in your .env';
  protected $code = Response::HTTP_BAD_REQUEST;

  public function report ()
  {
    // TODO: implement if desired
  }
}
