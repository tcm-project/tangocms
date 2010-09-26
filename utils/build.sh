#!/bin/sh

###
# Zula Framework building tools
#
# @author Alex Cartwright
# @author Evangelos Foutras
# @author Robert Clipsham
# @copyright Copyright (c) 2010 Alex Cartwright
# @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
# @package Zula_Utils
###

## All options and tasks with their default values
optPathClosure=/usr/share/java/closure-compiler/closure-compiler.jar
optVerbose=false
optPackageMode=production
optIsMsWag=false

taskJsCompress=false
taskPackage=false
taskCheckExceptions=false
taskLint=false

while [[ $1 == -* ]]; do
	case "$1" in
		-j)
			taskJsCompress=true
			if [[ $# -gt 1 && $2 =~ ^[^-] ]]; then
				optPathClosure="$2"
				shift 2;
			else
				shift
			fi
			;;
		-l)
			taskLint=true
			shift
			;;
		-m)
			optIsMsWag=true
			shift
			;;
		-p)
			taskPackage=true
			if [[ $# -gt 1 && $2 =~ ^[^-] ]]; then
				optPackageMode="$2"
				shift 2;
			else
				shift
			fi
			;;
		-v)
			optVerbose=true
			shift
			;;
		-x)
			taskCheckExceptions=true
			shift
			;;
		-h)
			echo -e "Zula Framework building tools.\nUsage: build.sh [-j [jar-path]] [-p [mode] [-m]] [-vxlh]"
			echo "Options:"
			echo -e "\t-j\tCompress source JavaScript files using Google Closure Compiler (requires Java)"
			echo -e "\t-l\tCheck PHP syntax on *.php, *.html and *.txt files (lint check)."
			echo -e "\t-m\tCreates Microsoft Web App Gallery package (used only with '-p')."
			echo -e "\t-p\tCreates .tar.gz, .tar.bz2 and .zip archives. This implies '-l' and '-j'. Zula" \
					"\n\t\tapplication mode argument optional, 'development' or 'production' which" \
					"\n\t\tdefaults to 'production'."
			echo -e "\t-x\tCheck all thrown PHP exceptions are defined, and list those not used."
			echo -e "\t-v\tBe more verbose with output, providing more detail."
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

##
# Returns if Verbose mode is enabled
##
verbose() {
	[[ $optVerbose = true ]] && return 1 || return 0
}

##
## Begin processing of the tasks
##
if [[ $taskLint = true ]]; then
	verbose || echo -n ":: PHP syntax check on *.php, *.html and *.txt files (this may take a while) ....."
	failedCount=0
	for file in $(find . -name "*.php" -o -name "*.html" -o -name "*.txt"); do
		lintResult=$(php -l $file 2>&1 > /dev/null)
		if (( $? > 0 )); then
			if [[ $failedCount -eq 0 && $optVerbose = true ]]; then
				echo "" # Add a new line
			fi
			echo $lintResult
			let failedCount++
		fi
	done
	if [[ $failedCount -gt 0 && $taskPackage = true ]]; then
		## See if the user wants to continue if there are syntax errors
		echo
		read -p "---- PHP syntax errors detected, do you wish to continue? (y/n) [n]: "
		if [[ $REPLY != y ]]; then
			exit 0
		fi
	elif [[ $failedCount -eq 0 && $optVerbose = true ]]; then
		echo  " done!"
	fi
fi

if [[ $taskJsCompress = true || $taskPackage = true ]]; then
	verbose || echo -ne ":: Compressing source JavaScript files (*.src.js) "
	if [[ -n $JAVA_HOME ]]; then
		javaBin=$JAVA_HOME/bin/java
	else
		javaBin=$(which java)
		if (( $? == 1 )); then
			echo -e "\n---- 'java' bin not found. Please set JAVA_HOME variable or install Java." >&2
			exit 2
		fi
	fi
	if [[ ! -f $optPathClosure ]]; then
		echo -e "\n---- jar file '$optPathClosure' does not exist." >&2
		exit 2
	fi
	for sourceFile in $(find . -name "*.src.js"); do
		fileName=$(basename $sourceFile .src.js).js
		$javaBin -jar $optPathClosure --js $sourceFile --charset utf-8 --js_output_file $(dirname $sourceFile)/$fileName \
					  --warning_level QUIET
		verbose || echo -ne "."
	done
	verbose || echo " done!"
fi

if [[ $taskCheckExceptions = true ]]; then
	##
	## List all exceptions that a) Are defined but not thrown B) Thrown but not defined
	##
	builtinExceptions="Exception PDOException"

	expThrown=$(find . -name '*.php' -not -path "*/3rd_party/*" -print0 | xargs -0 grep 'throw new' |
				perl -pi -e 's/.*throw new ([^\(; ]+).*/\1/' |
				sort | uniq | grep -viE "^(${builtinExceptions// /|})$" |
				tr '\n' ' ' | sed 's/ $//')
	expDefined=$(find . -name '*.php' -not -path "*/3rd_party/*" -print0 | xargs -0 grep -iE "extends (${builtinExceptions// /|})" |
				 perl -pi -e 's/.* ([^\(; ]+) extends.*/\1/' |
				 sort | uniq | tr '\n' ' ' | sed 's/ $//')
	## Classes which extend previously discovered exceptions
	toCheck="$expDefined"
	while [[ ! -z $toCheck ]]; do
		expDefinedTmp=$(find . -name '*.php' -not -path "*/3rd_party/*" -print0 | xargs -0 grep -iE "extends (${toCheck// /|})" |
						perl -pi -e 's/.* ([^\(; ]+) extends.*/\1/' |
						sort | uniq | tr '\n' ' ' | sed 's/ $//')
		expDefined="$expDefined $expDefinedTmp"
		toCheck="$expDefinedTmp"
	done
	expDefined=$(echo $expDefined | tr ' ' '\n' | sort | tr '\n' ' ' | sed 's/ $//')

	## Display the totals and exception names
	echo ":: Exceptions that need to be defined"
	for thrownException in $expThrown; do
		for definedException in $expDefined; do
			if [[ $thrownException = $definedException ]]; then
				continue 2
			fi
		done
		echo -e "\t$thrownException"
	done

	echo ":: Exceptions that are unused"
	for definedException in $expDefined; do
		for thrownException in $expThrown; do
			if [[ $definedException = $thrownException ]]; then
				continue 2
			fi
		done
		echo -e "\t$definedException"
	done
fi

if [[ $taskPackage = true ]]; then
	##
	## Create the tar.gz, tar.bz2 and .zip archive of this project
	##
	verbose || echo -ne ":: Creating package archives "
	if [[ ! -f index.php ]]; then
		echo -e "\n---- unable to find 'index.php', unable to create project packages" >&2
		exit 2
	fi

	projectId=$(grep -oP "(?<=_PROJECT_ID', ')([^']*)" index.php)
	projectVersion=$(grep -oP "(?<=version\s=\s).*$" config/default.dist/config.ini.php)
	packageName=$projectId-$projectVersion

	tmpDir=$(mktemp -d)/$packageName
	curPwd=$PWD

	if [[ -d .git ]]; then
		git checkout-index -a -f --prefix=$tmpDir/
	elif [[ -d .svn ]]; then
		svn export . $tmpDir/
	else
		cp -ar . $tmpDir
	fi

	## Move some files around and do certain edits depending on application mode
	rm -rf $tmpDir/{config/default,tmp/*,application/logs/*.log,assets/uploads/*,.gitignore,*~}
	touch $tmpDir/{tmp/index.html,assets/uploads/index.html} 2> /dev/null
	mv $tmpDir/config/default.dist $tmpDir/config/default

	## Edit and remove some files depending on required application mode
	if [[ $optPackageMode = production ]]; then
		find $tmpDir -name "*.src.js" -delete
		rm -rf $tmpDir/assets/js/ckeditor/_source $tmpDir/utils
		confDebugFlag=0
	else
		optPackageMode=development
		confDebugFlag=1
	fi
	sed -i "s/'\(development\|production\)'/'$optPackageMode'/" $tmpDir/index.php
	sed -i -e "s/\(php_display_errors\|zula_detailed_error\|zula_show_errors\) = \([0-1]\{1\}\)/\1 = $confDebugFlag/" \
		$tmpDir/config/default/config.ini.php

	if [[ $optIsMsWag = true && -d $tmpDir/ms-webapp ]]; then
		mkdir $tmpDir/$projectId
		mv $tmpDir/* $tmpDir/$projectId 2> /dev/null
		mv $tmpDir/$projectId/ms-webapp/* $tmpDir
		mv $tmpDir/$projectId/install/modules/stage/sql/base_tables.sql $tmpDir/$projectId.sql
		rm -rf $tmpDir/$projectId/install $tmpDir/$projectId/ms-webapp

		(cd $tmpDir && zip -qr9 $curPwd/$packageName-wag.zip . && verbose || echo -ne ".")
	else
		rm -rf $tmpDir/ms-webapp
		tar -czf $packageName.tar.gz -C $tmpDir/../ $packageName && verbose || echo -ne "."
		tar -cjf $packageName.tar.bz2 -C $tmpDir/../ $packageName && verbose || echo -ne "."
		(cd $tmpDir/../ && zip -qr9 $curPwd/$packageName.zip $packageName && verbose || echo -ne ".")
	fi

	rm -rf $tmpDir
	verbose || echo " done!"
fi

exit 0
