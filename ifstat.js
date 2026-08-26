<script>
function ifstat(){
        $("#ifstat").load("ifstat.php",function () {$(this).wrap();});
}
setInterval(function(){ifstat()}, 3100);
</script>
