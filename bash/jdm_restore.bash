#!/bin/bash


dir="chunk"
mv_dir="chunk_ok"

if [ ! -d $dir ]
then
	echo "Error dir $dir not exists";
fi

files=$(ls $dir -1 | sort -n)

rm import.log import.error.log
mkdir $mv_dir

i=0
max=30

for file in $files
do
  if [ $i -eq $max ]
  then
    break
  fi
  echo $file
  ./mongoimport --db jdm --collection relations --type json --file "$dir/$file" --jsonArray >> import.log 2>> import.error.log && mv "$dir/$file" "$mv_dir/$file"
  $i=$((i++))
done
