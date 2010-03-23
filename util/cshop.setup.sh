#!/bin/bash



CSHOP_BASEDIR="`pwd`/.."

echo "WELCOME TO cShop INSTALLER v.0.5"

read -p "enter full site domain name (no www's): " DOMAIN

CLEAN_DOMAIN=`echo $DOMAIN | tr A-Z a-z`     # lowercase
CLEAN_DOMAIN=${CLEAN_DOMAIN//./}             # strip dots
MYSQL_DB=${CLEAN_DOMAIN:0:10}                # first 10 chars

read -p "Create mysql database structure? [y/n] " DO_MYSQL
if [ "$DO_MYSQL" == "y" ]; then
    echo "==> Creating mySQL database store ============================="

    if [ $MYSQL_RPASS ]; then
        echo "I seem to have database credentials already."

    else 
        read -p "Enter admin/root mysql user: " MYSQL_RUSER
        read -p "Enter admin/root mysql pass: " MYSQL_RPASS
        read -p "Enter name of database to use (will be created if needed): [$MYSQL_DB] " ans
        if [ "$ans" != "" ]; then
            MYSQL_DB="$ans"
        fi
    fi

    echo "creating.... "
    mysql -p$MYSQL_RPASS -u$MYSQL_RUSER -e "CREATE DATABASE IF NOT EXISTS $MYSQL_DB"
    if [ $? -ne 0 ]; then
        echo "failed to create database store."
        exit 1
    fi

    echo "applying schema.... "
    mysql -p$MYSQL_RPASS -u$MYSQL_RUSER $MYSQL_DB < cShop.create.mysql-50.sql
    if [ $? -eq 0 ]; then
        echo "done."
    else
        echo "failed to apply schema."
        exit 1
    fi
fi

MYSQL_NEW_USER=$MYSQL_DB
MYSQL_NEW_PASS=`/usr/bin/openssl rand -base64 15 | cut -c 5-12`

read -p "Create a new mysql user unique to this site? [y/n] " DO_MYSQL_USER
if [ "$DO_MYSQL_USER" == "y" ]; then
    echo "==> Creating mySQL user ======================================="

    if [ $MYSQL_RPASS ]; then
        echo "I seem to have database credentials already."
    else 
        read -p "Enter admin/root mysql user: " MYSQL_RUSER
        read -p "Enter admin/root mysql pass: " MYSQL_RPASS
    fi

    read -p "Enter name of database to use (will be created if needed): [$MYSQL_DB] " ans
    if [ "$ans" != "" ]; then
        MYSQL_DB="$ans"
    fi

    read -p "Enter new mysql username: [$MYSQL_NEW_USER] " ans
    if [ "$ans" != "" ]; then
        MYSQL_NEW_USER="$ans"
    fi
    read -p "Enter new mysql password: [$MYSQL_NEW_PASS] " ans
    if [ "$ans" != "" ]; then
        MYSQL_NEW_PASS="$ans"
    fi

    mysql -p$MYSQL_RPASS -u$MYSQL_RUSER -e "GRANT ALL ON $MYSQL_DB.* TO $MYSQL_NEW_USER@localhost IDENTIFIED BY '$MYSQL_NEW_PASS'"

    if [ $? -ne 0 ]; then
        echo "failed to create database user."
        exit 1
    fi
else
    read -p "Enter mysql username to use for this site: [$MYSQL_NEW_USER] " ans
    if [ "$ans" != "" ]; then
        MYSQL_NEW_USER="$ans"
    fi
    read -p "Enter mysql password to use for this site: [] " MYSQL_NEW_PASS

    read -p "Enter mysql database to use for this site: [$MYSQL_DB] " ans
    if [ "$ans" != "" ]; then
        MYSQL_DB="$ans"
    fi
fi


echo "==> Creating symlinks ========================================="
echo "control:"
if [ ! -d ../../../web/control ]; then
    echo "/../web/control/ dir does not exist, creating"
    mkdir ../../../web/control
fi

cd ../../../web/control/
ln -s ../../local/cshop/control cshop

echo "cart templates"
if [ ! -d ../../templates ]; then
    echo "/templates/ dir does not exist, creating"
    mkdir ../../templates
fi

cd ../../templates/
ln -s ../local/cshop/templates cart

if [ ! -d cart_custom ]; then
    mkdir cart_custom
fi

if [ ! -d control ]; then
    read -p "/templates/control/ does not exist. Create it and populate with skeleton templates? [y/n] " DO_IT
    if [ "$DO_IT" == "y" ]; then
        echo "==> Copying skeleton templates for /control/..."
        mkdir control
        cp $CSHOP_BASEDIR/samples/templates/control/*tpl control/
    fi 
fi

templates=`ls -l | grep "\.tpl$" `
if [ "$templates" == "" ]; then
    read -p "There are no default templates here. Populate /templates/ dir with skeleton store templates?  [y/n] " DO_IT
    if [ "$DO_IT" == "y" ]; then
        echo "==> Copying skeleton templates for storefront and cart..."
        mkdir control
        cp $CSHOP_BASEDIR/samples/templates/*tpl ./
    fi 
fi


echo "symlinking to cart/checkout handlers"
cd ../web
if [ -d cart ]; then
    echo "directory 'cart' exists, skipping"
else
    ln -s ../local/cshop/cart
fi


if [ ! -f store.browse.php ]; then
    read -p "there does not seem to be a storefront controller (store.browse.php) Use the default one? [y/n] " DO_IT
    if [ "$DO_IT" == "y" ]; then
        cp $CSHOP_BASEDIR/samples/store.browse.php .
    fi 
fi


cd $CSHOP_BASEDIR
cd ../../
echo "==> Creating Smarty compile and cache dirs ===================="

mkdir .smarty.templates_c
chmod 777 .smarty.templates_c/
mkdir .smarty.cache
chmod 777 .smarty.cache/
echo '.smarty*' > .cvsignore

echo "==> Creating dir for uploading images ========================="
cd $CSHOP_BASEDIR/../../
mkdir -p web/uploads/cshop
chmod 771 web/uploads/cshop
echo '*' > web/uploads/.cvsignore


cd $CSHOP_BASEDIR
if [ ! -d ../../config ]; then
    echo "/config/ dir does not exist, creating"
    mkdir ../../config
fi

echo "==> creating config/cshop.config.php config file ========="
if [ -f $CSHOP_BASEDIR/../config/cshop.config.php ]; then
    echo "file exists, skipping"
else 
    cp $CSHOP_BASEDIR/samples/cshop.config.php $CSHOP_BASEDIR/../../config/cshop.config.php
fi

cd $CSHOP_BASEDIR/../../

if [ ! -f config/init.php ]; then
    echo "WARNING: /config/init.php does not exist. Copying from samples dir."
    cp $CSHOP_BASEDIR/samples/init.php $CSHOP_BASEDIR/../../config/init.php
fi




if [ ! -f config/local-init.dev.php ]; then
    echo "WARNING: /config/local-init.dev.php does not exist. Creating from known values."
    (
    cat <<EOF
<?php

# local DB connection and config values for development site.
# auto-generated by cshop.setup.sh

define("SITE_TITLE", "$DOMAIN"); // freeform string title of site ("My Site" or "somesite.com" or whatev)
define("SITE_DOMAIN_NAME", "$DOMAIN"); // canonical domain name of site, case doesn't matter

# PEAR::DB connection string
define('PEAR_DSN', "mysql://$MYSQL_NEW_USER:$MYSQL_NEW_PASS@localhost/$MYSQL_DB");

# abs. path to the site top-level dir (hardcode this for one iota of speed)
define("SITE_ROOT_DIR", str_replace("/config/local-init.dev.php", "", __FILE__)); 

# Recipient of auto error reports and other default emails from the site
define('ERROR_EMAIL_RECIP', 'debug@circusmedia.com');   # maybe you, maybe not

# sender of outgoing emails looks like this
define('EMAIL_SENDER', 'admin@'.getenv("SERVER_NAME"));# used as From:

# flag - maybe don't send emails to REAL site owners from debug or stage sites!
define('ON_LIVE_SERVER', false); // this just affects recips of outgoing emails.

# the all-powerful DEBUG flag
define('DEBUG', true); // set to true to have debug dumps + smarty console
    
EOF
    ) > $CSHOP_BASEDIR/../../config/local-init.dev.php
fi



cd $CSHOP_BASEDIR/..       # /local

read -p "checkout Smarty to 'local' dir? [y/n] " DO_IT
if [ "$DO_IT" == "y" ]; then
    echo "==> Checking out authlib from CVS repo $CVS_REPO"
    cvs -d :pserver:cvsread@cvs.php.net:/repository co -r Smarty_2_6_10 -d Smarty smarty/libs
fi 
echo 'Smarty onsetlib authlib cshop' > .cvsignore

cd $CSHOP_BASEDIR

read -p "Load sample Product/Category/Shipping data into database? [y/n] " DO_IT
if [ "$DO_IT" == "y" ]; then
    mysql -p$MYSQL_NEW_PASS -u$MYSQL_NEW_USER $MYSQL_DB < samples/cshop.sampleProducts.sql
    if [ $? -ne 0 ]; then
        echo "WARNING: failed to apply sample data."
    fi
fi 

read -p "Load sample Product images into /uploads directory? [y/n] " DO_IT
if [ "$DO_IT" == "y" ]; then
    cp samples/uploads/*.* ../../web/uploads/cshop/
    if [ $? -ne 0 ]; then
        echo "WARNING: failed to copy files."
    fi
fi 


read -p "Add mod_rewrite rules to .htaccess file for SEF URLs? [y/n] " DO_IT
if [ "$DO_IT" == "y" ]; then
    (
    cat <<EOF
RewriteEngine On
RewriteRule ^browse/([a-z0-9./_-]+)/?\$ store.browse.php?cn=\$1 [QSA,NS]
RewriteRule ^product/([0-9]+)/([^/]+)(/in/([a-z0-9_-]+))?(.*) /store.browse.php?pid=\$1&cn=\$4&etc=\$5 [QSA,NS]
EOF
    ) >> $CSHOP_BASEDIR/../../web/.htaccess

    if [ $? -ne 0 ]; then
        echo "WARNING: failed to create .htaccess file."
    fi
fi 

cd $CSHOP_BASEDIR

echo "done."

