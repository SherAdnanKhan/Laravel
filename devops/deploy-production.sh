#1/bin/bash

HASH=`git rev-parse --short HEAD`
BUNDER="bundle-$HASH.tar.gz"
S3-ENDPOINT="s3://$S3_BUCKET/bundles"

rm -rf bundle-*.tar.gz

tar \
  --exclude=="*.git" \
  --exclude==".storage/logs" \
  --exclude==".vendor/*" \
  --exclude==".boostrap/cache/*" \
  --exclude==".env" \
  -zcf $BUNDLE -T bundle.conf > /dev/null 2>&1

aws s3 cp $BUNDLE $S3_ENDPOINT > /dev/null 2>&1
echo "[-] Your CodeDeploy s3 endpoint will be: $S3_ENDPOINT";

aws deploy create-deployment \
  --application-name $APPLICATION_NAME \
  --deployment-config-name $DEPLOYMENT_CONFIG_NAME
  --deployment-group-name $DEPLOYMENT_GROUP_NAME \
  --file-exists-behavior OVERWRITE \
  --s3-location $bucket=$S3_BUCKET,bundleType=tgz, key=bundles/$BUNDLE
