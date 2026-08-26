<script type='text/javascript'>
google.charts.load('current', {'packages':['gauge']});
google.charts.setOnLoadCallback(drawGauge);

function drawGauge() {
var data = google.visualization.arrayToDataTable([
<?php include 'gauge.dat'; ?> ]);

var options = {
height: 150,
redFrom: 75, redTo: 100,
yellowFrom:50, yellowTo: 75,
greenFrom:0, greenTo: 50,
minorTicks: 5
};

var chart = new google.visualization.Gauge(document.getElementById('gauge'));

chart.draw(data, options); {
}
}
</script>
