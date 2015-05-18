import os, time

source = raw_input("Enter the source directory name: ")
#destination = raw_input("Enter the destination directory name: ")

sub_list = os.listdir("%s" % source)
sub_list.sort

for sub_dir in sub_list:

    file_list = os.listdir("%s/%s" % (source, sub_dir))
    file_list.sort
    
    page_count = 0
        
    for filename in file_list:

        if filename.endswith(".xml"):
            os.rename(os.path.join(source, sub_dir, filename), os.path.join(source, sub_dir, "MODS.xml"))

        if filename.endswith(".tif" or ".tiff"):
            page_count += 1
            os.makedirs(os.path.join(source, sub_dir, str(page_count)))
            os.rename(os.path.join(source, sub_dir, filename), os.path.join(source, sub_dir, str(page_count), "OBJ.tif"))

    print "Completed %s" % sub_dir
