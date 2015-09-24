#NIU Islandora Migration Scripts

This repository contains assorted scripts used during the migration of NIU's digital collections into Islandora. Also included are scripts and stylesheets used to correct problems with earlier migrations.

##Metadata Remediation

There were significant problems with the metadata migrated into Islandora. In part, this was because NIU provided Discovery Garden with a bad mapping from the Lincoln database to MODS, including elements that don't exist. There was also a great deal of variety between collections and content types, so one mapping didn't fit all. The xsl directory contains the stylesheets used to address some of these problems. This includes MODS>MODS transformations for the historical collections (Lincoln-Net, Twain, etc.) and DC>MODS transformations for SEADL. transform.py was the original script used to apply these transforms.

##Philologic

Many of the Philologic documents reference images that were never migrated. These were mostly illustrations and figures, which didn't have metadata in the Lincoln database. When Discovery Garden ran the migration, they identified these missing images for us within the Philologic documents using the span "ARTFL-figure-missing." philologic_missing_images.py retrieves the sysid in these spans, which I used to retrieve the images themselves from the decomissioned Lincoln fileserver. These were then batch ingested as basic or large images into philologic:collection. philologic_missing_link_fix.py replaces the spans in every Philologic document with the appropriate reference to the newly-created image object in Fedora.

The contents of the Philologic documents were not being indexed. I'm sure it's possible to index HTML, even non-standard HTML, but I wasn't having much luck on that front. Instead, philologic_add_text.py extracts the text from these documents, removes all HTML entities, and adds the resulting document as a seperate datastream, TEXT. I was able to index TEXT.

The original intention was to convert Philologic to TEI. ate2tei.xsl was an early effort. It's included here in the event that this work is picked up again.

##Southeast Asia Digital Library

We had TIFFs for many of the images in SEADL, but only the JPEGs and DJVU derivatives were ingested. seadl_tiff.php searches for these TIFFs by filename on the NAS, then adds them as OBJ datastreams to existing objects, creates appropriate derivatives, and changes the content model from basic image to large image. I didn't remove the DJVU and MEDIUM_SIZE datastreams from these objects, just in case there was a problem with the TIFF migration.

We were unable to find TIFFs for many of the basic images. The Southeast Asian curator felt strongly that the same viewer should be used for every image -- in this case, OpenSeadragon. I didn't have permission to investigate the possibility of using OpenSeadragon as a viewer for basic images. jpeg_to_tiff.php converts the MEDIUM_SIZE of existing basic images to TIFF using ImageMagick, then proceeds as above.

Most vidoes were not ingested into Fedora. Instead, a link to the file on a streaming server was added as a datastream. I converted all of these files from WMV to MP4 using ffmpeg:

for i in *.wmv; do ffmpeg -i "$i" -y -f mp4 -vcodec libx264 -preset medium -acodec libfaac -ab 128k -ac 2 -async 1 -movflags faststart "/media/libnas1/DigLab Projects/SEA/SEA Media/output/${i%.*}.mp4"; done

add_datastream_from_file.php adds these MP4s to the existing video objects and replaces the SEADL video cModel with the Islandora video cModel.

sea_cModels.py converts custom content models in use with Islandora 6 into content models for Islandora 7.

Many collections were purged prior to migration, but the relationships to those collections were not removed. remove_collection.php removes these phantom collection references.

