<?php

class PluginTest extends WP_UnitTestCase {

  const format_success = '%h (%a|-) %{User-Identifier}i %u %t "%r" %>s %b "%{Referer}i" "%{User-Agent}i" %{Cache-Status}i %T';
  const format_failure = '%T %T %T %T %T %T';
  // Check that that activation doesn't break
  function test_plugin_activated() {
    $this->assertTrue( is_plugin_active( 'wp-dashboard-log-monitor/wp-dashboard-log-monitor.php' ) );
  }

  function test_returns_array_on_success() {
    $lines = Dashboard_Log_Monitor_Widget::last_log_lines(__DIR__.'/fixtures/working-access-200-404.log',10,self::format_success,'');
    $this->assertTrue( is_array($lines) );
  }

  function test_status_code_exclude_success() {
    $lines = Dashboard_Log_Monitor_Widget::last_log_lines(__DIR__.'/fixtures/working-access-200-404.log',10,self::format_success,'404');
    $this->assertEquals( count($lines), 4 );
  }

  function test_return_false_nonexistent_file() {
    $lines = Dashboard_Log_Monitor_Widget::last_log_lines(__DIR__.'/fixtures/nonexistent.log',10,self::format_success,'');
    $this->assertFalse( $lines );
  }
}

