var BASE;
(function()
 {
    var ajaxFormData;
    
    BASE = {
            confrim:confrimBox,// 仿confrim功能
            redirect:redirect,// 跳转
            getVisionSize:getVisionSize,// 取得浏览可视尺寸
            lockBg:lockBg,// 锁屏
            unLockBg:unLockBg,// 解屏
            loading:loading,// loading效果
            unLoading:unLoading,//unloading
            alert:alert,// 提示框
            delConfrim:delConfrim,
            ajaxForm:ajaxForm,// ajax表单提交
            parseId:parseId,// 解析ID
            queryTable:queryTable,
            checkboxSelect:checkboxSelect,// checkbox选择，全选/全否
            checkboxSelectRun:checkboxSelectRun,
            checkCheckboxIds:checkCheckboxIds,
            simplePager:simplePager,
            delayURL:delayURL,
            boxAdd:boxAdd,
            boxReduce:boxReduce
           };
    
    function simplePager(clickCallBackName,pageIndex,pageSize,total,pageId)
    {
        if(total <= pageSize) return;
        pageIndex = parseInt(pageIndex);
        pageIndex = pageIndex > 0 ? pageIndex : 1;
        var pageNum = Math.ceil(total/pageSize);
        
        if(pageIndex == -1)
        {
            pageIndex = pageNum;
        }
        
        var h1 = pageIndex == 1 ? '<span class="inactivePrev">«</span>' : '<span class="inactivePrev"><a href="javascript:void(0)" onclick="'+clickCallBackName+'('+(pageIndex-1)+')">«</a></span>';
        var h2 = '<a class="paginate">'+ pageIndex + '/' + pageNum + '</a>';
        var h3 = pageIndex >= pageNum ? '<span class="inactiveNext">»</span>' : '<span class="inactiveNext"><a href="javascript:void(0)" onclick="'+clickCallBackName+'('+(pageIndex+1)+')">»</a></span>';
        $(parseId(pageId)).html(h1+h2+h3);
    }

    /**
    *检查checkbox是否选中了Ids
    */
    function checkCheckboxIds(callback,domName)
    {
        var domName = domName || 'ids[]';

        var ids = document.getElementsByName(domName);
     
        var idlist = new Array();
        
        for(var i = 0;i<ids.length;i++)
        {
          if(ids[i].checked == true)
          {
            idlist.push(ids[i].value);
          }
        }

        if(idlist.length > 0)
        {
            if(typeof callback == 'undefined')
            {
                return idlist;
            }
            else
            {
                callback(idlist);
                return idlist;
            }
        }
        else
        {
            alert("请勾选项目");
            return false;
        }
    }

    function in_array(needle,haystack)
    {
        for(var i in haystack)
        {
            if(haystack[i] == needle) return true;
        }

        return false;
    }

    function checkboxSelect(p, cb)
    {
        var c = $(parseId(p));
        c.bind('click',function()
        {   
		    checkboxSelectRun(c,p,cb);
        });
    }

    function checkboxSelectRun(c,p,cb)
    {
        if(c.attr('checked') == 'checked')
        {
            $('.'+p).attr('checked','checked');  
        }
        else
        {
            $('.'+p).removeAttr('checked');  
        }
        if (typeof cb === 'function') {
            cb(p);
        };  
    }

    function queryTable(options)
    {
        var def = {
            ids:'#js-queryTable',
            headTag:'thead th',
            bodyTag:'tbody tr',
            filterField:[0,9],
            filterText:'Filter: ',
            filterIds:'#js-queryTable-filter'
        };

        def = $.extend(def,options);

        var filterList = new Array();
        var filterTypeList = new Array();

        $(parseId(def.ids)).find(def.headTag).each(function(i)
        {
            var isFilter = false;

            for(var fi in def.filterField)
            {
                if(def.filterField[fi] == i) isFilter = true;
            }

            if(!isFilter)
            {
                filterList[i] = $(this).html();
                filterTypeList[i] = $(this).attr('filterType') || 'default';
            }
        });

        queryTable_filter(def,filterList,filterTypeList);
    }

    function queryTable_filter(def,filterList,filterTypeList)
    {
        var selecthtml = '<select>';

        for(var i in filterList)
        {
            selecthtml += '<option value="'+ i +'">' + filterList[i] + '</option>';
        }

        selecthtml += '</select>';
        selecthtml = def.filterText + selecthtml + '<span class="js-queryTable-filter-type"></span>';
        $(parseId(def.filterIds)).html(selecthtml);
        $(parseId(def.filterIds) + ' select').bind('change',function()
        {
            var index = $(this).val();
            queryTable_filter_type(def,index,filterTypeList);
        });
        $(parseId(def.filterIds) + ' select').change();
    }

    function queryTable_filter_type(def,index,filterTypeList)
    {
        var html = '';
        var type = 'input';
        switch(filterTypeList[index])
        {
            case 'default': // text
                html = '  <input text="text">';

            break;

            case 'enum': // enum
                var list = new Array();
                html = ' <select>';
                $(parseId(def.ids) + ' '+ def.bodyTag).each(function(row)
                {
                    var curVal = $(this).find('td').eq(index).text();
                    if(!in_array(curVal,list))
                    {
                        list.push(curVal);
                    }
                });
                html += '<option value="-1">--ALL--</option>';
                for(var i in list)
                {
                    html += '<option value="'+list[i]+'">'+list[i]+'</option>';
                }

                html += '</select>';
                type = 'select';
            break;

            default:
                html = '  <input text="text">';
            break;
        }

        $(def.filterIds).find('.js-queryTable-filter-type').html(html);
        $(def.filterIds).find('.js-queryTable-filter-type ' + type).bind('keyup change',function()
        {
            var val = $(this).val();
            queryTable_data(def,index,val);
        });
    }

    function queryTable_data(def,index,searchVal)
    {
        searchVal = searchVal.replace(/(^\s*)|(\s*$)/,"");//去除两边空格
        $(parseId(def.ids) + ' '+ def.bodyTag).each(function(row)
        {
            var curVal = $(this).find('td').eq(index).text();
            curVal = curVal.replace(/(^\s*)|(\s*$)/g, "");
            if(searchVal == -1 || searchVal == '')
            {
                $(this).show();
            }
            else if(curVal.indexOf(searchVal) == -1)
            {
                $(this).hide();
            }
            else
            {
                $(this).show();
            }
        });
    }

    function parseId(str,ch)
    {
        if(str[0] == '#' || str[0] == '.')
        {
            return str;
        }
        ch = ch || '#';
        return ch+str;
    }
    
    function loading(options)
    {
        // /app/global/images/loader.gif
        // /app/global/images/default/shared/loading-balls.gif
        var def = {
                   top:0,
                   left:0,
                   id:'js-base-loading',
                   type:'default',//default or custom
                   html:'<img src="'+BASEURL+'global/images/loader.gif">'
                  };
        def = $.extend(def,options);
        if(def.type == 'custom') //使用custom时,需要自己创建好dom元素
        {
            $(parseId(def.id,"#")).html(def.html);
        }
        else
        {
            if($(parseId(def.id,"#")).html() == null)
            {
                $('body').prepend('<div id="'+def.id+'">'+def.html+'</div>');
            }
            var top = document.body.scrollTop || document.documentElement.scrollTop;
            var left = document.body.scrollLeft || document.documentElement.scrollLeft;
            var inner = getVisionSize();
            $(parseId(def.id,"#")).css('top',top + inner.height/2 - def.top);
            $(parseId(def.id,"#")).css('left',inner.width/2 - def.left);
        }
        $(parseId(def.id,"#")).show();
    }
    
    function loginOut(def) 
    {
       setTimeout(" window.parent.location.href = APPURL + 'in'", 1000);
    }

    function unLoading(id)
    {
        id = id || 'js-base-loading';
        $(parseId(id,"#")).hide();
    }
    
    function lockBg(zIndex)
    {
        zIndex = zIndex || 9998;
        $('body').prepend('<div id="js-lockBg"></div>');
        $('#js-lockBg').css('z-index',zIndex);
    }
    
    function unLockBg()
    {
        $('#js-lockBg').remove();
    }
    
    function getVisionSize()
    {
        var inner = {width:null,height:null};
        if(window.navigator.userAgent.indexOf("MSIE")>=1)
        {
           inner.width = document.body.clientWidth;
           inner.height = document.body.clientHeight;
        }
        else//if(window.navigator.userAgent.indexOf("Firefox")>=1)
        {
           inner.width = window.innerWidth;
           inner.height = window.innerHeight;
        }
        return inner;
    }
    
    /*function alert(msg,options)
    {
        confrimBox(msg,options);
        $('#js-confrimBoxMsgCancel').hide();
    }*/

    function delConfrim(msg,url)
    {
        confrimBox(msg,{
            verifyCallback:function()
            {
                redirect(url);
            }
            });
    }
    
    function confrimBox(msg,options)
    {
        var def = {
                    verifyButton:'确定',
                    cancelButton:'取消',
                    verifyCallback:null, // 点击确定回调方法
                    cancelCallback:null,
                    id:'js-confrimBox',
                    msgId:'js-confrimBox-msg',
                    msg:msg,
                    completeCallback:null,
                    ac:false,// false 为点击了取消 true 为点击确定
                    defaultVerifyCallback:null,
                    defaultCancelCallback:null,
                    close:null,// 关闭
                    width:250,
                    height:120,
                    title:'提示信息'
                   };
        def.close = function()
        {
            unLockBg();
            $(parseId(def.id,"#")).remove();
        }
        def.defaultVerifyCallback = def.close;
        def.defaultCancelCallback = def.close;
        def = $.extend(def, options);
        var html = '<div class="confrimBoxTitle"><p><ins class="b-title"></ins><b>'+def.title+'</b></p></div>'+
                   '<div class="confrimBoxMsg" id="'+def.msgId+'">'+msg+'</div>'+
                   '<div class="confrimBoxButton">'+
                   '<input type="button" class="btn" id="js-confrimBoxMsgVerify" value="'+def.verifyButton+'"/>&nbsp;&nbsp;&nbsp;&nbsp;'+
                   '<input type="button" class="btn" id="js-confrimBoxMsgCancel" value="'+def.cancelButton+'"/>'+
                   '</div>';
        if($(def.id).html() == null)
        {
            $('body').prepend('<div id="'+def.id+'">'+html+'</div>');
        }
        else
        {
            $(parseId(def.id,"#")).html(html);
        }
        if(def.completeCallback)
        {
            def.completeCallback(def);
        }
        
        var top = document.body.scrollTop || document.documentElement.scrollTop;
		var left = document.body.scrollLeft || document.documentElement.scrollLeft;
        var inner = getVisionSize();
        //alert('top:'+(inner.height/2 - def.height/2)+'  left:'+(inner.width/2 - def.width/2));
        $(parseId(def.id,"#")).css('top',inner.height/2 - def.height/2);
        $(parseId(def.id,"#")).css('left',inner.width/2 - def.width/2);
        $(parseId(def.id,"#")).css('width',def.width);
        $(parseId(def.id,"#")).css('height',def.height);
        $(parseId(def.id,"#")).css('z-index',9999);
        lockBg(9998);

        $("#js-confrimBoxMsgVerify").bind('click',function()
        {
            if(def.verifyCallback)
            {
                def.verifyCallback(def);
            }
            def.ac = true;
            if(def.defaultVerifyCallback)
            {
                def.defaultVerifyCallback(def);
            }
        });
        
        $("#js-confrimBoxMsgCancel").bind('click',function()
        {
            if(def.cancelCallback)
            {
                def.cancelCallback(def);
            }
            def.ac = false;
            if(def.defaultCancelCallback)
            {
                def.defaultCancelCallback(def);
            }
        });
        
        return def;
    }
    
    function redirect(url)
    {
        location.href = url;
    }
    
    function delayURL(url) {

        if(url == '') {
            return;
        }
        if(document.getElementById("js-time")){
            var delay = document.getElementById("js-time").innerHTML;    
        }else{
            delay = 0;    
        }
        
        if(delay > 0) {
            delay--;
            document.getElementById("js-time").innerHTML = delay;
        } else {
            //window.top.location.href = url;
            location.href = url;
        }
        setTimeout("BASE.delayURL(\'" + url + "\')", 1000);
    }

    function ajaxForm(opts)
    {
        var def =
        {
            showType:'alert',// alert or confrim or custom
            msg:'提交成功',
            //autoRestForm:true,
            ids:'js-form',
            errIds:'js-form-errors',
            successCustomCallBack:false,//showType = custom 
            errorCustomCallBack:false
        };
        
        def = $.extend(def,opts);
        
        function showErrors(errors)
        {
            $('.error1').remove();
            //$("html, body").animate({ scrollTop: 0 }, 520);
            //$("#mainFrame",window.top.document).animate({ scrollTop: 0 }, 520);
            //$("html, body",window.top.document).animate({ scrollTop: 0 }, 520);
            //$("html, body").animate({ scrollTop: 0 }, 520);

            $(parseId(def.errIds,"#")).show();
            var er = $(parseId(def.errIds,"#"));
            var html = '';
            if(typeof errors == 'string') {
                html = errors;
                er.addClass('alert');
                er.addClass('alert-error');
                er.html('<ol>'+html+'</ol>');
                $("html, body").animate({ scrollTop: 0 }, 520);
                return;
            }

            var idPos = '';

            for(var i in errors)
            {
                if(typeof errors[i] == 'object')
                {
                    var errStr = [];
                    for(var j in errors[i])
                    {
                        if(typeof errors[i][j] == 'function') continue;
                        html += '<li>' + errors[i][j] + '</li>';
                        //alert(errors[i][j]);
                        errStr.push(errors[i][j]);
                        if(idPos == ''){
                            idPos = i;
                        }
                    }
                    $('#'+i+'-element').after('<span class="alert alert-error error1">'+errStr.join(',')+'</span>');
                }
                else
                {
                    if(typeof errors[i] == 'function') continue;
                    html += '<li>' + errors[i] + '</li>';  
                    //alert(errors[i]);
                    $('#'+i+'-element').after('<span class="alert alert-error error1">'+errors[i]+'</span>');
                    //$("input[name='id']").css("");
                    if(idPos == ''){
                        idPos = i;
                    }                    
                }
            }
            
            //$('#'+idPos+'-element').scrollTop();
            er.addClass('alert');
            er.addClass('alert-error');
            er.html('<ol>'+html+'</ol>');

            if($('#'+idPos+'-element').length>0){
                $("html, body",window.top.document).animate({ scrollTop: $('#'+idPos+'-element').position().top }, 520);
                $("html, body").animate({ scrollTop: $('#'+idPos+'-element').position().top }, 520);
            }else{
                $("html, body",window.top.document).animate({ scrollTop: 0 }, 520);
                $("html, body").animate({ scrollTop: 0 }, 520);
            }            
        }

        function showSuccess(errors) {
            //$("#mainFrame",window.top.document).animate({ scrollTop: 0 }, 520);
            $("html, body",window.top.document).animate({ scrollTop: 0 }, 520);
            $("html, body").animate({ scrollTop: 0 }, 520);
            
            var waitTime = 2;
            $(parseId(def.errIds,"#")).show();
            var er = $(parseId(def.errIds,"#"));
            er.removeClass('alert-error');
            er.addClass('alert');
            er.addClass('alert-success');
            if(errors == '') {
                er.html('<i class="icon-ok"></i>提交成功');
            } else {
                er.html('<i class="icon-ok"></i>提交成功,将于<span id="js-time">'+waitTime+'</span>秒后自动跳转...');
                $('.js-submitButton').hide();
                $(parseId(def.ids,"#")).resetForm();
                delayURL(errors);
            }
        }
        
        function hideErrors()
        {
            $(parseId(def.errIds,"#")).hide();
        }
        
        var opts =
        {
            beforeSend:function()
            {
                hideErrors();
                $('.js-submitButton').hide();
                BASE.loading({id:'.form-loading',type:'custom',html:'<img src="'+BASEURL+'/global/bootstrap/media/image/loading.gif" align="absmiddle">'});
            },
            dataType:'json',
            success:function(data)
            {
                def.data = data;
                hideErrors();
                if(data.success == 1)
                {
                    //alert(data.successCustomCallBack);
                    if(data.successCustomCallBack) {
                        eval(data.successCustomCallBack+'()');
                    }
                    showSuccess(data.errors);
                    if(def.successCustomCallBack) def.successCustomCallBack(def);
                }
                else
                {
                    showErrors(data.errors);
                    if(def.errorCustomCallBack) def.errorCustomCallBack(def);
                    if(data.errorCustomCallBack) data.errorCustomCallBack(def);
                }
                
                $('.js-submitButton').show();
                BASE.unLoading('.form-loading');
            },
            timeout: 0,// 15 seconds
            error: function (xmlHttpRequest, error) {
                //console.log(xmlHttpRequest, error);
                if(error == 'timeout')
                {
                    showErrors('请求超时')
                    //BASE.alert("请求超时");
                    $('.js-submitButton').show();
                    BASE.unLoading('.form-loading');
                }
                else
                {
                    //BASE.alert("请求出错:"+error);
                    showErrors('请求出错');
                    $('.js-submitButton').show();
                    BASE.unLoading('.form-loading');
                }
            }
        };
        
        $(parseId(def.ids,"#")).ajaxForm(opts);
    }

    function boxAdd(num) {
        // var height = $('#mainFrame',window.top.document).height();
        // $('#mainFrame',window.top.document).height(height+num);
    }

    function boxReduce(num) {
        // var height = $('#mainFrame',window.top.document).height();
        // $('#mainFrame',window.top.document).height(height-num);
    }
 }
)();

/******************************************************
 *Function Test
 *
 *
 ******************************************************/
$(document).ready(function()
{
    //alert(window.parent.doucmengetElementById('mainFrame'));
    //alert($('#mainFrame',window.top.document).attr('src'));
    //location.href='http://www.baidu.com';
    /*
    BASE.confrim("你确定吗",{
                 verifyCallback:function()
                 {
                    alert(1);
                 },
                 cancelCallback:function()
                 {
                    alert(2);
                 }});
    */
    //alert(BASE.parseId("st","."));
});

// function del_confirm(url){
//     if(confirm('确定要删除吗?')){
//          location.href=url;   
//     }
// }