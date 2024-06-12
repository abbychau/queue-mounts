<?php
include("db.php");
// $webhooks = dbRows("SELECT * FROM webhooks");
// $acls = dbRows("SELECT * FROM acl");
// $users = dbRows("SELECT * FROM auth");
$stats = file_get_contents('http://localhost:18080');

include("templates/header.php");

?>
<script src="https://d3js.org/d3.v4.min.js"></script>
<script>
    let memoryAllocHistory = [];

    function loadStatistic() {
        $.get("/api?action=stats", function(data) {
            data = JSON.parse(data);

            // for time and started, convert to human readable
            data.time = new Date(data.time * 1000).toLocaleString();
            data.started = new Date(data.started * 1000).toLocaleString();
            // for uptime, conver seconds to minutes/hours/days
            var uptime = data.uptime;
            var days = Math.floor(uptime / (24 * 3600));
            uptime = uptime % (24 * 3600);
            var hours = Math.floor(uptime / 3600);
            uptime %= 3600;
            var minutes = Math.floor(uptime / 60);
            var seconds = uptime % 60;
            data.uptime = days + " days, " + hours + " hours, " + minutes + " minutes"

            // record memory alloc history
            memoryAllocHistory.push(data.memory_alloc);
            if (memoryAllocHistory.length > 100) {
                memoryAllocHistory.shift();
            }

            // for bytes_received and bytes_sent and memory_alloc, convert to human readable
            data.bytes_received = (data.bytes_received / 1024).toFixed(2) + " KB";
            data.bytes_sent = (data.bytes_sent / 1024).toFixed(2) + " KB";
            data.memory_alloc = (data.memory_alloc / 1024).toFixed(2) + " KB";




            // proper case key
            data = Object.keys(data).reduce((obj, key) => {
                obj[key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())] = data[key];
                return obj;
            }, {});

            html = "<div class='stats'>";
            for (var key in data) {
                // data[key] length >10 then add <br>
                var neededBr = data[key].length > 15 ? "" : "";
                html += `<span class='key'>${key}:${neededBr} <span class='val'>${data[key]}</span><br>`;
            }
            html += "</div>";


            $("#statistic").html(html);
            graph()
        });
    }

    $(document).ready(function() {
        loadStatistic();
        setInterval(loadStatistic, 2000);
    });
</script>
<style>
    .stats {
        font-size: 12px;
        color: #0F0;
        padding: 8px;
        font-family: monospace;
    }

    .key {
        font-weight: bold;
    }

    .val {
        font-weight: normal;
        color: #f1f1f1;
    }

    #dashboard {
        border-radius: 2em;
        padding: 2em;
        color: white;
    }

    #dashboard pre {
        color: white;
    }

    path {
        fill: none;
        stroke-width: 1.5px;
    }

    path.domain {
        stroke: black;
        stroke-width: 1px;
    }
</style>
<div id="dashboard">
    <h1>CoreChainer Dashboard</h1>
    <h2>
        📡MQTT
        Endpoint</h2>
    <div>
        <div class="stats">
            <span class="key">MQTT Endpoint:</span>
            <span class="val">mqtt://35.221.150.154:1883</span><br>
            <span class="key">Username / Password:</span>
            <span class="val">Check Auth/ACL List</span>
        </div>
    </div>
    <h2><!--emoji here-->

        📊Statistic</h2>
    <div id="statistic"></div>
    <script>
        function graph() {
            $(".chart").html("");
            const N = 100;
            const data = [];
            let historyXY = [...memoryAllocHistory].reverse().map((value, index) => ({
                x: index,
                y: Math.round(value / 1000 / 1000)
            }));
            data.push({
                key: `memory_usage`,
                values: historyXY
            });
            console.log(historyXY)

            const width = 600;
            const height = 200;
            const margin = {
                top: 15,
                right: 25,
                bottom: 25,
                left: 25
            };

            const svg = d3.select('svg')
                .attr('width', width + margin.left + margin.right)
                .attr('height', height + margin.top + margin.bottom)
                .append('g')
                .attr('transform', `translate(${margin.left},${margin.top})`);

            const xDomain = [0, N - 1];
            const yDomain = [0, d3.max(data, d =>
                d3.max(d.values, dv => dv.y))];

            const xScale = d3.scaleLinear()
                .domain(xDomain)
                .range([0, width]);
            const yScale = d3.scaleLinear()
                .domain(yDomain)
                .range([height, 0]);

            svg.append('g')
                .attr('transform', `translate(0,${height})`)
                .call(d3.axisBottom(xScale));
            svg.append('g')
                .call(d3.axisLeft(yScale));

            const line = d3.line()
                .x(d => xScale(d.x))
                .y(d => yScale(d.y));

            const colorScale = d3.scaleOrdinal(d3.schemeCategory10);

            data.forEach(value => {
                svg.append('path')
                    .datum(value.values)
                    .attr('d', line)
                    .style('stroke', colorScale(value.key));
            });
        }
    </script>
    <div style="background-color: white; padding: 1em; border-radius: 1em; color:black">
        <h5>Memory Usage(MB/Seconds Ago)</h5>
    <svg class="chart"></svg>
    </div>
</div>



<?php include("templates/footer.php"); ?>