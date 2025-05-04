Settings:

1) go into your desired directory

2(
mkdir /var/www/capslock && cd /var/www/capslock                       # linux
OR
mkdir C:/Users/your_user/Downloads/capslock                           # windows
cd C:/Users/your_user/Downloads/capslock

3)
git clone https://github.com/dancostinel/capslock.git .

4) capslock$ docker compose -f docker/docker-compose.yaml up -d

5) docker exec -it capslock-php-container bash
   capslock#  composer install

6) access the website in browser:  http://localhost:7080

7) run unit tests
docker exec -it capslock-php-container bash
php vendor/bin/phpunit tests/