(function () {
  var chart = document.getElementById("queryChart");
  var valueNode = document.getElementById("queryValue");
  var modeNode = document.getElementById("queryMode");
  var rawNode = document.getElementById("statsRaw");
  var pulseNode = document.getElementById("queryPulse");

  // --- Blocklist chart ---
  var blChart = document.getElementById("blockedChart");
  var blValueNode = document.getElementById("blockedValue");
  var blModeNode = document.getElementById("blockedMode");
  var blPulseNode = document.getElementById("blockedPulse");

  if (!chart || !valueNode || !modeNode) return;

  var ctx = chart.getContext("2d");
  var points = [];
  var maxPoints = 48;
  var lastTotal = null;
  var lastTotalTime = null;
  var resourceHistory = {};

  // --- Blocklist chart state ---
  var blCtx = blChart ? blChart.getContext("2d") : null;
  var blPoints = [];
  var lastBlocked = null;
  var lastBlockedTime = null;

  // --- Canvas mini-charts for stat cards (removed, using full charts now) ---
  function numberText(value) {
    if (!isFinite(value)) return "0";
    if (value >= 1000) return Math.round(value).toLocaleString("en-US");
    if (value >= 10) return value.toFixed(0);
    return value.toFixed(1);
  }

  function extractStat(text) {
    // /usr/bin/s outputs: "queries  :   123 queries/s"
    var rateLine = text.match(/queries\s*:\s*([0-9]+(?:\.[0-9]+)?)\s*queries\/s/i);
    if (rateLine) return { value: parseFloat(rateLine[1]), mode: "queries/s" };

    // Fallback: raw total.num.queries from dn stats
    var total = text.match(/total\.num\.queries\s*[=:]\s*([0-9]+(?:\.[0-9]+)?)/i);
    if (total) return { value: parseFloat(total[1]), mode: "total" };

    var anyNumber = text.match(/[0-9]+(?:\.[0-9]+)?/);
    return { value: anyNumber ? parseFloat(anyNumber[0]) : 0, mode: "sample" };
  }

  function pushPoint(sample) {
    var now = Date.now();
    var value = sample.value;
    var label = sample.mode;

    // If we got a cumulative total, compute rate from delta
    if (sample.mode === "total") {
      if (lastTotal !== null && sample.value >= lastTotal) {
        value = (sample.value - lastTotal) / Math.max((now - lastTotalTime) / 1000, 1);
        label = "queries/s";
      } else {
        value = 0;
        label = "queries/s";
      }
      lastTotal = sample.value;
      lastTotalTime = now;
    }

    points.push({ value: Math.max(value, 0), time: now });
    if (points.length > maxPoints) points.shift();

    valueNode.textContent = numberText(value);
    modeNode.textContent = label;
    if (pulseNode) pulseNode.style.opacity = "1";
    drawChart();
  }

  function drawChart() {
    var ratio = window.devicePixelRatio || 1;
    var width = chart.clientWidth || chart.width;
    var height = chart.clientHeight || chart.height;

    if (chart.width !== Math.floor(width * ratio) || chart.height !== Math.floor(height * ratio)) {
      chart.width = Math.floor(width * ratio);
      chart.height = Math.floor(height * ratio);
    }

    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    ctx.clearRect(0, 0, width, height);

    var pad = 22;
    var plotW = width - pad * 2;
    var plotH = height - pad * 2;
    var maxValue = Math.max.apply(null, points.map(function (p) { return p.value; }).concat([1]));

    ctx.strokeStyle = "rgba(0, 242, 255, 0.08)";
    ctx.lineWidth = 1;
    for (var i = 0; i < 4; i++) {
      var y = pad + (plotH / 3) * i;
      ctx.beginPath();
      ctx.moveTo(pad, y);
      ctx.lineTo(width - pad, y);
      ctx.stroke();
    }

    if (points.length < 2) return;

    ctx.beginPath();
    points.forEach(function (point, index) {
      var x = pad + (plotW / Math.max(maxPoints - 1, 1)) * index;
      var y = pad + plotH - (point.value / maxValue) * plotH;
      if (index === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    });
    ctx.strokeStyle = "#00f2ff";
    ctx.lineWidth = 2;
    ctx.lineJoin = "round";
    ctx.lineCap = "round";
    ctx.stroke();

    var gradient = ctx.createLinearGradient(0, pad, 0, height - pad);
    gradient.addColorStop(0, "rgba(0, 242, 255, 0.18)");
    gradient.addColorStop(1, "rgba(0, 242, 255, 0)");

    ctx.lineTo(pad + (plotW / Math.max(maxPoints - 1, 1)) * (points.length - 1), height - pad);
    ctx.lineTo(pad, height - pad);
    ctx.closePath();
    ctx.fillStyle = gradient;
    ctx.fill();
  }

  function ingest(html) {
    var holder = document.createElement("div");
    holder.innerHTML = html;
    var text = holder.textContent || holder.innerText || "";
    if (rawNode) rawNode.innerHTML = html;
    pushPoint(extractStat(text));
    pushBlockedPoint(extractBlockedStat(text));
  }

  function extractBlockedStat(text) {
    // /usr/bin/s outputs: "blacklist:     3 queries/s"
    var blRate = text.match(/blacklist\s*:\s*([0-9]+(?:\.[0-9]+)?)\s*queries?\/s/i);
    if (blRate) return { value: parseFloat(blRate[1]), mode: "blocked/s" };

    // Fallback: raw total.num.blacklist cumulative
    var blTotal = text.match(/total\.num\.blacklist\s*[=:]\s*([0-9]+(?:\.[0-9]+)?)/i);
    if (blTotal) return { value: parseFloat(blTotal[1]), mode: "total" };

    return { value: 0, mode: "blocked/s" };
  }

  function pushBlockedPoint(sample) {
    var now = Date.now();
    var value = sample.value;
    var label = sample.mode;

    // If cumulative total, compute delta rate
    if (sample.mode === "total") {
      if (lastBlocked !== null && sample.value >= lastBlocked) {
        value = (sample.value - lastBlocked) / Math.max((now - lastBlockedTime) / 1000, 1);
        label = "blocked/s";
      } else {
        value = 0;
        label = "blocked/s";
      }
      lastBlocked = sample.value;
      lastBlockedTime = now;
    }

    blPoints.push({ value: Math.max(value, 0), time: now });
    if (blPoints.length > maxPoints) blPoints.shift();

    if (blValueNode) blValueNode.textContent = numberText(value);
    if (blModeNode) blModeNode.textContent = label;
    if (blPulseNode) blPulseNode.style.opacity = "1";
    drawBlockedChart();
  }

  function drawBlockedChart() {
    if (!blChart || !blCtx) return;
    var ratio = window.devicePixelRatio || 1;
    var width = blChart.clientWidth || blChart.width;
    var height = blChart.clientHeight || blChart.height;

    if (blChart.width !== Math.floor(width * ratio) || blChart.height !== Math.floor(height * ratio)) {
      blChart.width = Math.floor(width * ratio);
      blChart.height = Math.floor(height * ratio);
    }

    blCtx.setTransform(ratio, 0, 0, ratio, 0, 0);
    blCtx.clearRect(0, 0, width, height);

    var pad = 22;
    var plotW = width - pad * 2;
    var plotH = height - pad * 2;
    var maxValue = Math.max.apply(null, blPoints.map(function (p) { return p.value; }).concat([1]));

    // Grid lines
    blCtx.strokeStyle = "rgba(255, 207, 212, 0.06)";
    blCtx.lineWidth = 1;
    for (var i = 0; i < 4; i++) {
      var y = pad + (plotH / 3) * i;
      blCtx.beginPath();
      blCtx.moveTo(pad, y);
      blCtx.lineTo(width - pad, y);
      blCtx.stroke();
    }

    if (blPoints.length < 2) return;

    // Line
    blCtx.beginPath();
    blPoints.forEach(function (point, index) {
      var x = pad + (plotW / Math.max(maxPoints - 1, 1)) * index;
      var y = pad + plotH - (point.value / maxValue) * plotH;
      if (index === 0) blCtx.moveTo(x, y);
      else blCtx.lineTo(x, y);
    });
    blCtx.strokeStyle = "#ffcfd4";
    blCtx.lineWidth = 2;
    blCtx.lineJoin = "round";
    blCtx.lineCap = "round";
    blCtx.stroke();

    // Area fill
    var gradient = blCtx.createLinearGradient(0, pad, 0, height - pad);
    gradient.addColorStop(0, "rgba(255, 207, 212, 0.18)");
    gradient.addColorStop(1, "rgba(255, 207, 212, 0)");

    blCtx.lineTo(pad + (plotW / Math.max(maxPoints - 1, 1)) * (blPoints.length - 1), height - pad);
    blCtx.lineTo(pad, height - pad);
    blCtx.closePath();
    blCtx.fillStyle = gradient;
    blCtx.fill();
  }

  function refreshQueries() {
    var request = new XMLHttpRequest();
    request.open("GET", "s.php?_=" + Date.now(), true);
    request.onreadystatechange = function () {
      if (request.readyState === 4 && request.status >= 200 && request.status < 300) {
        ingest(request.responseText);
        if (pulseNode) {
          pulseNode.style.opacity = "0.55";
          setTimeout(function () { pulseNode.style.opacity = "1"; }, 160);
        }
      }
    };
    request.send();
  }

  function parseGaugeData(text) {
    var rows = {};
    var pattern = /\['([^']+)'\s*,\s*([0-9.]+)\]/g;
    var match;

    while ((match = pattern.exec(text)) !== null) {
      if (match[1] !== "Label") rows[match[1]] = parseFloat(match[2]);
    }

    return rows;
  }

  function formatResourceValue(value, unit) {
    var rounded = value >= 10 ? value.toFixed(0) : value.toFixed(1);
    return rounded.replace(/\.0$/, "") + unit;
  }

  function drawResourceSparkline(tile, values) {
    var line = tile.querySelector(".zabbix-line");
    var area = tile.querySelector(".zabbix-area");
    if (!line || !area || values.length < 2) return;

    var max = Math.max.apply(null, values.concat([100]));
    var min = 0;
    var width = 320;
    var height = 120;
    var pointsText = values.map(function (value, index) {
      var x = (width / Math.max(values.length - 1, 1)) * index;
      var y = height - ((value - min) / Math.max(max - min, 1)) * (height - 12) - 4;
      return x.toFixed(1) + "," + y.toFixed(1);
    }).join(" ");

    line.setAttribute("points", pointsText);
    area.setAttribute("points", "0,120 " + pointsText + " 320,120");
  }

  function updateResourceTiles(rows) {
    var now = new Date();
    var stamp = now.getFullYear() + "-" +
      String(now.getMonth() + 1).padStart(2, "0") + "-" +
      String(now.getDate()).padStart(2, "0") + " " +
      now.toLocaleTimeString("en-US", { hour12: true });

    Array.prototype.forEach.call(document.querySelectorAll(".zabbix-tile"), function (tile) {
      var name = tile.getAttribute("data-resource");
      var unit = tile.getAttribute("data-unit") || "";
      var value = rows[name];

      if (!isFinite(value)) value = parseFloat(tile.getAttribute("data-value")) || 0;
      value = Math.max(0, Math.min(100, value));

      if (!resourceHistory[name]) {
        resourceHistory[name] = [];
        for (var i = 17; i >= 0; i--) {
          var wave = Math.sin((i + name.length) * 0.78) * 5;
          resourceHistory[name].push(Math.max(0, Math.min(100, value + wave)));
        }
      }

      resourceHistory[name].push(value);
      if (resourceHistory[name].length > 28) resourceHistory[name].shift();

      tile.setAttribute("data-value", value);
      tile.classList.remove("normal", "warning", "critical");
      tile.classList.add(value >= 75 ? "critical" : value >= 50 ? "warning" : "normal");

      var valueNode = tile.querySelector(".zabbix-value strong");
      var unitNode = tile.querySelector(".zabbix-value span");
      var timeNode = tile.querySelector(".zabbix-time");
      if (valueNode) valueNode.textContent = formatResourceValue(value, "");
      if (unitNode) unitNode.textContent = unit;
      if (timeNode) timeNode.textContent = stamp;

      drawResourceSparkline(tile, resourceHistory[name]);
    });
  }

  function refreshResources() {
    var request = new XMLHttpRequest();
    request.open("GET", "gauge.dat?_=" + Date.now(), true);
    request.onreadystatechange = function () {
      if (request.readyState === 4 && request.status >= 200 && request.status < 300) {
        updateResourceTiles(parseGaugeData(request.responseText));
      }
    };
    request.send();
  }

  if (rawNode && rawNode.textContent.trim() !== "") {
    pushPoint(extractStat(rawNode.textContent));
    pushBlockedPoint(extractBlockedStat(rawNode.textContent));
  } else {
    drawChart();
    drawBlockedChart();
  }

  refreshQueries();
  refreshResources();
  setInterval(refreshQueries, 5000);
  setInterval(refreshResources, 5000);
  window.addEventListener("resize", function () {
    drawChart();
    drawBlockedChart();
  });
})();
