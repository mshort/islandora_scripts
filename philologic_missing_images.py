## Generates a report for missing Philologic images
import sys, urllib2, csv
from eulfedora.server import Repository
from bs4 import BeautifulSoup
from collections import defaultdict

HOST = 'http://localhost:8080'
fedoraUser = 'xxxxx'
fedoraPass = 'xxxxx'

def main(argv):

    repo = Repository(root='%s/fedora/' % HOST, username='%s' % fedoraUser, password='%s' % fedoraPass)
    
    philologic_pids = repo.get_objects_with_cmodel(cmodel_uri='info:fedora/niu-objects:cmodel')

    phil_doc = open('phil_doc.csv', 'w')

    image_ids = []
    d = defaultdict(int)
        
    for p in philologic_pids:
        
        philologic = p.getDatastreamObject('OBJ').content

        substring = 'ARTFL-figure-missing'

        if substring in philologic:

            print 'Processing %s' % p
           
            images = []
            image_count = 0

            url = '%s/fedora/objects/%s/datastreams/OBJ/content' % (HOST, p)

            passwordManager = urllib2.HTTPPasswordMgrWithDefaultRealm()
            fedoraAdmin = "%s/fedora" % HOST
            passwordManager.add_password(None, fedoraAdmin, fedoraUser, fedoraPass)
            handler = urllib2.HTTPBasicAuthHandler(passwordManager)
            fedoraOpener = urllib2.build_opener(handler)

            soup = BeautifulSoup(fedoraOpener.open(url), 'html.parser')

            spans = soup.find_all('span', 'ARTFL-figure-missing')

            for span in spans:
                
                image = span['sysid']
                images.append(image)
                image_count+= 1

            image_ids.extend(images)
            images_string = ';'.join(images)

            phil_doc.write('%s,%s,%s\n' % (p, image_count, images_string))

            print 'Successfully processed %s' % p

    for i in image_ids:
        d[i] += 1

    with open('phil_image.csv', 'w') as outfile:

        phil_image = csv.writer(outfile)

        for key, value in d.items():
            phil_image.writerow([key, value])
            
    phil_doc.close()
            

if __name__ == '__main__':
    sys.exit(main(sys.argv))
