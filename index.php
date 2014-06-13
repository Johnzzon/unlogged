<?php
// phpinfo();
require_once('functions.php');


// Read the iCal feed and save it as a file for the parser.
$calendar_filename = 'calendar.ics';
// $calendar_link = 'http://p03-calendarws.icloud.com/ca/subscribe/1/U2cSSBU-nJnXEqcVmnf-xsLf-ZFxBpFJCmjwO6Rb_ptpEhnvZXoY2LgVJOKayAx-DeDrZ3P0ZfkrV1qUi46JDXoTvQXTucuskegtYrPFH3E';
// $calendar_data = file_get_contents($calendar_link);
// file_put_contents($calendar_filename, $calendar_data);

$calendar = new ICal($calendar_filename);

// If there's a date in the query, use it as a filter or default to today.
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$unlogged_events = array();
$total_duration = 0;

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

    // Project duration
    if (isset($unlogged_events[$project]['duration'])) {
      $previous_project_duration = $unlogged_events[$project]['duration'];
      $unlogged_events[$project]['duration'] = $previous_project_duration + $event_duration_hours;
    }
    else {
      $unlogged_events[$project]['duration'] = $event_duration_hours;
      $unlogged_events[$project]['title'] = $project;
    }

    // Task duration
    if (isset($unlogged_events[$project]['tasks'][$task]['duration'])) {
      $previous_task_duration = $unlogged_events[$project]['tasks'][$task]['duration'];
      $unlogged_events[$project]['tasks'][$task]['duration'] = $previous_task_duration + $event_duration_hours;
    }
    else {
      $unlogged_events[$project]['tasks'][$task] = array(
        'title' => $task,
        'duration' => $event_duration_hours,
      );
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
      <h1>Unlogged events</h1>

      <h2><span class="glyphicon glyphicon-time"></span><?php print $date_filter; ?></h2>
      <table class="table">
        <tbody>
          <?php
            $i = 0;
            // Step through projects
            foreach ($unlogged_events as $project) {
              $project = (object)$project;

              print '<tr><td class="project-title" colspan="2">' . $project->title . '</td></tr>';

              // Step through tasks on a project
              foreach ($project->tasks as $task) {
                $task = (object)$task;

                // Add striping class
                $stripe = ($i%2 == 0) ? 'odd' : 'even';
                print '<tr class="task ' . $stripe . '"><td>' . $task->title . '</td><td class="duration">' . $task->duration . '</td></tr>';
                $i++;
              }
              print '<tr><td></td><td>' . $project->duration . '</td></tr>';
            }

          ?>
          <tr class="task total"><td>Total</td><td class="duration"><?php print $total_duration; ?></td></tr>
        </tbody>
      </table>

    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>
