import pymarc, codecs, urllib, requests, sys, re
from collections import defaultdict
from lxml import etree
from eulfedora.server import Repository
from eulxml import xmlmap

xmlschema = etree.XMLSchema(etree.parse(urllib.urlopen('http://www.loc.gov/standards/mods/v3/mods-3-5.xsd')))

HOST = 'http://localhost:8080'
fedoraUser = 'xxx'
fedoraPass = 'xxx'


pids = []
repo = Repository(root='%s/fedora/' % HOST, username='%s' % fedoraUser, password='%s' % fedoraPass)
results = repo.risearch.sparql_query('select ?pid where {?pid <fedora-rels-ext:isMemberOfCollection> <info:fedora/dekalb:collection>}')
for row in results:
    for k, v in row.items():
        pids.append(v.replace('info:fedora/', ''))

regexpNS = 'http://exslt.org/regular-expressions'
modsNS = 'http://www.loc.gov/mods/v3'

review = []

for p in pids:

    print "Currently processing %s\n" % p

    pid = repo.get_object(p)
    mods = pid.getDatastreamObject('MODS').content
    parser = etree.XMLParser(remove_blank_text=True)
    root = etree.XML(mods.serialize(pretty=True), parser)

    try:


        print "... Retrieving date ...\n"


        for date in root.findall(".//{http://www.loc.gov/mods/v3}dateCreated"):
            print "Retrieved date: %s\n" % date.text
            response = raw_input('Do you wish to change this date? (y/n): ')
            if response == 'y':
                new_date = raw_input('Enter date here (YYYY-MM-DD): ')
                date.set('keyDate', 'yes')
                date.set('encoding', 'w3cdtf')
                response = raw_input('Do you wish to add a qualifier? (y/n): ')
                if response == 'y':
                    qualifier = raw_input('Enter qualifier: ')
                    date.set('qualifier', qualifier)
                date.text = new_date
                print "\n"
            else:
                review.append(p)
            


        # Validate record and save to file    
       
        if xmlschema.validate(root) is True:  
            print(etree.tostring(root, pretty_print=True))
            root = xmlmap.load_xmlobject_from_string(etree.tostring(root, pretty_print=True))
            obj = pid.getDatastreamObject('MODS')
            if obj.content != root:
                obj.content = root
                obj.save()
        else:
            print "Record failed to validate."
            review.append(p)
    except:
        continue

print review
