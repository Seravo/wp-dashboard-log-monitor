<?php
/**
 * This file could be used to catch submitted form data. When using a non-configuration
 * view to save form data, remember to use some kind of identifying field in your form.
 */
?>
<p class="Description">
  Tähän on haarukoitu sivustosi mahdollisia ongelmakohtia. Havaitsimme ne sivustosi lokitiedoista.
</p>
<table class="log-monitor">
<?php 
$lines = self::get_access_log_lines();
foreach ($lines as $index => $line) {
  if (!isset($line->status))
    continue;
?>
<tr class="<?php echo ($index%2 == 0 ? '' : 'alternate') ?>">
  <td class="time-table">
    <p class="time" style="white-space:nowrap"><?php echo strftime(  "%b %d @ %H:%M:%S", $line->stamp ); ?></p>
  </td>
  <td class="request-table">
    
    <table class="request-info">
      <tr>
        <th>
          <span class="status-code <?php echo (isset($line->status) ? "code-{$line->status[0]}xx code-{$line->status}" : ''); ?>"><? echo (isset($line->status) ? $line->status : __('status NA')); ?></span>
        </th>
        <td>
          <span class="request">"<? echo (isset($line->request) ? $line->request : __('request NA')); ?>"</span>
        </td>
      </tr>
      <?php
      unset($line->status);
      unset($line->stamp);
      unset($line->time);
      unset($line->request);
      unset($line->HeaderUserIdentifier);
      unset($line->user);
      $table_data = get_object_vars($line);

      foreach ($table_data as $key => $value ) {
        if ($value == '-' || $value == '')
          continue;
        ?>
        <tr class="request-spec">
          <th><?php echo $key ?>:</th>
          <td><?php echo $value ?></td>
        </tr>
      <?php } ?>
    </table>
  </td>
</tr>
<?php } ?>
</table>
<p><?php _e("Älä näytä status koodeja:"); ?> <?php echo self::get_dashboard_widget_option(self::wid, 'exclude_status_codes'); ?></p>
<p><?php _e("Lokitiedosto:"); ?> <?php echo self::get_dashboard_widget_option(self::wid, 'access_log_path'); ?></p>
