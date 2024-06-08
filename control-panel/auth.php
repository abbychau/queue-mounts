<?php
/*
-- 傾印  資料表 mqtt-auth.acl 結構
CREATE TABLE IF NOT EXISTS `acl` (
  `username` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `topic` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `access` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 取消選取資料匯出。

-- 傾印  資料表 mqtt-auth.auth 結構
CREATE TABLE IF NOT EXISTS `auth` (
  `username` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allow` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 dsn:
  host: 34.81.250.84
  port: 3306
  schema: mqtt-auth
  charset: utf8mb4
  login-name: dev_user
  login-password: aassddff
  max-open-conns: 10
  max-idle-conns: 5
*/

include('db.php');

if (isset($_POST['action'])) {
    if ($_POST['action'] == 'add_user') {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $allow = $_POST['allow'];
        $sql = "INSERT INTO auth (username, password, allow) VALUES (:username, :password, :allow)";
        dbExec($sql, [':username' => $username, ':password' => $password, ':allow' => $allow]);
    } else if ($_POST['action'] == 'add_acl') {
        $username = $_POST['username'];
        $topic = $_POST['topic'];
        $access = $_POST['access'];
        $sql = "INSERT INTO acl (username, topic, access) VALUES (:username, :topic, :access)";
        dbExec($sql, [':username' => $username, ':topic' => $topic, ':access' => $access]);
    } else if ($_POST['action'] == 'delete_user') {
        $username = $_POST['username'];
        $sql = "DELETE FROM auth WHERE username = :username";
        dbExec($sql, [':username' => $username]);
    } else if ($_POST['action'] == 'delete_acl') {
        $username = $_POST['username'];
        $topic = $_POST['topic'];
        $sql = "DELETE FROM acl WHERE username = :username AND topic = :topic";
        dbExec($sql, [':username' => $username, ':topic' => $topic]);
    }
}

$users = dbRows("SELECT * FROM auth");

$acls = dbRows("SELECT * FROM acl");

?>

<?php include('templates/header.php'); ?>
<h1>MQTT Auth</h1>
<h2>Users</h2>
<table class="table">
    <thead>
        <tr>
            <th>Username</th>
            <th>Password</th>
            <th>Allow</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user) { ?>
            <tr>
                <td><?php echo $user['username']; ?></td>
                <td><?php echo $user['password']; ?></td>
                <td><?php echo $user['allow']; ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="username" value="<?php echo $user['username']; ?>">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>
<form method="post">
    <input type="hidden" name="action" value="add_user">
    <div class="form-group">
        <label for="username">Username</label>
        <input type="text" class="form-control" id="username" name="username">
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <input type="text" class="form-control" id="password" name="password">
    </div>
    <div class="form-group">
        <label for="allow">Allow</label>
        <input type="text" class="form-control" id="allow" name="allow">
    </div>
    <button type="submit" class="btn btn-primary">Add User</button>
</form>
<h2>ACLs</h2>
<table class="table">
    <thead>
        <tr>
            <th>Username</th>
            <th>Topic</th>
            <th>Access</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($acls as $acl) { ?>
            <tr>
                <td><?php echo $acl['username']; ?></td>
                <td><?php echo $acl['topic']; ?></td>
                <td><?php echo $acl['access']; ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="action" value="delete_acl">
                        <input type="hidden" name="username" value="<?php echo $acl['username']; ?>">
                        <input type="hidden" name="topic" value="<?php echo $acl['topic']; ?>">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>
<form method="post">
    <input type="hidden" name="action" value="add_acl">
    <div class="form-group">
        <label for="username">Username</label>
        <input type="text" class="form-control" id="username" name="username">
    </div>
    <div class="form-group">
        <label for="topic">Topic</label>
        <input type="text" class="form-control" id="topic" name="topic">
    </div>
    <div class="form-group">
        <label for="access">Access</label>
        <input type="text" class="form-control" id="access" name="access">
    </div>
    <button type="submit" class="btn btn-primary">Add ACL</button>
</form>


<?php include('templates/footer.php'); ?>