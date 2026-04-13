#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	if [ ! -f composer.json ]; then
		echo 'composer.json not found in /srv/app; cannot bootstrap the container.' >&2
		exit 1
	fi

	if [ -z "$(ls -A 'vendor/' 2>/dev/null)" ]; then
		if ! command -v composer >/dev/null 2>&1; then
			echo 'vendor/ is empty and composer is unavailable in this image; rebuild the image with dependencies or mount vendor/ before boot.' >&2
			exit 1
		fi

		composer_flags='--prefer-dist --no-progress --no-interaction'

		if [ "${APP_ENV:-dev}" = 'prod' ]; then
			composer_flags="$composer_flags --no-dev"
		fi

		composer install $composer_flags
	fi

	php bin/console -V
	echo 'PHP app ready!'
fi

exec docker-php-entrypoint "$@"
