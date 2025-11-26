<?php
include("templates/header.php");
?>
<style>
.table-sm td, .table-sm th { padding: .3rem; }
.badge { font-size: 90%; }
</style>
<div>
  <h3>Connection Logs</h3>
  <div class="form-row align-items-end" style="margin-bottom:10px;">
    <div class="col-auto">
      <label for="clientId">Client ID</label>
      <input type="text" class="form-control" id="clientId" placeholder="publisher/client id">
    </div>
    <div class="col-auto">
      <label for="clientSelect">Known Clients</label>
      <select class="form-control" id="clientSelect">
        <option value="">-- select from list --</option>
      </select>
    </div>
    <div class="col-auto">
      <label for="limit">Limit</label>
      <input type="number" class="form-control" id="limit" value="100" min="1" max="100000">
    </div>
    <div class="col-auto">
      <button id="loadBtn" class="btn btn-primary">Load</button>
    </div>
    <div class="col-auto">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" value="" id="autoRefresh">
        <label class="form-check-label" for="autoRefresh">Auto Refresh (2s)</label>
      </div>
    </div>
  </div>
  <div id="result">
    <table class="table table-dark table-striped table-borderless table-sm">
      <thead>
        <tr>
          <th>Time</th>
          <th>Event</th>
          <th>Client</th>
          <th>Remote</th>
          <th>Listener</th>
          <th>Reason</th>
        </tr>
      </thead>
      <tbody id="rows"></tbody>
    </table>
  </div>
</div>
<script>
function fmt(ts){ try { return new Date(ts*1000).toLocaleString(); } catch(e){ return ts; } }
function badge(text, cls){ return `<span class="badge badge-${cls}">${text}</span>`; }
function load(){
  const id = $("#clientId").val().trim();
  const limit = parseInt($("#limit").val()||"100",10);
  if(!id){ $("#rows").html("<tr><td colspan='6'>Enter client_id</td></tr>"); return; }
  $.get(`/api?action=connlogs&client_id=${encodeURIComponent(id)}&limit=${limit}`, function(data){
    try { data = JSON.parse(data); } catch(e) { data = []; }
    if(!Array.isArray(data)) data = [];
    if(data.length===0){ $("#rows").html("<tr><td colspan='6'>No records</td></tr>"); return; }
    let html = '';
    for(const ev of data){
      const when = fmt(ev.timestamp);
      const evt = ev.event === 'connect' ? badge('connect','success') : badge('disconnect','warning');
      const reasonParts = [];
      if(ev.heartbeat_timeout) reasonParts.push(badge('heartbeat-timeout','danger'));
      if(ev.stop_cause) reasonParts.push(badge(ev.stop_cause,'info'));
      if(ev.error) reasonParts.push(badge(ev.error,'secondary'));
      if(ev.expire) reasonParts.push(badge('session-expired','light'));
      html += `<tr>
        <td>${when}</td>
        <td>${evt}</td>
        <td>${ev.client_id||''}</td>
        <td>${ev.remote||''}</td>
        <td>${ev.listener||''}</td>
        <td>${reasonParts.join(' ')}</td>
      </tr>`;
    }
    $("#rows").html(html);
  });
}
$(function(){
  $("#loadBtn").on('click', load);
  let timer=null; $("#autoRefresh").on('change', function(){
    if(this.checked){ if(timer) clearInterval(timer); timer=setInterval(load,2000); }
    else { if(timer) clearInterval(timer); timer=null; }
  });

  // Load known clients and populate selector
  function loadClients(){
    $.get('/api?action=clients', function(data){
      try { data = JSON.parse(data); } catch(e) { data = []; }
      if(!Array.isArray(data)) data = [];
      const $sel = $('#clientSelect');
      $sel.empty();
      $sel.append('<option value="">-- select from list --</option>');
      for(const item of data){
        const label = `${item.client_id} (${new Date((item.last_seen||0)*1000).toLocaleString()} · ${item.last_event||''})`;
        $sel.append(`<option value="${item.client_id}">${label}</option>`);
      }
    });
  }
  loadClients();
  // When selecting a client, populate the text input
  $('#clientSelect').on('change', function(){
    const v = $(this).val();
    if(v){ $('#clientId').val(v); }
  });
});
</script>
<?php include("templates/footer.php"); ?>
