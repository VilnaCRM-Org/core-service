#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

if [ "$1" = 'php-fpm' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	# Install the project the first time PHP is started
	# After the installation, the following block can be deleted
	if [ ! -f composer.json ]; then
		CREATION=1

		rm -Rf tmp/
		composer create-project "symfony/skeleton $SYMFONY_VERSION" tmp --stability="$STABILITY" --prefer-dist --no-progress --no-interaction --no-install

		cd tmp
		composer require "php:>=$PHP_VERSION"
		composer config --json extra.symfony.docker 'true'
		cp -Rp . ..
		cd -

		rm -Rf tmp/
	fi

	if [ "$APP_ENV" != 'prod' ]; then
		composer install --prefer-dist --no-progress --no-interaction
	fi

	if grep -q '^DB_URL=".*"' .env; then
		# After the installation, the following block can be deleted
		if [ "$CREATION" = "1" ]; then
			echo "To finish the installation please press Ctrl+C to stop Docker Compose and run: docker compose up --build"
			sleep infinity
		fi

		echo "Waiting for MongoDB to be ready..."
		ATTEMPTS_LEFT_TO_REACH_MONGO=60
		until [ $ATTEMPTS_LEFT_TO_REACH_MONGO -eq 0 ]; do
			if mongo --eval "db.runCommand({ ping: 1 })" "DB_URL" > /dev/null 2>&1; then
				break
			fi
			sleep 1
			ATTEMPTS_LEFT_TO_REACH_MONGO=$((ATTEMPTS_LEFT_TO_REACH_MONGO - 1))
			echo "Still waiting for MongoDB to be ready... $ATTEMPTS_LEFT_TO_REACH_MONGO attempts left"
		done

		if [ $ATTEMPTS_LEFT_TO_REACH_MONGO -eq 0 ]; then
			echo "MongoDB is not up or not reachable"
			exit 1
		else
			echo "MongoDB is now ready and reachable"
		fi

	fi

	setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var
	setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var
fi

exec docker-php-entrypoint "$@"
