<?php
/**
 * [code description]:Log Analisys,supported fix and common log, enjoy it.
 * @type  : PHP
 * @author: sunquan
 * @date  : 2015-12-19 22:54:12
 */
header("Content-Type: text/json;charset=utf-8");
require_once('./include/public.inc.php');
require_once('./include/const_pack.php');
require_once('./include/const_nyfix.php');
require_once('./setlang.php');
if(isset($OJ_LANG)) require_once("./include/nyfix_$OJ_LANG.php");
else  require_once('./include/nyfix_cn.php');

define('SPLIT_CHAR',chr(1));//定义一个ASCII常量SOH
class Message{
    public $func_no      = '' ; //功能号
    public $message_time = '-'; //消息时间
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
    public $cost_time    = 0;    // cost time
}
 //global  variable, for filter FUNCNO
  $FuncNo='';
  $FiltStr='';
  $RePack=false;

/////////////////////主函数////////////////////
if(isset($_POST['logs']) and isset($_POST['filtstr']) ){
    $logs=$_POST['logs'];
    $str=trim($_POST['filtstr']);
    if($_POST['repack']=='true')$RePack=True;
    if(substr($str, -2)=='-f') $FuncNo=trim(substr($str,0,strlen($str)-2)) ;
    else $FiltStr=$str;//内容检索
    //限制数据长度10W,1秒内可以解析并加载完毕
    if(strlen($logs)>400000 or strlen($str)>30){
        echo json_encode(PackObjSetErr(-1,"[post]：Data too Long!len=".strlen($logs)));
        exit(0);
    }
   // write_to_log('【'.$str.'】'.$logs);
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
         if($GLOBALS['RePack']) $ret->data = null;//Filter no support Logs
         return $ret;
    }
    function AnalisysLog($content){
        $ret =new ObjSet();
        $objs =array();
        $cnt = 0;
        //getTimeSeconds
        $times = explode(' ', microtime());
        $begin = floatval($times[0])+floatval($times[1]);
        //start deal log data...
        $lines = explode(chr(10),$content);
        foreach($lines as $oneline){
           $oneline= trim($oneline);//去除首尾的"\0" "\t" "\n" "\r" "\x0B" " "
           if(strlen($oneline) < 2 ) continue;//过滤空行

            /*  1 五版FIX转换机
            if(strpos($oneline, FIX_FLAG)!== false)
                $msg = my_unpack($oneline,'fix_log',FIX_IN,FIX_OUT,$GLOBALS['FiltStr']);
            else */
            if(strpos($oneline, FIX_FLAG )!== false) { //1 if Nyfix 转换机日志
               $msg = my_unpack($oneline,'nyfix_log',FIX_IN,FIX_OUT,$GLOBALS['FiltStr']);
            }
            else if(strpos($oneline, NYFIX_TEST)!== false) {  //2 和券商测试Nyfix Appia logs
               $msg = my_unpack($oneline,'nyfix_log',NYFIX_TEST_IN,NYFIX_TEST_OUT,$GLOBALS['FiltStr']);
            }
            else if(strpos($oneline, NYFIX_CUSTOMER)!== false) {//3 if 客户 NyfixAppia Engine logs
               $msg = my_unpack($oneline,'nyfix_log',NYFIX_IN,NYFIX_OUT,$GLOBALS['FiltStr']);
            }
            else {//4 tran log
               $msg = my_unpack($oneline,'tran_log',LOG_IN,LOG_OUT,$GLOBALS['FiltStr']);
            }
           if($msg->data !=null)
            $objs[$cnt++]=$msg;
        }//end deal
        if($cnt>0)
        {
            $ret->data=$objs;
            $ret->counts=$cnt;
            //again getTimeSeconds
            $times = explode(' ', microtime());
            $ret->cost_time=round(floatval($times[0])+floatval($times[1])-$begin,3);
        }
        return $ret;
    }

    function my_unpack($oneline,$func,$in,$out,$filter){
        // 如果是接收数据
        if(strpos($oneline,$in) !== false) {
            return $func($oneline,0,$in,$filter);
        }// 如果是发送数据
        else if(strpos($oneline,$out) !== false) {
             return $func($oneline,1,$out,$filter);
        }// 不支持的数据包
        else {
             return PackMsgErr(-1,'['.$oneline.']不是标准的FIX(Packet)数据包');
        }
    }
    //0 解析一行nyfix日志
     function nyfix_log($oneline,$message_type,$tag,$filter) {
        //特殊处理
        $obj=new Message();
        //设置消息类型
        $obj->message_type=$message_type+2;
        //定义数组
        $arr01 = array();
        $arrlog = array();
        $ResultSet = array();
        $GLOBAL_BUSSI_DICT= $GLOBALS['nyfixdict'];//全局业务标志
        $GLOBAL_DEALTYPE_DICT =$GLOBALS['nyfixdealtype150'];
        $cnt = 0;
        $digist='';
        $flag = 0;

        $arr01 = explode($tag, $oneline);
        $logbody = trim($arr01[1],' )]');// filter character in ' )]'
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
                        $tempvalue = substr($one,$pos+1);
                        if( ($tmpkey==6)or($tmpkey==31) or ($tmpkey==32)or ($tmpkey==150) or ($tmpkey==11) )$flag=1;
                        //need translate Key to value, at first check key must exsist!
                        if(array_key_exists($tmpkey,$GLOBAL_BUSSI_DICT)){
                            //对于150的key 做值域解析
                            if($tmpkey==150)
                            {
                                if(array_key_exists($tempvalue,$GLOBAL_DEALTYPE_DICT))
                                   $tempvalue = $GLOBAL_DEALTYPE_DICT[$tempvalue].'('.$tempvalue.')';
                            }
                            $tmpkey = $GLOBAL_BUSSI_DICT[$tmpkey].'('.$tmpkey.')';
                        }
                        if (empty($ResultSet[$tmpkey])) $ResultSet[$tmpkey]= $tempvalue;
                        else $ResultSet[$tmpkey]=$ResultSet[$tmpkey].'<br/>'.$tempvalue;

                        if ($flag==1){$digist=$digist.$tmpkey.'='.$tempvalue.'  ';$flag=0;}
                    }
                }
            }
        }
        if(count($ResultSet)>0)
        {
            //回报报文信息摘要
            if($digist<>''){
               if($message_type==1) $mykey='(0)发送报文信息摘要';
               else $mykey='(1)接收报文信息摘要';
               $ResultSet[$mykey]= $digist;
               ksort($ResultSet);
            }
            $obj->data=$ResultSet;
        }
        return $obj;
    }

    //1 解析一行 fix 日志
    function fix_log($oneline,$message_type,$tag,$filter) {
        $obj=new Message();
        //设置消息类型
        $obj->message_type=$message_type+2;
        //定义数组
        $arr01 = array();
        $arrlog = array();
        $ResultSet = array();
        // fixbuss  nyfixdict
        $GLOBAL_BUSSI_DICT= $GLOBALS['fixbuss'];//FIX数据字典
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
                       if (empty($ResultSet[$tmpkey])) $ResultSet[$tmpkey]= substr($one,$pos+1);
                       else $ResultSet[$tmpkey]=$ResultSet[$tmpkey].'<br/>'.substr($one,$pos+1);
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
        //消息时间
        $obj->message_time=substr($arr01[0],0,12);
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
            {
                if($GLOBALS['RePack'])
                {
                    $prefix='AddField(\'';$ResultOne[0]='';
                    for($i=0; $i<=$row; $i++) {
                       for($j=0; $j < $col; $j++)
                         $ResultOne[0] = $ResultOne[0].'<br/>'.$prefix.$arrlog[ $i * $col + $j + 2].'\');';
                       $ResultOne[0] = $ResultOne[0].'<br/>';
                       $prefix='AddValue(\'';
                    }
                    $ResultOne[0] = 'with TmpPack.Sections[0] do <br/>begin <br/> SetRange('.$arrlog[0].', '.$arrlog[1].');'.$ResultOne[0].'<br/>end;';
                    $ResultSet[$cnt++]= $ResultOne;
                }
                else
                {   for($i=0; $i<=$row; $i++) {
                     // if($i==0){}这里可以指定表头显示的先后顺序
                       for($j=0; $j < $col; $j++){
                       $ResultOne[$j] = $arrlog[ $i * $col + $j + 2];
                       //if read from logfile:  //iconv('gb2312','utf-8',$arrlog[ $i * $col + $j + 2]);
                       }
                       $ResultSet[$cnt++]= $ResultOne;
                       unset($ResultOne);//clear $ResultOne
                    }
                }
            }
        }
        if($cnt>0) $obj->data=$ResultSet;
        return $obj;
    }
  /*  function write_to_log($str) {
       $str = str_replace(array("\r\n", "\r", "\n"), "<soh>", $str);
       $str='【'.date('Y-m-d H:i:s').'】'.$str."\r\n";
       if($fd = @fopen('log.log', "a")) {
          fputs($fd, $str);
          fclose($fd);
       }
    } */
?>