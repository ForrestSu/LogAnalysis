<?php  
/**
 * [code description]:Log Analisys,supported fix and common log, enjoy it. 
 * @type  : PHP 
 * @author: sunquan
 * @date  : 2015-12-19 22:54:12
 */
header("Content-Type: text/json;charset=utf-8");
require_once('./include/const_info.php');

define('SPLIT_CHAR',chr(1));//定义一个ASCII常量SOH
class Message{
    public $func_no      = '' ; //功能号
    public $message_time = '0'; //消息时间
    public $message_type =  0 ; //消息类型 0-收到的packet 1-发送出去的packet 2-收到的fix,3-发出去的fix
    public $data         = null;//消息内容
}
/**
 * Finally we will return a JsonObject of ObjSet. 
 * It contains one or a lot of Message object, Maybe contains null in case of error. 
 */
class ObjSet{
    public $data         = null; // save message array
    public $counts       = 0;    // length of message array 
}
 //global  variable, for filter FUNCNO
  $FuncNo='';
  $FiltStr=''; 
/////////////////////主函数////////////////////
if(isset($_POST['logs']) and isset($_POST['filtstr']) ){
    $logs=$_POST['logs'];
    $str=trim($_POST['filtstr']);
    if(substr($str, -2)=='-f') $FuncNo=trim(substr($str,0,strlen($str)-2)) ;
    else $FiltStr=$str;//内容检索
    //限制数据长度10W,1秒内可以解析并加载完毕
    if(strlen($logs)>100000 or strlen($str)>30){
        echo json_encode(PackObjSetErr(-1,"[post]：Data too Long!len=".strlen($logs)));
        exit(0);
    } 
    echo json_encode( AnalisysLog($logs) );
}
else{
    echo json_encode(PackObjSetErr(-1,"缺少参数logpath"));
} 
exit(0);
////////////////end main//////////////
    /*返回错误信息*/
    function PackObjSetErr($cnt,$error_info){
         $ret =new ObjSet();
         $ret->counts = $cnt;
         $ret->data = $error_info;
         return $ret;
    }
    function PackMsgErr($msg_type,$error_info){
         $ret =new Message();
         $ret->message_type = $msg_type;
         $ret->data = $error_info;
         return $ret;
    }
    function AnalisysLog($content){
        $ret =new ObjSet();
        $objs =array();
        $cnt = 0; 
        //start deal log data...
        $lines = explode(chr(10),$content);
        foreach($lines as $oneline){
           $oneline= trim($oneline);//去除首尾的"\0" "\t" "\n" "\r" "\x0B" " "
           if(strlen($oneline) < 2 ) continue;//过滤空行
           //distinct fix_log or tran_log 
           $pos = strpos($oneline, FIX_FLAG);
           if($pos !== false)  //if fix_log
             $msg = my_unpack($oneline,'fix_log',FIX_LOG0,FIX_LOG1,$GLOBALS['FiltStr']);
           else
             $msg = my_unpack($oneline,'tran_log',LOG0,LOG1,$GLOBALS['FiltStr']);
           if($msg->data !=null)
            $objs[$cnt++]=$msg;
        }//end deal
        if($cnt>0)
        {
            $ret->data=$objs;
            $ret->counts=$cnt;
        }
        return $ret;
    }
     
    function my_unpack($oneline,$func,$in,$out,$filter){
        // 如果是入参
        if(strpos($oneline,$in) !== false){  
            return $func($oneline,0,$in,$filter);                   
        }// 如果是出参
        else if(strpos($oneline,$out) !== false){
             return $func($oneline,1,$out,$filter);           
        }// 不支持的数据包
        else{
             return PackMsgErr(-1,'[errorinfo]=不是标准的Packet或FIX数据包=>'.$oneline);
        }
    }
    //1 解析一行 fix 日志
    function fix_log($oneline,$message_type,$tag,$filter){ 
        $obj=new Message();
        //设置消息类型
        $obj->message_type=$message_type+2;
        //定义数组
        $arr01 = array();
        $arrlog = array();
        $ResultSet = array();
        $GLOBAL_BUSSI_DICT= $GLOBALS['fixbuss'];//全局业务标志
        $cnt = 0;

        $arr01 = explode($tag, $oneline);
        $logbody = trim($arr01[1],' =[]:');// filter character in ' =[]'
        //如果指定了过滤字符，当前数据包全文过滤字符
        if((!empty($filter)) and (strpos($logbody,$filter)===false)) return $obj; 
        //如果不是空数据包
        if(strlen($logbody)>1 and (strpos($logbody,SPLIT_CHAR) !== false) ){
            $arrlog = explode(SPLIT_CHAR,$logbody);
            foreach($arrlog as $one){
                $pos=strpos($one,'=');
                if($pos!==false){
                    $tmpkey=substr($one,0,$pos);
                    if($tmpkey<>''){
                      //need translate Key to value, at first check key must exsist!
                       if($GLOBALS['NeedTranslate'] and array_key_exists($tmpkey,$GLOBAL_BUSSI_DICT)) 
                          $tmpkey = $GLOBAL_BUSSI_DICT[$tmpkey].'('.$tmpkey.')';
                       $ResultSet[$tmpkey] = substr($one,$pos+1);
                    }  
                }       
            }
        }
        if(count($ResultSet)>0)
        {  
            $tmpno='';
            if(array_key_exists('1180',$ResultSet)) $tmpno = $ResultSet['1180'];
           //如果当前会话的func_no in(传入的FuncNo集合)，此数据包需要返回
            if(empty($GLOBALS['FuncNo']) or (stripos($GLOBALS['FuncNo'],$tmpno) !== false))
            {   //此数据包有功能号,则翻译 
                if(!empty($tmpno)){
                    $GLOBAL_BUSSI_DICT= $GLOBALS['fixdict'];
                    if(array_key_exists($tmpno,$GLOBAL_BUSSI_DICT))
                        $obj->func_no = $GLOBAL_BUSSI_DICT[$tmpno].'('.($tmpno).')';
                    else $obj->func_no = $tmpno;
                }
                $obj->data=$ResultSet; 
            }   
        }
        return $obj;
    }
    //2 解析通用日志 
    function tran_log($oneline,$message_type,$tag,$filter){
        $obj=new Message();
        //设置消息类型
        $obj->message_type=$message_type;
        //定义数组
        $arr01 = array();
        $arrlog = array();
        $ResultOne=array();
        $ResultSet=array();
        $cnt = 0;
        $arr01 = explode($tag, $oneline);
        $pos=strpos($arr01[0],'No:');
        if($pos!==false)
        {
           $obj->func_no=substr($arr01[0],$pos+3,4);
        }//如果需要按功能号过滤，且当前数据包的功能号不在过滤列表，直接返回
        if( (!empty($GLOBALS['FuncNo'])) and (stripos($GLOBALS['FuncNo'],$obj->func_no)===false) ) 
            return  $obj; 
        $logbody = trim($arr01[1],' =[]:');// filter character ' =[]' head or rear
        //如果指定了过滤字符，当前数据包全文过滤字符
        if( (!empty($filter)) and (strpos($logbody,$filter)===false) ) return $obj;  
        //如果不是空数据包
        if(strlen($logbody)>1 and (strpos($logbody,SPLIT_CHAR) !== false) )
        {
            $arrlog = explode(SPLIT_CHAR,$logbody);
            $row = (int)$arrlog[1]; //row size
            $col = (int)$arrlog[0]; //col size
            if($row>0)//filter 0 rows log data 
            for($i=0; $i<=$row; $i++) {
               // if($i==0){}这里可以指定表头显示的先后顺序
               for($j=0; $j < $col; $j++){
                   $ResultOne[$j] = $arrlog[ $i * $col + $j + 2];
                   //if read from logfile:  //iconv('gb2312','utf-8',$arrlog[ $i * $col + $j + 2]);
               }
               $ResultSet[$cnt++]= $ResultOne;
               unset($ResultOne);//clear $ResultOne 
            }
        }
        if($cnt>0) $obj->data=$ResultSet;
        return $obj;    
    }
?>