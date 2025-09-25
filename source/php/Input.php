<?php

namespace adApiWpIntegration;

use adApiWpIntegration\Contracts\InputHandlerInterface;

/**
 * Input handling service implementation.
 * 
 * This class follows the Single Responsibility Principle by handling only
 * input sanitization and retrieval. It implements the InputHandlerInterface
 * to allow for easy testing and extensibility.
 */
class Input implements InputHandlerInterface
{
  private $postVars = array(
    'pwd' => null,
    'rememberme' => null,
    '_wp_http_referer' => null,
    '_ad_nonce' => null,
    '_wpnonce' => null
  );

  private $getVars = array(
    'adbulkimport' => null,
    'adbulkprofile' => null,
    'adbulkpropagate' => null,
    'maxDeletelimit' => null,
    'adcleanusers' => null,
    'adcleanmeta' => null,
    'adcleancap' => null
  );

  public function __construct()
  {
    $this->postVars = array_merge($this->postVars, $_POST ?? []);
    $this->getVars  = array_merge($this->getVars, $_GET ?? []);
  }

  public function post($key)
  {
    return isset($this->postVars[$key]) ? sanitize_text_field($this->postVars[$key]) : null;
  }

  public function get($key)
  {
    return isset($this->getVars[$key]) ? sanitize_text_field($this->getVars[$key]) : null;
  }

}