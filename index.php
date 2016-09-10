<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8">
    <title>Log Analysis 1.2</title>
    <link rel=stylesheet href='files/style.css' type='text/css'>
    <link rel="shortcut icon" href="files/logo.ico">
    <SCRIPT type="text/javascript" src="jquery/jquery-1.11.2.min.js"></SCRIPT> 
    <script type="text/javascript" language = "javascript" >
    function getJSON(){
      $.ajax({
                url: "./analisys.php", 
                type: "POST",
                data: {filtstr:document.getElementById("search").value,
                       logs:document.getElementById("input_text").value,
                       repack:document.getElementById("repack").checked},
                datatype: "json",
                success: function (resp) {
                    showtable(resp);
                }
        }); 
    /*    $.getJSON('./analisys.php',{funcno:document.getElementById("search").value,logs:document.getElementById("input_text").value },function(resp){   
            showtable(resp);
       }); */
    } 
    function showtable(resp)
    {
        if(resp.counts == -1){
           document.getElementById("msg").innerHTML="解析出错：<font color='red'>"+resp.data+"</font>";
           return 0;
        }
        document.getElementById("msg").innerHTML="<font color='blue'> "+resp.counts+"</font> records  "+"<font color='blue'>"+resp.cost_time+"</font>s";
        //清空之前解析的内容
        document.getElementById("user_rec").innerHTML="";
        var retset = new Array();
        retset =resp.data; 
        if(retset ===null) return 0;
        for(var n=0;n<retset.length;n++)
        {
            var jsonObj={};
             jsonObj=retset[n];
            var message_type = jsonObj.message_type;
            if(message_type == -1)
            { 
                $("#user_rec").append("<tr><td>error</td></tr><tr><td title=\""+jsonObj.data+"\">"+jsonObj.data+"</td></tr>");
                continue;
            }
            //如果Message数据包为空，直接退出
            if( (!jsonObj.data)||(jsonObj.data=== null)) continue;
            // 显示功能号
            if(jsonObj.func_no != '')
                $("#user_rec").append("<tr><td title=\""+jsonObj.func_no+"\">"+jsonObj.func_no+"</td></tr>");
            var class_type=(message_type%2==0)?"inpararow":"outpararow";
            var tds="th";
            if(message_type < 2)//1 如果是转换机日志
            {
                var data=new Array();
                data=jsonObj.data;
                for(var i=0;i<data.length;i++){
                    var one=data[i];
                    var oBuffer = "<tr class=\""+class_type+"\">";
                    oBuffer+="<"+tds+">"+(tds=="th"?"Time":jsonObj.message_time)+"</"+tds+">";
                    for(var j=0;j<one.length;j++){
                       oBuffer += ("<"+tds + " title=\""+one[j]+"\">"+ one[j] + "</"+tds+">");
                    }
                     tds="td";
                     oBuffer=oBuffer+"</tr>";
                    $("#user_rec").append(oBuffer); 
                  // "<td><a href=\"problem.php?id="+ data[i][0]+"\">"+data[i][1] +"</a></td>";
               }
            }else //2 五版日志
            {
                var data={};
                data=jsonObj.data;
                var oBufferKey = "<tr class=\""+class_type+"\">";
                var oBufferVal = "<tr class=\""+class_type+"\">";
                for(var key in data)
                {
                    oBufferKey = oBufferKey+"<th title=\""+ key+"\">" + key + "</th>";
                    oBufferVal = oBufferVal+"<td title=\""+ data[key]+"\">" + data[key] + "</td>";
                }
                oBufferKey = oBufferKey+ "</tr>";
                oBufferVal = oBufferVal+ "</tr>";
                $("#user_rec").append(oBufferKey+oBufferVal); 
            }
        }
    }
    </script>
</head>
<body>
 <div class="container">
 
   <div id="markup">
   <div id="profile" >
       <a href="./setlang.php?lang=en">English</a>
       <a href="./setlang.php?lang=cn">Chinese</a>
   </div>
   <article id="content" class="markdown-body">
       <h3>Log Analysis v1.2</h3>
       <textarea style="width:100%" cols="30" rows="4" id="input_text" name="input_text" maxlength="300000"></textarea> <br/>
       Filter&nbsp;<input style="width:20%" oninput="javascript:getJSON();" title="Input keywords likes 010(filter FUNCNO like 1003,1004 -f)" type="text" id="search" name="search" class="input-small search-query">
        <font  id="msg"> </font>  
       <input type="checkbox" id="repack">FilterNoSupportLogs</input> 
       <input type="button" style="float:right" class="btn btn-info" id="btn" value="Resolves" onClick="javascript:getJSON();"/>
       
       <TABLE>
          <tbody id="user_rec">
          </tbody>
       </TABLE>
  </article>
  </div>
</div>
</body>
</html>