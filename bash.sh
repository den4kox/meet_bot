#/bin/bash

rm -rf ./meetbot
git clone https://github.com/den4kox/meet_bot.git ./meetbot
cp env ./meetbot/.env
cd ./meetbot
echo $(pwd)
docker run --rm --interactive --tty --volume $PWD:/app composer install
docker-compose up -d --build

docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
