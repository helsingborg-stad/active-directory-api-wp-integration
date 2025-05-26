<?php

namespace adApiWpIntegration;

class Input
{
  private $postVars = array(
    'username' => null,
    'password' => null,
    'rememberme' => null,
    '_wp_http_referer' => null,
    '_wpnonce' => null,
    'pwd' => null
  );

  private $getVars = array(
    'action' => null,
    'redirect_to' => null,
    'error' => null,
    'login' => null,
    'loggedout' => null
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