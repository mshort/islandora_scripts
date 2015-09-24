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
        echo "Processing: " . $object->id . "\n";

        $base_name = str_replace(':', '-', $object->id);
        $jpeg_file = '/tmp/'.$base_name.'_JPG.jpg';
        $object['MEDIUM_SIZE']->getContent($jpeg_file);
        $tiff_file = '/tmp/'.$base_name.'_TIFF.tif';
        shell_exec('convert '.$jpeg_file.' '.$tiff_file);

        if (file_exists($tiff_file)) {

                $dsid = 'OBJ';
                $datastream = isset($object[$dsid]) ? $object[$dsid] : $object->constructDatastream($dsid);
                $datastream->label = 'TIFF Image';
                $datastream->mimeType = 'image/tiff';
                $datastream->setContentFromFile($tiff_file);
                $object->ingestDatastream($datastream);
                echo "Finished adding TIFF for ".$object->id."\n";
                //file_unmanaged_delete($jpeg_file);
                //file_unmanaged_delete($tiff_file);
                $base_name = str_replace(':', '-', $object->id);
                $force = TRUE;
                $uploaded_file = islandora_large_image_get_uploaded_file($object, $base_name);
                islandora_large_image_create_jpg_derivative($object, $uploaded_file, $base_name, $force);
                islandora_large_image_create_jp2_derivative($object, $uploaded_file, $base_name, $force);
                echo "Finished adding derivatives for ".$object->id."\n";
                $object->relationships->add(FEDORA_MODEL_URI, 'hasModel', 'islandora:sp_large_image_cmodel');
                $object->relationships->remove(FEDORA_MODEL_URI, 'hasModel', 'islandora:sp_basic_image');
                echo "Finished converting ".$object->id."\n\n";
        }
}

?>
