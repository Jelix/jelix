#!/bin/bash

ROOTDIR="/jelixapp"
MYSQL_VERSION=""
PHP_VERSION="8.0"
APPNAME="testapp"
APPDIR="$ROOTDIR/$APPNAME"
VAGRANTDIR="/vagrantscripts"
APPHOSTNAME="testapp16.local"
APPHOSTNAME2=""
LDAPCN="testapp16"
FPM_SOCK="php\\/php8.0-fpm.sock"
POSTGRESQL_VERSION=11

source $VAGRANTDIR/common_provision.sh

