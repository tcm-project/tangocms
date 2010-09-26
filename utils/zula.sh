#!/bin/sh

###
# Zula Framework CLI
#
# @author Alex Cartwright
# @copyright Copyright (c) 2010 Alex Cartwright
# @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
# @package Zula_Cli
###

## All options with their default values
optConfig=default

## Available tasks
taskUpgrade=false

if [[ ! -f index.php || ! -d config ]]; then
	echo "---- Please run from the root directory of your Zula Framework application." >&2
	exit 2
fi

pathPHP=$(which php 2> /dev/null)
if [[ -z $pathPHP || $($pathPHP -v | grep "(cli)" &> /dev/null; echo $?) -ne 0 ]]; then
	echo "---- Unable to find PHP (CLI) binary, please install or update your PATH variable." >&2
	exit 2
fi

while [[ $1 == -* ]]; do
	case "$1" in
		--upgrade)
			taskUpgrade=true
			shift
			;;
		-c)
			optConfig="$2"
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

if [[ $taskUpgrade = true ]]; then
	## Attempt to upgrade to the latest version
	if [[ ! -f install/index.php ]]; then
		echo "---- Unable to find 'install/index.php', failed to upgrade." >&2
		exit 2
	fi
	count=0
	for config in $(find config -maxdepth 1 -type d -path "config/*" \! -name "default.dist" -printf '%f\n'); do
		if (( $count > 0 )); then
			echo -en "\n"
		fi
		echo "Starting upgrade for '$config' ..."
		(cd install && $pathPHP -f index.php "$config" upgrade/stage1)
		let count++;
	done;
	if (( $count == 0 )); then
		echo "There are no available configurations."
	fi
	exit 0
else
	## Get output from the provided request path
	$pathPHP -f index.php "$optConfig" "${!#}"
	exit $?
fi