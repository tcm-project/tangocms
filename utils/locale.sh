#!/bin/bash
#---
# Zula Locale Tools
#
# Author: Robert Clipsham
# Copyright: Copyright (c) 2008, 2009 Robert Clipsham
# Licence: http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
#---

# Make sure the script is running from the utils directory
cd `dirname $0`

# Load common functions
source common.sh

#---
# Clean duplicated strings from generated pot files
#---
clean_dups() {
	verbose || echo "[debug] Removing duplicated strings from pot files"
	OLD_PWD=`pwd`
	cd ${LOCALE_DIR}/pot/
	MSGUNIQ=`which msguniq 2>/dev/null`
	msguniq --help &>/dev/null || die "Can not remove duplicated strings from .pot files, \`msguniq' tool not found."
	for f in `ls *.pot`; do
		verbose || echo "[debug] Cleaning $f"
		msguniq --to-code=UTF-8 --no-wrap $f -o $f
	done
	cd $OLD_PWD
}

#---
# Count the number of tabs in the given string
#  Params:
#	$1 - The string to use
#---
count_tabs() {
	s=$1
	x=0
	for c in `seq ${#s}`; do
		if [ "${s:$c:1}" == "`echo -e '\t'`" ]; then
			x=`expr $x + 1`
		fi
	done
	echo $x
}

#---
# Generate the .mo files from the .po files
#  Params:
#	$1 - The language to generate the .mo files for
#---
gen_mo() {
	MO_LANG=$1
	OLD_DIR=`pwd`
	PO_DIR=${LOCALE_DIR}/po/${MO_LANG}/LC_MESSAGES
	cd $PO_DIR
	for f in `ls $PO_DIR/*.po`; do
		po=`basname $f`
		mo=${f%.*}.mo
		msgfmt $po -o $mo
	done
	cd $OLD_DIR
}

#--
# Generate the .po files for the given language
#  Params:
#	$1 - The language to generate the .po files for
#---
gen_po() {
	PO_LANG=$1
	POT_DIR=${LOCALE_DIR}/pot
	PO_DIR=${LOCALE_DIR}/po/${PO_LANG}/LC_MESSAGES
	ls ${LOCALE_DIR}/pot/*.pot &>/dev/null || die "Please generate .pot files with \`$0' before using \`$0 --gen-po'."
	for n in `ls ${POT_DIR}/*.pot`; do
		pot=`basename $n`
		po=${pot%.*}.po
		if [ ! -f ${PO_DIR}/${po} ]; then
			msginit --locale=${PO_LANG} --no-translator --no-wrap --input=${n} --output=${PO_DIR}/${po}
		else
			becho "Not generating .po file $po as it already exists."
		fi
	done
}

#---
# Generate the .pot files
#---
gen_pot() {
	verbose || echo "[debug] Generating .pot files"
	# Generate .pot files for Zula and its installer
	pot_file=${LOCALE_DIR}/pot/zula-base.pot
	verbose || echo "[debug] Generating .pot file for Zula"
	echo "# Zula locale file for Zula Base
# Copyright: Copyright (C) `date +%Y`, The TangoCMS Project
# Licence: http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
# Generated automatically by the Zula Locale Tools script
#
#, fuzzy
msgid \"\"
msgstr \"\"
\"Project-Id-Version: Zula $PROJECT_VERSION\n\"
\"Report-Msgid-Bugs-To: i18n@tangocms.org\n\"
\"POT-Creation-Date: `date '+%Y-%m-%d %H:%M%z'`\n\"
\"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n\"
\"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n\"
\"Language-Team: LANGUAGE <LL@li.org>\n\"
\"MIME-Version: 1.0\n\"
\"Content-Type: text/plain; charset=UTF-8\n\"
\"Content-Transfer-Encoding: 8bit\n\"
" > $pot_file
	for f in `find application/{libraries,modules} -type f -name '*.php' | grep -v .svn | grep -v 3rd_party`; do
		if [ "$SPELL_CHECK" == "1" ]; then
			echo -e "\e[1mFile:\e[m $f"
		fi
		MATCHES=`perl -ne '@matches = m/\st\(\s?'"'"'([^'"'"']+)'"'"'\s?,\s?I18n::_DTD\s?\)/g; foreach(@matches) { print "$.\t"; $_ =~ s/\"/\\\"/g; print "$_\t"; }' $f`
		make_arr "$MATCHES"
		output "$MATCHARR" "$pot_file"
	done
	for f in `find application/views -type f -name '*.html' | grep -v .svn`; do
		if [ "$SPELL_CHECK" == "1" ]; then
			echo -e "\e[1mFile:\e[m $f"
		fi
		MATCHES=`perl -ne '@matches = m/\st\(\s?'"'"'([^'"'"']+)'"'"'\s?,\s?I18n::_DTD\s?\)/g; foreach(@matches) { print "$.\t"; $_ =~ s/\"/\\\"/g; print "$_\t"; }' $f`
		make_arr "$MATCHES"
		output "$MATCHARR" "$pot_file"
		MATCHES=`perl -ne '@matches = m/{L_\[([^\]]+)\]}/g; foreach(@matches) { print "$.\t"; $_ =~ s/\"/\\\"/g; print "$_\t"; }' $f`
		make_arr "$MATCHES"
		output "$MATCHARR" "$pot_file"
	done
	if [ "$SPELL_CHECK" == "0" ]; then
		echo "# Zula locale file for the installer
# Copyright: Copyright (C) `date +%Y`, The TangoCMS Project
# Generated automatically by the Zula Locale Tools script
#
#, fuzzy
msgid \"\"
msgstr \"\"
\"Project-Id-Version: Zula $PROJECT_VERSION\n\"
\"Report-Msgid-Bugs-To: i18n@tangocms.org\n\"
\"POT-Creation-Date: `date '+%Y-%m-%d %H:%M%z'`\n\"
\"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n\"
\"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n\"
\"Language-Team: LANGUAGE <LL@li.org>\n\"
\"MIME-Version: 1.0\n\"
\"Content-Type: text/plain; charset=UTF-8\n\"
\"Content-Transfer-Encoding: 8bit\n\"
" > ${LOCALE_DIR}/pot/zula-installer.pot
	fi
	for d in `find install/modules -maxdepth 1 -type d`; do
		m=`basename $d`
		if [ "$m" != "modules" -a "$m" != ".svn" ]; then
			verbose || echo "[debug] Generating .pot file for the installer module $m"
			pot_file=${LOCALE_DIR}/pot/zula-installer.pot
			if [ -d "$d/views" ]; then
				for f in `find $d/views -type f -name '*' | grep -v .svn | grep -E '(.html|.txt)'`; do
					if [ "$SPELL_CHECK" == "1" ]; then
						echo -e "\e[1mFile:\e[m $f"
					fi
					MATCHES=`perl -ne '@matches = m/{L_\[([^\]]+)\]}/g; foreach(@matches) { print "$.\t"; $_ =~ s/\"/\\\"/g; print "$_\t"; }' $f`
					make_arr "$MATCHES"
					output "$MATCHARR" "$pot_file"
				done
			fi
			for f in `find $d -type f -name '*' | grep -v .svn | grep -E '(.php|.html|.txt)$'`; do
				if [ "$SPELL_CHECK" == "1" ]; then
					echo -e "\e[1mFile:\e[m $f"
				fi
				MATCHES=`perl -ne '@matches = m/\st\(\s?'"'"'([^'"'"']+)'"'"'\s?\)/g; foreach(@matches) { print "$.\t"; $_ =~ s/\"/\\\"/g; print "$_\t"; }' $f`
				make_arr "$MATCHES"
				output "$MATCHARR" "$pot_file"
			done
		fi
	done
	# Generate .pot files for modules
	for d in `find application/modules -maxdepth 1 -type d`; do
		m=`basename $d`
		if [ "$m" != "modules" -a "$m" != ".svn" ]; then
			verbose || echo "[debug] Generating .pot file for the $m module"
			pot_file=${LOCALE_DIR}/pot/${PROJECT_ID}-${m}.pot
			if [ "$SPELL_CHECK" == "0" ]; then
				echo "# Zula locale file for the $m module
# Copyright: Copyright (C) `date +%Y`, The TangoCMS Project
# Generated automatically by the Zula Locale Tools script
#
#, fuzzy
msgid \"\"
msgstr \"\"
\"Project-Id-Version: TangoCMS $PROJECT_VERSION\n\"
\"Report-Msgid-Bugs-To: i18n@tangocms.org\n\"
\"POT-Creation-Date: `date '+%Y-%m-%d %H:%M%z'`\n\"
\"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n\"
\"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n\"
\"Language-Team: LANGUAGE <LL@li.org>\n\"
\"MIME-Version: 1.0\n\"
\"Content-Type: text/plain; charset=UTF-8\n\"
\"Content-Transfer-Encoding: 8bit\n\"
" > $pot_file
			fi
			if [ -d "$d/views" ]; then
				for f in `find $d/views -type f -name '*' | grep -v .svn | grep -E '(.html|.txt)'`; do
					if [ "$SPELL_CHECK" == "1" ]; then
						echo -e "\e[1mFile:\e[m $f"
					fi
					MATCHES=`perl -ne '@matches = m/{L_\[([^\]]+)\]}/g; foreach(@matches) { print "$.\t"; $_ =~ s/\"/\\\"/g; print "$_\t"; }' $f`
					make_arr "$MATCHES"
					output "$MATCHARR" "$pot_file"
				done
			fi
			for f in `find $d -type f -name '*' | grep -v .svn | grep -E '(.php|.html|.txt)$'`; do
				if [ "$SPELL_CHECK" == "1" ]; then
					echo -e "\e[1mFile:\e[m $f"
				fi
				MATCHES=`perl -ne '@matches = m/\st\(\s?'"'"'([^'"'"']+)'"'"'\s?\)/g; foreach(@matches) { print "$.\t"; $_ =~ s/\"/\\\"/g; print "$_\t"; }' $f`
				make_arr "$MATCHES"
				output "$MATCHARR" "$pot_file"
			done
		fi
	done
}

#---
# Help function
#---
help() {
	echo -e "\e[1mUsage:\e[m $0 [OPTIONS]"
	echo
	becho "Options"
	echo -e "\t--gen-mo <language>\t\tGenerate the completed .mo files"
	echo -e "\t--gen-po <language>\t\tGenerate blank po files for the given langauge (given in the form en_GB)"
	echo -e "\t--help\t\t\t\tShow this help message"
	echo -e "\t--merge <language>\t\tMerge the newly generated .pot files with previous completed .po files"
	echo -e "\t--merge-launchpad\t\tMerge the newly generated .pot files with .po files downloaded from launchpad"
	echo -e "\t--project-id <id>\t\tThe name of the project"
	echo -e "\t--project-version <version>\tThe version (guessed if not supplied)"
	echo -e "\t--spell-check\t\t\tRun a spell check on strings"
	echo -e "\t--verbose\t\t\tGive debug output"
}

#---
# Make an array from perls output
#  Params:
#	$1 - The string of matches, \t seperated
#---
make_arr() {
	MATCHES=$1
	MATCHARR=()
	for i in `seq $(count_tabs "$MATCHES")`; do
		cmatch=`echo "$MATCHES" | cut -f $i`
		if [ -n "$cmatch" ]; then
			MATCHARR[`expr $i - 1`]=$cmatch
		fi
	done
	# Here we take advantage of the fact all variables are in the same scope,
	# and the fact sh doesn't support passing arguements by reference
}

#---
# Merge translated .po files with new .pot files
#  Params:
#	$1 - Langauge to merge
#---
merge() {
	MERGE_LANG=$1
	OLD_DIR=`pwd`
	POT_DIR=${LOCALE_DIR}/pot
	PO_DIR=${LOCALE_DIR}/po/${MERGE_LANG}/LC_MESSAGES
	cd $PO_DIR
	for f in `ls $PO_DIR`; do
		verbose || echo "[debug] Merging $f"
		if [ -f ${POT_DIR}/${f}t ]; then
			msgmerge --no-wrap --no-fuzzy-matching --sort-output --output-file=$f $f ${POT_DIR}/${f}t
			# Replace the project version
			perl -pi -e 's/\"Project-Id-Version: TangoCMS r([0-9]+)\\n\"/\"Project-Id-Version: TangoCMS '${PROJECT_VERSION}'\\n\"/' $f
		fi
	done
	cd $OLD_DIR
}

#---
# Merge translated .po files from launchpad with the new .pot files
# Creates multiple tar files for upload
#---
merge_lp() {
	POT_DIR=${LOCALE_DIR}/pot
	for f in `ls utils/lp/*.po`; do
		verbose || echo "[debug] Merging $f"
		name=`echo $f | perl -p -e 's|utils/lp/([a-z]+)-([a-z\-]+)-([A-Za-z_]+).po|\1-\2|'`
		if [ -f ${POT_DIR}/${name}.pot ]; then
			msgmerge --no-wrap --no-fuzzy-matching --sort-output --output-file=$f $f ${POT_DIR}/${name}.pot
		fi
	done
	for f in `ls utils/lp/*/*.po`; do
		verbose || echo "[debug] Merging $f"
		name=`echo $f | perl -p -e 's|utils/lp/[^/]+/([a-z]+)-([a-z\-]+)-([A-Za-z_]+).po|\1-\2|'`
		if [ -f ${POT_DIR}/${name}.pot ]; then
			msgmerge --no-wrap --no-fuzzy-matching --sort-output --output-file=$f $f ${POT_DIR}/${name}.pot
		fi
	done
	for f in `ls utils/lp/*/*.pot`; do
		verbose || echo "[debug] Merging $f"
		name=`echo $f | perl -p -e 's|utils/lp/[^/]+/([a-z]+)-([a-z\-]+).pot|\1-\2|'`
		if [ -f ${POT_DIR}/${name}.pot ]; then
			msgmerge --no-wrap --no-fuzzy-matching --sort-output --output-file=$f $f ${POT_DIR}/${name}.pot
		fi
	done
	cd utils/lp
	for f in `find . -type d`; do
		name=`echo $f | perl -p -e 's|.+/([^/]+)|\1|'`
		if [ "$name" != "." ]; then
			tar czf ${name}.tar.gz ${name}/* ${name}-*.po
		fi
	done
	cd ../..
}

#---
# Output the .pot files or typos
#  Params:
#	$1 - The array to use
#	$2 - The name of the .pot file
#---
output() {
	MATCHARR=$1
	pot_file=$2
	LOF=0
	for match in "${MATCHARR[@]}"; do
		if [ $LOF -eq 0 ]; then
			if [ "$SPELL_CHECK" == "0" -a -n "$match" ]; then
				echo "#: ${f}:${match}" >> $pot_file
			fi
			line=$match
			LOF=1
		else
			if [ "$SPELL_CHECK" == "0" ]; then
				echo "msgid \"$match\""	>> $pot_file
				echo -e 'msgstr ""\n'	>> $pot_file
			else
				verbose || echo " String: $match"
				typo=`echo "$match" | aspell --encoding=UTF-8 --lang=en_US list`
				for ignore in $IGNORE_LIST; do
					typo=`echo $typo | grep -iv $ignore`
				done
				if [ -n "$typo" ]; then
					echo $opts "\e[1;31mLine $line: \e[0;31m$typo\e[m"
					if [ "$pause" != "n" ]; then
						read
					fi
				fi
			fi
			LOF=0
		fi
	done
}

#---
# Display usage information and exit
#---
usage() {
	echo "Usage: $0 [OPTIONS]"
	exit 1
}

SPELL_CHECK=0
GEN_PO=0
GEN_MO=0
GEN_POT=1

#---
# Parse script parameters
#---
while [ "$#" != "0" ]
do
	case "$1" in
		--verbose)
			VERBOSE=1
		;;
		--gen-mo)
			if [ -z "$2" ]; then
				help
				exit 1
			fi
			GEN_POT=0
			GEN_MO=1
			MO_LANG=$2
			shift
		;;
		--gen-po)
			if [ -z "$2" ]; then
				help
				exit 1
			fi
			GEN_POT=0
			GEN_PO=1
			PO_LANG=$2
			shift
		;;
		--help)
			help
			exit 0
		;;
		--merge)
			if [ -z "$2" ]; then
				help
				exit 1
			fi
			GEN_POT=0
			MERGE=1
			MERGE_LANG=$2
			becho "Merging .po files for $MERGE_LANG - Make sure you have generated the latest .pot files!"
			shift
		;;
		--merge-launchpad)
			becho "Merging .po files for launchpad - Make sure you have generated the latest .pot files!"
			GEN_POT=0
			MERGE_LAUNCHPAD=1
		;;
		--project-id)
			if [ -z "$2" ]; then
				help
				exit 1
			fi
			PROJECT_ID=$2
			shift
		;;
		--project-version)
			if [ -z "$2" ]; then
				help
				exit 1
			fi
			PROJECT_VERSION=$2
			shift
		;;
		--spell-check)
			ASPELL=`which aspell 2>/dev/null`
			$ASPELL usage &>/dev/null || die "Aspell is required when using $1"
			SPELL_CHECK=1
		;;
		*)
		die "Unrecognised option: $1"
		;;
	esac
	shift
done

if [ "$SPELL_CHECK" != "0" ]; then
	IGNORE_LIST=`cat ignore.list`
	becho "Would you like the script to pause after each spelling mistake found? [Y/n] " -n
	read pause
	if [ "$pause" != "n" ]; then
		opts='-en'
	else
		opts='-e'
	fi
fi

# Move the the TangoCMS root directory
cd ..

# Set the project id if not already set
if [ -z "$PROJECT_ID" ]; then
	PROJECT_ID=tangocms
fi

# Attempt to set the project version if not already set
if [ -z "$PROJECT_VERSION" ]; then
	SVN=`which svn 2>/dev/null`
	$SVN help &>/dev/null || die "Please specify a project version."
	PROJECT_VERSION=r`svn info | grep Revision | tr -dc '0-9' 2>/dev/null`
fi

PERL=`which perl 2>/dev/null`
$PERL --help &>/dev/null || die "This script requires perl."

# Place the .pot files in this dir for now
LOCALE_DIR=`pwd`/application/locale

if [ -d "$LOCALE_DIR" -a -w "$LOCALE_DIR" ]; then
	if [ "$GEN_POT" == "1" ]; then
		if [ ! -d "$LOCALE_DIR/pot" ]; then
			mkdir ${LOCALE_DIR}/pot
		fi
		gen_pot
		if [ "$SPELL_CHECK" == "0" ]; then
			clean_dups
		fi
	elif [ "$GEN_PO" == "1" ]; then
		if [ ! -d "${LOCALE_DIR}/po/${PO_LANG}/LC_MESSAGES" ]; then
			mkdir -p ${LOCALE_DIR}/po/${PO_LANG}/LC_MESSAGES
		fi
		gen_po $PO_LANG
	elif [ "$GEN_MO" == "1" ]; then
		if [ ! -d "${LOCALE_DIR}/po/${MO_LANG}/LC_MESSAGES" ]; then
			mkdir -p ${LOCALE_DIR}/po/${MO_LANG}/LC_MESSAGES
		fi
		gen_mo $MO_LANG
	elif [ "$MERGE" == "1" ]; then
		if [ ! -d "${LOCALE_DIR}/po/${MERGE_LANG}/LC_MESSAGES" ]; then
			die "No po files for specified language to merge language"
		fi
		merge $MERGE_LANG
	elif [ "$MERGE_LAUNCHPAD" == "1" ]; then
		if [ ! -d "utils/lp" ]; then
			die "You need to extract the launchpad-export.tar.gz to utils/lp"
		fi
		merge_lp
	fi
else
	if [ -w "`dirname ${LOCALE_DIR}`" ]; then
		if [ "$GEN_POT" == "1" ]; then
			mkdir -p ${LOCALE_DIR}/pot
			gen_pot
			if [ "$SPELL_CHECK" == "0" ]; then
				clean_dups
			fi
		elif [ "$GEN_PO" == "1" ]; then
			if [ ! -d "${LOCALE_DIR}/po/${PO_LANG}/LC_MESSAGES" ]; then
				mkdir -p ${LOCALE_DIR}/po/${PO_LANG}/LC_MESSAGES
			fi
			gen_po $PO_LANG
		elif [ "$GEN_MO" == "1" ]; then
			if [ ! -d "${LOCALE_DIR}/po/${MO_LANG}/LC_MESSAGES" ]; then
				mkdir -p ${LOCALE_DIR}/po/${MO_LANG}/LC_MESSAGES
			fi
			gen_mo $MO_LANG
		elif [ "$MERGE" == "1" ]; then
			if [ ! -d "${LOCALE_DIR}/po/${MERGE_LANG}/LC_MESSAGES" ]; then
				die "No po files for specified language to merge language"
			fi
			merge $MERGE_LANG
		elif [ "$MERGE_LAUNCHPAD" == "1" ]; then
			if [ ! -d "utils/lp" ]; then
				die "You need to extract the launchpad-export.tar.gz to utils/lp"
			fi
			merge_lp
		fi
	else
		die "Locale directory '$LOCALE_DIR' does not exist or is not writable"

	fi
fi
