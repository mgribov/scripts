$ORIGIN .
$TTL 3600   ; 1 hour
<domain.com>        IN SOA  ns1.webhost.com. hostmaster.<domain.com>. (
                <timestamp> ; serial
                3600       ; refresh (1 hour)
                600        ; retry (10 minutes)
                1209602    ; expire (2 weeks)
                3600       ; minimum (1 hour)
                )
            NS  ns1.webhost.com.
            NS  ns2.webhost.com.
            NS  ns3.webhost.com.
            A   1.2.3.4
            MX  10 mx1.<domain.com>.
            TXT "v=spf1 +a +mx -all"


$ORIGIN <domain.com>.
            SPF "v=spf1 +a +mx -all"
ns1         A   1.2.3.4
ns2         A   1.2.3.4
ns3         A   1.2.3.4

mx1         A   1.2.3.4
mx2         A   1.2.3.4

www         A   1.2.3.4
svn         A   1.2.3.4
mail        A   1.2.3.4

ftp         CNAME   www.<domain.com>.
stats       CNAME   www.<domain.com>.
webmail     CNAME   mail.<domain.com>.

