<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>webSocket Test</title>
    <link href="fdflex.min.css" rel="stylesheet" type="text/css">
    <script src="brutal_1711.js"></script>
    <style>
      .container{
        width:80%;
        margin:auto;
      }
    </style>
  </head>

  <body>
    <div class="container">
      <div class="row">
        <div class="column small-12" id="message-info" style="font-size: 185%">
          
        </div>
      </div>
    </div>

    <script>
      var msg_count = 0;
      var ws = new WebSocket("ws://<?php echo $_SERVER['SERVER_ADDR']; ?>:4567");
      ws.onopen = function() {
      };
      ws.onmessage = function (evt) {
          if (msg_count > 8) {
            brutal.autod('#message-info','');
            msg_count = 0;
          }
          msg_count += 1;
          brutal.autod('#message-info','<p>'+evt.data+'</p>',true);
          
      };
      
      
    </script>
  </body>
</html>
