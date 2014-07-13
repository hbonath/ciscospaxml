#!/bin/bash
#
#	Cisco SPA Parking lot CGI XML Application for Asterisk
#
#	Install to apache cgi-bin directory and chmod +x
#
#	Call via http request on IP phone via http://pbx-hostname/cgi-bin/parkinglot.sh
#	
#	(c)2014 Henry Bonath
#	henry@thinkcsc.com
#
PATH=$PATH:/usr/sbin
echo "Content-type: text/xml"
echo ""

# Pull list of parked calls from Asterisk CLI
PARKED=`asterisk -rx 'parkedcalls show' | grep total`

read -r -a PARKARRAY <<< "$PARKED"
PARKCOUNT=${PARKARRAY[0]}

echo '<CiscoIPPhoneDirectory>'
echo '<Title>Parking Lot</Title>'
echo '<Prompt>'$PARKED'</Prompt>'

if [ $PARKCOUNT != 0 ]; then
	OLDIFS=$IFS
	IFS=$'\n'			# Change IFS to "newline" so that we may read list of all parked calls into an array
	PARKLIST=`asterisk -rx 'parkedcalls show' | egrep 'SIP|SCCP'`
	for LOOP in $PARKLIST; do 
		IFS=$OLDIFS		# Change IFS back to default "whitespace" so that we can extract the SIP channel from the LOOP array
		read -r -a SIPCHANNEL <<< "$LOOP"
		CALLERID=`asterisk -rx "core show channel ${SIPCHANNEL[1]}" | grep "Caller ID Name"`
		CALLERIDNAME="${CALLERID##*:}"
		PARKSLOT=${SIPCHANNEL[0]}
		echo '<DirectoryEntry>'	
		echo '<Name>'$CALLERIDNAME'</Name>'
		echo '<Telephone>'$PARKSLOT'</Telephone>'
		echo '</DirectoryEntry>'
	done
fi

echo '</CiscoIPPhoneDirectory>'

exit 0