<?php
/**
 * Plugin Name: Dashboard log monitor
 * Plugin URI: https://github.com/Seravo/wp-dashboard-log-monitor
 * Description: Take a sneak peek on your access logs from the wordpress dashboard.
 * Author: Onni Hakala / Seravo Oy
 * Author URI: http://seravo.fi
 * Version: 1.0.3
 * License: GPLv2 or later
*/
/** Copyright 2014 Seravo Oy
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.
  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

define('__ROOT__', dirname(__FILE__));

require_once __ROOT__."/log-parser/src/Kassner/LogParser/FormatException.php";
require_once __ROOT__."/log-parser/src/Kassner/LogParser/LogParser.php";

function load_custom_wp_admin_style() {
    // Only allow admins to use this
    if (!current_user_can('activate_plugins')) { return; }
    
    wp_register_style( 'custom_wp_admin_css', plugins_url('/admin-style.css', __FILE__), false, '1.0.0' );
    wp_enqueue_style( 'custom_wp_admin_css' );
}
add_action( 'admin_enqueue_scripts', 'load_custom_wp_admin_style' );

add_action('wp_dashboard_setup', array('Dashboard_Log_Monitor_Widget','init') );

class Dashboard_Log_Monitor_Widget {

    /**
     * The id of this widget.
     */
    const wid = 'dashboard_log_monitor';
    /** By Default exclude status codes:
     * - successful requests
     * - redirects
     * - not modified
     * - 499 because nginx prints these everytime when wp-cron is activated
     */
    const default_exclude = "200,301,302,304,499";
    const default_line_count = 10;
    const default_extended_info = false;
    const default_access_log_path = '/data/log/nginx-access.log';
    const default_access_log_format = '%h %a %{User-Identifier}i %u %t "%r" %>s %b "%{Referer}i" "%{User-Agent}i" %{Cache-Status}i %T';

    /**
     * Hook to wp_dashboard_setup to add the widget.
     */
    public static function init() {
        // Only allow admins to use this
        if (!current_user_can('activate_plugins')) { return; }

        //Register widget settings...
        self::update_dashboard_widget_options(
            self::wid,                                  //The  widget id
            array(                                      //Associative array of options & default values
                'line_count' => self::default_line_count,
                'exclude_status_codes' => self::default_exclude,
                'extended_info' => self::default_extended_info,
                'access_log_path' => self::default_access_log_path,
                'access_log_format' => self::default_access_log_format
            ),
            true                                        //Add only (will not update existing options)
        );

        //Register the widget...
        wp_add_dashboard_widget(
            self::wid,                                  //A unique slug/ID
            __( 'Log monitor', 'nouveau' ),      //Visible name for the widget
            array('Dashboard_Log_Monitor_Widget','widget'),      //Callback for the main widget content
            array('Dashboard_Log_Monitor_Widget','config')       //Optional callback for widget configuration content
        );
    }

    /**
     * Load the widget code
     */
    public static function widget() {
        $lines = self::last_log_lines(self::get_dashboard_widget_option(self::wid, 'access_log_path'), 1,self::get_dashboard_widget_option(self::wid, 'access_log_format'));
        if (!file_exists(self::get_dashboard_widget_option(self::wid, 'access_log_path')))
            echo "logfile: ".self::get_dashboard_widget_option(self::wid, 'access_log_path')." not found!";
        elseif (gettype($lines[0]) == "string") {
            ?>
        <p><?php _e("Log format has problems."); ?></p>
        <p><?php _e("Format is:"); ?></p>
        <p><?php echo self::get_dashboard_widget_option(self::wid, 'access_log_format') ?></p>
        <p><?php _e("Log lines look like:"); ?></p>
        <p><?php echo $lines[0] ?></p>
        <a href="https://github.com/kassner/log-parser"><?php _e("See more info on log format strings here") ?></a>
        <?php }
        else
            require_once( 'widget.php' );
    }

    /**
     * Load widget config code.
     *
     * This is what will display when an admin clicks 'edit'
     */
    public static function config() {
        require_once( 'widget-config.php' );
    }

    /**
     * Load widget config code.
     *
     * This is what will display when an admin clicks 'edit'
     */
    public static function clear_cache() {
        delete_transient( 'access-log-monitoring-lines' );
    }

    /**
     * Gets the options for a widget of the specified name.
     *
     * @param string $widget_id Optional. If provided, will only get options for the specified widget.
     * @return array An associative array containing the widget's options and values. False if no opts.
     */
    public static function get_dashboard_widget_options( $widget_id='' )
    {
        //Fetch ALL dashboard widget options from the db...
        $opts = get_option( 'dashboard_widget_options' );

        //If no widget is specified, return everything
        if ( empty( $widget_id ) )
            return $opts;

        //If we request a widget and it exists, return it
        if ( isset( $opts[$widget_id] ) )
            return $opts[$widget_id];

        //Something went wrong...
        return false;
    }

    /**
     * Gets one specific option for the specified widget.
     * @param $widget_id
     * @param $option
     * @param null $default
     *
     * @return string
     */
    public static function get_dashboard_widget_option( $widget_id, $option, $default=NULL ) {

        $opts = self::get_dashboard_widget_options($widget_id);

        //If widget opts dont exist, return false
        if ( ! $opts )
            return false;

        //Otherwise fetch the option or use default
        if ( isset( $opts[$option] ) && ! empty($opts[$option]) )
            return $opts[$option];
        else
            return ( isset($default) ) ? $default : false;

    }

    /**
     * Saves an array of options for a single dashboard widget to the database.
     * Can also be used to define default values for a widget.
     *
     * @param string $widget_id The name of the widget being updated
     * @param array $args An associative array of options being saved.
     * @param bool $add_only If true, options will not be added if widget options already exist
     */
    public static function update_dashboard_widget_options( $widget_id , $args=array(), $add_only=false )
    {
        #Clear earlier transients when updating
        self::clear_cache();
        //Fetch ALL dashboard widget options from the db...
        $opts = get_option( 'dashboard_widget_options' );

        //Get just our widget's options, or set empty array
        $w_opts = ( isset( $opts[$widget_id] ) ) ? $opts[$widget_id] : array();

        if ( $add_only ) {
            //Flesh out any missing options (existing ones overwrite new ones)
            $opts[$widget_id] = array_merge($args,$w_opts);
        }
        else {
            //Merge new options with existing ones, and add it back to the widgets array
            $opts[$widget_id] = array_merge($w_opts,$args);
        }

        //Save the entire widgets array back to the db
        return update_option('dashboard_widget_options', $opts);
    }

    /**
     * Gets access log lines
     */
    public static function get_access_log_lines($errors = null,$line_count = null)
    {
        $filename = self::get_dashboard_widget_option(self::wid, 'access_log_path');
        if (!$line_count)
            $line_count = self::get_dashboard_widget_option(self::wid, 'line_count');
        $log_format = self::get_dashboard_widget_option(self::wid, 'access_log_format');
        $lines = get_transient( 'access-log-monitoring-lines' );
        if ( false === $lines ) {
             // this code runs when there is no valid transient set
            $lines = self::last_log_lines($filename,$line_count,$log_format,$errors);
            set_transient( 'access-log-monitoring-lines', $lines, 30 * MINUTE_IN_SECONDS );
        }
        return $lines;

    }

    /**
     * Excellent php file tailing
     * Taken from: http://stackoverflow.com/questions/6451232/reading-large-files-from-end
     *
     * Modified to exclude http-status codes
     *
     * @return array of parsed log objects
     *
     * @param string $filename The log filename
     * @param integer $lines Amount of lines to return
     */

    private static function last_log_lines($path, $line_count, $log_format, $errors = null, $block_size = 512){
        $lines = array();

        // we will always have a fragment of a non-complete line
        // keep this in here till we have our next entire line.
        $leftover = "";

        // Exclude status codes
        $exclude_status = self::default_exclude;

        // Remove whitespace and empty values
        $exclude_status = preg_replace('/\s+/', '', $exclude_status);
        $exclude_array = array_filter(explode(",", $exclude_status),'strlen');

        // For parsing logs with common log format
        
        $parser = new \Kassner\LogParser\LogParser($log_format);
        $fh = fopen($path, 'r');
        if (!$fh)
            return false;
        // go to the end of the file
        fseek($fh, 0, SEEK_END);
        do{
            // need to know whether we can actually go back
            // $block_size bytes
            $can_read = $block_size;
            if(ftell($fh) < $block_size){
                $can_read = ftell($fh);
            }

            // go back as many bytes as we can
            // read them to $data and then move the file pointer
            // back to where we were.
            fseek($fh, -$can_read, SEEK_CUR);
            $data = fread($fh, $can_read);
            $data .= $leftover;
            fseek($fh, -$can_read, SEEK_CUR);

            // split lines by \n. Then reverse them,
            // now the last line is most likely not a complete
            // line which is why we do not directly add it, but
            // append it to the data read the next time.
            $split_data = array_reverse(explode("\n", $data));
            $new_lines = array_slice($split_data, 0, -1);
            $parsed_lines = [];
            // Check conditions on new lines
            foreach ($new_lines as $line) {
                if ($line == '')
                    continue;
                try {
                    $log_entry = $parser->parse($line);
                    //Append into lines if log_entry has bad status code
                    if (!in_array($log_entry->status,$exclude_array))
                        $parsed_lines[] = $parser->parse($line);
                } catch (Exception $e) {
                    ++$errors;
                }
            }
            $lines = array_merge($lines, $parsed_lines);
            $leftover = $split_data[count($split_data) - 1];
        }
        while(count($lines) < $line_count && ftell($fh) != 0);
        if(ftell($fh) == 0){
            $lines[] = $leftover;
        }
        fclose($fh);
        // Usually, we will read too many lines, correct that here.
        return array_reverse(array_slice($lines, 0, $line_count));
    }
}
