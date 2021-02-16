git submodule update --init --recursive --remote
npm install
composer install --ignore-platform-reqs
composer dump-autoload --no-dev --classmap-authoritative
cd public/assets
bower install --allow-root
cd ../..
export NODE_OPTIONS="--max-old-space-size=2048"
./node_modules/.bin/encore production
bin/console doctrine:schema:update --force
bin/console cache:clear
bin/console cache:warmup --env=prod
