#/bin/sh

rm -rf /tmp/com
git clone http://github.com/webooxx/com.git /tmp/com
cd ..
rm -rf com
mv /tmp/com ./
cd ..
pwd
