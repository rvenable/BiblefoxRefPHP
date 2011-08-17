<?php

require_once 'biblefox-ref.php';
require_once 'bfox_plan_parser.php';
require_once 'bfox_plan_scheduler.php';

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

$scheduler = new BfoxPlanScheduler;
$scheduler->setStartDate('2011-08-16');
$scheduler->pushNumDates(4);
$ans = array('2011-08-16', '2011-08-17', '2011-08-18', '2011-08-19');
assertArrays($ans, $scheduler->dates());

$scheduler->setFrequency('monthly');
$scheduler->pushNumDates(4);
$ans = array_merge($ans, array('2011-08-20', '2011-09-20', '2011-10-20', '2011-11-20'));
assertArrays($ans, $scheduler->dates());

$scheduler->setFrequency('weekly');
$scheduler->pushNumDates(4);
$ans = array_merge($ans, array('2011-12-20', '2011-12-27', '2012-01-03', '2012-01-10'));
assertArrays($ans, $scheduler->dates());

$scheduler->setFrequency('daily');
$scheduler->setDaysOfWeek(array('Sunday', 'Tuesday', 'Fri'));
$scheduler->pushNumDates(4);
$ans = array_merge($ans, array('2012-01-17', '2012-01-20', '2012-01-22', '2012-01-24'));
assertArrays($ans, $scheduler->dates());

$scheduler->setDaysOfWeek(array('Monday', 'Wednesday'));
$scheduler->pushNumDates(3);
$ans = array_merge($ans, array('2012-01-25', '2012-01-30', '2012-02-01'));
assertArrays($ans, $scheduler->dates());

?>