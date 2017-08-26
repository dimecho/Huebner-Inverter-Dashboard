<?php
    set_time_limit(30);
	
	$com = "/dev/ttyUSB0";
    /*
	exec("stty -f " .$com);
	exec("stty -f " .$com. " 115200");
	exec("stty -f " .$com. " -parenb");
	exec("stty -f " .$com. " cs8");
	exec("stty -f " .$com. " cstopb");
	exec("stty -f " .$com. " clocal -crtscts -ixon -ixoff");
    */
    exec("screen " .$com. " 115200");
	
	if(isset($_GET["get"]))
	{
		if(strpos($_GET["get"],",") !== false) //Multi-value support
		{
			$split = explode(",",$_GET["get"]);
			for ($x = 0; $x < count($split); $x++)
				echo readSerial("get " .$split[$x]). "\n";
		}else{
			echo readSerial("get " .$_GET["get"]);
		}
	}
	else if(isset($_GET["command"]))
	{
		echo readSerial($_GET["command"]);
	}
	else if(isset($_GET["average"]))
	{
		echo calculateAverage(readArray($_GET["average"],6));
	}
	else if(isset($_GET["median"]))
	{
		echo calculateMedian(readArray($_GET["median"],3));
	}
    
	function readArray($cmd,$n)
    {
		$cmd = "get " .urldecode($cmd). "\r";
		$read = "";
        $arr = array();
		
		$uart = fopen(serialDevice(), "rb+"); //Read & Write
        stream_set_blocking($uart, 1); //O_NONBLOCK
        stream_set_timeout($uart, 8);
        
		fwrite($uart, $cmd);
		while($read .= fread($uart, 1))
		{
			if(strpos($read,$cmd) !== false) //Reached end of echo
			{
				for ($x = 0; $x <= $n; $x++)
				{
                    $read = "";

					fwrite($uart, "!");
					fread($uart, 1); //Remove echo
					
					while($read .= fread($uart, 1))
						if(strpos($read,"\n") !== false)
							break;

                    array_push($arr, (float)$read);
				}
				break;
			}
		}
		fclose($uart);

		return $arr;
    }
	
    function readSerial($cmd)
    {
		$cmd = urldecode($cmd). "\r";
        $read = "";

		$uart = fopen(serialDevice(), "rb+"); //Read & Write
        stream_set_blocking($uart, 1); //O_NONBLOCK
        stream_set_timeout($uart, 8);
        
		fwrite($uart, $cmd);
        
        while($read .= fread($uart, 1)) //stream_get_contents($uart)
        {
            if(strpos($read,$cmd) !== false) //Reached end of echo
            {
                //Continue reading
                $read = "";
                if($cmd === "json\r"){
                    do {
                        $read .= fread($uart, 1);
                        json_decode($read);
                    } while (json_last_error() != JSON_ERROR_NONE);
                }else if($cmd === "all\r"){
                    while($read.= fread($uart, 1))
                        if(strpos($read,"tm_meas") !== false)
                            break;
                    $read .= "\t\t59652322";
                //OSX has trouble reading sequencial command
                /*
                }else if(strpos($cmd,",") !== false){ //Multi-value support
                    $split = explode(",",$cmd);
                    for ($x = 0; $x < count($split); $x++) {
                        $r = "";
                        while($r .= fread($uart, 1))
                            if(strpos($r,"\n") !== false)
                                break;
                        $read .= $r;
                    }
                */
                //TODO: command=errors
                }else{
                    while($read .= fread($uart, 1))
                        if(strpos($read,"\n") !== false)
                            break;
                }
                $read = rtrim($read ,"\r");
                $read = rtrim($read ,"\n");
                break;
            }
        }
        
		//$read = fgets($uart);
		//while(!feof($uart)){
			//$read .= stream_get_contents($fd, 1);
			//$read .= fgets($uart);
		//}
		fclose($uart);

        return $read;
    }

    function calculateMedian($arr)
    {
        $count = count($arr); // total numbers in array
        $middleval = floor(($count-1)/2); // find the middle value, or the lowest middle value

        if($count % 2) { // odd number, middle is the median
            $median = $arr[$middleval];
        } else { // even number, calculate avg of 2 medians
            $low = $arr[$middleval];
            $high = $arr[$middleval+1];
            $median = (($low+$high)/2);
        }
        return round($median,2);
    }

    function calculateAverage($arr)
    {
        $count = count($arr); // total numbers in array
        foreach ($arr as $value) {
            $total = $total + $value; // total value of array numbers
        }
        $average = ($total/$count); // get average value
        return round($average,2);
    }
?>