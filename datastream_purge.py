import sys, urllib2, time
from eulfedora.server import Repository

HOST = 'xxxx'
fedoraUser = 'xxxx'
fedoraPass = 'xxxx'

def main(argv):

    repo = Repository(root='%s/fedora/' % HOST, username='%s' % fedoraUser, password='%s' % fedoraPass)
    risearch = repo.risearch
    query = 'select ?pid where {?pid <fedora-view:disseminates> ?ds . ?pid <fedora-model:hasModel> <info:fedora/islandora:pageCModel> . ?ds <fedora-view:disseminationType> <info:fedora/*/PDF>}'

    pids = risearch.find_statements(query, language='sparql', type='tuples', flush=None)

    #total = 0
    

    for dictionary in pids:

      for key in dictionary:

        p = dictionary[key]
        pid = p.replace('info:fedora/', '')


        obj = repo.get_object(pid)
        pdf = obj.getDatastreamObject("PDF")
        #size = pdf.size
        #total += size
        obj.api.purgeDatastream(pid, "PDF")
        obj.save()
        
        print "Purged PDF for %s" % pid
            
        #time.sleep(0.2)

    #print total

if __name__ == '__main__':
    sys.exit(main(sys.argv))
