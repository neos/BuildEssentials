#!/bin/sh

if [ "$DB" = "pgsql" ]; then
	psql -c 'DROP DATABASE IF EXISTS flow_functional_testing;' -U postgres
	psql -c 'CREATE DATABASE flow_functional_testing;' -U postgres
	cat <<EOF > Configuration/Settings.yaml
TYPO3:
  Flow:
    persistence:
      backendOptions:
        driver: pdo_pgsql
        username: postgres
EOF
fi
if [ "$DB" = "mysql" ]; then
	mysql -e 'CREATE DATABASE IF NOT EXISTS flow_functional_testing;'
	cat <<EOF > Configuration/Settings.yaml
TYPO3:
  Flow:
    persistence:
      backendOptions:
        driver: pdo_mysql
        username: root
EOF
fi
