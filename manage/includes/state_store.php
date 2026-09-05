<?php

function trustng_state_path($name)
{
    if ($name !== basename($name)) {
        throw new InvalidArgumentException('Invalid state file name');
    }

    return dirname(__DIR__) . DIRECTORY_SEPARATOR . $name;
}

function trustng_state_read($name, $default = '')
{
    $path = trustng_state_path($name);
    $data = @file_get_contents($path);
    return $data === false ? $default : $data;
}

function trustng_state_lines($name)
{
    $data = trustng_state_read($name);
    return $data === '' ? array() : preg_split('/(?<=\n)/', $data, -1, PREG_SPLIT_NO_EMPTY);
}

function trustng_state_write($name, $data)
{
    $path = trustng_state_path($name);
    $tmp = @tempnam(dirname($path), '.trustng-');
    if ($tmp === false) {
        throw new RuntimeException("Unable to create temporary state file for $name");
    }

    $written = @file_put_contents($tmp, (string) $data, LOCK_EX);
    if ($written === false || !@chmod($tmp, 0664) || !@rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException("Unable to store state file $name");
    }
}

function trustng_state_touch($name)
{
    trustng_state_write($name, '');
}

function trustng_state_exists($name)
{
    return is_file(trustng_state_path($name));
}

function trustng_state_delete($name)
{
    $path = trustng_state_path($name);
    return !file_exists($path) || @unlink($path);
}

function trustng_state_promote($pendingName, $activeName)
{
    $value = trustng_state_read($pendingName, null);
    if ($value === null) {
        throw new RuntimeException("Pending state $pendingName does not exist");
    }
    trustng_state_write($activeName, $value);
    if (!trustng_state_delete($pendingName)) {
        throw new RuntimeException("Unable to remove pending state $pendingName");
    }
}

function trustng_run_panel_script($name)
{
    $path = trustng_state_path($name);
    // panel runs as www-data, scripts write to /etc/unbound/* → need sudo (NOPASSWD /usr/bin/sh)
    return shell_exec('sudo -n sh ' . escapeshellarg($path) . ' 2>&1');
}
