#!/bin/bash

rm Thumbs.db;
rename 's/ /-/g' *;
rename 'y/A-Z/a-z/' *;
mkdir 100;
mkdir 800;
mkdir tall;

for i in *.jpg;
do
	DIM=`identify $i | cut -d' ' -f3 | cut -dx -f1,2`;
	WIDTH=`echo $DIM | cut -dx -f1`;
	HEIGHT=`echo $DIM | cut -dx -f2`;

	if [ $HEIGHT -gt $WIDTH ]; then
		mv $i ./tall;
	fi
done

#echo 'Converting long images..';

for i in *.jpg;
do
	convert $i -geometry 800x600 ./800/$i;
	convert ./800/$i -geometry 100x75 ./100/$i;
done

#echo 'Done!';
#if [ `ls ./tall` ]; then
	#echo 'Converting tall images..';

	for i in ./tall/*.jpg;
	do
		PIC=`echo $i | cut -d'/' -f3`;
		convert $i -geometry 600x800 ./800/$PIC;
		convert ./800/$PIC -geometry 75x100 ./100/$PIC;
	done

	#echo 'Done!';
#else
#	echo 'No tall images..';
#fi

for i in *.jpg;
do

	echo "insert into pics (path,layout) values ('$i',1);" >> db.txt;

done

#if [ `ls ./tall` ]; then
	for i in ./tall/*.jpg;
	do

		PIC=`echo $i | cut -d'/' -f3`;

		if [ $PIC != '*.jpg' ];	then
			echo "insert into pics (path,layout) values ('$PIC',2);" >> db.txt;
		fi
	done
#else
#	echo 'No tall to add to DB..';
#	echo 'Done!';
#fi

