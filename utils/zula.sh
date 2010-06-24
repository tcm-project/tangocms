#!/bin/bash
#---
# Zula Framework CLI
#
# @author Alex Cartwright
# @copyright Copyright (c) 2010 Alex Cartwright
# @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
# @package Zula_Cli
#---

## All options with their default values
OPT_CONFIG=default
## Available tasks
TASK_UPGRADE=false

if [ ! -f index.php -o ! -d config ]; then
	echo "---- Please run from the root directory of your Zula Framework application." >&2
	exit 1
fi

PATH_PHP=`which php 2> /dev/null`
if [ -z "$PATH_PHP" -o `$PATH_PHP -v | grep "(cli)" &> /dev/null; echo $?` -ne 0 ]; then
	echo "---- Unable to find PHP (CLI) binary, please install or update your PATH variable." >&2
	exit 1
fi

while [[ $1 == -* ]]; do
	case "$1" in
		--upgrade)
			TASK_UPGRADE=true
			shift
			;;
		-c)
			OPT_CONFIG="$2"
			shift 2
			;;
		-h)
			echo -e "Zula Framework (cli)"
			echo -e "Usage:\tzula.sh [--upgrade]"
			echo -e "Usage:\tzula.sh [options] request-path"
			echo -e "\nOptions:"
			echo -e "\t-c\tConfiguration name to use."
			echo -e "\t-h\tDisplays this help text.\n"
			echo "Report bugs to <bugs@tangocms.org>"
			exit 0
			;;
		-*)
			echo "Invalid argument '$1'. See '-h' for help text." >&2
			exit 1
			;;
	esac
done

if [ $TASK_UPGRADE == true ]; then
	## Attempt to upgrade to the latest version
	if [ ! -f install/index.php ]; then
		echo "---- Unable to find 'install/index.php', failed to upgrade." >&2
		exit 1
	fi
	count=0
	for CONFIG in `find config -maxdepth 1 -type d -path "config/*" \! -name "default.dist" -printf '%f\n'`; do
		if [ $count -gt 0 ]; then
			echo -en "\n"
		fi
		echo "Starting upgrade for '$CONFIG' ..."
		(cd install && $PATH_PHP -f index.php "$CONFIG" upgrade/stage1)
		let count++;
	done;
	exit 0
else
	## Get output from the provided request path
	$PATH_PHP -f index.php "$OPT_CONFIG" "${!#}"
	exit $?
fi