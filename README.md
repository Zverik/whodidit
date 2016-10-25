# WHODIDIT: OpenStreetMap Changeset Analyzer

This tool downloads replication diffs from OSM Planet site, calculates statistics on changes
and registers which 0.01-degree tiles were affected, and stores this in a MySQL database.
A series of PHP scripts and a JS frontend are used to access that data.

You can check a working installation at http://zverik.osm.rambler.ru/whodidit/

## Installation

### Database

Make a directory outside www root (for example, `/home/?/whodidit`)
and place `parse_osc.pl` there. Then create mysql database with utf8 collation and grant a user
right to create and update tables there. After that, create database tables:

    ./parse_osc.pl -h <host> -d <database> -u <user> -p <password> -c -v

Add the script to crontab:

    6 * * * * /home/?/whodidit/parse_osc.pl -h <host> -d <database> -u <user> -p <password> \
        -l http://planet.openstreetmap.org/replication/hour/ \
        -s /home/?/whodidit/state.txt -w /usr/local/bin/wget

Now each hour your database will be updated with fresh data. Note that the same osmChange
file **should not** be processed twice: the database has no means of skipping already
processed files.

If you do not want to wait several days to import backlog of changesets, you can download
a weekly backup (and a relevant state.txt) from http://zverik.osm.rambler.ru/whodidit/backup/

### Frontend

Make a directory inside www root, for example, `/var/www/whodidit`. Put all files
from `www` directory in it. Then create another directory, `/var/www/whodidit/scripts`
and put there all four PHP scripts from `scripts`.

Update the line `<script>var scripts = 'http://localhost/wdi/scripts/';</script>` in `index.html`
with the absolute URL of the directory you've put PHP files in. Then edit
`db.inc.php` script, updating `$frontend_url` variable with the absolute path to `index.html`.

Then write your database parameters into `connect()` function in `db.inc.php`, and you're set.

## What do scripts do?

* `parse_osc.pl`: This script downloads and parses replication diffs, storing changeset information
    in a MySQL database. It can create tables (with `-c` switch). Run it without parameters
    to see a list of all possible options.
* `db.inc.php`: Global settings for PHP scripts, also two useful functions (which can be updated
    in later versions, so be careful not to lose your settings -- sorry).
* `tiles.php`: Queries the database for tiles in an area. Returns JSON with either error message
    (large areas and areas that have more than 1000 tiles are rejected) or all tiles with changeset
    numbers and other information.
* `changeset.php`: Returns a JSON with detailed information for requested changeset ids. When
    called with `latest=1` parameter, returns the latest changeset.
* `rss.php`: As the title suggests, it generated an RSS feed with the latest changesets in a bbox.
* `index.html`: The HTML page is a front-end to WDI infrastructure. It makes use of all PHP scripts
    and allows user to check WDI tiles and acquire RSS links.
* `whodidit.js`: The JavaScript behind the front-end.

## Author

Everything here (except OpenLayers, of course) is written by Ilya Zverev, licensed WTFPL.
