#! /bin/sh
xgettext --language=PHP --keyword="__" --keyword="__p:1c,2" --copyright-holder="Sean Proctor" --package-name="PHP-Calendar" \
	--package-version="2.0.2" --msgid-bugs-address="sproctor@gmail.com" *.php includes/*.php
sed '0,/SOME DESCRIPTIVE TITLE/s//PHP-Calendar/' messages.po > messages.tmp
mv messages.tmp messages.po
sed '0,/YEAR/s//2014/' messages.po > messages.tmp
mv messages.tmp messages.po
sed '0,/PACKAGE/s//PHP-Calendar/' messages.po > messages.tmp
mv messages.tmp messages.po
sed '0,/CHARSET/s//UTF-8/' messages.po > messages.tmp
mv messages.tmp messages.po
