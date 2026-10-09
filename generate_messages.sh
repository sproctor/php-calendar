#! /bin/sh
version=$(sed -n 's/^\$phpc_version = "\(.*\)";/\1/p' src/calendar.php)
xgettext --language=PHP --keyword="__" --keyword="__p:1c,2" --copyright-holder="Sean Proctor" \
	--package-name="PHP-Calendar" \
	--package-version="$version" --msgid-bugs-address="sproctor@gmail.com" *.php src/*.php
sed '0,/SOME DESCRIPTIVE TITLE/s//PHP-Calendar/' messages.po > messages.tmp
mv messages.tmp messages.po
sed "0,/YEAR/s//$(date +%Y)/" messages.po > messages.tmp
mv messages.tmp messages.po
sed '0,/PACKAGE/s//PHP-Calendar/' messages.po > messages.tmp
mv messages.tmp messages.po
sed '0,/CHARSET/s//UTF-8/' messages.po > messages.tmp
mv messages.tmp messages.po
