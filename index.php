<?php
require_once('functions.php');

echo "hello world";

$calendar_link = 'http://p03-calendarws.icloud.com/ca/subscribe/1/U2cSSBU-nJnXEqcVmnf-xsLf-ZFxBpFJCmjwO6Rb_ptpEhnvZXoY2LgVJOKayAx-DeDrZ3P0ZfkrV1qUi46JDXoTvQXTucuskegtYrPFH3E';

$calendar_data = file_get_contents($calendar_link);

$calendar_filename = 'calendar.ics';

file_put_contents($calendar_filename, $calendar_data);

$calendar = new ICal($calendar_filename);

$date_filter = isset($_GET['date']) ? strtotime($_GET['date']) : strtotime('today');
dsm($date_filter, '?q');

foreach ($calendar->cal['VEVENT'] as $event) {
  // dsm($event);
  $start_date = $event['DTSTART']; // 20140613T190000
  $start_date_timestamp = strtotime($start_date); // 1402610400
  $start_date_pretty = date('Y-m-d', $start_date_timestamp); // 2014-06-13
  if ($start_date_pretty == $date_filter) {

  }

  dsm($start_date_pretty, 'pretty');

  $end_date = $event['DTEND']; // 20140613T194500

  $title = $event['SUMMARY'];
  // dsm($title);
}
