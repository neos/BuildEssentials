#!/bin/sh

if [ "$DB" = "pgsql" ]; then
	psql -c 'DROP DATABASE IF EXISTS flow_functional_testing;' -U postgres;
fi
if [ "$DB" = "pgsql" ]; then
	psql -c 'CREATE DATABASE flow_functional_testing;' -U postgres;
fi
if [ "$DB" = "mysql" ]; then
	mysql -e 'CREATE DATABASE IF NOT EXISTS flow_functional_testing;';
fi
if [ "$DB" = "pgsql" ]; then
	sed -i.bak $'s/# adjust to your database host/\\\n        driver: pdo_pgsql\\\n        username: postgres/' Configuration/Settings.yaml;
fi
if [ "$DB" = "mysql" ]; then
	sed -i.bak $'s/# adjust to your database host/\\\n        driver: pdo_mysql\\\n        username: root/' Configuration/Settings.yaml;
fi
