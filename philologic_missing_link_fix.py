import sys, urllib2, csv, re
from bs4 import BeautifulSoup
from eulfedora.server import Repository
from collections import defaultdict

HOST = 'http://localhost:8080'
fedoraUser = 'xxx'
fedoraPass = 'xxx'
passwordManager = urllib2.HTTPPasswordMgrWithDefaultRealm()
gsearch = "%s/fedoragsearch/rest" % HOST
passwordManager.add_password(None, gsearch, fedoraUser, fedoraPass)
handler = urllib2.HTTPBasicAuthHandler(passwordManager)
gsearchOpener = urllib2.build_opener(handler)

def main(argv):

    # Connect to repository
    repo = Repository(root='%s/fedora/' % HOST, username='%s' % fedoraUser, password='%s' % fedoraPass)
    # Get philologic pids using content model
    philologic_pids = repo.get_objects_with_cmodel(cmodel_uri='info:fedora/niu-objects:cmodel')

    # Logging
    phil_doc = open('phil_doc_dev.csv', 'w')
    image_ids = []
    d = defaultdict(int)

    for pid in philologic_pids:

        # Logging
        images = []
        image_count = 0

        # Get the OBJ's content as string
        philologic = pid.getDatastreamObject('OBJ').content
        # Take the opportunity to replace deprecated HTML entity reference
        philologic = re.sub("&dot;", ".", philologic)
        # Load OBJ content into soup. Must specify html5lib parser, b/c lxml causes fatal exception (memory leak)
        soup = BeautifulSoup(philologic, "html5lib")
        # Find all ARTFL spans and <a>'s
        spans = soup.find_all("span", "ARTFL-figure-missing")
        links = soup.find_all("a", "ARTFL-figure")

        # Replace /fedora/repository with /islandora/object in existing links
        for a in links:

            href = a['href']
            if href.startswith('/fedora/repository/'):
                a['href'] = '/islandora/object/%s' % href[19:]

        for span in spans:

            # Retreive the sysid and strip the file format.
            title = span['sysid'].split('.')[0]
            # Use sysid as title to send RI query for pid
            results = repo.risearch.sparql_query('select ?pid where {?pid <dc:title> "%s"}' % title)
            try:
                # sparql_query returns CSV object; next will retreive first row.
                # If no results, throw exception and log that image
                p = next(results)['pid'].replace('info:fedora/', '')
                # Create <a> tag with @href pointing to object
                new_tag = soup.new_tag("a", href="/islandora/object/%s/datastream/OBJ/view" % p)
                # B/c it's a reserved word, we have to add @class seperately
                new_tag['class']="ARTFL-figure"
                # Grab and add the <span> string
                new_tag.string = span.string
                # Replace <span> with <a>
                span.replace_with(new_tag)
                print "Successfully changed %s in %s" % (title, pid)
            except:
                print "Failed to locate %s in %s" % (title, pid)
                # Logging
                images.append(title)
                image_count+= 1
                pass

        # Retreive entire OBJ datastream
        obj = pid.getDatastreamObject('OBJ')
        # Replace OBJ content with soup. Encoding as html to maintain entity references.
        obj.content = soup.encode(formatter="html")
        # Save and we're done.
        obj.save()

        # Because GSearch isn't listening, we have to index the update
        url = '%s/fedoragsearch/rest?operation=updateIndex&action=fromPid&value=%s' % (HOST, pid)
        gsearchOpener.open(url)

        # Rest is all logging not founds and errors
        image_ids.extend(images)
        images_string = ';'.join(images)

        phil_doc.write('%s,%s,%s\n' % (pid, image_count, images_string))

    for i in image_ids:
        d[i] += 1

    with open('phil_image_dev.csv', 'w') as outfile:

        phil_image = csv.writer(outfile)

        for key, value in d.items():
            phil_image.writerow([key, value])
            
    phil_doc.close()

if __name__ == '__main__':
    sys.exit(main(sys.argv))
