# Your own themes

Put Bootstrap 5.3 themes here to choose them in a calendar's settings or as the site's default
(Admin → Appearance). Upgrading PHP-Calendar doesn't touch this folder.

A theme is either:

- one stylesheet, e.g. `club.css`, or
- a folder with `bootstrap.min.css`, e.g. `club/bootstrap.min.css`, and optionally
  `bootstrap.rtl.min.css` for right-to-left languages.

The stylesheet replaces Bootstrap's, so it must be a complete Bootstrap build, such as one made with
Bootstrap's Sass or downloaded from a theme site. To only change a few things, `@import` Bootstrap (or
one of the included themes) first and add your rules after it:

    @import url("../static/lib/bootstrap/bootstrap.min.css");
    body { font-family: Georgia, serif; }
    .navbar { background-color: #7a1f3d !important; }

The theme list names it after the file. To give it another name, add `club.json` (or `club/theme.json`):

    {"name": "Club colors"}

Themes with a dark background are always shown in dark mode, and the light/dark menu is hidden.
