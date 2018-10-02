#!/bin/bash
set -ex
hhvm --version
curl https://getcomposer.org/installer | hhvm -d hhvm.jit=0 --php -- /dev/stdin --install-dir=/usr/local/bin --filename=composer

cd /var/source
hhvm /usr/local/bin/composer install

hhvm ./vendor/bin/hacktest tests/
