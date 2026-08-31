<?php
// TRUST-NG — dependency-free CDB (djb cdb format) reader for blacklist.db
// Layout matches create_domain_cdb.py:
//   2048-byte header = 256 x (pos:uint32, size:uint32)
//   record: klen:uint32, vlen:uint32, key, value
//   subtable: size x (hash:uint32, pos:uint32); slot=(h>>8)%size, linear probe.

if (!function_exists('trust_wire')) {
    function trust_wire($domain) {
        $domain = rtrim($domain, '.');
        if ($domain === '') return false;
        $labels = explode('.', $domain);
        $w = '';
        foreach ($labels as $l) {
            $len = strlen($l);
            if ($len === 0 || $len > 63) return false;
            $w .= chr($len) . $l;
        }
        $w .= "\x00";
        return $w;
    }
}

if (!function_exists('trust_cdb_hash')) {
    function trust_cdb_hash($key) {
        $h = 5381;
        $len = strlen($key);
        for ($i = 0; $i < $len; $i++) {
            $h = ((($h << 5) + $h) ^ ord($key[$i])) & 0xFFFFFFFF;
        }
        return $h;
    }
}

if (!function_exists('trust_cdb_get')) {
    function trust_cdb_get($cdb, $key) {
        $fp = @fopen($cdb, 'rb');
        if (!$fp) return false;
        $header = fread($fp, 2048);
        if (strlen($header) < 2048) { fclose($fp); return false; }
        $hd = unpack('V512', $header);
        $h = trust_cdb_hash($key);
        $b = $h & 255;
        $pos = $hd[$b * 2 + 1];
        $size = $hd[$b * 2 + 2];
        if ($size == 0) { fclose($fp); return false; }
        fseek($fp, $pos);
        $table = fread($fp, $size * 8);
        $t = unpack('V' . ($size * 2), $table);
        $slot = ($h >> 8) % $size;
        for ($k = 0; $k < $size; $k++) {
            $eh = $t[$slot * 2 + 1];
            $ep = $t[$slot * 2 + 2];
            if ($ep == 0) break;
            if ($eh == $h) {
                fseek($fp, $ep);
                $meta = fread($fp, 8);
                $m = unpack('V2', $meta);
                $kl = $m[1];
                $kdata = fread($fp, $kl);
                if ($kdata === $key) { fclose($fp); return true; }
            }
            $slot = ($slot + 1) % $size;
        }
        fclose($fp);
        return false;
    }
}

if (!function_exists('trust_cdb_lookup')) {
    // Walk parent domains; return matched qname or null.
    function trust_cdb_lookup($cdb, $domain) {
        $domain = strtolower(rtrim(trim($domain), '.'));
        if ($domain === '') return null;
        if (!preg_match('/^[a-z0-9._-]+$/', $domain)) return null;
        $labels = explode('.', $domain);
        for ($i = 0; $i < count($labels); $i++) {
            $sub = implode('.', array_slice($labels, $i));
            $kw = trust_wire($sub);
            if ($kw === false) continue;
            if (trust_cdb_get($cdb, $kw)) return $sub;
        }
        return null;
    }
}
