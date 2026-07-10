<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$active_group  = 'default';
$query_builder = TRUE;

$db['default'] = array(
    'dsn'          => '',
    'hostname'     => 'localhost',
    'username'     => 'root',
    'password'     => '',
    'database'     => 'school_default',
    'dbdriver'     => 'mysqli',
    'dbprefix'     => '',
    'pconnect'     => FALSE,
    'db_debug'     => (ENVIRONMENT !== 'production'),
    'cache_on'     => FALSE,
    'cachedir'     => '',
    'char_set'     => 'utf8',
    'dbcollat'     => 'utf8_general_ci',
    'swap_pre'     => '',
    'encrypt'      => FALSE,
    'compress'     => FALSE,
    'stricton'     => FALSE,
    'failover'     => array(),
    'save_queries' => TRUE,
    'multi_branch' => FALSE,
);

if (!function_exists('api_build_db_config')) {
    function api_build_db_config($hostname, $username, $password, $database, $multi_branch = FALSE)
    {
        return array(
            'dsn'          => '',
            'hostname'     => $hostname,
            'username'     => $username,
            'password'     => $password,
            'database'     => $database,
            'dbdriver'     => 'mysqli',
            'dbprefix'     => '',
            'pconnect'     => FALSE,
            'db_debug'     => FALSE,
            'cache_on'     => FALSE,
            'cachedir'     => '',
            'char_set'     => 'utf8',
            'dbcollat'     => 'utf8_general_ci',
            'swap_pre'     => '',
            'encrypt'      => FALSE,
            'compress'     => FALSE,
            'stricton'     => FALSE,
            'failover'     => array(),
            'save_queries' => TRUE,
            'multi_branch' => $multi_branch,
        );
    }
}

if (!function_exists('api_request_headers')) {
    function api_request_headers()
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                return array_change_key_case($headers, CASE_LOWER);
            }
        }

        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header_key            = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$header_key] = $value;
            }
        }

        return $headers;
    }
}

if (!function_exists('api_request_json')) {
    function api_request_json()
    {
        static $payload = NULL;

        if ($payload !== NULL) {
            return $payload;
        }

        $payload = array();
        $raw     = file_get_contents('php://input');
        if (!empty($raw)) {
            $decoded = json_decode($raw, TRUE);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $payload = $decoded;
            }
        }

        return $payload;
    }
}

if (!function_exists('api_request_value')) {
    function api_request_value($keys)
    {
        $headers = api_request_headers();
        $json    = api_request_json();

        foreach ((array) $keys as $key) {
            $normalized = strtolower($key);
            if (isset($headers[$normalized]) && $headers[$normalized] !== '') {
                return $headers[$normalized];
            }

            if (isset($_POST[$key]) && $_POST[$key] !== '') {
                return $_POST[$key];
            }

            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                return $_GET[$key];
            }

            if (isset($json[$key]) && $json[$key] !== '') {
                return $json[$key];
            }
        }

        return NULL;
    }
}

if (!function_exists('api_find_group_from_token')) {
    function api_find_group_from_token($db_groups, $user_id, $token)
    {
        if ($user_id === NULL || $token === NULL || $user_id === '' || $token === '') {
            return NULL;
        }

        foreach ($db_groups as $group_name => $config) {
            $mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
            if ($mysqli->connect_errno) {
                continue;
            }

            $query = "SELECT users_id FROM users_authentication WHERE users_id = ? AND token = ? LIMIT 1";
            $stmt  = $mysqli->prepare($query);
            if ($stmt) {
                $stmt->bind_param('is', $user_id, $token);
                $stmt->execute();
                $stmt->store_result();
                $matched = $stmt->num_rows > 0;
                $stmt->close();
                $mysqli->close();

                if ($matched) {
                    return $group_name;
                }
            } else {
                $mysqli->close();
            }
        }

        return NULL;
    }
}

if (!function_exists('api_find_group_from_login')) {
    function api_find_group_from_login($db_groups, $username, $password)
    {
        if ($username === NULL || $password === NULL || $username === '' || $password === '') {
            return NULL;
        }

        foreach ($db_groups as $group_name => $config) {
            $mysqli = @new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
            if ($mysqli->connect_errno) {
                continue;
            }

            $query = "SELECT users.id
                FROM users
                LEFT JOIN students ON students.id = users.user_id OR students.parent_id = users.id
                WHERE users.password = ?
                AND (
                    users.username = ?
                    OR students.admission_no = ?
                    OR students.mobileno = ?
                    OR students.email = ?
                    OR students.guardian_phone = ?
                    OR students.guardian_email = ?
                )
                LIMIT 1";

            $stmt = $mysqli->prepare($query);
            if ($stmt) {
                $stmt->bind_param('sssssss', $password, $username, $username, $username, $username, $username, $username);
                $stmt->execute();
                $stmt->store_result();
                $matched = $stmt->num_rows > 0;
                $stmt->close();
                $mysqli->close();

                if ($matched) {
                    return $group_name;
                }
            } else {
                $mysqli->close();
            }
        }

        return NULL;
    }
}

$mydb   = $db['default'];
$mysqli = @new mysqli($mydb['hostname'], $mydb['username'], $mydb['password'], $mydb['database']);

if (!$mysqli->connect_errno) {
    if ($results = $mysqli->query("SHOW TABLES LIKE 'multi_branch'")) {
        if ($results->num_rows == 1) {
            if ($result = $mysqli->query("SELECT * FROM multi_branch WHERE is_verified = 1")) {
                while ($row = $result->fetch_assoc()) {
                    $short_name       = 'branch_' . $row['id'];
                    $db[$short_name] = api_build_db_config(
                        $row['hostname'],
                        $row['username'],
                        $row['password'],
                        $row['database_name'],
                        TRUE
                    );
                }
                $result->close();
            }
        }
        $results->close();
    }
    $mysqli->close();
}

$requested_group = api_request_value(array('db_group', 'branch_group'));
if ($requested_group && isset($db[$requested_group])) {
    $active_group = $requested_group;
}

$requested_database = api_request_value(array('database', 'database_name', 'branch_database'));
if ($active_group === 'default' && $requested_database) {
    foreach ($db as $group_name => $config) {
        if (strcasecmp($config['database'], $requested_database) === 0) {
            $active_group = $group_name;
            break;
        }
    }
}

$requested_branch_id = api_request_value(array('branch_id'));
if ($active_group === 'default' && $requested_branch_id !== NULL) {
    $branch_group = 'branch_' . preg_replace('/[^0-9]/', '', (string) $requested_branch_id);
    if (isset($db[$branch_group])) {
        $active_group = $branch_group;
    }
}

if ($active_group === 'default') {
    $user_id = api_request_value(array('user-id', 'user_id'));
    $token   = api_request_value(array('authorization'));
    $group   = api_find_group_from_token($db, $user_id, $token);
    if ($group !== NULL) {
        $active_group = $group;
    }
}

if ($active_group === 'default') {
    $username = api_request_value(array('username'));
    $password = api_request_value(array('password'));
    $group    = api_find_group_from_login($db, $username, $password);
    if ($group !== NULL) {
        $active_group = $group;
    }
}
