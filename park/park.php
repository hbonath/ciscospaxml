<?php
require_once('../include/CiscoAsterisk.class.php')

function Aastra_get_parked_calls_Asterisk()
{
# Initial array
$park=array();

# Connect to AGI
$as = new AGI_AsteriskManager();    // need to locate this function
$res = $as->connect();

# Prepare command
if(Aastra_compare_version_Asterisk('1.6'))
        {
        $command1='parkedcalls show';
        $command2='core show channel';
        $parameter=1;
        }
else
        {
        $command1='show parkedcalls';
        $command2='show channel';
        $parameter=1;
        }

# Check current list
$res=$as->Command($command1);
$line=split("\n", $res['data']);
$count=0;
$found=False;
foreach($line as $myline)
        {
        if((Aastra_compare_version_Asterisk('1.6')) and (!$found))
                {
                if(strstr($myline,'Extension') and strstr($myline,'Channel'))
                        {
                        $linevalue= preg_split('/ /', $myline,-1,PREG_SPLIT_NO_EMPTY);
                        if(($parameter=array_search('Channel',$linevalue))!==false) $found=True;
                        }
                }
        if((!strstr($myline,'Privilege')) && (!strstr($myline,'Extension')) && (!strstr($myline,'parked')) && (!strstr($myline,'Parking')) && ($myline!=''))
                {
                $linevalue= preg_split('/ /', $myline,-1,PREG_SPLIT_NO_EMPTY);
                if(($linevalue[0]!='') and (is_numeric($linevalue[0])))
                        {
                        $park[$count][0]=$linevalue[0];
                        $res_i=$as->Command($command2.' '.$linevalue[$parameter]);
                        $line_i=@split("\n", $res_i['data']);
                        foreach($line_i as $myline_i)
                                {
                                if(strstr($myline_i,'Caller ID Name:') and !strstr($myline_i,'(N/A)')) $park[$count][1]=substr(substr(strrchr($myline_i,':'),1),1);
                                else if(strstr($myline_i,'Caller ID:')) $park[$count][1]=substr(substr(strrchr($myline_i,':'),1),1);
                                }
                        $count++;
                        }
                }
        }

# Disconnect properly
$as->disconnect();

# Return answer
return($park);
}


?>