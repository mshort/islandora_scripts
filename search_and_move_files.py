import os, shutil
from eulfedora.server import Repository

HOST = 'http://localhost:8080'
fedoraUser = 'xxx'
fedoraPass = 'xxx'

files_to_find = []

repo = Repository(root='%s/fedora/' % HOST, username='%s' % fedoraUser, password='%s' % fedoraPass)
results = repo.risearch.sparql_query('select ?pid where {?pid <fedora-model:hasModel> <info:fedora/islandora:sp_basic_image> ; <fedora-rels-ext:isMemberOfCollection> <info:fedora/seadl:collection> . }')
for row in results:
    for k, v in row.items():
        files_to_find.append(v.replace('info:fedora/SEAImages:', '')+'.tif')

print files_to_find

for root, dirs, files in os.walk('N://DigLab Projects/SEA/SEA Master Files/Master tiffs'):
    for _file in files:
        if _file in files_to_find:
            print 'Found file in: ' + str(root)
            shutil.copy(os.path.abspath(root + '/' + _file), 'N://tech_services/seadl/tiffs')
