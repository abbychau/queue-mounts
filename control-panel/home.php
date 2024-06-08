<?php
include("db.php");
// $webhooks = dbRows("SELECT * FROM webhooks");
// $acls = dbRows("SELECT * FROM acl");
// $users = dbRows("SELECT * FROM auth");
$stats = file_get_contents('http://localhost:18080');

include("templates/header.php");

?>

<h1>MQTT Dashboard</h1>
<h2>Endpoint</h2>
<pre>
    mqtt://35.221.150.154:1883
    user: abby
    pass: 1234
</pre>
<h2>Stats</h2>
<pre><?php echo $stats; ?></pre>



<?php include("templates/footer.php"); ?>
