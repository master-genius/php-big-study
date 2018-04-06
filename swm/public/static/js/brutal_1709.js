var brutal = {
    html:function(cid,data,attach){
        var dq = document.querySelector(cid);
        if(dq){
            if (data===undefined) {
                return dq.innerHTML;
            } else {
                if (attach==true) {
                    dq.innerHTML += data;
                } else {
                    dq.innerHTML = data;
                }
            }
        } else {
            return '';
        }
    },
    val:function(cid,data){
        var dq = document.querySelector(cid);
        if(dq&&dq.value!==undefined){
            if (data===undefined) {
                return dq.value;
            } else {
                dq.value = data;
            }
        } else {
            return '';
        }
    },
    autod:function(cid,data,attach){
        var dq = document.querySelector(cid);
        if(dq&&dq.value!==undefined){
            if (data===undefined) {
                return dq.value;
            } else {
                dq.value = data;
            }
        } else if(dq) {
            if (data===undefined) {
                return dq.innerHTML;
            } else {
                if (attach==true) {
                    dq.innerHTML += data;
                } else {
                    dq.innerHTML = data;
                }
            }
        } else {
            return '';
        }
    },
    autonode:function(cid,type){
        var dq = document.querySelector(cid);
        if (!dq) {
            return document;
        }
        if (type=='childs') {
            return dq.childNodes;
        } else if (type=='first') {
            return dq.firstChild;
        } else if (type=='last') {
            return dq.lastChild;
        } else {
            return dq;
        }
    },
    classname:function(cid,data){
        var dq = document.querySelector(cid);
        if(dq){
            if (data===undefined) {
                return dq.className;
            } else {
                dq.className = data;
            }
        } else {
            return '';
        }
    },
    checked:function(cid,handle,type){
        var dq = document.querySelectorAll(cid);
        if (!dq) {
            return false;
        }
        if(handle===undefined || handle===false){
            handle='bool';
            if(dq.length !==undefined) {
                handle='list';
            }
        }

        if(handle=='bool') {
            if(dq.length){
                return (dq[0].checked?1:0);
            }
            return (dq.checked?1:0);
        } else if (handle=='value') {
            if(dq.length && dq.length>0){
                for(var i=0;i<dq.length;i++){
                    if(dq[i].checked){
                        return dq[i].value;
                    }
                }
            }
            return dq.value;
        } else if (handle=='list') {
            if (type===undefined) {
                type = 'check';
            }

            var check_arr = [];
            var uncheck_arr = [];
            for(var i=0;i<dq.length;i++){
                if(dq[i].checked){
                    check_arr.push(dq[i].value);
                } else {
                    uncheck_arr.push(dq[i].value);
                }
            }

            return (type=='uncheck'?uncheck_arr:check_arr);
        } else if (handle=='set' || handle=='unset') {
            var chbool = (handle=='set'?true:false);
            for(var i=0;i<dq.length;i++){
                dq[i].checked = chbool;
            }
        } else {
            return null;
        }

    },
    selected:function(cid,handle,val){
        var dq = document.querySelector(cid);
        if (!dq) {
            return false;
        }
        if(dq.tagName!=='SELECT'){
            return ;
        }
        if(handle===undefined || handle===false){
            handle='value';
        }
        if (handle=='value' || handle=='html') {
            if(dq.options.length>0){
                if(handle=='html'){
                    return dq[dq.selectedIndex].innerHTML;
                } else {
                    return dq[dq.selectedIndex].value;
                }
            } else {
                return null;
            }
        } else if (handle=='set') {
            if(dq.tagName)
            for(var i=0;i<dq.options.length;i++){
                if(dq.options[i].value == val){
                    dq.options[i].selected = true;
                    break;
                }
            }
        } else {
            return false;
        }
    },
    addevent:function(cid,ev,callback){
        var dq = document.querySelector(cid);
        if(!dq){
            return false;
        }
        if (typeof callback !== 'function') {
            return false;
        }
        dq.addEventListener(ev,callback);
    },
    rmevent:function(cid,ev,callback){
        var dq = document.querySelector(cid);
        if(!dq){
            return ;
        }
        if (typeof callback !== 'function') {
            return ;
        }
        dq.removeEventListener(ev,callback);
    },
    jsontodata:function(jsd){
        var data = '';
        for(var k in jsd){
            if(typeof jsd[k] == 'object'){
                jsd[k] = jsd[k].toString();
            }
            data += k+'='+encodeURIComponent(jsd[k])+'&';
        }
        return data.substring(0,data.length-1);
    },
    domattr:function(cid,attr,val){
        var dq = document.querySelector(cid);
        if(!dq){
            return null;
        }
        if(dq[attr]!==undefined) {
            if(val===undefined){
                return dq[attr];
            }
            dq[attr] = val;
        } else {
            return null;
        }
    },
    areq:{
        ajax:function(xd){
            if(xd.async === undefined){
                xd.async = true;
            }
            if(xd.error === undefined){
                xd.error = function(xr){xr=null;};
            }
            if(xd.success === undefined){
                xd.success = function(xr){xr=null;};
            }
            if(xd.datatype == 'json'){
                xd.data = JSON.stringify(xd.data);
            }
            var retdata = '';
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function(){
                if(xhr.readyState==4){
                    if(xd.retformat == 'json'){
                        retdata = JSON.parse(xhr.responseText);
                    } else {
                        retdata = xhr.responseText;
                    }
                    
                    if(xhr.status==200){
                        xd.success(retdata);
                    } else if(xhr.status==404) {
                        xd.error(retdata);
                    }
                    retdata=null;
                    xhr.responseText=null;
                }
            }
            if( xd.type == 'POST' || xd.type == 'post'){
                xhr.open("POST",xd.url,xd.async);
                xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
                xhr.send(xd.data);
            } else if( xd.type == 'GET' || xd.type == 'get') {
                xhr.open("GET",xd.url,xd.async);
                xhr.send();
            }
        },
        get:function(gxd){
            brutal.areq.ajax({
                url:gxd.url,
                type:'get',
                data:'',
                datatype:gxd.datatype,
                success:gxd.success,
                error:gxd.error,
                async:true,
                retformat:'json',
            });
        },
        post:function(pxd){
            brutal.areq.ajax({
                url:pxd.url,
                type:'post',
                data:pxd.data,
                datatype:pxd.datatype,
                success:pxd.success,
                error:pxd.error,
                async:true,
                retformat:'json',
            });
        },
        upload_one:function(file,jup){
            if(jup.success === undefined){
                jup.success = function(){};
            }
            if(jup.error === undefined){
                jup.error = function(){};
            }
            if(jup.retformat === undefined){
                jup.retformat = 'json';
            }
            var retdata = '';
            var frd = new FileReader();
            frd.onload = function(){
                var xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function(){
                    if(xhr.readyState==4){
                        if(jup.retformat == 'json'){retdata = JSON.parse(xhr.responseText);}
                        else{retdata = xhr.responseText;}
                        if(xhr.status==200){
                            jup.success(retdata);
                        }
                        if(xhr.status==404){
                            jup.error(retdata);
                        }
                    }
                }
                var fd = new FormData();
                xhr.open("POST",jup.url,true);
                xhr.setRequestHeader("content-length",file.size);
                xhr.overrideMimeType("application/octet-stream");
                fd.append(jup.upload_name,file);
                xhr.send(fd);
            }
            frd.readAsBinaryString(file);
        },
        upload:function (file,jup){
            if(file.files.length>0){
                brutal.areq.upload_one(file.files[0],jup);
            }
        }

    }

};
