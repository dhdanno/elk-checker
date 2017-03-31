#!/usr/bin/php
<?php
/*
  detect_events.php 
  We are looking for at least one occurence of $1 in $timeframe

  This is how you can send an event into the Graylog listener
  echo -e '{"message": "test123"}' | gzip | nc -w 1 -u 127.0.0.1 12201
*/

require 'vendor/autoload.php';

// This holds name of the events we are looking for.
$events_arr = array('test123', 'backups_rule1', '12222');

// Setup
ini_set('date.timezone', 'UTC' );
$today = date("Y.m.d");

$client = Elasticsearch\ClientBuilder::create()->build();

$timeframe = "15"; //time to lookback
$unit = "h"; // h/m/d/m

$params = [
  'index' => 'logstash-*', //scan all available indexes
  'size' => 500,
  'body' => [
    'query' => [
      'filtered' => [
        'query' => [
          'range' => [
            '@timestamp' => [
              'from' => 'now-'.$timeframe.'m/m',
              'to'   => 'now/m'
            ]
          ]
        ]
      ]
    ]
  ]
];

$response = $client->search($params);
//print_r($response); exit(0);

foreach ($events_arr as $event) {
  search($response, $event, $timeframe, $unit);  
}

function search($response, $event, $timeframe, $unit) {
  // check the array of hits for what we are looking for
  if (count($response['hits']['hits']) > 0) {
    $count = count($response['hits']['hits']);
    
    $c=0;
    $i=0;

    foreach($response['hits']['hits'] as $arr) {

      $host = $arr['_source']['host'];
      $msg  = $arr['_source']['message'];
      $suc  = $arr['_source']['success'];

      if ($msg == $event && $suc == 1) {
        echo "Found the event in the specified timeframe ".$msg."\n";
        $i++;
      }
    $c++;
    }

  } else {
    print("no records in specified timeframe");
    exit(1);
  }

  if ($i > 0) {
    //echo "things are good\n";
  } else {
    echo "Presence or success not found for event ".$event." in the past ".$timeframe." ".$unit."... This is a problem\n";
    $fail = true;
  }

  if ($count == $c) {
    if ($fail) {
      exit(1);
    }
  }

}