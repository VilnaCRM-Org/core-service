#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	if [ ! -f composer.json ]; then
		echo 'composer.json not found in /srv/app; cannot bootstrap the container.' >&2
		exit 1
	fi

	if [ -z "$(ls -A 'vendor/' 2>/dev/null)" ]; then
		composer install --prefer-dist --no-progress --no-interaction
	fi

	php bin/console -V
	echo 'PHP app ready!'
fi

exec docker-php-entrypoint "$@"
