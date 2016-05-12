#!/usr/bin/env drush

// Adds children to compound objects after batch ingest. Updates child label and MODS. Updates parent thumbnail.
// Requires child label with parent identifier and sequence number.

// Author: Matthew Short
// Date: 2016-05-12

<?php

$connection = islandora_get_tuque_connection();
$children = $connection->repository->ri->sparqlQuery('select distinct ?pid where {?pid <fedora-rels-ext:isMemberOfCollection>+ <info:fedora/rbsc:schreiner> ; <fedora-model:hasModel> <info:fedora/islandora:sp_large_image_cmodel> .}', 'ro$

foreach ($children as $row){
        $pid = $row['pid']['value'];
        echo "Currently processing child: " . $pid . "\n";
        $object = $connection->repository->getObject($pid);
        $label = $object->label;
        echo "Child has label: " . $label . "\n";
        list($identifier, $sequence) = explode("-", $label);
        echo "Child has parent identifier: " . $identifier . "\n";
        echo "Child has sequence in parent: " . $sequence . "\n";

        $url = parse_url(variable_get('islandora_solr_url', 'localhost:8080/solr'));
        $solr = new Apache_Solr_Service($url['host'], $url['port'], $url['path'] . '/');
        $solr->setCreateDocuments(FALSE);
        $query = 'mods_identifier_local_ms:"' . $identifier . '"';
          $params = array(
                'fl' => 'PID',
          );
        $results = $solr->search($query, 0, 1, $params);
        $json = json_decode($results->getRawResponse(), TRUE);
        $parent = $json['response']['docs'][0]['PID'];
        if (!empty($parent)){
                echo "Child has parent: " . $parent . "\n";
                $parent = $connection->repository->getObject($parent);
                $object->relationships->add(FEDORA_RELS_EXT_URI, 'isConstituentOf', $parent);
                $object->relationships->add(ISLANDORA_RELS_EXT_URI, "isSequenceNumberOf" . str_replace(':', '_', $parent), intval($sequence), RELS_TYPE_PLAIN_LITERAL);
                $object->relationships->remove(FEDORA_RELS_EXT_URI, 'isMemberOfCollection', 'rbsc:schreiner');
                $parent_mods = $parent['MODS']->content;
                $object->label = $parent->label . " (page " . intval($sequence) . ")";
                $object['MODS']->setContentFromString($parent_mods);
                echo "Done processing parent and child.\n\n";
        } else {
                echo "----------\nFailed to find parent for" . $label . "\n----------\n\n";
        }
}

$parents = $connection->repository->ri->sparqlQuery('select distinct ?pid where {?pid <fedora-rels-ext:isMemberOfCollection>+ <info:fedora/rbsc:schreiner> ; <fedora-model:hasModel> <info:fedora/islandora:compoundCModel> .}', 'rows');

foreach ($parent as $row){
        $pid = $row['pid']['value'];
        $object = $connection->repository->getObject($pid);
        islandora_compound_object_update_parent_thumbnail($object);
}

?>
