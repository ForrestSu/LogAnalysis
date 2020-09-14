<?php require_once('./include/public.inc.php');
	if (isset($_GET['lang'])){
	    $newlang=strval($_GET['lang']);
	    if(strlen($newlang)<3){
	       $_SESSION['OJ_LANG']=$newlang;
	    }
		echo "<script language='javascript'>\n";
		echo "history.go(-1);\n";
		echo "</script>";
	}
	
?>