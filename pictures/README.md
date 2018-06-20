#PICTURES.SH
This is an old bash script I put together to resize / move / and add pictures to a database for my website. When it was first created, I barely had enough storage for the pictures I had, let alone with them at full size. This script cut them down to 800x600, and created a 100x75 thumbnail. It creates a DB2 script with all the filenames as well for importing to the database and stages the files for moving to the live images directory. 

Initially I had to run this against every single folder I had individually, but I was able to add in parameters so it could work with a for loop. The flowerbox in the script itself is pretty well written. Check it out!

#PICSILENT.SH
This script is an almost unmodified draft of the original script I was using. This script was intended to become what pictures.sh did, but never made the cut. Essentially this version requires running against a single directory; it will create the resized files and the DB script, but nothing more. It was modified to run quietly whereas the initial script was outputting to STDOUT where it was in the process for almost every image it finished.