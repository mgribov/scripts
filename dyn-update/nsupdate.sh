#!/usr/local/bin/bash

cd /root/nsupdate
ip=`/sbin/ifconfig re0|grep inet|awk '{print $2}'`

nsupdate -k Kkey.domain.com.+123+45678.private <<EOF
server ns1.domain.com 
zone domain.com
update delete myhost.domain.com. A
update add myhost.domain.com. 3600 A $ip
send
EOF

