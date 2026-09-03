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

    // Fallback: raw cumulative value from Unbound. Patched builds may
    // expose num.queries without the total. prefix.
    var total = text.match(/(?:^|\n)(?:total\.)?num\.queries\s*[=:]\s*([0-9]+(?:\.[0-9]+)?)/i);
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

    // Fallback: raw cumulative value, with or without total. prefix.
    var blTotal = text.match(/(?:^|\n)(?:total\.)?num\.blacklist\s*[=:]\s*([0-9]+(?:\.[0-9]+)?)/i);
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

  // --- Hero stats (cumulative totals from Unbound) ---
  function updateHero(data) {
    var heroTotal = document.getElementById("heroTotal");
    var heroBlocked = document.getElementById("heroBlocked");
    var heroRate = document.getElementById("heroRate");

    var q = data.total_queries || 0;
    var b = data.blocked_queries || 0;
    var rate = q > 0 ? ((b / q) * 100).toFixed(1) : "0.0";

    if (heroTotal) heroTotal.textContent = q.toLocaleString("en-US");
    if (heroBlocked) heroBlocked.textContent = b.toLocaleString("en-US");
    if (heroRate) heroRate.textContent = rate;
  }

  function refreshHero() {
    fetchJSON("stats_totals.php", updateHero);
  }

  // --- Donut chart (query types) ---
  var donutCanvas = document.getElementById("donutChart");
  var donutCtx = donutCanvas ? donutCanvas.getContext("2d") : null;
  var donutColors = ["#00f2ff", "#27ff97", "#ffcfd4", "#ffc857", "#b9cacb", "#849495"];

  function drawDonut(data) {
    if (!donutCanvas || !donutCtx) return;
    var keys = Object.keys(data);
    if (keys.length === 0) return;

    var ratio = window.devicePixelRatio || 1;
    var w = 180, h = 180;
    donutCanvas.width = w * ratio;
    donutCanvas.height = h * ratio;
    donutCtx.setTransform(ratio, 0, 0, ratio, 0, 0);
    donutCtx.clearRect(0, 0, w, h);

    var total = 0;
    keys.forEach(function (k) { total += data[k]; });
    if (total === 0) return;

    var cx = w / 2, cy = h / 2;
    var outerR = 70, innerR = 40;
    var startAngle = -Math.PI / 2;

    keys.forEach(function (key, i) {
      var val = data[key];
      var angle = (val / total) * Math.PI * 2;
      var endAngle = startAngle + angle;

      donutCtx.beginPath();
      donutCtx.arc(cx, cy, outerR, startAngle, endAngle);
      donutCtx.arc(cx, cy, innerR, endAngle, startAngle, true);
      donutCtx.closePath();
      donutCtx.fillStyle = donutColors[i % donutColors.length];
      donutCtx.fill();

      startAngle = endAngle;
    });

    // Center text
    donutCtx.fillStyle = "#e1e2eb";
    donutCtx.font = "500 20px 'JetBrains Mono', monospace";
    donutCtx.textAlign = "center";
    donutCtx.textBaseline = "middle";
    donutCtx.fillText(total.toLocaleString("en-US"), cx, cy - 6);
    donutCtx.font = "400 10px 'Inter', sans-serif";
    donutCtx.fillStyle = "#8a9aae";
    donutCtx.fillText("queries", cx, cy + 12);
    // Legend
    var legend = document.getElementById("donutLegend");
    if (legend) {
      legend.innerHTML = "";
      keys.forEach(function (key, i) {
        var pct = ((data[key] / total) * 100).toFixed(1);
        var item = document.createElement("div");
        item.className = "tng-donut-legend-item";
        item.innerHTML = '<span class="tng-donut-legend-dot" style="background:' + donutColors[i % donutColors.length] + '"></span>' +
          '<span class="tng-donut-legend-label">' + key + '</span>' +
          '<span class="tng-donut-legend-val">' + pct + '%</span>';
        legend.appendChild(item);
      });
    }
  }

  // --- Forward destination bars ---
  function drawForwardBars(data) {
    var container = document.getElementById("forwardBars");
    if (!container) return;
    var keys = Object.keys(data);
    if (keys.length === 0) return;

    var total = 0;
    keys.forEach(function (k) { total += data[k]; });
    if (total === 0) return;

    container.innerHTML = "";
    keys.forEach(function (key, i) {
      var val = data[key];
      var pct = (val / total) * 100;
      var color = i === 0 ? "#00f2ff" : "#27ff97";
      if (key.indexOf("Resolver") === -1 && key !== "Local Cache") color = "#ffcfd4";

      var item = document.createElement("div");
      item.className = "tng-forward-bar-item";
      item.innerHTML = '<span class="tng-forward-bar-label">' + key + '</span>' +
        '<span class="tng-forward-bar-track"><span class="tng-forward-bar-fill" style="width:' + pct.toFixed(1) + '%;background:' + color + '"></span></span>' +
        '<span class="tng-forward-bar-val">' + Math.round(pct) + '%</span>';
      container.appendChild(item);
    });
  }

  // --- Fetch analytics data (cache donut, forward bars) ---
  function fetchJSON(url, callback) {
    var req = new XMLHttpRequest();
    req.open("GET", url + "?_=" + Date.now(), true);
    req.onreadystatechange = function () {
      if (req.readyState === 4 && req.status >= 200 && req.status < 300) {
        try { callback(JSON.parse(req.responseText)); }
        catch (e) { /* ignore parse errors */ }
      }
    };
    req.send();
  }

  function refreshAnalytics() {
    fetchJSON("stats_cache.php", drawDonut);
    fetchJSON("stats_forward.php", drawForwardBars);
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

  function updateResourceTiles(rows) {
    Array.prototype.forEach.call(document.querySelectorAll(".tng-res-tile"), function (tile) {
      var name = tile.getAttribute("data-resource");
      var unit = tile.getAttribute("data-unit") || "";
      var value = rows[name];

      if (!isFinite(value)) value = parseFloat(tile.getAttribute("data-value")) || 0;
      value = Math.max(0, Math.min(100, value));

      // Update state class
      tile.setAttribute("data-value", value);
      tile.classList.remove("normal", "warning", "critical");
      var state = value >= 75 ? "critical" : value >= 50 ? "warning" : "normal";
      tile.classList.add(state);

      // Update value text
      var valEl = tile.querySelector(".tng-res-tile-value");
      if (valEl) {
        var rounded = value >= 10 ? value.toFixed(0) : value.toFixed(1);
        rounded = rounded.replace(/\.0$/, "");
        valEl.innerHTML = rounded + "<span>" + unit + "</span>";
      }

      // Update bar
      var barFill = tile.querySelector(".tng-res-tile-bar-fill");
      if (barFill) barFill.style.width = value + "%";
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

  refreshHero();
  refreshQueries();
  refreshResources();
  refreshAnalytics();
  setInterval(refreshHero, 10000);
  setInterval(refreshQueries, 5000);
  setInterval(refreshResources, 5000);
  setInterval(refreshAnalytics, 10000);
  window.addEventListener("resize", function () {
    drawChart();
    drawBlockedChart();
    drawDonut();
  });
})();
