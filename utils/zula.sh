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

if [ ! -f index.php ]; then
	echo "---- Unable to find 'index.php', please run from root directory of your Zula Framework application."
	exit 1
fi

PATH_PHP=`which php 2> /dev/null`
if [ -z "$PATH_PHP" -o `$PATH_PHP -v | grep "(cli)" &> /dev/null; echo $?` -ne 0 ]; then
	echo "---- Unable to find PHP (CLI) binary, please install or update your PATH variable."
fi

while [[ $1 == -* ]]; do
	case "$1" in
		-c)
			OPT_CONFIG="$2"
			shift 2
			;;
		-h)
			echo -e "Zula Framework (cli)"
			echo -e "Usage:\tzula.sh [options] request-path"
			echo -e "\nOptions:"
			echo -e "\t-c\tConfiguration name to use."
			echo -e "\t-h\tDisplays this help text.\n"
			echo "Report bugs to <bugs@tangocms.org>"
			exit 0
			;;
		-*)
			echo "Invalid argument '$1'. See '-h' for help text."
			exit 1
			;;
	esac
done

$PATH_PHP -f index.php "$OPT_CONFIG" "${!#}"

exit $?