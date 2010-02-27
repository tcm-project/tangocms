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
OPT_PATH_CLOSURE=/usr/share/java/closure-compiler/closure-compiler.jar
OPT_VERBOSE=false
OPT_PACKAGE_MODE=production
OPT_IS_MSWAG=false

TASK_JS_COMPRESS=false
TASK_PACKAGE=false
TASK_CHECK_EXCEPTIONS=false

if [ ! -f "index.php" ]; then
	echo "TangoCMS Project building tools must be run in the TangoCMS root directory."
	exit 1
fi

while [[ $1 == -* ]]; do
	case "$1" in
		-j)
			TASK_JS_COMPRESS=true
			if [ $# -gt 1 -a "${2:0:1}" != "-" ]; then
				OPT_PATH_CLOSURE="$2"
				shift 2;
			else
				shift
			fi
			;;
		-m)
			OPT_IS_MSWAG=true
			shift
			;;
		-p)
			TASK_PACKAGE=true
			if [ $# -gt 1 -a "${2:0:1}" != "-" ]; then
				OPT_PACKAGE_MODE="$2"
				shift 2;
			else
				shift
			fi
			;;
		-v)
			OPT_VERBOSE=true
			shift
			;;
		-x)
			TASK_CHECK_EXCEPTIONS=true
			shift
			;;
		-h)
			echo -e "TangoCMS Project building tools.\nUsage: build.sh [-j [jar-path]] [-p [mode] [-m]] [-xvh]"
			echo "Options:"
			echo -e "\t-j\tCompress source JavaScript files using Google Closure Compiler (requires Java)"
			echo -e "\t-m\tCreates Microsoft Web App Gallery package (used only with '-p')."
			echo -e "\t-p\tCreates .tar.gz, .tar.bz2 and .zip archives. This implies '-j' always. Zula" \
					"\n\t\tapplication mode argument optional, 'development' or 'production' which" \
					"\n\t\tdefaults to 'production'."
			echo -e "\t-x\tCheck all thrown PHP exceptions are defined, and list those not used."
			echo -e "\t-v\tBe more verbose with output, providing more detail."
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
	if [ -n "$JAVA_HOME" ]; then
		javaBin="${JAVA_HOME}/bin/java"
	else
		javaBin=`which java`
		if [ $? == "1" ]; then
			echo -e "\n---- 'java' bin not found. Please set JAVA_HOME variable or install Java."
			exit 1
		fi
	fi
	if [ ! -f $OPT_PATH_CLOSURE ]; then
		echo -e "\n---- jar file '$OPT_PATH_CLOSURE' does not exist."
		exit 1
	fi
	for sourceFile in `find . -name "*.src.js"`; do
		fileName=`basename $sourceFile .src.js`.js
		$javaBin -jar $OPT_PATH_CLOSURE --js $sourceFile --charset utf-8 --js_output_file `dirname $sourceFile`/$fileName \
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
	projectVersion=`grep "version =" ./config/default/config.ini.php | sed -e 's/[^0-9.]//g'`
	tmpDir=`mktemp -d`"/tangocms-${projectVersion}"
	if [ -d ".git" ]; then
		git checkout-index -a -f --prefix="${tmpDir}/"
	else
		cp -ar . $tmpDir
	fi

	## Move some files around and do certain edits depending on application mode
	rm -rf "${tmpDir}/config/default" "${tmpDir}/tmp/*" "${tmpDir}/application/logs/*.log" \
		   "${tmpDir}/assets/uploads/*" "${tmpDir}/.gitignore" "${tmpDir}/*~"
	touch "${tmDir}/tmp/index.html" "${tmpDir}/assets/uploads/index.html"
	mv "${tmpDir}/config/default.dist" "${tmpDir}/config/default"

	## Edit and remove some files depending on required application mode
	if [ "$OPT_PACKAGE_MODE" == "production" ]; then
		find "${tmpDir}" -name "*.src.js" -delete
		rm -rf "${tmpDir}/assets/js/ckeditor/_source" "${tmpDir}/utils"
		confDebugFlag=0
	else
		OPT_PACKAGE_MODE=development
		confDebugFlag=1
	fi
	sed -i "s/'\(development\|production\)'/'${OPT_PACKAGE_MODE}'/" "${tmpDir}/index.php"
	sed -i -e "s/\(php_display_errors\|zula_detailed_error\|zula_show_errors\) = \([0-1]\{1\}\)/\1 = ${confDebugFlag}/" \
		"${tmpDir}/config/default/config.ini.php"

	if [ "$OPT_IS_MSWAG" == "true" ]; then
		mkdir "${tmpDir}/tangocms"
		mv ${tmpDir}/* ${tmpDir}/tangocms 2> /dev/null
		mv ${tmpDir}/tangocms/ms-webapp/* ${tmpDir}
		mv "${tmpDir}/tangocms/install/modules/stage/sql/base_tables.sql" "${tmpDir}/tangocms.sql"
		rm -rf "${tmpDir}/tangocms/install" "${tmpDir}/tangocms/ms-webapp"

		(cd ${tmpDir} && zip -qr9 $curPwd/TangoCMS_${projectVersion}-wag.zip . && verbose || echo -ne ".")
	else
		rm -rf "${tmpDir}/ms-webapp"
		tar -czf TangoCMS_${projectVersion}.tar.gz -C "${tmpDir}/../" "tangocms-${projectVersion}" && verbose || echo -ne "."
		tar -cjf TangoCMS_${projectVersion}.tar.bz2 -C "${tmpDir}/../" "tangocms-${projectVersion}" && verbose || echo -ne "."
		(cd "${tmpDir}/../" && zip -qr9 $curPwd/TangoCMS_${projectVersion}.zip "tangocms-${projectVersion}" && verbose || echo -ne ".")
	fi

	rm -rf $tmpDir
	verbose || echo " done!"
fi

exit 0
