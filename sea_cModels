import sys, urllib
from islandoraUtils.metadata import fedora_relationships
from utils.commonFedora import *
import pdb

#Get etree from somewhere it should be...
try:
    from lxml import etree
    print("running with lxml.etree")
except ImportError:
    try:
        # Python 2.5
        import xml.etree.cElementTree as etree
        print("running with cElementTree on Python 2.5+")
    except ImportError:
        try:
            # Python 2.5
            import xml.etree.ElementTree as etree
            print("running with ElementTree on Python 2.5+")
        except ImportError:
            try:
                # normal cElementTree install
                import cElementTree as etree
                print("running with cElementTree")
            except ImportError:
                try:
                    # normal ElementTree install
                    import elementtree.ElementTree as etree
                    print("running with ElementTree")
                except ImportError:
                    message = "Failed to import ElementTree from any known place"
                    print(message)
                    raise ImportError(message)

HOSTURL = "localhost"

def getPidsForContentModel(contentModel):
    if not (contentModel.startswith("<") or contentModel.endswith(">")):
        contentModel = "<info:fedora/%s>" % contentModel

    # start with the query to pull the desired objects
    query_string = "select $object from <#ri> where $object <fedora-model:hasModel> %s" % contentModel
    url = urllib.urlopen("http://%s:8080/fedora/risearch?type=tuples&flush=TRUE&format=Sparql&lang=itql&stream=on&query=%s" % (HOSTURL, urllib.quote_plus(query_string)))

    # create the xml parser to retrieve the results
    parser = etree.XMLParser(remove_blank_text=True)
    xmlFile = etree.parse(url, parser)
    xmlFileRoot = xmlFile.getroot()

    results = []
    pids = []
    ns = { "results" : "http://www.w3.org/2001/sw/DataAccess/rf1/result" }
    xmlPids = xmlFileRoot.xpath("/results:sparql/results:results/results:result/results:object", namespaces=ns)
    pids = [p.attrib["uri"] for p in xmlPids]
    return pids

def editRelsExt(rels_ext, rels_predicate, newvalue):
    # remove this relation
    rels_ext.purgeRelationships(predicate=rels_predicate)
    # add
    rels_ext.addRelationship(predicate=rels_predicate, object=newvalue)

def commitRelsExt(rels_ext):
    loop = True
    while loop:
        loop = False
        try:
            rels_ext.update()
        except FedoraConnectionException, fedoraEXL:
            if str(fedoraEXL.body).find("is currently being modified by another thread") != -1:
                loop = True
                print("Trouble (thread lock) updating obj(%s) RELS-EXT - retrying." % childObject.pid)
            else:
                print("Error updating obj(%s) RELS-EXT" % childObject.pid)
                return False
    return True

def main(argv):

    fedora = connectToFedora("localhost", "fedoraAdmin", "password")
    if not fedora:
        print("Failed to connect to fedora instance")
        return 1

    ### SCAN FOR OBJECTS
    oldModel = "ilives:pageCModel"
    newModel = "islandora:pageCModel"
    objects = getPidsForContentModel(oldModel)

    print("Found %d objects to update" % len(objects))
    for pid in objects:
        strippedPid = pid.replace('info:fedora/', '')
        print(strippedPid) + " ...",
        try:
            obj = fedora.getObject(strippedPid)
        except FedoraConnectionException, fcx:
            print("Failed to connect to object %s" % pid)
            continue

        nsmap = [ fedora_relationships.rels_namespace('fedora', 'info:fedora/fedora-system:def/relations-external#'),
                  fedora_relationships.rels_namespace('fedora-model', 'info:fedora/fedora-system:def/model#')
                 ]
        rels_ext = fedora_relationships.rels_ext(obj, nsmap, 'fedora')
        editRelsExt(rels_ext, ["fedora-model", "hasModel"], newModel)
        commitRelsExt(rels_ext)
        print("Done")

if __name__ == '__main__':
    sys.exit(main(sys.argv))
