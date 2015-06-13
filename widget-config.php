<?php
/**
 * This file could be used to catch submitted form data. When using a non-configuration
 * view to save form data, remember to use some kind of identifying field in your form.
 */
    $line_count = ( isset( $_POST['lineCount'] ) ) ? stripslashes( $_POST['lineCount'] ) : '';
    $exclude_status_codes = ( isset( $_POST['excludeStatuses'] ) ) ? stripslashes( $_POST['excludeStatuses'] ) : '';
    $access_log_path = ( isset( $_POST['accessLogFile'] ) ) ? stripslashes( $_POST['accessLogFile'] ) : '';
    $access_log_format = ( isset( $_POST['accessLogFormat'] ) ) ? stripslashes( $_POST['accessLogFormat'] ) : '';

    //Update options if not null
    if ($line_count != null && $exclude_status_codes != null && $access_log_path != null) {
      self::update_dashboard_widget_options(
              self::wid,                                  //The  widget id
              array(                                      //Associative array of options & default values
                  'line_count' => $line_count,
                  'exclude_status_codes' => $exclude_status_codes,
                  'access_log_path' => $access_log_path,
                  'access_log_format' => $access_log_format
              )
      );
    }
?>
<div class="log-monitor-options">
  <p>
  <label><?php _e("Line count:"); ?></label><br>
  <input type="number" name="lineCount" value="<?php echo(htmlspecialchars(self::get_dashboard_widget_option(self::wid, 'line_count',self::default_line_count))); ?>"/>
  </p>
  <p>
    <label><?php _e("Exclude status codes (separated by ','):"); ?></label><br>
    <input type="text" name="excludeStatuses" value="<?php echo(htmlspecialchars(self::get_dashboard_widget_option(self::wid, 'exclude_status_codes',self::default_exclude))); ?>" />
  </p>
  <p>
    <label><?php _e("Access log format:"); ?></label><br>
    <input type="text" name="accessLogFormat" value="<?php echo(htmlspecialchars(self::get_dashboard_widget_option(self::wid, 'access_log_format',self::default_access_log_formats[0]))); ?>" />
  </p>
  <p>
    <label><?php _e("Access logfile:"); ?></label><br>
    <input type="text" name="accessLogFile" value="<?php echo(htmlspecialchars(self::get_dashboard_widget_option(self::wid, 'access_log_path',self::default_access_log_path))); ?>" />
  </p>
</div>