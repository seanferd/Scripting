#!/bin/bash
########################################################################
#                        pictures.sh
#                  Written by Sean R Ford 2007
########################################################################
#This script was written to resize pictures for use on the website. It
#takes the original and resizes to 800x600 and makes a thumbnail from
#the resized file that's 100x75. It accounts for landscape and portrait
#since the website was never written to account for them, the working
#solution is to have a block of landscape pictures, then a block of portrait
#underneath. After resizing we create a script for DB2 to update its tables
#for the new pictures. Eventually would like to automate this as well, along 
#with moving the resized pictures to the website's pictures directories.
#
#Currently it will accept up to two parameters [INPUT DIR] and [OUTPUT DIR], this
#allows for easier scripting for multiple directories or redirected output for
#space contraints. It still works under the old behavior as well; running
#against ./ - wherever the script is invoked from - if no parameters are passed.
#
#I began writing this in 2007 as a timesaver for all of the convert steps required
#per picture. I got tired of doing all of these commands manually and eventually
#threw the commands into a simple script, but still did almost all of the work manually
#over time some logic has been added, along with the extra parameters. However, 
#there is still not much sanity checking and error checking is non-existent, instead
#relying on any failures to harmlessly error out. Though this does nothing to help the
#end user. 
#
#TODO: Sanity checking for inputs
#Error handling
#DB2 'integration', i.e. UPDATE table from the script automatically
#Move completed pictures to website pictures directory
#Handle MOV/MPG video files - move them to a central location, perhaps add them to the site
#Create a directory for OUTDIR that may be several layers deep xx/junk/2010 will fail if junk doesn't exist first
#Maybe have DB2 store the actual pictures itself to become fully integrated


#Sanitize user input to remove trailing slash - at this time, cannot accept ./ or any nested directory - e.g. /home/sean/tablet/picdir/ 
CLEAN=$1;
CLEAN=${CLEAN%/};
INPATH=$CLEAN;

#Output path, shouldn't need to sanitize
CLEAN=$2;
OUTPATH=$CLEAN;

if [ -z "$INPATH" ]; then #if INPATH is not set, default it to .
        export INPATH=".";
fi

if [ -n "$OUTPATH" ]; then #verify OUTPATH is not null and add a trailing slash if missing
	OUTPATH=$( echo $OUTPATH | sed 's/\/$//' ); #strip any trailing slash, if present
	#OUTPATH=$OUTPATH/; #add trailing slash back, to ensure we have one
	if [ ! -d $OUTPATH ]; then
		mkdir $OUTPATH;
	fi
else
	OUTPATH=$INPATH; #if outpath is not set, put files in the current dir
	echo $OUTPATH;
fi

rm $INPATH/Thumbs.db;
rename 's/ /-/g' $INPATH/*;
rename 'y/A-Z/a-z/' $INPATH/*;
mkdir $OUTPATH/100;
mkdir $OUTPATH/800;
mkdir $OUTPATH/tall;

find $INPATH/* -type f -iname \*.jpg | while read ITEM; do DIM=($(identify "${ITEM}"))
 [ ${DIM[2]//*x/} -gt ${DIM[2]//x*/} ] && mv "${ITEM}" $OUTPATH/tall/ && touch $OUTPATH/look
done

echo 'Converting long images..';

for i in $INPATH/*.jpg;
do
	image=$( echo "$i" | cut -d '/' -f 2 ); 
	dir=$( echo "$i" | cut -d '/' -f 1 );
	convert $INPATH/$image -geometry 800x600 $OUTPATH/800/$image;
	convert $OUTPATH/800/$image -geometry 100x75 $OUTPATH/100/$image;
done

echo 'Done!';

#Attempting to check if there are any jpg files in the 'tall' directory
if [ -f $OUTPATH/look ]; then
	echo 'Converting tall images..';

	for i in $OUTPATH/tall/*.jpg;
	do
		#PIC=`echo $i | cut -d'/' -f3`;
		PIC=$( basename $i );
		convert $i -geometry 600x800 $OUTPATH/800/$PIC;
		convert $OUTPATH/800/$PIC -geometry 75x100 $OUTPATH/100/$PIC;
	done

	echo 'Done!';
else
	echo 'No tall images..';
fi

for i in $INPATH/*.jpg;
do
	PIC=$( echo "$i" | cut -d '/' -f 2 );
	echo "insert into pics (path,layout) values ('$PIC',1);" >> $OUTPATH/db.txt;

done

echo 'Creating DB entries..';

if [ -f $OUTPATH/look ]; then
	for i in $OUTPATH/tall/*.jpg;
	do

		#PIC=`echo $i | cut -d'/' -f3`;
		PIC=$( basename $i );
		if [ $PIC != '*.jpg' ];	then
			echo "insert into pics (path,layout) values ('$PIC',2);" >> $OUTPATH/db.txt;
		fi
	done

#Cleanup
rm $OUTPATH/look;
cp $OUTPATH/tall/* $INPATH; #attempt to put tall files back to original location, in the case of using an actual outpath, this splits up where all the files are located

echo 'Done!';
else
	echo 'No tall to add to DB..';
	echo 'Done!';
fi

