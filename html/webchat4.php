<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>WSChat Test</title>
    <link href="fdflex.min.css" rel="stylesheet" type="text/css">
    <script src="brutal_1711.js"></script>
    <style>
      .container{
        width:100%;
        margin:auto;
        overflow-x:hidden;
      }
      .send-msg-block{
        z-index:1024;
        position:fixed;
        width:100%;
        height:15%;
        background-color: #f5f5dc;
        bottom:0rem;
      }
      .msg-list-block{
        border-right:.1rem solid #dd5c5c;
        width:100%;
        min-height: 20rem;
        max-height: 23rem;
        overflow-y: auto;
        border: .1rem solid #ff6347 ;
      }
    </style>
  </head>

  <body>
    <div class="container" id="main-block">
      <div class="row">
        <div class="column small-12 medium-12 large-9" style="overflow:hidden;">
          <div class="msg-list-block" id="msg-list" style="font-size:135%;">

          </div>
        </div>
      </div>

      <div class="send-msg-block">
        <div class="row" style="margin-top:1.2rem;">
          <div class="column small-12 medium-12 large-9">
            <form onsubmit="return false;">
              <div class="input-group">
                  <input type="text" value="" id="my-message" class="input-group-field">
                <div class="input-group-button">
                  <input type="submit" class="button hollow alert" value="send" onsubmit="wsc_send_msg()" onclick="wsc_send_msg()">
                </div>
              </div>
            </form>
          </div>
          <div class="column large-3 show-for-large-only">
            
          </div>
        </div>
      </div>
    </div>

    <script>
      var msg_count = 1;
      var conn = {

      };
      var ws = new WebSocket("ws://<?php echo $_SERVER['SERVER_ADDR']; ?>:9876");

      ws.onopen = function() {
      };

      ws.onclose = function() {
        //clear connect
      }

      ws.onmessage = function (evt) {
          if (msg_count > 1000) {
            brutal.autonode('#msg-list').removeChild(brutal.autonode('#msg-list','first'));
          }
          var json_msg = JSON.parse(evt.data);
          var msg = '';
          if (json_msg.msg_source!==undefined) {
            brutal.autod('#msg-list',`<div style="text-align:center;">
                  <p style="font-size:169%;">
                  ${json_msg.msg}</p></div>`,
                  true
                );
          } else {
            if (json_msg.msg == '--//--clear') {
              brutal.autod('#msg-list','');
              msg_count=0;
            }
            else if (json_msg.msg == '--//--kill') {
              //window.location.reload(true);
              var main_html = brutal.autod('#main-block');
              brutal.autod('#main-block', `<div style="text-align:center;">
                    <p style="font-size:280%;">--fobid--</p></div>`);
              setTimeout(() => {
                brutal.autod('#main-block',main_html);
              }, 1000);
            }
            else {
              msg_count += 1;
              msg = `${json_msg.from_id}:<br>&nbsp;&nbsp;&nbsp;&nbsp;
                      ${json_msg.msg}<br>`;
              brutal.autod('#msg-list',msg,true);
              brutal.autonode('#msg-list').scrollTop = 
              brutal.autonode('#msg-list').scrollHeight;
            }
          }
          
          
      };
      
      function wsc_send_msg(){
        var msg = brutal.autod('#my-message');
        if(msg==''){
          return ;
        }
        ws.send( JSON.stringify( {"msg":msg} ) );
        brutal.autod('#my-message','');
        brutal.autod('#msg-list','<p style="text-align:right;">'+msg+'</p>',true);
        brutal.autonode('#msg-list').scrollTop = 
            brutal.autonode('#msg-list').scrollHeight;
      }      
    </script>
  </body>
</html>

