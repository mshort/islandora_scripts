#!/usr/bin/env drush

<?php

$connection = islandora_get_tuque_connection();
$children = $connection->repository->ri->sparqlQuery('select distinct ?pid where {?pid <fedora-rels-ext:isMemberOfCollection> <info:fedora/rbsc:schreiner> ; <fedora-model:hasModel> <info:fedora/islandora:sp_large_image_cmodel> . OPTIONAL {?pid <fedora-rels-ext:isConstituentOf> ?parent .}FILTER (!BOUND(?parent))}', 'rows');

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
                $parent_mods = $parent['MODS']->content;
                //$parent_label = $parent->label;
                //$object->label = $parent_label . " (page " . intval($sequence) . ")";
                $object['MODS']->setContentFromString($parent_mods);
                echo "Done processing parent and child.\n\n";
        } else {
                echo "----------\nFailed to find parent for" . $label . "\n----------\n\n";
        }
}

?>
