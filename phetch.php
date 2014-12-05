<?php

include(dirname(__FILE__) . '/vendor/autoload.php');

use DMS\Service\Meetup\MeetupKeyAuthClient;
use DMS\Service\Meetup\MeetupOAuthClient;

/**
 * TODO:
 *  - Implement caching
 */

$config = include(dirname(__FILE__) . '/config.php');

$targetDirectory = $config['target_directory'];
if(!file_exists($targetDirectory)) {
    mkdir($targetDirectory);
}

$client = null;

$client = MeetupKeyAuthClient::factory($config);

$events = $client->getEvents(array(
    'member_id' => isset($config['member_id']) ? $config['member_id'] : 'self',
    'rsvp' => 'yes',
    'status' => 'past', // Would be cool to already fetch upcoming photos... lol
));

foreach($events as $event) {
    $eventName = $event['name'];
    $groupName = $event['group']['name'];
    $timestamp = $event['time'] / 1000;
    $date = strftime("%Y-%m-%d", $timestamp);
    $datetime = strftime("%Y-%m-%d %H:%M:%S", $timestamp);

    $groupDir = $targetDirectory . DIRECTORY_SEPARATOR . str_replace(DIRECTORY_SEPARATOR, '-', $groupName);
    if(!file_exists($groupDir)) {
        mkdir($groupDir);
    }

    $eventDir = $groupDir . DIRECTORY_SEPARATOR . $date;
    if(!file_exists($eventDir)) {
        mkdir($eventDir);
    }

    $photos = $client->getPhotos(array(
        'event_id' => $event['id'],
    ));

    printf("%-20s -  %-40s %-40s\n", $datetime, $groupName, $eventName);

    foreach($photos as $photo) {
        $photoId = $photo['photo_id'];
        $photoUrl = $photo['photo_link'];
        printf("\t%-60s\n", $photoUrl);

        $contents = file_get_contents($photoUrl);
        $filename = basename($photoUrl);
        $targetFilename = $eventDir . DIRECTORY_SEPARATOR . $filename;

        if(!file_put_contents($targetFilename, $contents)) {
            throw new Exception("Unable to save photo");
        }

        // We don't want to hit the rate limit, taking it easy
        usleep(5000);
    }


}
