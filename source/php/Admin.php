<?php

namespace adApiWpIntegration;

class Admin
{

    private $message = array();

    /**
     * Define error messages on configuration errors.
     * @return void
     */
    public function __construct()
    {
        //Display error message (not defined)
        if (!defined('AD_INTEGRATION_URL')) {
            $this->storeMessage(__("Please add AD_INTEGRATION_URL to this sites configuration file(s).", 'adintegration'));
        }

        //Display error message (not valid url)
        if (filter_var(AD_INTEGRATION_URL, FILTER_VALIDATE_URL) === false) {
            $this->storeMessage(__("The AD_INTEGRATION_URL provided is not a properly formatted url (should be with https:// and pointing at the base directory of the api).", 'adintegration'));
        }

        //Both cannot be active
        if (AD_RANDOM_PASSWORD === true && AD_SAVE_PASSWORD === true) {
            $this->storeMessage(__("The AD_RANDOM_PASSWORD and AD_SAVE_PASSWORD constants cannot be true at the same time.", 'adintegration'));
        }

        $this->render();
    }

    /**
     * Render active error messages
     * @return void
     */
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

    /**
     * Add error messages to error array
     * @return void
     */
    private function storeMessage($message)
    {
        $this->message[] = $message;
    }
}
