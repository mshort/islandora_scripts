
#!/usr/bin/env drush

<?php

$tuquePath = libraries_get_path('tuque') . '/*.php';
foreach ( glob($tuquePath) as $filename) {
    require_once($filename);
}

$url = 'http://localhost:8080/fedora';
$username = 'fedoraAdmin';
$password = 'xxxx';


$connection = new RepositoryConnection($url, $username, $password);
$api = new FedoraApi($connection);
$repository = new FedoraRepository($api, new SimpleCache());

$query =
'select ?pid
from <#ri>
where {?pid <fedora-model:hasModel> <info:fedora/SEAImages:videoCModel> . }
';
$objects = $repository->ri->sparqlQuery($query);

foreach ($objects as $pid){

        $object = islandora_object_load($pid[pid][value]);
        echo "Currently processing".$pid[pid][value]."\n";
        $link = $object['VIDEOLINK']->getContent(''); // getContent expects an argument
        echo "Link: ".$link."\n";
        $ext = explode("/", $link);
        $file = explode(".", end($ext));
        $full_path = "/media/libnas1/tech_services/seadl/mp4/".$file[0].".mp4";
        $dsid = 'MP4';
        $datastream = isset($object[$dsid]) ? $object[$dsid] : $object->constructDatastream($dsid);
        $datastream->label = 'MP4';
        $datastream->mimeType = 'video/mp4';
        $datastream->setContentFromFile($full_path);
        $object->ingestDatastream($datastream);
        $object->relationships->add(FEDORA_MODEL_URI, 'hasModel', 'islandora:sp_videoCModel');
        $object->relationships->remove(FEDORA_MODEL_URI, 'hasModel', 'SEAImages:videoCModel');
        echo "Finished adding MP4 for".$pid."\n\n";
}

?>
