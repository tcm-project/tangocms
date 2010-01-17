#!/bin/bash
#---
# TangoCMS Project building tools
#
# @author Alex Cartwright
# @author Evangelos Foutras
# @author Robert Clipsham
# @copyright Copyright (c) 2010, Alex Cartwright
# @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
#---

## All options and tasks with their default values
OPT_PATH_CLOSURE=/usr/bin/closure
OPT_VERBOSE=false
OPT_PACKAGE_MODE=

TASK_JS_COMPRESS=false
TASK_PACKAGE=false
TASK_CHECK_EXCEPTIONS=false

if [ ! -f "index.php" ]; then
	echo "TangoCMS Project building tools must be run in the TangoCMS root directory."
	exit 1
fi

while getopts ":jp:vxh" OPTION
do
	case "$OPTION" in
		j)
			TASK_JS_COMPRESS=true
			if [[ -n "$OPTARG" ]]; then
				OPT_PATH_CLOSURE="$OPTARG"
			fi
			;;
		p)
			TASK_PACKAGE=true
			OPT_PACKAGE_MODE="$OPTARG"
			;;
		v)
			OPT_VERBOSE=true
			;;
		x)
			TASK_CHECK_EXCEPTIONS=true
			;;
		h)
			echo -e "TangoCMS Project building tools.\nUsage: build.sh [-jpvxh]"
			echo "Options:"
			echo -e "\t-j\tCompress source JavaScript files using Google Closure Compiler."
			echo -e "\t-p\tCreates .tar.gz, .tar.bz2 and .zip archives. This implies '-j' always. Zula" \
					"\n\t\tapplication mode argument required, either 'development' or 'production.'"
			echo -e "\t-x\tCheck all thrown PHP exceptions are defined, and list those not used."
			echo -e "\t-v\tBe more verbose with output, providing more detail."
			echo -e "\t-h\tDisplays this help text.\n"
			echo "Report bugs to <bugs@tangocms.org>"
			exit 0
			;;
		*)
			echo "Invalid argument or missing value for '$OPTARG'. See '-h' for help text."
			exit 1
			;;
	esac
done

##
# Returns if Verbose mode is enabled
##
verbose() {
	[ $OPT_VERBOSE == "true" ] && return 1 || return 0
}

##
## Begin processing of the tasks
##
if [ $TASK_JS_COMPRESS == "true" -o $TASK_PACKAGE == "true" ]; then
	verbose || echo -ne ":: Compressing source JavaScript files (*.src.js) "
	for sourceFile in `find . -name "*.src.js"`; do
		fileName=`basename $sourceFile .src.js`.js
		$OPT_PATH_CLOSURE --js $sourceFile --charset utf-8 --js_output_file `dirname $sourceFile`/$fileName \
						  --warning_level QUIET
		verbose || echo -ne "."
	done
	verbose || echo " done!"
fi

if [ $TASK_CHECK_EXCEPTIONS == "true" ]; then
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
	while [ ! -z "$toCheck" ]; do
		expDefinedTmp=$(find . -name '*.php' -not -path "*/3rd_party/*" -print0 | xargs -0 grep -iE "extends (${toCheck// /|})" |
						perl -pi -e 's/.* ([^\(; ]+) extends.*/\1/' |
						sort | uniq | tr '\n' ' ' | sed 's/ $//')
		expDefined="$expDefined ${expDefinedTmp}"
		toCheck="${expDefinedTmp}"
	done
	expDefined=$(echo $expDefined | tr ' ' '\n' | sort | tr '\n' ' ' | sed 's/ $//')

	## Display the totals and exception names
	echo ":: Exceptions that need to be defined"
	for thrownException in $expThrown; do
		for definedException in $expDefined; do
			if [ ${thrownException} == ${definedException} ]; then
				continue 2
			fi
		done
		echo -e "\t${thrownException}"
	done

	echo ":: Exceptions that are unused"
	for definedException in $expDefined; do
		for thrownException in $expThrown; do
			if [ ${definedException} == ${thrownException} ]; then
				continue 2
			fi
		done
		echo -e "\t${definedException}"
	done
fi

if [ $TASK_PACKAGE == "true" ]; then
	##
	## Create the tar.gz, tar.bz2 and .zip archive of this project
	##
	verbose || echo -ne ":: Creating package archives "
	curPwd=$PWD
	tmpDir=`mktemp -d`"/tangocms"
	if [ -d ".git" ]; then
		git checkout-index -a -f --prefix="${tmpDir}/"
	else
		cp -ar . $tmpDir
	fi

	## Move some files around and do certain edits depending on application mode
	rm -rf "${tmpDir}/config/default" "${tmpDir}/tmp/*" "${tmpDir}/application/logs/*.log" \
		   "${tmpDir}/assets/uploads/*" "${tmpDir}/.gitignore"
	touch "${tmDir}/tmp/index.html" "${tmpDir}/assets/uploads/index.html"
	mv "${tmpDir}/config/default.dist" "${tmpDir}/config/default"

	## Edit and remove some files depending on required application mode
	if [ "$OPT_PACKAGE_MODE" == "production" ]; then
		find "${tmpDir}" -name "*.src.js" -delete
		rm -rf "${tmpDir}/assets/js/ckeditor/_source"
		confDebugFlag=0
	else
		OPT_PACKAGE_MODE=development
		confDebugFlag=1
	fi
	sed -i "s/'\(development\|production\)'/'${OPT_PACKAGE_MODE}'/" "${tmpDir}/index.php"
	sed -i -e "s/\(php_display_errors\|zula_detailed_error\|zula_show_errors\) = \([0-1]\{1\}\)/\1 = ${confDebugFlag}/" \
		"${tmpDir}/config/default/config.ini.php"

	tar -czf TangoCMS.tar.gz -C "${tmpDir}/../" tangocms && verbose || echo -ne "."
	tar -cjf TangoCMS.tar.bz2 -C "${tmpDir}/../" tangocms && verbose || echo -ne "."
	(cd "${tmpDir}/../" && zip -qr9 $curPwd/TangoCMS.zip tangocms && verbose || echo -ne ".")

	rm -rf $tmpDir
	verbose || echo " done!"
fi

exit 0