#!/bin/sh

#---
# Zula JavaScript source compressor
#
# Author: Alex Cartwright
# Id: $Id$
# Copyright: Copyright (C) 2009 Alex Cartwright
# Licence: http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
#---

	RETVAL=0;

	# Setup needed vars
	if [ -z "$1" ]; then
		echo "Error: missing path to YUI Compressor JAR file"
		exit 1
	else
		yuiPath=$1
	fi
	if [ -z "$2" ]; then
		srcPath="."
	else
		srcPath=$2
	fi

	for file in `find $srcPath -name "*.src.js"`; do
		fileName=`basename $file .src.js`.js
		$yuiPath --type js --charset utf8 $file -o `dirname $file`/$fileName
	done

	# All done
	exit $RETVAL
