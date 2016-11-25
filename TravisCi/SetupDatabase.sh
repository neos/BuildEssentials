#!/bin/sh

if [ "$DB" = "pgsql" ]; then
	psql -c 'DROP DATABASE IF EXISTS flow_functional_testing;' -U postgres
	psql -c 'CREATE DATABASE flow_functional_testing;' -U postgres
	cat <<EOF > Configuration/Settings.yaml
Neos:
  Flow:
    persistence:
      backendOptions:
        host: '127.0.0.1'
        driver: pdo_pgsql
        user: 'postgres'
        dbname: 'flow_functional_testing'
EOF
fi
if [ "$DB" = "mysql" ]; then
	mysql -e 'CREATE DATABASE IF NOT EXISTS flow_functional_testing;'
	cat <<EOF > Configuration/Settings.yaml
Neos:
  Flow:
    persistence:
      backendOptions:
        host: '127.0.0.1'
        driver: pdo_mysql
        user: 'root'
        dbname: 'flow_functional_testing'
EOF
fi
