<?php
include("db.php");
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'add_webhook') {
        $trigger_topic = $_POST['trigger_topic'];
        $trigger_eui = $_POST['trigger_eui'];
        $url = $_POST['url'];
        $created_by = 'abby';
        $sql = "INSERT INTO webhooks (trigger_topic, trigger_eui, url, created_by) VALUES (:trigger_topic, :trigger_eui, :url, :created_by)";
        dbExec($sql, [':trigger_topic' => $trigger_topic, ':trigger_eui' => $trigger_eui, ':url' => $url, ':created_by' => $created_by]);
    } else if ($_POST['action'] == 'delete_webhook') {
        $id = $_POST['id'];
        $sql = "DELETE FROM webhooks WHERE id = :id";
        dbExec($sql, [':id' => $id]);
    }
}
$webhooks = dbRows("SELECT * FROM webhooks");


include("templates/header.php");



?>
<h2>Webhooks</h2>
<table class="table">
    <thead>
        <tr>
            <th>Trigger Topic</th>
            <th>Trigger EUI</th>
            <th>URL</th>
            <th>Created By</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($webhooks as $webhook) { ?>
            <tr>
                <td><?php echo $webhook['trigger_topic']; ?></td>
                <td><?php echo $webhook['trigger_eui']; ?></td>
                <td><?php echo $webhook['url']; ?></td>
                <td><?php echo $webhook['created_by']; ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="action" value="delete_webhook">
                        <input type="hidden" name="id" value="<?php echo $webhook['id']; ?>">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<form method="post">
    <input type="hidden" name="action" value="add_webhook">
    <div class="form-group">
        <label for="trigger_topic">Trigger Topic</label>
        <input type="text" class="form-control" id="trigger_topic" name="trigger_topic">
    </div>
    <div class="form-group">
        <label for="trigger_eui">Trigger EUI</label>
        <input type="text" class="form-control" id="trigger_eui" name="trigger_eui">
    </div>
    <div class="form-group">
        <label for="url">URL</label>
        <input type="text" class="form-control" id="url" name="url">
    </div>
    <button type="submit" class="btn btn-primary">Add Webhook</button>
</form>
<?php include("templates/footer.php"); ?>
