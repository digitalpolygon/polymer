<?php

namespace DigitalPolygon\Polymer\Robo\Exceptions;

/**
 * Custom reporting and error handling for exceptions.
 *
 * @package Acquia\Blt\Robo\Exceptions
 */
class PolymerException extends \Exception
{
  /**
   * Report exception.
   */
    public function __construct(
        string $message = "",
        int $code = 0,
        \Throwable $previous = null
    ) {

        $message .= "\nFor troubleshooting guidance and support, see https://github.com/digitalpolygon/polymer";
        parent::__construct($message, $code, $previous);
    }
}
