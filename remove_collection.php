#!/usr/bin/env drush

<?php

$tuquePath = libraries_get_path('tuque') . '/*.php';
foreach ( glob($tuquePath) as $filename) {
    require_once($filename);
}

$url = 'http://localhost:8080/fedora';
$username = 'xxx';
$password = 'xxx';

$connection = new RepositoryConnection($url, $username, $password);
$api = new FedoraApi($connection);
$repository = new FedoraRepository($api, new SimpleCache());

$query =
'select ?pid
from <#ri>
where {?pid <fedora-rels-ext:isMemberOfCollection> <info:fedora/SEAImages:VietnameseCollection> . }
';
$objects = $repository->ri->sparqlQuery($query);

foreach ($objects as $pid){

        $object = islandora_object_load($pid[pid][value]);
        echo "Processing: " . $object->id . "\n";
	$object->relationships->remove(FEDORA_RELS_EXT_URI, 'isMemberOfCollection', 'SEAImages:VietnameseCollection');
        echo "Finished converting ".$object->id."\n\n";
}

?>
