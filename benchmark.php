<?php
require_once("bootstrap.php");

$loops = isset($argv[1])?$argv[1]:100;

$date = date("Y-m-d H:i:s");
$text = "Test text";
$totalStart = microtime(true);

echo "Benchmarking for {$loops} loops...\n";

echo "Construct:\n";
$start = microtime(true);
for ($i = 0; $i < $loops; $i++) {
    $object = new \Thru\ActiveRecord\Test\Models\TestModel();
    $object->date_field = $date;
    $object->text_field = $text;
    $object->integer_field = $i;
    $object->save();
    echo "\r > Construct {$i}";
}
$end = microtime(true);
$delay = number_format($end - $start, 2);
echo "\nConstruct Done in {$delay} sec\n";
$metrics['Construct'] = $delay / $loops;

echo "Search:\n";
$start = microtime(true);
for ($i = 0; $i < $loops; $i++) {
    $object = \Thru\ActiveRecord\Test\Models\TestModel::search()->where('integer_field', rand(0, $loops))->execOne();
    echo "\r > Search {$i}";
}
$end = microtime(true);
$delay = number_format($end - $start, 2);
echo "\nSearch Done in {$delay} sec\n";
$metrics['Search'] = $delay / $loops;

echo "Destroy:\n";
$start = microtime(true);
for ($i = 0; $i < $loops; $i++) {
    $object = \Thru\ActiveRecord\Test\Models\TestModel::search()->where('integer_field', $i)->execOne();
    $object->delete();
    echo "\r > Search {$i}";
}
$end = microtime(true);
$delay = number_format($end - $start, 2);
echo "\nDestroy Done in {$delay} sec\n";
$metrics['Destroy'] = $delay / $loops;

echo "\n\n*************************************************************************\n";
echo "TOTAL TIME: " . number_format(microtime(true) - $totalStart, 2) . " sec\n\n";
foreach ($metrics as $function => $timeEach) {
    $perSecond = 1 / $timeEach;
    echo "{$function}: \t" . number_format($timeEach, 4) . "/ea, " . number_format($perSecond, 2) . "/s\n";
}


