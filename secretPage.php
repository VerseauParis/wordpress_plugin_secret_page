<?php
/*
Plugin Name: SecretePage
Description: This plugin permit to centralize an access on secrets pages by codes.
Author: Damien Martin
Version: 1.1.1
Licensed under the MIT license
See LICENSE.txt file  or opensource.org/licenses/MIT
Copyright (c) 2013 VerseauParis
*/

require_once('define.php');

//////////////////////////////////////////
//          INSTALL / UNINSTALL         //
//////////////////////////////////////////
/* Plugin Activation
 * - Create table and save DB var access
 */
function dm_install() {
    global $wpdb;
    $table = $wpdb->prefix."rm";
    $structure = "CREATE TABLE $table (
        id INT(9) NOT NULL AUTO_INCREMENT,
        dm_email VARCHAR(200) NOT NULL,
    UNIQUE KEY id (id)
    );";
    $wpdb->query($structure);
}
register_activation_hook( __FILE__, 'dm_install' );

/* Plugin Deactivation 
 * - Delete ref to DB var access
 */
function dm_uninstall() {
    global $wpdb; 
}
register_deactivation_hook( __FILE__, 'dm_uninstall' );

//////////////////////////////////////////
//               LEFT MENU              //
//////////////////////////////////////////
// Left Menu Button
function register_dm_menu() {
    add_menu_page('Subscribers', SUBSCRIBERS , 'add_users', dirname(__FILE__).'/index.php', '',   '', 58.122);
}
add_action('admin_menu', 'register_dm_menu');


//////////////////////////////////////////
//               SHORTCODES             // 
//////////////////////////////////////////
// Shortcodes
function dm_manage_page($atts, $content = null ) {

    $returnVal = "";
    if(is_RMCode_Correct('secret_code') ) {
        $returnVal = $content;
    }
    else {
        $options = get_option('dm_options');
        $returnVal .= redirectTo("http://".$_SERVER["HTTP_HOST"]."/".$options['home_page']);
        $returnVal .= dm_unauthorizedView();
    }
    return $returnVal;
}

function dm_manage_access_page() {
    $returnVal = "";
    if(isset($_REQUEST['code']) ) {
        $code = $_REQUEST['code'];

        $urlRedirect = get_RMUrl($code);
        if($urlRedirect !== false) {
            $params = array();
            $params['secret_code'] = $code;
            $returnVal .= redirectTo("http://".$_SERVER["HTTP_HOST"].$urlRedirect, 'POST', $params);
        }
        else {
            $returnVal .= '<div class="wrongCodeLabel"> '.WRONG_CODE_LABEL.' </div>';
        }
    }

    $returnVal .= "<div class='accessContainer'>";
    $returnVal .= '<form class="dm_subscribe" method="POST"><input class="dm_hiddenfield" name="dm_subscribe" type="hidden" value="1">';    
    $user_email = "";
    if(isset($_REQUEST['dm_email'])) {
        $user_email = $_REQUEST['dm_email'];
    }
    $returnVal .= '<p class="dm_email"><label class="dm_emaillabel" for="dm_email">'.EMAIL_LABEL.' : </label><input class="dm_emailinput" name="dm_email" placeholder="'.EMAIL_PLACEHOLDER.'" type="email" value="'.$user_email.'" required></p>';
    $returnVal .= "<p class='dm_code'><label class='labelCode' for='code'>".CODE_LABEL." : </label><input class='inputCode' id='code' name='code' type='text' placeholder='".CODE_PLACEHOLDER."' required/><br/></p>";
    $returnVal .= "<p class='dm_submit'><input class='submit_button' type='submit'/></p>";
    $returnVal .= "</form>";
    $returnVal .= "</div>";

    return $returnVal;
}

//////////////////////////////////////////
//               TOOLS                  // 
//////////////////////////////////////////
// TOOLS
function get_RMUrl($code) {
    $settings = new RMSettings();
    $options = get_option('dm_options');
    $codes = array();
    
    for($i = 0 ; $i < $settings->max_number_secrets_codes; $i++) {
        $codes[$options['code'.$i]] = "/".$options['url'.$i]; 
    }

    if(isset($codes[$code])) {
        return $codes[$code];
    }
    return false;
}

function is_RMCode_Correct($param_name) {
    $settings = new RMSettings();
    $options = get_option('dm_options');
    $URLs = array();
    $secret_code = $_REQUEST[$param_name];

    for($i = 0 ; $i < $settings->max_number_secrets_codes; $i++) {
        $URLs["/".$options['url'.$i]] = $options['code'.$i]; 
    }

    if($secret_code) {
        if($URLs[dm_currentPageUrl()] == $secret_code ) {
            return true;
        }
        else {
            return false;
        }
    }
    else {
        return false;
    }
}

function redirectTo($location, $method = 'GET',$args = null) {
    if($method == 'GET') {
        $javascriptCode =  '<script type="text/javascript">';
        $javascriptCode .= 'window.location = "'.$location.'"';
        $javascriptCode .= '</script>';
        return $javascriptCode;
    }
    else {

        $javascriptCode =   "<form method='post' action='".$location."' id='RMForm'>\n";
        foreach($args as $key => $value) { 
            $javascriptCode .=  "<input type='hidden' value='".$value."' name='".$key."'>\n";
        }
        $javascriptCode .=  "</form>\n";
        $javascriptCode .=  "<script type='text/javascript'>\n";
        $javascriptCode .=  "function formSend(){\n";
        $javascriptCode .=  "    f=document.getElementById('RMForm');\n";
        $javascriptCode .=  "    if(f){\n";
        $javascriptCode .=  "        f.submit();\n";
        $javascriptCode .=  "    }\n";
        $javascriptCode .=  "}\n";
        $javascriptCode .=  "formSend()\n";
        $javascriptCode .=  "</script>\n";
        return $javascriptCode;   
    }
}

function dm_currentPageUrl() {
    $currentURL = $_SERVER["REQUEST_URI"];
    $currentURL = explode("?", $currentURL)[0];
    return $currentURL;
}

function dm_unauthorizedView() {
    return "<div class='unauthorizedPage'> Unauthorized page </div>";
}

// Register
function dm_register_shortcodes() {
    add_shortcode(SHORTCODES_SECRETE_PAGE, 'dm_manage_page');
    add_shortcode(SHORTCODES_HOMEPAGE, 'dm_manage_access_page');
}
add_action( 'init', 'dm_register_shortcodes');

//////////////////////////////////////////
//            CLASS SETTINGS            // 
//////////////////////////////////////////
/* Settings */
class RMSettings {
    public $max_number_secrets_codes;

    public function __construct() {
        $this->max_number_secrets_codes = NUMBER_OF_SECRET_CODES;
    }
}

class RMSettingsPage {
    private $options;
    private $settings; // Class settings with values etc
    
    public function __construct() {
        $this->settings = new RMSettings();
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }
    
    /**
     * Add options page
     * under Settings in admin part
     */
    public function add_plugin_page() {
        add_options_page(
            'Settings', 
            PLUGIN_SETTINGS_NAME_DISPLAYED, 
            'manage_options', 
            'rm-setting-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    public function create_admin_page() {
        $this->options = get_option( 'dm_options' );

        //crapy ^^
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php echo SETTINGS_TITLE_LABEL ?></h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'dm_option_group' );   
                do_settings_sections( 'rm-setting-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'dm_option_group', // Option group
            'dm_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        /* SECTION : DESCRIPTION / TUTOS */
        add_settings_section(
            'settings_section_description', // ID
            'Utilisation du plugin', // Title
            array( $this, 'print_section_description' ), // Callback
            'rm-setting-admin' // Page
        );

        
        /* SECTION : GENERAL */
        add_settings_section(
            'settings_section_0', // ID
            GENERAL_SETTINGS_LABEL, // Title
            array( $this, 'print_section_info' ), // Callback
            'rm-setting-admin' // Page
        );

        add_settings_field(
           'urlCode', // ID
            HOME_PAGE_SETTINGS_LABEL, // Title 
            array( $this, 'general_settings_callback'), // Callback
            'rm-setting-admin', // Page
            'settings_section_0', // Section
            array()
        );


        /* SECTION : SECRET CODE */
        add_settings_section(
            'setting_section_id', // ID
            URLS_CODE_SETTINGS_LABEL,
            array( $this, 'print_section_info' ), // Callback
            'rm-setting-admin' // Page
        );

        for ($i = 1 ; $i <= $this->settings->max_number_secrets_codes ; $i++) {

            add_settings_field(
                'urlCode'.$i, // ID
                $i.' - ', // Title 
                array( $this, 'urlcode_callback'), // Callback
                'rm-setting-admin', // Page
                'setting_section_id', // Section
                array("idx"=>$i)
          );
        }
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {
        $new_input = array();
        for ($i = 1 ; $i <= $this->settings->max_number_secrets_codes ; $i++) {
            if( isset( $input['url'.$i] ) ) {
                $new_input['url'.$i] = sanitize_text_field( $input['url'.$i] );
            }

            if( isset( $input['code'.$i] ) ) {
                $new_input['code'.$i] = sanitize_text_field( $input['code'.$i] );
            }
        }

        if( isset( $input['home_page'] ) ) {
            $new_input['home_page'] = sanitize_text_field( $input['home_page'] );
        }

        return $new_input;
    }

    /* Sections print */
    public function print_section_info() {}
    public function print_section_description()  {
        printf('
            <p>
                Use the marker <i><b>['.SHORTCODES_HOMEPAGE.']</b></i> on the page where you want to display your access form. 
            </p>
            <p>
                Use the marker <i><b>['.SHORTCODES_SECRETE_PAGE.']</b></i> on pages where you want to protected content by code (defined on this page)<br/> 
                
                To protect content, you have to put the content on the marker like this : <br/> 
                <i><b>['.SHORTCODES_SECRETE_PAGE.']</b> - HTML CONTENT - <b>[/'.SHORTCODES_SECRETE_PAGE.']</b></i><br/>
                </code>
            </p>
            ');
    }

    /* Fields print */
    public function urlcode_callback($args) {
        $idx = $args['idx'];
        printf(
            ' Url : '.$_SERVER["HTTP_HOST"].'/<input type="text" id="url'.$idx.'" name="dm_options[url'.$idx.']" value="%s" placeholder="Enter URL here" /> code : <input type="text" id="code'.$idx.'" name="dm_options[code'.$idx.']" value="%s" placeholder="Enter code here" /> ',
            isset( $this->options['url'.$idx] ) ? esc_attr( $this->options['url'.$idx]) : '',
            isset( $this->options['code'.$idx] ) ? esc_attr( $this->options['code'.$idx]) : ''
        );
    }
    public function general_settings_callback($args) {
        printf(
            ' Url : '.$_SERVER["HTTP_HOST"].'/<input type="text" id="home_page" name="dm_options[home_page]" value="%s" placeholder="Enter URL here" />',
            isset( $this->options['home_page'] ) ? esc_attr( $this->options['home_page']) : '' 
        );
    }
    public function title_callback() {
        printf(
            '<input type="text" id="title" name="dm_options[title]" value="%s" />',
            isset( $this->options['title'] ) ? esc_attr( $this->options['title']) : ''
        );
    }
}


//////////////////////////////////////////
//               main()                 //
//////////////////////////////////////////
/*
 * Init if is admin 
 */
if( is_admin() ) {
    $my_settings_page = new RMSettingsPage();
}

/* Handle form post
 * - Insert email on DB
 */
if ($_POST['dm_subscribe']) {
    $email = $_POST['dm_email'];
    if (is_email($email)) {
        $query = "SELECT * FROM ".$wpdb->prefix."rm where dm_email like '".$wpdb->escape($email)."' limit 1";
        $exists = mysql_query($query);
        if (mysql_num_rows($exists) < 1) {
            $wpdb->query("insert into ".$wpdb->prefix."rm (dm_email) values ('".$wpdb->escape($email)."')");
        }
    }
}
