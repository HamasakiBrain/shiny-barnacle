#!/bin/sh
set -eu
MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS market_testing;
GRANT ALL PRIVILEGES ON market_testing.* TO '${MYSQL_USER}'@'%';
SQL
