#---
# Common functions for use in all utility scripts
#
# Author: Robert Clipsham
# Copyright: Copyright (c) 2008, Robert Clipsham
# Licence: http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
#---

#---
# Bold echo
#  Params:
#	$1 - String to echo
#	$2 - Extra paramaters to pass to echo
#---
becho() {
	echo $2 -e "\e[1m$1\e[m"
}

#---
# Die function
#  Params:
#	$1 - Message
#---
die() {
	becho "$1"
	exit 1
}

#---
# Return if verbose is enabled
#---
verbose() {
	if [ "$VERBOSE" == "1" ]; then
		return 1
	else
		return 0
	fi
}
