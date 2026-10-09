#!/usr/bin/env bash
# Builds a PHP-Calendar release.
#
#   ./release.sh <version>             Check the code, set the version, regenerate
#                                      messages.po, commit, tag v<version> and build
#                                      dist/php-calendar-<version>.{zip,tar.gz}.
#                                      Nothing is pushed.
#   ./release.sh <version> --publish   Push master and the tag, then create a draft
#                                      GitHub release with the packages attached.
#                                      Runs the steps above first if the tag
#                                      doesn't exist yet.
#
# Before releasing, add a "<version>:" entry to NEWS.md listing the changes. The
# script adds the date to it.
set -euo pipefail

usage() { echo "usage: $0 <version> [--publish]" >&2; exit 1; }
die() { echo "error: $*" >&2; exit 1; }

[ $# -ge 1 ] && [ $# -le 2 ] || usage
version=$1
publish=false
if [ $# -eq 2 ]; then
    [ "$2" = "--publish" ] || usage
    publish=true
fi
[[ $version =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || die "version must look like 2.0.0"

tag="v$version"
name="php-calendar-$version"
version_re=${version//./[.]}
# The NEWS.md heading for this version, undated ("2.0.14:") or already dated
#   by an earlier run ("2.0.14 (9 October 2026):")
news_re="^$version_re( \\([^)]*\\))?:\$"

cd "$(dirname "$0")"

for tool in php xgettext msgfmt zip; do
    command -v "$tool" >/dev/null || die "$tool is required"
done

prepare() {
    [ "$(git branch --show-current)" = master ] || die "releases are made from master"
    git diff --quiet && git diff --cached --quiet \
        || die "commit or stash your changes first"
    grep -qE "$news_re" NEWS.md \
        || die "add a \"$version:\" entry to NEWS.md first"

    echo "Checking PHP syntax..."
    local file
    while IFS= read -r file; do
        php -l "$file" >/dev/null || die "syntax error in $file"
    done < <(git ls-files '*.php')

    echo "Checking formatting..."
    [ -x vendor/bin/php-cs-fixer ] || die "run 'composer install' first"
    vendor/bin/php-cs-fixer fix --dry-run --diff \
        || die "fix formatting with: vendor/bin/php-cs-fixer fix"

    echo "Setting version $version..."
    sed -i "s/^\$phpc_version = \".*\";/\$phpc_version = \"$version\";/" src/calendar.php
    grep -q "^\$phpc_version = \"$version_re\";" src/calendar.php \
        || die "couldn't set \$phpc_version in src/calendar.php"
    sed -i -E "s/$news_re/$version ($(date '+%-d %B %Y')):/" NEWS.md

    echo "Regenerating messages.po..."
    ./generate_messages.sh

    git add src/calendar.php NEWS.md messages.po
    # Re-running after the tag was deleted may leave nothing to change
    if git diff --cached --quiet; then
        echo "Nothing changed since the last release commit; tagging it as is."
    else
        git commit -q -m "Release $version"
    fi
    git tag -a "$tag" -m "PHP-Calendar $version"
    echo "Committed and tagged $tag."
}

# Packages contain the tagged files, minus the export-ignore'd development files
#   (see .gitattributes), plus compiled translations so admins don't have to
#   generate them.
build() {
    echo "Building packages..."
    rm -rf "dist/$name" "dist/$name.zip" "dist/$name.tar.gz"
    mkdir -p dist
    git archive --format=tar --prefix="$name/" "$tag" | tar -x -C dist
    local po
    for po in "dist/$name"/locale/*/LC_MESSAGES/messages.po; do
        msgfmt -o "${po%.po}.mo" "$po"
    done
    (cd dist && zip -qr "$name.zip" "$name" && tar -czf "$name.tar.gz" "$name")
    rm -rf "dist/$name"
    echo "Built dist/$name.zip and dist/$name.tar.gz"
}

# The NEWS.md entry for this version, as GitHub release notes
release_notes() {
    local previous
    previous=$(git describe --tags --abbrev=0 "$tag^" 2>/dev/null || true)
    echo "## What's Changed"
    awk -v header="^$version_re[ :(]" '
        $0 ~ header { found = 1; next }
        found && /^[[:space:]]*$/ { exit }
        found { sub(/^[[:space:]]*-[[:space:]]*/, "* "); print }
    ' NEWS.md
    if [ -n "$previous" ]; then
        echo
        echo "**Full Changelog**: https://github.com/sproctor/php-calendar/compare/$previous...$tag"
    fi
}

if git rev-parse -q --verify "refs/tags/$tag" >/dev/null; then
    $publish || die "tag $tag already exists; use --publish to publish it"
    echo "Tag $tag exists; skipping preparation."
else
    prepare
fi

build

if $publish; then
    command -v gh >/dev/null || die "gh (GitHub CLI) is required to publish"
    git push origin master "$tag"
    gh release create "$tag" "dist/$name.zip" "dist/$name.tar.gz" \
        --draft --title "PHP-Calendar $version" --notes "$(release_notes)"
    echo "Created a draft GitHub release for $tag. Review it and publish it on GitHub."
else
    echo
    echo "Review the release commit and packages, then publish with:"
    echo "  $0 $version --publish"
fi
