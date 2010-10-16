#!/bin/sh

###
# Zula Framework CLI
#
# @author Alex Cartwright
# @copyright Copyright (c) 2010 Alex Cartwright
# @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
# @package Zula_Cli
###

## Default config profile to use
optConfig=default

if [[ ! -f index.php || ! -d config ]]; then
	echo "---- Please run from the root directory of your Zula Framework application." >&2
	exit 2
fi

pathPHP=$(which php 2> /dev/null)
if [[ -z $pathPHP || $($pathPHP -v | grep "(cli)" &> /dev/null; echo -n $?) -ne 0 ]]; then
	echo "---- Unable to find PHP (CLI) binary, please install or update your PATH variable." >&2
	exit 2
fi

## See what action we need to run
case "$1" in
	--request)
		##
		## Create a new stateless request to the provided request path
		##
		while [[ $2 == -* ]]; do
			case "$2" in
				-c)
					optConfig="$3"
					shift 2
					;;
				-h)
					echo "Zula Framework (cli)"
					echo -e "Usage: zula.sh --request [options] requestPath"
					echo -e "\nOptions:"
					echo -e "\t-c\tConfiguration name to use."
					echo -e "\t-h\tDisplays this help text.\n"
					echo "Report bugs to <bugs@tangocms.org>"
					exit 0
					;;
				-*)
					echo "Invalid argument '$2'. See '-h' for help text." >&2
					exit 1
					;;
			esac
		done

		## Get output from the provided request path
		$pathPHP -f index.php -- -c "$optConfig" -r "${!#}"
		exit $?
		;;

	--upgrade)
		##
		## Attempt to upgrade to the latest version
		##
		while [[ $2 == -* ]]; do
			case "$2" in
				-h)
					echo "Zula Framework (cli)"
					echo -e "Usage: zula.sh --upgrade\n"
					echo "Report bugs to <bugs@tangocms.org>"
					exit 0
					;;
				-*)
					echo "Invalid argument '$2'. See '-h' for help text." >&2
					exit 1
					;;
			esac
		done

		if [[ ! -f setup/index.php ]]; then
			echo "---- Unable to find 'setup/index.php', failed to upgrade." >&2
			exit 2
		fi
		count=0
		for config in $(find config -maxdepth 1 -type d -path "config/*" \! -name "default.dist" -printf '%f\n'); do
			if (( $count > 0 )); then
				echo
			fi
			echo "Starting upgrade for '$config' ..."
			(cd setup && $pathPHP -f index.php -- -c "$config" -r upgrade/version)
			let count++;
		done;
		if (( $count == 0 )); then
			echo "There are no available configurations."
		fi
		exit 0
		;;

	*)
		echo "Zula Framework (cli)"
		echo -e "Usage: zula.sh |--request|--upgrade]\n"
		echo "Options:"
		echo -e "\t-h\tDisplays this help text, or help for each action e.g. zula.sh --request -h\n"
		echo "Report bugs to <bugs@tangocms.org>"
		;;
esac

exit 0