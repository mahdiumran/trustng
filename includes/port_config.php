<?php

require_once __DIR__ . '/state_store.php';

function trustng_validate_port($value, $label = 'Port')
{
    if (!is_string($value) && !is_int($value)) {
        throw new InvalidArgumentException("$label tidak valid");
    }

    $value = (string) $value;
    if (!preg_match('/^[0-9]{1,5}$/', $value)) {
        throw new InvalidArgumentException("$label harus berupa angka 1-65535");
    }

    $port = (int) $value;
    if ($port < 1 || $port > 65535) {
        throw new InvalidArgumentException("$label harus berada pada rentang 1-65535");
    }

    return (string) $port;
}

function trustng_validate_port_pair($sshPort, $webPort)
{
    if ($sshPort === $webPort) {
        throw new InvalidArgumentException('Port SSH dan Cpanel tidak boleh sama');
    }

    $reserved = array('53', '161');
    if (in_array($sshPort, $reserved, true) || in_array($webPort, $reserved, true)) {
        throw new InvalidArgumentException('Port 53 dan 161 dipakai oleh layanan sistem');
    }
}

function trustng_port_read($name, $default)
{
    $value = trim(trustng_state_read($name, $default));
    try {
        return trustng_validate_port($value);
    } catch (InvalidArgumentException $e) {
        return (string) $default;
    }
}

function trustng_detect_ssh_port($default = '22')
{
    $output = array();
    $status = 1;
    @exec('/usr/sbin/sshd -T 2>/dev/null', $output, $status);
    if ($status === 0) {
        foreach ($output as $line) {
            if (preg_match('/^port\s+([0-9]{1,5})$/', trim($line), $match)) {
                try {
                    return trustng_validate_port($match[1], 'Port SSH');
                } catch (InvalidArgumentException $e) {
                    break;
                }
            }
        }
    }
    return trustng_port_read('ssh.port', $default);
}

function trustng_request_host()
{
    $host = isset($_SERVER['HTTP_HOST']) ? trim($_SERVER['HTTP_HOST']) : '';
    if ($host !== '' && preg_match('/^\[([0-9a-f:]+)\](?::[0-9]{1,5})?$/i', $host, $match)) {
        return '[' . $match[1] . ']';
    }
    if ($host !== '') {
        $name = preg_replace('/:[0-9]{1,5}$/', '', $host);
        if (preg_match('/^[a-z0-9.-]+$/i', $name)) {
            return $name;
        }
    }

    $fallback = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1';
    return strpos($fallback, ':') !== false ? '[' . trim($fallback, '[]') . ']' : $fallback;
}

function trustng_panel_url($port, $path = '/')
{
    $port = trustng_validate_port($port, 'Port Cpanel');
    if ($path === '' || $path[0] !== '/') {
        $path = '/';
    }
    return 'https://' . trustng_request_host() . ':' . $port . $path;
}

function trustng_referer_is_same_panel($referer, $allowedPath = null)
{
    if (!is_string($referer) || $referer === '') return false;
    $parts = parse_url($referer);
    if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https') return false;

    $host = strtolower(trim($parts['host'] ?? '', '[]'));
    $expected = strtolower(trim(trustng_request_host(), '[]'));
    if ($host !== $expected) return false;

    $ports = array(
        (int) trustng_port_read('ssl.port', '40443'),
        isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 0,
        40443,
    );
    $port = isset($parts['port']) ? (int) $parts['port'] : 443;
    if (!in_array($port, $ports, true)) return false;

    return $allowedPath === null || ($parts['path'] ?? '/') === $allowedPath;
}

function trustng_nginx_with_port($config, $oldPort, $newPort)
{
    $oldPort = preg_quote(trustng_validate_port($oldPort), '/');
    $newPort = trustng_validate_port($newPort);
    $count = 0;
    $updated = preg_replace_callback(
        '/^(\s*listen\s+)(\[::\]:)?' . $oldPort . '(\s+[^;]*\bssl\b[^;]*;\s*)$/mi',
        function ($match) use ($newPort, &$count) {
            $count++;
            return $match[1] . ($match[2] ?? '') . $newPort . $match[3];
        },
        $config
    );
    if ($count < 1 || $updated === null) {
        throw new RuntimeException('Listener HTTPS panel tidak ditemukan pada konfigurasi nginx');
    }
    return $updated;
}
