<?php
// phpinfo();
require_once('functions.php');


// Read the iCal feed and save it as a file for the parser.
$calendar_filename = 'calendar.ics';
$calendar_link = '<YOUR CALENDAR LINK HERE>';
$calendar_data = file_get_contents($calendar_link);
file_put_contents($calendar_filename, $calendar_data);

$calendar = new ICal($calendar_filename);

// If there's a date in the query, use it as a filter or default to today.
date_default_timezone_set('Europe/Stockholm');
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$date_filter_timestamp = strtotime($date_filter);
$yesterday = date('Y-m-d', strtotime('-1 day', $date_filter_timestamp));
$tomorrow = date('Y-m-d', strtotime('+1 day', $date_filter_timestamp));

$unlogged_events = array();
$total_duration = 0;

if (!empty($calendar->cal['VEVENT'])) {
  foreach ($calendar->cal['VEVENT'] as $event) {
    // Convert to object for ease.
    $event = (object)$event;
    $start_date_timestamp = strtotime($event->DTSTART);
    $start_date_pretty = date('Y-m-d', $start_date_timestamp);

    // Make sure we list events from given date
    if ($start_date_pretty == $date_filter) {

      $end_date_timestamp = strtotime($event->DTEND);

      // Calculate duration
      $event_duration = ($end_date_timestamp - $start_date_timestamp);
      $event_duration_hours = ($event_duration / 60 / 60); // seconds to hours
      $event_duration_hours = sprintf('%0.2f', $event_duration_hours);
      $total_duration = ($total_duration + $event_duration_hours);

      $summary = explode(': ', $event->SUMMARY);
      $project = $summary[0];
      $task = $summary[1];
      $task_output = $task;
      // Build JIRA links for matched projects.
      $task_output = preg_replace('/([A-Z0-9]+\b-[0-9]+\b)/', '<a href="https://kodamera.atlassian.net/browse/$1" target="_blank">$1</a>', $task);

      // if (preg_match('/[A-Z]+\b-[0-9]+\b/', $task)) {
      //   $task_output = preg
      // }

      // Calculate project duration
      if (isset($unlogged_events[$project]['duration'])) {
        $previous_project_duration = $unlogged_events[$project]['duration'];
        $unlogged_events[$project]['duration'] = sprintf('%0.2f', ($previous_project_duration + $event_duration_hours));
      }
      else {
        $unlogged_events[$project]['duration'] = $event_duration_hours;
        $unlogged_events[$project]['title'] = $project;
      }

      // Calculate task duration
      if (isset($unlogged_events[$project]['tasks'][$task]['duration'])) {
        $previous_task_duration = $unlogged_events[$project]['tasks'][$task]['duration'];
        $unlogged_events[$project]['tasks'][$task]['duration'] = sprintf('%0.2f', ($previous_task_duration + $event_duration_hours));
      }
      else {
        $unlogged_events[$project]['tasks'][$task] = array(
          'title' => $task_output,
          'duration' => $event_duration_hours,
        );
      }
    }
  }
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unlogged</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">

    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <div class="main-content">
      <h1>Unlogged</h1>

      <h2><a href="?date=<?php print $yesterday; ?>"><span class="glyphicon glyphicon-chevron-left"></span></a><span class="glyphicon glyphicon-time"></span><?php print $date_filter; ?><a href="?date=<?php print $tomorrow; ?>"><span class="glyphicon glyphicon-chevron-right"></span></a></h2>
      <table class="table">
        <tbody>
          <?php
            $i = 0;
            if (!empty($unlogged_events)) {
              // Step through projects
              foreach ($unlogged_events as $project) {
                $project = (object)$project;

                print '<tr><td class="project-title" colspan="2">' . $project->title . '</td></tr>';

                // Step through tasks on a project
                foreach ($project->tasks as $task) {
                  $task = (object)$task;

                  // Add striping class
                  $stripe = ($i%2 == 0) ? 'odd' : 'even';
                  print '<tr class="task ' . $stripe . '"><td>' . $task->title . '</td><td class="duration">' . $task->duration . '<div class="harvest-timer" data-project=\'{"id":"' . urlencode($project->title) . '","name":"' . $project->title . '"}\' data-item=\'{"id":"' . urlencode($task->title) . '","name":"' . $task->title . '"}\'></div></td></tr>';
                  // print '<tr class="task ' . $stripe . '"><td>' . $task->title . '</td><td class="duration">' . $task->duration .
                  // '<div class="harvest-timer"></div></td></tr>';
                  $i++;
                }
                print '<tr><td></td><td>(' . $project->duration . ')</td></tr>';
              }
              print '<tr class="task total"><td>Total</td><td class="duration">' . $total_duration . '</td></tr>';
            }
            else {
              print '<tr class=""><td colspan="2">No unlogged entries for this day</td></tr>';
            }

          ?>
        </tbody>
      </table>

    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
      (function() {
        window._harvestPlatformConfig = {
          "applicationName": "Unlogged",
        };
        var s = document.createElement('script');
        s.src = '//platform.harvestapp.com/assets/platform.js';
        s.async = true;
        var ph = document.getElementsByTagName('script')[0];
        ph.parentNode.insertBefore(s, ph);
      })();
    </script>
  </body>
</html>
