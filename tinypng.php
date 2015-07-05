<?php 

use Cadrone\TinyPNGClient\TinyPNGClient;
use Cadrone\TinyPNGClient\TinyPNGRequestException;
use Curl\Curl;

require_once realpath(__DIR__) . "/vendor/autoload.php";

$options = getopt("i:o:c:k:");

if (!array_key_exists("i", $options)) {
    print "Please set input file: -i <path_to_file>\n";
    exit(1);
}

if (!array_key_exists("o", $options)) {
    print "Please set output file: -o <path_to_file>\n";
    exit(1);
}

if (!array_key_exists("k", $options)) {
    print "Please set api key: -k <key>\n";
    exit(1);
}

$inputFile = $options["i"];
$outputFile = $options["o"];
$key = $options["k"];

$curl = new Curl();
$client = new TinyPNGClient($key, $curl);

if (array_key_exists("c", $options)) {
    $client->setCertFile($options["c"]);
}

try {
    $client
            ->shrink($inputFile)
            ->downloadImage($outputFile)
    ;
} catch (\Exception $e) {
    print "Error: {$e->getMessage()} ({$e->getCode()})";
}