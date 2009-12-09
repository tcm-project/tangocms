#!/bin/bash

builtin_exceptions="Exception PDOException Swift_ConnectionException"

show_location() {
	find "$base" -name '*.php' -print0 | xargs -0 grep -il "$1" |
	grep -v 3rd_party | cut -c$((${#base} + 1))- | sed 's/^\/*/    /'
}

if [[ ! -z "$1" ]]; then
	base="$1"
else
	base=.
fi

if [[ ! -f $base/COPYING ]]; then
	echo -e "\nError: Invalid base path is invalid! .-." >&2
	echo -e "Usage: $0 [base dir]\n" >&2
	exit 1
fi

# Exceptions thrown
thrown=$(find "$base" -name '*.php' -print0 | xargs -0 grep 'throw new' |
         grep -v 3rd_party | perl -pi -e 's/.*throw new ([^\(; ]+).*/\1/' |
         sort | uniq | grep -viE "^(${builtin_exceptions// /|})$" |
         tr '\n' ' ' | sed 's/ $//')

# Classes that extend built-in exceptions
defined=$(find "$base" -name '*.php' -print0 | xargs -0 grep -iE "extends (${builtin_exceptions// /|})" |
          grep -v 3rd_party | perl -pi -e 's/.* ([^\(; ]+) extends.*/\1/' |
          sort | uniq | tr '\n' ' ' | sed 's/ $//')

# Classes that extend previously discovered exceptions
tocheck="$defined"
while [[ ! -z $tocheck ]]; do
	defined_temp=$(find "$base" -name '*.php' -print0 |
	               xargs -0 grep -iE "extends (${tocheck// /|})" |
                   grep -v 3rd_party | perl -pi -e 's/.* ([^\(; ]+) extends.*/\1/' |
                   sort | uniq | tr '\n' ' ' | sed 's/ $//')
	defined="$defined ${defined_temp}"
	tocheck="${defined_temp}"
done
defined=$(echo $defined | tr ' ' '\n' | sort | tr '\n' ' ' | sed 's/ $//')

thrown_count=$(echo "$thrown" | wc -w)
defined_count=$(echo "$defined" | wc -w)

echo "Exceptions thrown ($thrown_count)"
echo "======================="
echo
echo $thrown | tr ' ' '\n'
echo
echo
echo "Exceptions defined ($defined_count)"
echo "========================"
echo
echo $defined | tr ' ' '\n'
echo
echo
echo "Exceptions that need to be defined"
echo "=================================="
echo
none=1
for thrown_exception in $thrown; do
	for defined_exception in $defined; do
		if [[ ${thrown_exception} == ${defined_exception} ]]; then
			continue 2
		fi
	done
	echo ${thrown_exception}
	show_location "throw new ${thrown_exception}"
	none=0
done
[[ $none == 1 ]] && echo "(None) :D"
echo
echo
echo "Unused exceptions"
echo "================="
none=1
for defined_exception in $defined; do
	for thrown_exception in $thrown; do
		if [[ ${defined_exception} == ${thrown_exception} ]]; then
			continue 2
		fi
	done
	echo -e "\n${defined_exception}"
	show_location "${defined_exception} extends"
	none=0
done
[[ $none == 1 ]] && echo -e "\n(None) D:"
