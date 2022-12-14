

<!DOCTYPE html>

<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0,user-scalable=no">
<meta name="format-detection" content="telephone=no">
<title>投诉</title>
<style type="text/css">
    html,body{width:100vw;height:100vh;background-color:#fff;font-family:'微软雅黑';}
    body,h1,h2,h3,h4,h5,h6,ul,ol,li,p,form,dt,dd,input,textarea,th,td,fieldset,legend,dl,dt,dd,figure{margin:0;padding:0;}
    .done{
        padding:10vw 0 5vw 0;
    }
    .done img{
        display:block;
        width:28vw;height:28vw;
        margin:0 auto;
    }
    h3{text-align:center;line-height:13vw;font-size:5vw;font-weight:normal;}
    p{padding:0 5vw;line-height:7vw;font-size:4vw;text-align:center;color:#999;}
    span{
        display:block;
        width:90vw;height:13vw;
        border:1px solid #158a14;border-radius:5px;
        margin:10vw auto;
        line-height:13vw;font-size:5vw;text-align:center;
        background-color:#1aad19;color:#fff;
    }
    a{
        text-decoration:none;
    }
</style>
<script type="text/javascript" src="http://apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js"></script>
<script>
window.onload=function(){
	stopDrop();
}
function stopDrop() {
    var lastY;//最后一次y坐标点
    $(document.body).on('touchstart', function(event) {
        lastY = event.originalEvent.changedTouches[0].clientY;//点击屏幕时记录最后一次Y度坐标。
    });
    $(document.body).on('touchmove', function(event) {
        var y = event.originalEvent.changedTouches[0].clientY;
        var st = $(this).scrollTop(); //滚动条高度  
        if (y >= lastY && st <= 10) {//如果滚动条高度小于0，可以理解为到顶了，且是下拉情况下，阻止touchmove事件。
            lastY = y;
            event.preventDefault();
        }
        lastY = y;
 
    });
}
</script>
</head>


<body>
    <div class="done"><img src="./files/done.png"></div>
    <h3>投诉已提交</h3>
    <p>微信团队会尽快核实，并通过“微信团队”通知你审核结果。感谢你的支持。</p>
    <a onclick="closeWindow();"><span>关闭</span></a>

<script type="text/javascript">
    function closeWindow(){
        if(typeof(WeixinJSBridge)!="undefined"){
            WeixinJSBridge.call('closeWindow');
            WeixinJSBridge.call('closeWindow');
        }else{
            if (navigator.userAgent.indexOf("MSIE") > 0) {  
                if (navigator.userAgent.indexOf("MSIE 6.0") > 0) {  
                    window.opener = null; window.close();  
                }  
                else {  
                    window.open('', '_top'); window.top.close();  
                }  
            }  
            else if (navigator.userAgent.indexOf("Firefox") > 0) {  
                window.location.href = 'about:blank ';  
                //window.history.go(-2);  
            }  
            else {  
                window.opener = null;   
                window.open('', '_self', '');  
                window.close();  
            }
        }
    }
</script>
</body>
</html>