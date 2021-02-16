# Dependencies
git submodule update --init --recursive --remote
npm install
composer install --ignore-platform-reqs
composer dump-autoload --no-dev --classmap-authoritative

# Legacy Console
cd public/assets || exit
bower install --allow-root
cd ../..

# Build Vue
export NODE_OPTIONS="--max-old-space-size=2048"
./node_modules/.bin/encore production

# Symfony Warm Up
bin/console doctrine:schema:update --force
bin/console cache:clear
bin/console cache:warmup --env=prod

# Fix Permissions
chown -R www-data ./
chgrp -R www-data ./