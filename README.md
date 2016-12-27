#WPCLI Migrate Script#

##Descirption##
At the current time ( Dec/2016 ) this code based is intended to be a proof of concept for a JSON based migration into WordPress currently based on the WP JSON API ( V2 ) which was release with WP 4.7.

Additional platforms will require custom code / extending the currently written classes to account for their data structure.

##Parameters##
- 'json_file', // Path to local json: --json_file=<path to local file> - A local JSON file location
- 'json_url', // URL for remote json return: --json_url=<url which returns a json response> - A JSON URL endpoint
- 'wp2wp', // 'true' if doing a WordPress to WordPress migrations: --wp2wp=true - A WordPress to Wordpress migration
- 'migrate_debug', // Used for outputting terminal logs: --migrate_debug=true
- 'offset', // Offset as understood by WordPress Core queries: --offset=<integer>

###Usage:###
wp migrate --json_url=http://test.me.dev/wp-json/wp/v2/posts?per_page=10
wp migrate --json_file=<path to local file>

###Standar WordPress to Wordpress command:###
wp migrate --json_url=http://test.me.dev/wp-json/wp/v2/posts?per_page=10 --wp2wp=true

###Demo Posts URL:###
https://demo.wp-api.org/wp-json/wp/v2/posts

###Users URL:###
https://demo.wp-api.org/wp-json/wp/v2/users/<user ID>

###WordPress to WordPress Import with debugging:###
`wp migrate --json_url=http://test.me.dev/wp-json/wp/v2/posts?per_page=100 --wp2wp=true --migrate_debug=true`
Note that the WP JSON API defaults to a limit of 100 objects accessed to account for this
you may also use the --offset parameter to get more content