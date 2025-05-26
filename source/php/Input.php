<?php

namespace adApiWpIntegration;

class Input
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