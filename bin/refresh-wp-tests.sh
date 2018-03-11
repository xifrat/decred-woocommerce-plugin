#!/bin/bash

if [ $# != 1 ]
then
   echo "usage $0 mysql-root-password"
exit
fi

./bin/install-wp-tests.sh wordpress_test root $1 localhost latest true
