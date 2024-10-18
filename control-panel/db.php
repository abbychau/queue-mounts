<?php
//show errors
ini_set('display_errors', 1);
$confFile = __DIR__ . '/../auth-mysql.yml';
$content = file_get_contents($confFile);
$lines = explode("\n", $content);
$conf = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line == '') {
        continue;
    }
    $parts = explode(':', $line);
    $key = trim($parts[0]);
    $value = trim($parts[1]);
    $conf[$key] = $value;
}
$dbh = new PDO("mysql:host={$conf['host']};port={$conf['port']};dbname={$conf['schema']};charset={$conf['charset']}", $conf['login-name'], $conf['login-password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_PERSISTENT => false,
    PDO::ATTR_AUTOCOMMIT => true,
    PDO::ATTR_TIMEOUT => 0,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);

function dbRows($sql, $params = [])
{
    global $dbh;
    $sth = $dbh->prepare($sql);
    $sth->execute($params);
    return $sth->fetchAll(PDO::FETCH_ASSOC);
}
function dbRow($sql, $params = [])
{
    global $dbh;
    $sth = $dbh->prepare($sql);
    $sth->execute($params);
    $allRows = $sth->fetchAll(PDO::FETCH_ASSOC);
    if (count($allRows) > 0) {
        return $allRows[0];
    }
    return null;
}
function dbOne($sql, $params = [])
{
    global $dbh;
    $res = dbRow($sql, $params);
    if ($res) {
        return array_values($res)[0];
    }
    return null;
}
function dbExec($sql, $params = [])
{
    global $dbh;
    $sth = $dbh->prepare($sql);
    return $sth->execute($params);
}
function dbInsert($table, $data)
{
    global $dbh;
    $fields = array_keys($data);
    $sql = "INSERT INTO $table (`" . implode('`,`', $fields) . "`) VALUES (:" . implode(',:', $fields) . ")";
    $sth = $dbh->prepare($sql);
    $sth->execute($data);
    return $dbh->lastInsertId();
}
function dbUpdate($table, $data, $where)
{
    global $dbh;
    $fields = array_keys($data);
    $sql = "UPDATE $table SET ";
    foreach ($fields as $field) {
        $sql .= "`$field`=:$field,";
    }
    $sql = rtrim($sql, ',');

    //construst where clause
    $whereClause = '';
    $whereParams = [];
    if (is_array($where)) {
        $whereClause = ' WHERE ';
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
                $whereClause .= "`$field` in ($value) AND ";
            } else {
                $whereClause .= "`$field`=:$field AND ";
                $whereParams[$field] = $value;
            }
        }
        $whereClause = rtrim($whereClause, ' AND ');
    } else {
        $whereClause = ' WHERE ' . $where;
    }
    $sql .= $whereClause;
    $sth = $dbh->prepare($sql);
    $sth->execute(array_merge($data, $whereParams));
    return $sth->rowCount();
}
function dbDelete($table, $where)
{
    global $dbh;
    $whereClause = '';
    $whereParams = [];
    if (is_array($where)) {
        $whereClause = ' WHERE ';
        foreach ($where as $field => $value) {
            $whereClause .= "`$field`=:$field AND ";
            $whereParams[$field] = $value;
        }
        $whereClause = rtrim($whereClause, ' AND ');
    } else {
        $whereClause = ' WHERE ' . $where;
    }
    $sql = "DELETE FROM $table $whereClause";
    $sth = $dbh->prepare($sql);
    $sth->execute($whereParams);
    return $sth->rowCount();
}
function dbCount($table, $where)
{
    global $dbh;
    $whereClause = '';
    $whereParams = [];
    if (is_array($where)) {
        $whereClause = ' WHERE ';
        foreach ($where as $field => $value) {
            $whereClause .= "`$field`=:$field AND ";
            $whereParams[$field] = $value;
        }
        $whereClause = rtrim($whereClause, ' AND ');
    } else {
        $whereClause = ' WHERE ' . $where;
    }
    $sql = "SELECT COUNT(*) FROM $table $whereClause";
    $sth = $dbh->prepare($sql);
    $sth->execute($whereParams);
    return $sth->fetchColumn();
}
function dbDropMultipleTables($tables)
{
    global $dbh;
    foreach ($tables as $table) {
        $sql = "DROP TABLE IF EXISTS $table";
        $dbh->exec($sql);
    }
}

function dbSelect($selectArr, $distinct = false)
{
    $distinct = $distinct ? "DISTINCT " : "";
    return "SELECT " . $distinct . implode(',', $selectArr);
}

function dbWhere($conditions)
{
    return count($conditions) == 0 ? "" : "WHERE " . implode(" AND ", $conditions);
}
function dbTables($from, $join)
{
    return "FROM $from " . implode(" ", $join);
}

function extractDataFromBody($mode, $body, $db_key_to_body_key_map = [], $default_values = [])
{
    $extracted = array_map(function ($v) use ($body, $default_values) {
        return $body[$v] ?? $default_values[$v] ?? null;
    }, $db_key_to_body_key_map);
    if ($mode == 'insert') {
        return $extracted;
    }
    if ($mode == 'update') {
        return array_filter($extracted, function ($v) {
            return $v != null;
        });
    }
    return [];
}

function dbSearchStatment($fields = '', $value = '', $key_map = null)
{
    $search_fields = '';
    if ($fields) {
        $search_fields = explode(',', $fields);
    } else if ($key_map) {
        $search_fields = array_values($key_map);
    } else {
        return '';
    }
    $statement = implode(' OR ', array_map(function ($field) use ($value, $key_map) {
        $db_field_name = $key_map["$field"] ?? $field;
        return "$db_field_name LIKE '%$value%'";
    }, $search_fields));
    return "(" . $statement . ")";
}