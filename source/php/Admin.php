<?php

namespace adApiWpIntegration;

class Admin
{

    private $message = array();

    public function __construct()
    {
        //Display error message (not defined)
        if (!defined('AD_INTEGRATION_URL')) {
            $this->storeMessage(__("Please add AD_INTEGRATION_URL to this sites configuration file(s).", 'adintegration'));
        }

        //Display error message (not valid url)
        if (filter_var(AD_INTEGRATION_URL, FILTER_VALIDATE_URL) === false) {
            $this->storeMessage(__("The AD_INTEGRATION_URL provided is not a properly formatted url.", 'adintegration'));
        }

        $this->render();
    }

    private function render()
    {
        add_action('admin_notices', function () {
            if (is_array($this->message) && !empty($this->message)) {
                echo '<div class="error notice">';
                echo '<ul>';
                foreach ($this->message as $message) {
                    echo '<li>' . $message . '</li>';
                }
                echo '</ul>';
                echo '</div>';

                $this->message = array();
            }
        });
    }

    private function storeMessage($message)
    {
        $this->message[] = $message;
    }
}
