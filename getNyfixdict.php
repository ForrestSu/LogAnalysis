<?php
  
  $myfile = fopen("map.ini", "r") or die("Unable to open file!");
   $dict=array();
// 输出单行直到 end-of-file
	while(true) {
		 $oneline=fgets($myfile);
		 if(feof($myfile)) break;
		 $chinese='';
		 $english='';
		 $myarray = explode(' ', $oneline);
		 $key=$myarray[0];
		 $content=$myarray[1];

		 $pos=strpos($content,'（');
		if($pos!== false){ 
	       $chinese=substr($content,0,$pos);
	       $posrig=strpos($content,'）');
	       $english=substr($content,$pos+3,$posrig-$pos-3);
		}
		$value=$english;
		if(array_key_exists($key,$dict) and ($value<>$dict[$key]))
	    {
		   	 echo '出现冲突==>before=['.$dict[$key].']now=['.$value.'] 当前节点=['.$key.']'."<br/>";
		}else
		{
		   	 $dict[$key]=$value;
		}
	}
    ksort($dict);
	foreach($dict as $key=>$value)
	{  
		 if(strlen($value)>1)
		  echo $key." => '".$value."' ,<br/>";
	}

    fclose($myfile);
  
  
   /* $map=parse_ini_file("map.ini",true);
    $dict=array();
    $sim =array();
    //print_r($map);
    foreach($map as $keys=>$values){
		foreach($values as $key=>$value){
		   if(array_key_exists($key,$dict) and ($value<>$dict[$key]))
		   {
		   	 echo '出现冲突==>before=['.$dict[$key].']now=['.$value.'] 当前节点=['.$keys.']'."<br/>";
		   }else
		   {
		   	 $dict[$key]=$value;
		   }
		}
	}
	echo "<br/>__________________________<br/>";
	ksort($dict);
	foreach($dict as $key=>$value)
	{
		if(array_key_exists($value,$sim))
			echo '出现冲突==>key=['.$key.']oldval=['.$sim[$value].']newvalue=['.$value."]<br/>";
		else
			$sim[$value]=$key;
	} 
	echo "<br/>__________________________<br/>";
	foreach($sim as $key=>$value)
	{
		echo $key.'==>'.$value."<br/>";
	}*/

?>