DIR=`pwd`
PLUGIN_NAME=`basename $DIR`
PLUGIN_LOADER="$PLUGIN_NAME.php"
ZIP_FILE="/tmp/$PLUGIN_NAME.zip"

if [ ! -f $PLUGIN_LOADER ] 
then
  echo "Missing $PLUGIN_LOADER -- is this the plugin directory?"
  pwd
  exit;
fi

set -x
composer install --no-dev
set +x

[ -f $ZIP_FILE ] && rm $ZIP_FILE
set -x
zip -9qrT $ZIP_FILE LICENSE README.md $PLUGIN_LOADER assets includes vendor
set +x
ls -lah $ZIP_FILE
