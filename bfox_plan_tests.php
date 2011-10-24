<?php

require_once 'biblefox-ref.php';
require_once 'bfox_plan_parser.php';
require_once 'bfox_plan_scheduler.php';

global $showSuccess;
$showSuccess = true;

function assertArrays($a1, $a2) {
	if (!assert($a1 == $a2)) {
		print_r($a1);
		print_r($a2);
		exit;
	}
}

/*
Test BfoxPlanParser
*/
$content = "Reading 1: Gen 1-10; Exo 1-2;
Reading 2 - 1 Sam 5 (good); 2 sam 6 (great)
Hello
Reading 3 -

1 sam 7

";

$parser = new BfoxPlanParser;
$parser->parseContent($content, "\n");

assertArrays($parser->readingRefStrings(), array('Genesis 1-10; Exodus 1-2', '1 Samuel 5; 2 Samuel 6', '1 Samuel 7'));

/*
Test BfoxPlanScheduler
*/

date_default_timezone_set('America/New_York'); // Suppress silly php date warnings by setting time zone manually

function outputSchedulerDates($scheduler, $numDates) {
	print_r($scheduler->dates($numDates));
	echo "Latest Date Index: " . $scheduler->latestDateIndex . "\n";
}

function assertLatest($scheduler, $date, $targetDate, $message) {
	global $showSuccess;

	$latest = $scheduler->latestDateIndex(strtotime($date));
	$dates = $scheduler->dates();
	$latestDate = $dates[$latest];
	if ($latestDate != $targetDate) {
		$message = sprintf($message, $target, $latest, $time);
		echo "Failed: $date -> $latestDate != $targetDate - $message\n";
	}
	else if ($showSuccess) echo "Success: $date -> $latestDate == $targetDate - $message\n";
}

$scheduler = new BfoxPlanScheduler;
$scheduler->setStartDate('2011-08-16');
$scheduler->pushNumDates(4);
$ans = array('2011-08-16', '2011-08-17', '2011-08-18', '2011-08-19');
assertArrays($ans, $scheduler->dates());

assertLatest($scheduler, '2011-08-15', '2011-08-16', 'Dates before a plan begins should point to the first reading');
assertLatest($scheduler, '2011-08-16', '2011-08-16', 'The date of the first reading');
assertLatest($scheduler, '2011-08-17', '2011-08-17', 'The date of the second reading');

$scheduler->setFrequency('monthly');
$scheduler->pushNumDates(4);
$ans = array_merge($ans, array('2011-08-20', '2011-09-20', '2011-10-20', '2011-11-20'));
assertArrays($ans, $scheduler->dates());

assertLatest($scheduler, '2011-08-20', '2011-08-20', 'The first date of the month');
assertLatest($scheduler, '2011-09-19', '2011-08-20', 'The last date of the month should still have the old reading from the beginning of the month');
assertLatest($scheduler, '2011-09-20', '2011-09-20', 'The first date of the new month');

$scheduler->setFrequency('weekly');
$scheduler->pushNumDates(4);
$ans = array_merge($ans, array('2011-12-20', '2011-12-27', '2012-01-03', '2012-01-10'));
assertArrays($ans, $scheduler->dates());

$scheduler->setFrequency('daily');
$scheduler->setDaysOfWeek(array('Sunday', 'Tuesday', 'Fri'));
$scheduler->pushNumDates(4);
$ans = array_merge($ans, array('2012-01-17', '2012-01-20', '2012-01-22', '2012-01-24'));
assertArrays($ans, $scheduler->dates());

assertLatest($scheduler, '2012-01-19', '2012-01-17', 'An excluded day of the week');
assertLatest($scheduler, '2012-01-23', '2012-01-22', 'Another excluded day of the week');

$scheduler->setDaysOfWeek(array('Monday', 'Wednesday'));
$scheduler->pushNumDates(3);
$ans = array_merge($ans, array('2012-01-25', '2012-01-30', '2012-02-01'));
assertArrays($ans, $scheduler->dates());

// New Scheduler for dates around current time
$scheduler = new BfoxPlanScheduler;
$scheduler->setStartDate('yesterday');
$scheduler->pushNumDates(4);
if (1 != $scheduler->latestDateIndex()) echo "Error: LatestDateIndex for today is wrong\n";
else if ($showSuccess) echo "Success: LatestDateIndex for today\n";

echo "Tests Complete\n";

?>