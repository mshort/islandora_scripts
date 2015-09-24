#!/usr/bin/env drush

<?php

$tuquePath = libraries_get_path('tuque') . '/*.php';
foreach ( glob($tuquePath) as $filename) {
    require_once($filename);
}

module_load_include('inc', 'islandora_large_image', 'includes/derivatives');

$url = 'http://localhost:8080/fedora';
$username = 'xxx';
$password = 'xxx';

$connection = new RepositoryConnection($url, $username, $password);
$api = new FedoraApi($connection);
$repository = new FedoraRepository($api, new SimpleCache());

$query =
'select ?pid
from <#ri>
where {?pid <fedora-model:hasModel> <info:fedora/islandora:sp_basic_image> ; <fedora-rels-ext:isMemberOfCollection> <info:fedora/seadl:collection> . }
';
$objects = $repository->ri->sparqlQuery($query);

foreach ($objects as $pid){

	$object = islandora_object_load($pid[pid][value]);
        $file = explode(':',$object->id);
	$full_path = "/media/libnas1/tech_services/seadl/tiffs/".$file[1].".tif";
	if (file_exists($full_path)) {
	        echo "Processing: " . $object->id . "\n";
		$dsid = 'OBJ';
      		$datastream = isset($object[$dsid]) ? $object[$dsid] : $object->constructDatastream($dsid);
      		$datastream->label = $file[1];
      		$datastream->mimeType = 'image/tiff';
      		$datastream->setContentFromFile($full_path);
      		$object->ingestDatastream($datastream);
      		echo "Finished adding TIFF for ".$object->id."\n";
      		$base_name = str_replace(':', '-', $object->id);
      		$force = TRUE;
      		$uploaded_file = islandora_large_image_get_uploaded_file($object, $base_name);
      		islandora_large_image_create_jpg_derivative($object, $uploaded_file, $base_name, $force);
      		islandora_large_image_create_jp2_derivative($object, $uploaded_file, $base_name, $force);
//      		return $jp2 && $jpg;
	      	echo "Finished adding derivatives for ".$object->id."\n";
		$object->relationships->add(FEDORA_MODEL_URI, 'hasModel', 'islandora:sp_large_image_cmodel');
		$object->relationships->remove(FEDORA_MODEL_URI, 'hasModel', 'islandora:sp_basic_image');
	}
}
?>
