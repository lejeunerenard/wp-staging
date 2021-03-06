<?php
namespace WPStaging\Backend\Modules;

use WPStaging\DI\InjectionAware;
use WPStaging\Library\Browser;
use WPStaging\WPStaging;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

/**
 * Class SystemInfo
 * @package WPStaging\Backend\Modules
 */
class SystemInfo extends InjectionAware
{

    /**
     * @var bool
     */
    private $isMultiSite;

    /**
     * Initialize class
     */
    public function initialize()
    {
        $this->isMultiSite = is_multisite();
    }

    /**
     * Magic method
     * @return string
     */
    public function __toString()
    {
        return $this->get();
    }

    /**
     * Get System Information as text
     * @return string
     */
    public function get()
    {
        $output  = "### Begin System Info ###" . PHP_EOL . PHP_EOL;

        $output .= $this->site();

        $output .= $this->browser();

        $output .= $this->wp();

        $output .= $this->plugins();

        $output .= $this->multiSitePlugins();

        $output .= $this->server();

        $output .= $this->php();

        $output .= $this->phpExtensions();

        $output .= PHP_EOL . "### End System Info ###";

        return $output;
    }

    /**
     * @param string $string
     * @return string
     */
    public function header($string)
    {
        return PHP_EOL . "-- {$string}" . PHP_EOL . PHP_EOL;
    }

    /**
     * Formating title and the value
     * @param string $title
     * @param string $value
     * @return string
     */
    public function info($title, $value)
    {
        return str_pad($title, 26, ' ', STR_PAD_RIGHT) . $value . PHP_EOL;
    }

    /**
     * Theme Information
     * @return string
     */
    public function theme()
    {
        // Versions earlier than 3.4
        if (get_bloginfo("version") < "3.4" )
        {
            $themeData = get_theme_data(get_stylesheet_directory() . "/style.css");
            return "{$themeData["Name"]} {$themeData["Version"]}";
        }

        $themeData = wp_get_theme();
        return "{$themeData->Name} {$themeData->Version}";
    }

    /**
     * Site Information
     * @return string
     */
    public function site()
    {
        $output  = "-- Site Info" . PHP_EOL . PHP_EOL;
        $output .= $this->info("Site URL:", site_url());
        $output .= $this->info("Home URL:", home_url());
        $output .= $this->info("Home Path:", get_home_path());
        $output .= $this->info("Installed in subdir:", ( $this->isSubDir() ? 'Yes' : 'No' )) ;
        $output .= $this->info("Multisite:", ($this->isMultiSite ? "Yes" : "No" ));

        return apply_filters("wpstg_sysinfo_after_site_info", $output);
    }

    /**
     * Browser Information
     * @return string
     */
    public function browser()
    {
        $output  = $this->header("User Browser");
        $output .= (new Browser);

        return apply_filters("wpstg_sysinfo_after_user_browser", $output);
    }

    /**
     * Frontpage Information when frontpage is set to "page"
     * @return string
     */
    public function frontPage()
    {
        if (get_option("show_on_front") !== "page")
        {
            return '';
        }

        $frontPageID  = get_option("page_on_front");
        $blogPageID   = get_option("page_for_posts");

        // Front Page
        $pageFront    = ($frontPageID != 0) ? get_the_title($frontPageID) . " (#{$frontPageID})" : "Unset";
        // Blog Page ID
        $pageBlog     = ($blogPageID != 0) ? get_the_title($blogPageID) . " (#{$blogPageID})" : "Unset";

        $output  = $this->info("Page On Front:", $pageFront);
        $output .= $this->info("Page For Posts:", $pageBlog);

        return $output;
    }

    /**
     * Check wp_remote_post() functionality
     * @return string
     */
    public function wpRemotePost()
    {
        // Make sure wp_remote_post() is working
        $wpRemotePost = "wp_remote_post() does not work";

        // Send request
        $response = wp_remote_post(
            "https://www.paypal.com/cgi-bin/webscr",
            array(
                "sslverify"     => false,
                "timeout"       => 60,
                "user-agent"    => "WPSTG/" . WPStaging::VERSION,
                "body"          => array("cmd" => "_notify-validate")
            )
        );

        // Validate it worked
        if (!is_wp_error($response) && 200 <= $response["response"]["code"] && 300> $response["response"]["code"])
        {
            $wpRemotePost = "wp_remote_post() works";
        }

        return $this->info("Remote Post:", $wpRemotePost);
    }

    /**
     * WordPress Configuration
     * @return string
     */
    public function wp()
    {
        $output  = $this->header("WordPress Configuration");
        $output .= $this->info("Version:", get_bloginfo("version"));
        $output .= $this->info("Language:", (defined("WPLANG") && WPLANG) ? WPLANG : "en_US");

        $permalinkStructure = get_option("permalink_structure");;
        $output .= $this->info("Permalink Structure:", ($permalinkStructure) ? $permalinkStructure : "Default");

        $output .= $this->info("Active Theme:", $this->theme());
        $output .= $this->info("Show On Front:", get_option("show_on_front"));

        // Frontpage information
        $output .= $this->frontPage();

        // WP Remote Post
        $output .= $this->wpRemotePost();

        // Table Prefix
        $wpDB           = $this->di->get("wpdb");
        $tablePrefix    = "Length: " . strlen($wpDB->prefix) . "   Status: ";
        $tablePrefix   .= (strlen($wpDB->prefix) > 16) ? "ERROR: Too long" : "Acceptable";

        $output .= $this->info("Table Prefix:", $tablePrefix);

        // WP Debug
        $output .= $this->info("WP_DEBUG:", (defined("WP_DEBUG")) ? WP_DEBUG ? "Enabled" : "Disabled" : "Not set");
        $output .= $this->info("Memory Limit:", WP_MEMORY_LIMIT);
        $output .= $this->info("Registered Post Stati:", implode(", ", \get_post_stati()));

        return apply_filters("wpstg_sysinfo_after_wpstg_config", $output);
    }

    /**
     * List of Active Plugins
     * @param array $plugins
     * @param array $activePlugins
     * @return string
     */
    public function activePlugins($plugins, $activePlugins)
    {
        $output  = $this->header("WordPress Active Plugins");

        foreach ($plugins as $path => $plugin)
        {
            if (!in_array($path, $activePlugins))
            {
                continue;
            }

            $output .= "{$plugin["Name"]}: {$plugin["Version"]}" . PHP_EOL;
        }

        return apply_filters("wpstg_sysinfo_after_wordpress_plugins", $output);
    }

    /**
     * List of Inactive Plugins
     * @param array $plugins
     * @param array $activePlugins
     * @return string
     */
    public function inactivePlugins($plugins, $activePlugins)
    {
        $output  = $this->header("WordPress Inactive Plugins");

        foreach ($plugins as $path => $plugin)
        {
            if (in_array($path, $activePlugins))
            {
                continue;
            }

            $output .= "{$plugin["Name"]}: {$plugin["Version"]}" . PHP_EOL;
        }

        return apply_filters("wpstg_sysinfo_after_wordpress_plugins_inactive", $output);
    }

    /**
     * Get list of active and inactive plugins
     * @return string
     */
    public function plugins()
    {
        // Get plugins and active plugins
        $plugins        = get_plugins();
        $activePlugins  = get_option("active_plugins", array());

        // Active plugins
        $output  = $this->activePlugins($plugins, $activePlugins);
        $output .= $this->inactivePlugins($plugins, $activePlugins);

        return $output;
    }

    /**
     * Multisite Plugins
     * @return string
     */
    public function multiSitePlugins()
    {
        if (!$this->isMultiSite)
        {
            return '';
        }

        $output = $this->header("Network Active Plugins");

        $plugins        = wp_get_active_network_plugins();
        $activePlugins  = get_site_option("active_sitewide_plugins", array());

        foreach ($plugins as $pluginPath)
        {
            $pluginBase = plugin_basename($pluginPath);

            if (!array_key_exists($pluginBase, $activePlugins))
            {
                continue;
            }

            $plugin  = get_plugin_data($pluginPath);

            $output .= "{$plugin["Name"]}: {$plugin["Version"]}" . PHP_EOL;
        }
        unset($plugins, $activePlugins);

        return $output;
    }

    /**
     * Server Information
     * @return string
     */
    public function server()
    {
        // Server Configuration
        $output  = $this->header("Webserver Configuration");

        $output .= $this->info("PHP Version:", PHP_VERSION);
        $output .= $this->info("MySQL Version:", $this->di->get("wpdb")->db_version());
        $output .= $this->info("Webserver Info:", $_SERVER["SERVER_SOFTWARE"]);

        return apply_filters("wpstg_sysinfo_after_webserver_config", $output);
    }

    /**
     * PHP Configuration
     * @return string
     */
    public function php()
    {
        $output  = $this->header("PHP Configuration");
        $output .= $this->info("Safe Mode:", ($this->isSafeModeEnabled() ? "Enabled" : "Disabled"));
        $output .= $this->info("Memory Limit:", ini_get("memory_limit"));
        $output .= $this->info("Upload Max Size:", ini_get("upload_max_filesize"));
        $output .= $this->info("Post Max Size:", ini_get("post_max_size"));
        $output .= $this->info("Upload Max Filesize:", ini_get("upload_max_filesize"));
        $output .= $this->info("Time Limit:", ini_get("max_execution_time"));
        $output .= $this->info("Max Input Vars:", ini_get("max_input_vars"));

        $displayErrors = ini_get("display_errors");
        $output .= $this->info("Display Errors:", ($displayErrors) ? "On ({$displayErrors})" : "N/A");

        return apply_filters("wpstg_sysinfo_after_php_config", $output);
    }

    /**
     * Check if PHP is on Safe Mode
     * @return bool
     */
    public function isSafeModeEnabled()
    {
        return (
            version_compare(PHP_VERSION, "5.4.0", '<') &&
            @ini_get("safe_mode")
        );
    }

    /**
     * Checks if function exists or not
     * @param string $functionName
     * @return string
     */
    public function isSupported($functionName)
    {
        return (function_exists($functionName)) ? "Supported" : "Not Supported";
    }

    /**
     * Checks if class or extension is loaded / exists to determine if it is installed or not
     * @param string $name
     * @param bool $isClass
     * @return string
     */
    public function isInstalled($name, $isClass = true)
    {
        if (true === $isClass)
        {
            return (class_exists($name)) ? "Installed" : "Not Installed";
        }
        else
        {
            return (extension_loaded($name)) ? "Installed" : "Not Installed";
        }
    }

    /**
     * Gets Installed Important PHP Extensions
     * @return string
     */
    public function phpExtensions()
    {
        // Important PHP Extensions
        $output  = $this->header("PHP Extensions");
        $output .= $this->info("cURL:", $this->isSupported("curl_init"));
        $output .= $this->info("fsockopen:", $this->isSupported("fsockopen"));
        $output .= $this->info("SOAP Client:", $this->isInstalled("SoapClient"));
        $output .= $this->info("Suhosin:", $this->isInstalled("suhosin", false));

        return apply_filters("wpstg_sysinfo_after_php_ext", $output);
    }
    
    /**
     * Check if WP is installed in subdir
     * @return boolean
     */
    private function isSubDir(){
        if ( get_option( 'siteurl' ) !== get_option( 'home' ) ) { 
            return true;
        }
        return false;
    }
}