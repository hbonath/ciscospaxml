<?php
###################################################################################################
#
# Adapted from the phpagi manager available on Sourceforge
#
# Adapted from Aastra XML API - AastraAsterisk.php
# Copyright Aastra Telecom 2010
#
# This file includes a class that interfaces with the Asterisk Manager
#
# Public methods
#   connect(server,username,secret)
#   disconnect()
#   Command(command,actionid)
#   Originate(...)
#   database_show(family) 
#   database_put(family,key,value) 
#   database_get(family,key) 
#   database_del(family,key) 
#   ExtensionState(exten,context,actionid)
#
# Private methods
#   send_request(action,parameters)
#   wait_response(allow_timeout)
#   Logoff()
#   log(message)
#
###################################################################################################
require_once('config.php');

class AGI_AsteriskManager
{
	# Variables
	var $config;
	var $socket = NULL;
	var $server;
	var $port;
	var $event_handlers;
	var $timeout=False;

	# Constructor
	function AGI_AsteriskManager()
	{
		// # Load config from freePBX
		// $config=Aastra_readINIfile(AASTRA_CONFIG_DIRECTORY.'asterisk.conf','#','=');
		// $this->config['username']='aastra-xml';
		// $this->config['secret']=$config['General']['password'];

		// # Add default values to config for uninitialized values
		// if(!isset($this->config['server'])) $this->config['server']='localhost';
		// if(!isset($this->config['port'])) $this->config['port']=5038;
		// if(!isset($this->config['username'])) $this->config['username']='phpagi';
		// if(!isset($this->config['secret'])) $this->config['secret']='phpagi';
  $server=$amiserver
  $port=$amiport
  $username=$amiuser
  $secret=$amisecret
	}

	function add_event_handler($event, $callback)
    	{
      		$event = strtolower($event);
      		if(isset($this->event_handlers[$event]))
      			{
        		$this->log("$event handler is already defined, not over-writing.");
        		return false;
      			}
      		$this->event_handlers[$event] = $callback;
      		return true;
    	}

	function process_event($parameters)
    	{
      		$ret = false;
      		$e = strtolower($parameters['Event']);
	      	$handler = '';
      		if(isset($this->event_handlers[$e])) $handler = $this->event_handlers[$e];
      		elseif(isset($this->event_handlers['*'])) $handler = $this->event_handlers['*'];
      		if(function_exists($handler)) $ret = $handler($e, $parameters, $this->server, $this->port);
      		return $ret;
	}

    	function send_request($action, $parameters=array())
    	{
      		$req="Action: $action\r\n";
	      	foreach($parameters as $var=>$val) $req.="$var: $val\r\n";
	      	$req.="\r\n";
      		fwrite($this->socket, $req);
      		return $this->wait_response();
    	}

	function get_socket()
	{
		if($this->socket) return(True);
		else return(False);
	}

	function wait_response($allow_timeout=False)
    	{
     		$timeout=False;
      		do
      			{
        		$type='';
        		$parameters=array();
        		$temp=fgets($this->socket, 4096);
			if($temp===False) 
				{
				$check=stream_get_meta_data($this->socket);
				if($check['timed_out']!='1')
					{
					$this->socket=NULL;
					break;	
					}
				else $buffer='';
				}
			else $buffer=trim($temp);

        		while($buffer != '')
        			{
          			$a = strpos($buffer, ':');
          			if($a)
          				{
            				if(!count($parameters)) // first line in a response?
            					{
              				$type=strtolower(substr($buffer, 0, $a));
              				if(substr($buffer,$a+2)=='Follows')
              					{
                					# A follows response means there is a multiline field that follows.
                					$parameters['data'] = '';
                					$buff=fgets($this->socket, 4096);
                					while(substr($buff, 0, 6) != '--END ')
                						{
                  						$parameters['data'].=$buff;
                  						$buff=fgets($this->socket,4096);
                						}
              					}
            					}

            				# Store parameter in $parameters
            				$parameters[substr($buffer,0,$a)]=substr($buffer,$a+2);
          				}
          			$buffer=trim(fgets($this->socket,4096));
        			}

        		# Process response
        		switch($type)
        			{
          			case '':
            				$timeout=$allow_timeout;
            				break;
          			case 'event':
            				$this->process_event($parameters);
            				break;
          			case 'response':
            				break;
          			default:
            				$this->log('Unhandled response packet from Manager: '.$type);
            				break;
        			}
      			} while($type!='response' && !$timeout);
      		return $parameters;
    	}

	function listen($timeout='60')
    	{
     		$exit=False;
		$time1=time();
      		do
      			{
        		$type='';
        		$parameters=array();
        		$temp=fgets($this->socket, 4096);
			if($temp===False) 
				{
				$check=stream_get_meta_data($this->socket);
				if($check['timed_out']!='1')
					{
					$this->socket=NULL;
					break;	
					}
				else $buffer='';
				}
			else $buffer=trim($temp);
        		while($buffer!='')
        			{
          			$a=strpos($buffer, ':');
          			if($a)
          				{
            				if(!count($parameters)) 
            					{
              				$type=strtolower(substr($buffer,0,$a));
              				if(substr($buffer,$a+2)=='Follows')
              					{
                					# A follows response means there is a multiline field that follows.
                					$parameters['data']='';
                					$buff=fgets($this->socket, 4096);
                					while(substr($buff, 0, 6) != '--END ')
                						{
                  						$parameters['data'].=$buff;
                  						$buff=fgets($this->socket,4096);
                						}
              					}
            					}

            				# Store parameter in $parameters
            				$parameters[substr($buffer,0,$a)]=substr($buffer,$a+2);
          				}
          			$buffer=trim(fgets($this->socket,4096));
        			}

        		# Process response
        		switch($type)
        			{
          			case 'event':
            				$this->process_event($parameters);
            				break;
          			default:
            				if($type!='') $this->log('Unhandled response packet from Manager: '.$type);
            				break;
        			}

			# Check timeout
			$time2=time();
			if($time2>($time1+$timeout)) $exit=True;
      			} while(!$exit);
      		return $parameters;
    	}


    	function connect($server=NULL,$username=NULL,$secret=NULL,$display=True)
    	{
		# OK so far
		$return=True;

      		# Use config if not specified
      		if(!isset($server)) $server = $this->config['server'];
      		if(!isset($username)) $username = $this->config['username'];
      		if(!isset($secret)) $secret = $this->config['secret'];

      		# Get port from server if specified
      		if(strpos($server, ':') !== false)
      			{
        		$c = explode(':', $server);
        		$this->server = $c[0];
        		$this->port = $c[1];
      			}
      		else
      			{
        		$this->server = $server;
        		$this->port = $this->config['port'];
      			}

      		# Connect the socket
      		$errno = $errstr = NULL;
      		$this->socket = @fsockopen($this->server, $this->port, $errno, $errstr);
      		if($this->socket == false)
      			{
        		$this->log("Unable to connect to manager {$this->server}:{$this->port} ($errno): $errstr");
			$return=False;
      			}

      		# Read the header
		if($return)
			{
	      		$str=fgets($this->socket);
     			if($str==false)
     				{
       			# Problem.
	        		$this->log("Asterisk Manager header not received.");
				$return=False;
				}
			}

      		# Login
		if($return)
			{
	      		$res=$this->send_request('login', array('Username'=>$username, 'Secret'=>$secret));
      			if($res['Response'] != 'Success')
      				{
        			$this->log('Failed to login using ['.$username.'] for username and ['.$secret.'] for the password.');
	        		fclose($this->socket);
       	 		$return=False; 
      				}
			}

		# Timeout
		if($return) stream_set_timeout($this->socket,30); 

		# Error message
		if(!$return and $display)
			{
			$output = "<AastraIPPhoneTextScreen>\n";
			$output .= "<Title>Configuration error</Title>\n";
			$output .= "<Text>The application cannot connect to the Asterisk Manager. Please contact your administrator.</Text>\n";
			$output .= "</AastraIPPhoneTextScreen>\n";
			header('Content-Type: text/xml');
			header('Content-Length: '.strlen($output));
			echo $output;
			exit;
			}

		# Return result
      		return($return);
    	}

    	function disconnect()
    		{
      		$this->logoff();
      		fclose($this->socket);
    		}

    	function Command($command, $actionid=NULL)
    	{
      		$parameters = array('Command'=>$command);
      		if($actionid) $parameters['ActionID'] = $actionid;
      		return $this->send_request('Command', $parameters);
    	}

    	function Logoff()
    	{
      		return $this->send_request('Logoff');
    	}

	function ExtensionState($exten,$context,$actionid=NULL)
    	{
      		$parameters=array('Exten'=>$exten,'Context'=>$context);
      		if($actionid) $parameters['ActionID'] = $actionid;
      		return($this->send_request('ExtensionState', $parameters));
    	}


    	function Originate2($channel,
                       $exten=NULL, $context=NULL, $priority=NULL,
                       $application=NULL, $data=NULL,
                       $timeout=NULL, $callerid=NULL, $variable=NULL, $account=NULL, $async=NULL, $actionid=NULL)
    	{
      		$parameters = array('Channel'=>$channel);
      		if($exten) $parameters['Exten'] = $exten;
      		if($context) $parameters['Context'] = $context;
      		if($priority) $parameters['Priority'] = $priority;
      		if($application) $parameters['Application'] = $application;
      		if($data) $parameters['Data'] = $data;
      		if($timeout) $parameters['Timeout'] = $timeout;
      		if($callerid) $parameters['CallerID'] = $callerid;
      		if($variable) $parameters['Variable'] = $variable;
      		if($account) $parameters['Account'] = $account;
      		if(!is_null($async)) $parameters['Async'] = ($async) ? 'true' : 'false';
      		if($actionid) $parameters['ActionID']=$actionid;
	      	return($this->send_request('Originate',$parameters));
    	}	

	function Originate($channel, $exten, $context, $priority, $timeout, $callerid, $variable, $account, $application, $data)
    	{
      		$parameters = array();
      		if($channel) $parameters['Channel'] = $channel;
      		if($exten) $parameters['Exten'] = $exten;
      		if($context) $parameters['Context'] = $context;
      		if($priority) $parameters['Priority'] = $priority;
      		if($timeout) $parameters['Timeout'] = $timeout;
      		if($callerid) $parameters['CallerID'] = $callerid;
      		if($variable) $parameters['Variable'] = $variable;
      		if($account) $parameters['Account'] = $account;
      		if($application) $parameters['Application'] = $application;
      		if($data) $parameters['Data'] = $data;
      		return($this->send_request('Originate', $parameters));
    	}	



	function database_show($family='') 
	{
		$r=$this->command('database show '.$family);
		$data=explode("\n",$r['data']);
		$db=array();
		array_shift($data);
		foreach($data as $line) 
			{
			$temp = explode(":",$line);
			if (trim($temp[0]) != '') 
				{
				$temp[1] = isset($temp[1])?$temp[1]:null;
				$db[ trim($temp[0]) ] = trim($temp[1]);
				}
			}
		return $db;
	}
	
	function database_put($family, $key, $value) 
	{
		$r = $this->command("database put ".str_replace(" ","/",$family)." ".str_replace(" ","/",$key)." ".$value);
		return (bool)strstr($r["data"], "success");
	}
	
	function database_get($family,$key) 
	{
		$r=$this->command('database get '.str_replace(' ','/',$family).' '.str_replace(' ','/',$key));
		$data=strpos($r['data'],'Value:');
		if($data!==false) return trim(substr($r['data'],6+$data));
		else return false;
	}
	
	function database_del($family, $key) 
	{
		$r = $this->command("database del ".str_replace(" ","/",$family)." ".str_replace(" ","/",$key));
		return (bool)strstr($r["data"], "removed");
	}

	function QueueAdd($queue, $interface, $penalty=0)
    	{
      		$parameters = array('Queue'=>$queue, 'Interface'=>$interface);
      		if($penalty) $parameters['Penalty'] = $penalty;
      		return $this->send_request('QueueAdd', $parameters);
    	}

    	function QueueRemove($queue, $interface)
    	{
      	return $this->send_request('QueueRemove', array('Queue'=>$queue, 'Interface'=>$interface));
    	}

	function QueuePause($queue, $interface, $pause)
    	{
      	return $this->send_request('QueuePause', array('Queue'=>$queue, 'Interface'=>$interface,'Paused'=>$pause));
    	}

    	function Queues()
    	{
      	return $this->send_request('Queues');
    	}

    	function QueueStatus($actionid=NULL)
    	{
      	if($actionid) return $this->send_request('QueueStatus', array('ActionID'=>$actionid));
      	else return $this->send_request('QueueStatus');
    	}

	function ParkedCalls($actionid=NULL)
    	{
      	if($actionid) return $this->send_request('ParkedCalls', array('ActionID'=>$actionid));
      	else return $this->send_request('ParkedCalls');
    	}

	function Redirect($channel,$extension,$priority,$context)
    	{
      	return $this->send_request('Redirect', array('Channel'=>$channel,'Exten'=>$extension,'Priority'=>$priority,'Context'=>$context));
    	}

	function Reload($module='') 
	{
		$this->command('reload '.$module);
	}

	function UserEvent($event,$data)
	{
	return $this->send_request('UserEvent', array('Privilege'=>'user,all','UserEvent'=>$event,'Data'=>$data));
	}
}
?>
