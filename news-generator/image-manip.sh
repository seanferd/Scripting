#!/bin/bash

PATH=$1;

echo $PATH;

if [ -z "$PATH" ]; then
  echo "Must set the path to the image directory."
  echo "This is generally ~/Code/www/sites/news-generator/articles/_20161010/"
  echo "Exiting...";
  exit;
fi

$( cp /Users/seanferd/Code/www/sites/news-generator/articles/_20161010/shutterstock_251596087.jpg /Users/seanferd/Code/www/sites/news-generator/articles/_20161010/shutterstock_251596087.jpg.2 );

`/usr/local/bin/identify /Users/seanferd/Code/www/sites/news-generator/articles/_20161010/shutterstock_251596087.jpg`;

echo "uid is ${UID}"
echo "user is ${USER}"
echo "username is ${USERNAME}"
