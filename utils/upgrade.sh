#!/bin/sh
#---
# Zula Command line upgrader
#
# Author: Robert Clipsham
# Id: $Id$
# Copyright: Copyright (c) 2008, Robert Clipsham
# Licence: http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
#---

ZULA_MINIMUM_PHP="5.2.0"
PROJECT_LATEST_VERSION="2.2.0"

# Make sure the script is running from the utils directory
if [ "$( basename `pwd` )" != "utils" ]; then
	echo "Please run this script from the utils/ directory."
	exit 1
fi

# Load common functions
source common.sh

#---
# Check all the required PHP extensions are available
#---
check_extensions() {
	FAIL=0
	EXTS=( ctype date dom hash pdo pdo_mysql pcre session simplexml )
	becho Extensions
	for ext in ${EXTS[@]}
	do
		if [ ${#ext} -gt 6 ]
		then
			tabs="\t\t\t\t\t"
		else
			tabs="\t\t\t\t\t\t"
		fi
		$PHP -r "if ( extension_loaded( '$ext' ) ) { exit(1); }"
		if [ $? -eq 1 ]
		then
			echo -e " ${ext}${tabs}\e[1;32mPass\e[m"
		else
			echo -e " ${ext}${tabs}\e[1;31mFail\e[m"
			FAIL=1
		fi
	done
	
	OPT_EXTS=( gd )
	becho 'Optional Extensions'
	for ext in ${OPT_EXTS[@]}
	do
		if [ ${#ext} -gt 6 ]
		then
			tabs="\t\t\t\t\t"
		else
			tabs="\t\t\t\t\t\t"
		fi
		$PHP -r "if ( extension_loaded( '$ext' ) ) { exit(1); }"
		if [ $? -eq 1 ]
		then
			echo -e " ${ext}${tabs}\e[1;32mPass\e[m"
		else
			echo -e " ${ext}${tabs}\e[1;31mFail\e[m"
			FAIL=1
		fi
	done
	return $FAIL
}

#---
# Help function
#---
help() {
	echo -e "\e[1mUsage:\e[m $0 [OPTIONS]"
	echo
	becho "Options"
	echo -e "\t--downloader <command>\tThe command used to download files."
	echo -e "\t--no-check-extensions\tDo not check if the necesary extensions exist"
	echo -e "\t--no-svn\t\tDo not use SVN for upgrades. If neither --use-svn or --nosvn are specified " 
	echo -e "\t\t\t\tor --nosvn are specified then the installer will attempt to autodetect it."
	echo -e "\t--php-path <path>\tSpecify the path to php"
	echo -e "\t--use-svn\t\tUse SVN for upgrades"
	echo -e "\t--user\t\tSet the user to su to before running the script"
	echo -e "\t--verbose\t\tGive debug output"
}

#---
# The actual upgrade
#---
upgrade() {
	cd ../install/
	$PHP index.php /upgrade/stage1 &>/dev/null
	if [ $? -eq 1 ]
	then
		die "Your current TangoCMS version is unsupported by the upgrader"
	fi
	cd ../utils/
	if [ $CHECK_EXTENSIONS -eq 1 ]
	then
		check_extensions || die "Please make sure you have all the necessary extensions before you upgrade"
	fi
	writable_files_dirs || die "Please make sure all the specified files and directories are writable, then run the script again."
}

#---
# Upgrade the script to the latest version
#---
upgrade_script() {
	if [ "$USE_SVN" == "1" ]
	then
		verbose || echo "[debug] SVN directory found, attempting to upgrade using svn"
		cd ..
		UP=`svn up utils/upgrade.sh`
		if [ -n "$( echo $UP | grep 'utils/upgrade.sh' )" ]
		then
			becho "The upgrade script has been updated. Please rerun the script."
			exit 0
		else
			verbose || echo "[debug] No new version of the upgrade script, continuing"
			return 0
		fi
	#else
		# ToDo: Implement it without SVN... the easiet way to do this would be to have the
		#	upgrade script and its dependancies available as a seperate download,
		#	otherwise the whole archive will need redownloading (depending on
		#	implementation)
	fi
}

#---
# Display usage information and exit
#---
usage() {
	echo "Usage: $0 [OPTIONS]"
	exit 1	
}

#---
# Check if the necessary files and directories are writable
#---
writable_files_dirs() {
	FAIL=0
	FILES=( ../config/default/main_frontpage_map.xml 
		../config/default/admin_frontpage_map.xml
		../config/default/config.ini.php
		../config/default/main_sector_map.xml
		../config/default/admin_sector_map.xml
		)
	DIRS=(	../application/logs/
		../tmp/
		../html/uploads/
		)

	becho Files	
	# Check each file is writable
	for file in "${FILES[@]}"
	do
		# Set the number of tabs before the file status to keep them lined up
		if [ ${#file} -ge 40 ]
		then
			tabs="\t"
		elif [ ${#file} -lt 40 ]
		then
			tabs="\t\t"
		fi
		if [ -f $file -a -w $file ]
		then
			echo -e " ${file}${tabs}\e[1;32mWritable\e[m"
		else
			echo -e " ${file}${tabs}\e[1;31mNot Writable\e[m"
			FAIL=1
		fi
	done
	
	becho Directories
	# Check each directory is writable
	for dir in "${DIRS[@]}"
	do
		# Set the number of tabs before the file status to keep them lined up
		if [ ${#dir} -gt 7 ]
		then
			tabs="\t\t\t\t"
		else
			tabs="\t\t\t\t\t"
		fi
		if [ -d $dir -a -w $dir ]
		then
			echo -e " ${dir}${tabs}\e[1;32mWritable\e[m"
		else
			echo -e " ${dir}${tabs}\e[1;31mNot Writable\e[m"
			FAIL=1
		fi
	done
	
	return $FAIL
}

if [ "$( basename `pwd` )" != "utils" ]
then
	die "Please run this script from the utils/ directory."
fi

CHECK_EXTENSIONS=1

#---
# Parse script parameters
#---
while [ "$#" != "0" ]
do
	case "$1" in
 		--downloader)
			if [ -z "$2" ]
			then
				help
				exit 1
			fi
			DOWNLOADER=$2
			shift
		;;
		--no-check-extensions)
			CHECK_EXTENSIONS=0
		;;
		--no-svn)
			USE_SVN=0
		;;
		--php-path)
			if [ -z "$2" ]
			then
				help
				exit 1
			fi
			PHP=$2
			$PHP --help &>/dev/null || die "Specified PHP path is incorrect!"
			shift
       		;;
		--use-svn)
			USE_SVN=1
		;;
		--user)
			if [ -z "$2" ]
			then
				help
				exit 1
			fi
			USER=$2
			shift
		;;
		--verbose)
			VERBOSE=1
		;;
		--help)
			help
			exit 0
		;;	
		*)
		die "Unrecognised option: $1"
		;;
	esac
	shift	
done

if [ -n "$USER" ]
then
	su $USER
fi

# Autodetect SVN
if [ -z "$USE_SVN" ]
then
	if [ -d "../.svn" ]
	then
		which svn &>/dev/null
		if [ "$?" == "0" ]
		then
			USE_SVN=1
		else
			USE_SVN=0
		fi
	else
		USE_SVN=0
	fi
fi

# Autodetect PHP
verbose || echo "[debug] Attempting to find PHP"
if [ -z "$PHP" ]
then
	PHP=`which php 2>/dev/null`
	$PHP --help &>/dev/null || die "Could not find PHP in your path"
fi

# Check PHP version number
verbose || echo "[debug] Checking PHP Version number"
$PHP -r "if ( !version_compare( '$ZULA_MINIMUM_PHP', PHP_VERSION, '<' ) ) { exit(1); }" || die "Your PHP version does not meet the minimum required for Zula: $ZULA_MINIMUM_PHP"

# Autodetect downloader
verbose || echo "[debug] Attempting to find a downloader"
if [ -z "$DOWNLOADER" ]
then
	DOWNLOADER=`which curl 2>/dev/null`
	if [ "$?" != "0" ]
	then
		verbose || echo "[debug] Curl unavailable, attempting to use wget"
		DOWNLOADER=`which wget 2>/dev/null`
		if [ "$?" != "0" ]
		then
			die "Unable to find a downloader in your path. Please specify one using --downloader."
		fi
		DOWNLOADER="$DOWNOADER -qO- "
	else
		DOWNLOADER="$DOWNLOADER -s "
	fi
fi

# Check version type
verbose || echo "[debug] Finding version type"
STABLE=`$PHP -r "include '../application/zula/common.php'; echo zula_version_type( '$PROJECT_LATEST_VERSION' );"`
PROJECT_CURRENT_VERSION=$PROJECT_LATEST_VERSION

# Get the latest version number
if [ "$STABLE" == "stable" ]
then
	verbose || echo "[debug] Project version is stable, attempting to find latest stable version number"
	PROJECT_LATEST_VERSION=`$DOWNLOADER http://releases.tangocms.org/latest/stable`
elif [ "$STABLE" == "unstable" ]
then
	verbose || echo "[debug] Project version is unstable, attempting to find latest unstable version number"
	PROJECT_LATEST_VERSION=`$DOWNLOADER http://releases.tangocms.org/latest/unstable`
else
	echo $STABLE
	die "Failed to check version type!"
fi

# Compare version numbers
verbose || echo "[debug] Comparing current ($PROJECT_CURRENT_VERSION) and latest ($PROJECT_LATEST_VERSION) versions"
$PHP -r "if ( version_compare( '$PROJECT_CURRENT_VERSION', '$PROJECT_LATEST_VERSION', '<' ) ) { exit(1); }"
if [ "$?" == "1" ]
then
	# Attempt to update
	becho "There may be new version of the upgrader script available. Attempting to download..."
	upgrade_script
	upgrade
else
	becho "Already up to date!"
fi
