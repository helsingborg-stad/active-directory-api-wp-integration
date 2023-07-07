<?php

namespace adApiWpIntegration;

class LoginHoneyPot
{
    private $fieldMinTime = ""; //ms
    private $fieldName = ""; 

    /**
     * Hook nonce validation
     * @return void
     */
    public function __construct()
    {
        //Define AD_HP_VALIDATION to false to disable validation
        if(defined('AD_HP_VALIDATION') && AD_HP_VALIDATION === false) {
            return false;
        }

        $this->fieldMinTime     = 457; 
        $this->fieldName        = '_ad_hp_' . substr(md5(AUTH_KEY), 5, 10);

        add_action('login_form', array($this, 'renderHoneyPot'), 15);
        add_action('wp_authenticate', array($this, 'validateHoneyPot'), 15); // Ad login priority = 20
    }

    /**
     * Render the nonce field
     * @return void
     */
    public function renderHoneyPot()
    {
        echo '<style>.fake-hide {width: 1px; height: 1px; opacity: 0.0001; position: absolute; overflow: hidden;}</style>';
        echo '
            <div class="fake-hide">
                <input 
                        class="ad-login-field" 
                        type="text" 
                        name="'. $this->fieldName .'"
                        autocomplete="off" 
                        tabIndex="-1"
                        aria-hidden="true"
                />
            </div>
        ';
        echo '
            <script type="text/javascript">
                ["onload"].forEach(function(e){
                    [].forEach.call(document.querySelectorAll(".ad-login-field"), function(item) {
                        setTimeout(function() {
                            item.value = "' . $this->fieldMinTime . '"; 
                        }.bind(item), ' . $this->fieldMinTime . '); 
                    });
                });
            </script>
        ';
    }

    /**
     * Check the nonce before running login procedure, must use a hook lower than 20 (ad hijack).
     * @return void / bool
     */
    public function validateHoneyPot($username = "")
    {
        if (isset($_POST) && is_array($_POST) && !empty($_POST)) {
            if (isset($_POST[$this->fieldName])) {
                if($_POST[$this->fieldName] == $this->fieldMinTime) {
                    return true;
                }
            }
            wp_die(__("Could not verify that you are not a bot. <a href='/wp-login.php'>Please try again.</a>", 'adintegration'));
        }
    }
}
