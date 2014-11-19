import sys, urllib2, time
from eulfedora.server import Repository

HOST = 'localhost:8080'
fedoraUser = 'fedoraAdmin'
fedoraPass = 'xxx'

passwordManager = urllib2.HTTPPasswordMgrWithDefaultRealm()
gsearch = "%s/fedoragsearch/rest" % HOST
passwordManager.add_password(None, gsearch, fedoraUser, fedoraPass)
handler = urllib2.HTTPBasicAuthHandler(passwordManager)
gsearchOpener = urllib2.build_opener(handler)

def main(argv):

    repo = Repository(root='%s/fedora/' % HOST, username='%s' % fedoraUser, password='%s' % fedoraPass).risearch
    query = 'select ?pid where {?pid <fedora-rels-ext:isMemberOfCollection> ?collection . FILTER (?collection= <info:fedora/dimenovels:collection> || ?collection= <info:fedora/niu-dig:collection>)}'
    pids = repo.find_statements(query, language='sparql', type='tuples', flush=None)
    

    for dictionary in pids:

      for key in dictionary:

        p = dictionary[key]
        pid = p.replace('info:fedora/', '')
        
        url = 'http://localhost:8080/fedoragsearch/rest?operation=updateIndex&action=fromPid&value=%s' % pid

        gsearchOpener.open(url)
            
        time.sleep(3.0)

if __name__ == '__main__':
    sys.exit(main(sys.argv))
