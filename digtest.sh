ds0=`cat d0.dig 2>/dev/null`
ds1=`cat d1.dig 2>/dev/null`
ds2=`cat d2.dig 2>/dev/null`
ds3=`cat d3.dig 2>/dev/null`
ds4=`cat d4.dig 2>/dev/null`
ds5=`cat d5.dig 2>/dev/null`
ds6=`cat d6.dig 2>/dev/null`
ds7=`cat d7.dig 2>/dev/null`
ds8=`cat d8.dig 2>/dev/null`
ds9=`cat d9.dig 2>/dev/null`

if [ "$ds0" = "" ]; then ds0='www.google.com'; fi
if [ "$ds1" = "" ]; then ds1='www.facebook.com'; fi
if [ "$ds2" = "" ]; then ds2='www.bca.co.id'; fi
if [ "$ds3" = "" ]; then ds3='www.detik.com'; fi
if [ "$ds4" = "" ]; then ds4='www.youtube.com'; fi
if [ "$ds5" = "" ]; then ds5='pornhub.com'; fi
if [ "$ds6" = "" ]; then ds6='kominfo.go.id'; fi
if [ "$ds7" = "" ]; then ds7='reddit.com'; fi
if [ "$ds8" = "" ]; then ds8='lamanlabuh.resolver.id'; fi
if [ "$ds9" = "" ]; then ds9='www.tiktok.com'; fi

printf "<table><tr><td width=100><b>Domain Name</b></td><td><b>A Result (IPv4)</b></td></tr>\n"
s0=`dig +short $ds0 | tr '\n' ' '`
printf " <tr><td><small>$ds0</small></td><td><small>$s0</small></td></tr>\n"
s1=`dig +short $ds1 | tr '\n' ' '`
printf " <tr><td><small>$ds1</small></td><td><small>$s1</small></td></tr>\n"
s2=`dig +short $ds2 | tr '\n' ' '`
printf " <tr><td><small>$ds2</small></td><td><small>$s2</small></td></tr>\n"
s3=`dig +short $ds3 | tr '\n' ' '`
printf " <tr><td><small>$ds3</small></td><td><small>$s3</small></td></tr>\n"
s4=`dig +short $ds4 | tr '\n' ' '`
printf " <tr><td><small>$ds4</small></td><td><small>$s4</small></td></tr>\n"
s5=`dig +short $ds5 | tr '\n' ' '`
printf " <tr><td><small>$ds5</small></td><td><small>$s5</small></td></tr>\n"
s6=`dig +short $ds6 | tr '\n' ' '`
printf " <tr><td><small>$ds6</small></td><td><small>$s6</small></td></tr>\n"
s7=`dig +short $ds7 | tr '\n' ' '`
printf " <tr><td><small>$ds7</small></td><td><small>$s7</small></td></tr>\n"
s8=`dig +short $ds8 | tr '\n' ' '`
printf " <tr><td><small>$ds8</small></td><td><small>$s8</small></td></tr>\n"
s9=`dig +short $ds9 | tr '\n' ' '`
printf " <tr><td><small>$ds9</small></td><td><small>$s9</small></td></tr>\n"

printf "<tr><td><b>Domain Name</b></td><td><b>AAAA Result (IPv6)</b></td></tr>\n"
as0=`dig +short AAAA $ds0 | tr '\n' ' '`
printf " <tr><td><small>$ds0</small></td><td><small>$as0</small></td></tr>\n"
as1=`dig +short AAAA $ds1 | tr '\n' ' '`
printf " <tr><td><small>$ds1</small></td><td><small>$as1</small></td></tr>\n"
as2=`dig +short AAAA $ds2 | tr '\n' ' '`
printf " <tr><td><small>$ds2</small></td><td><small>$as2</small></td></tr>\n"
as3=`dig +short AAAA $ds3 | tr '\n' ' '`
printf " <tr><td><small>$ds3</small></td><td><small>$as3</small></td></tr>\n"
as4=`dig +short AAAA $ds4 | tr '\n' ' '`
printf " <tr><td><small>$ds4</small></td><td><small>$as4</small></td></tr>\n"
as5=`dig +short AAAA $ds5 | tr '\n' ' '`
printf " <tr><td><small>$ds5</small></td><td><small>$as5</small></td></tr>\n"
as6=`dig +short AAAA $ds6 | tr '\n' ' '`
printf " <tr><td><small>$ds6</small></td><td><small>$as6</small></td></tr>\n"
as7=`dig +short AAAA $ds7 | tr '\n' ' '`
printf " <tr><td><small>$ds7</small></td><td><small>$as7</small></td></tr>\n"
as8=`dig +short AAAA $ds8 | tr '\n' ' '`
printf " <tr><td><small>$ds8</small></td><td><small>$as8</small></td></tr>\n"
as9=`dig +short AAAA $ds9 | tr '\n' ' '`
printf " <tr><td><small>$ds9</small></td><td><small>$as9</small></td></tr></table><p>\n"

