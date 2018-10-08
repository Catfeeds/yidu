(function ($) {
    var defaluts={
        x:false,
        d:250,
        r:0,
        c:function () {}
    };
    var win=$(window);
    $.fn.scrollShow=function (options) {
        var options=$.extend({},defaluts,options);
        var _that=this;
        var allyes=0;
        var yesno=true;
        var length=_that.length;
        function setshow(obj) {
            var t=win.scrollTop();
            while (obj.is(':hidden')){
                obj=obj.parent();
                if(obj[0].tagName=='BODY'){
                    return true;
                }
            }
            if(t<obj.offset().top+obj.outerHeight()-options.r&&t>obj.offset().top-options.r){
                return true;
            }else{
                return false;
            }
        }
        function setscroll(obj) {
            obj.each(function () {
                console.log(1)
                var _that=$(this);
                if(!_that.attr('data-scroll-suc')){
                    if(setshow(_that)){
                        if(!options.x){
                            allyes++;
                            _that.attr('data-scroll-suc',true);
                        }
                        options.c(_that);
                        
                    }
                }
            });
            if(allyes==length){
                // alert(1)
                yesno=false;
            }else{
                yesno=true;
            }
        }
        setscroll(_that);
        win.scroll(function () {
            if(yesno){
                yesno=false;
                setTimeout(function () {
                    setscroll(_that);
                },options.d);
            }
        })
    };
})(jQuery);