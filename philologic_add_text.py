## Script to extract text from Philologic documents and add as FULL_TEXT datastreams

import sys
from eulfedora.server import Repository
from HTMLParser import HTMLParser

class MLStripper(HTMLParser):
    def __init__(self):
        self.reset()
        self.fed = []
    def handle_data(self, d):
        self.fed.append(d)
    def get_data(self):
        return ''.join(self.fed)

def strip_tags(html):
    s = MLStripper()
    s.feed(html)
    return s.get_data()

def main(argv):

    # Make Fedora connection
    repo = Repository(root='http://localhost:8080/fedora/', username='fedoraAdmin', password='xxxxx')
    
    # Retreive pids using content model
    philologic_pids = repo.get_objects_with_cmodel(cmodel_uri='info:fedora/niu-objects:cmodel')

    # Loop through Philologic pids and retreive each object
    for p in philologic_pids:

        print 'Processing %s' % p

        # Extract the text

        philologic = p.getDatastreamObject('OBJ').content
        text=strip_tags(philologic)

        # Add FULL_TEXT
        
        full_text = p.getDatastreamObject('FULL_TEXT')
        full_text.label='Full text'
        full_text.mimetype='text/plain'
        full_text.versionable=True
        full_text.state='A'
        full_text.checksum_type='MD5'

        full_text.content = text

        full_text.save()
        

if __name__ == '__main__':
    sys.exit(main(sys.argv))
