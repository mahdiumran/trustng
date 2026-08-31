function gauge(){
        $("#gauge").load("gauge.php",function () {$(this).wrap();});
}
setInterval(function(){gauge()}, 3100);
