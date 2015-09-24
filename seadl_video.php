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
where {?pid <fedora-model:hasModel> <info:fedora/islandora:sp_videoCModel> . }
';
$objects = $repository->ri->sparqlQuery($query);

//$missing = file('/media/libnas1/tech_services/seadl/mp4/missing.txt');

foreach ($objects as $pid){

	$object = islandora_object_load($pid[pid][value]);
	if (isset($object['VIDEOLINK'])) {
        	$link = $object['VIDEOLINK']->getContent(''); // getContent expects an argument
		$ext = explode("/", $link);
		$file = explode(".", end($ext));
		$dsid = 'MP4';
		$datastream = isset($object[$dsid]) ? $object[$dsid] : $object->constructDatastream($dsid);
		$size = $datastream->size;
		if  ($size < 1) {
			echo $object->id . "\n";
			$ext = explode("/", $link);
			$file = explode(".", end($ext));
			$full_path = "/media/libnas1/tech_services/seadl/mp4/".$file[0].".mp4";
			$datastream->setContentFromFile($full_path);
			echo "Finished adding MP4 for".$object->id."\n\n";
		}
	}
}

?>
