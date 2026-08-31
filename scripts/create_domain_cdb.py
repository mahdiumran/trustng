#!/usr/bin/env python3
"""
Create CDB files with domain names in DNS wire format.
Memory-efficient rewrite: streams stdin, keeps only (hash, position) pairs
in compact unsigned-int arrays (one pair per domain, ~8 bytes) instead of
holding the full key list 3-4x in memory. Produces byte-identical layout to
the previous builder so the patched unbound reader (cdb filter) keeps working.
"""
import struct
import sys
import array


def domain_to_wire_format(domain):
    """Convert domain name to DNS wire format"""
    domain = domain.rstrip('.')
    labels = domain.split('.')
    if not labels or not all(labels):
        return None
    wire_format = b''
    for label in labels:
        label_bytes = label.encode('ascii', errors='ignore')
        if len(label_bytes) == 0 or len(label_bytes) > 63:
            return None
        wire_format += bytes([len(label_bytes)]) + label_bytes
    wire_format += b'\x00'
    return wire_format


def calculate_hash(key):
    """Calculate CDB hash for a key (djb2 / djb hash)"""
    h = 5381
    for byte in key:
        h = ((h << 5) + h) ^ byte
    return h & 0xffffffff


def main():
    if len(sys.argv) < 2:
        sys.stderr.write(
            "Usage:\n"
            "  python3 create_domain_cdb.py <output.cdb> < domains.txt\n")
        sys.exit(1)

    output_file = sys.argv[1]

    # Compact per-bucket arrays of (hash, position). position is always >=2048
    # so 0 means "empty slot" when probing the subtable.
    bucket_h = [array.array('I') for _ in range(256)]
    bucket_p = [array.array('I') for _ in range(256)]

    written = 0
    skipped = 0

    with open(output_file, 'wb') as f:
        f.seek(2048)  # reserve 256*8 header

        for line in sys.stdin:
            d = line.strip()
            if not d or d.startswith('#'):
                continue
            wf = domain_to_wire_format(d)
            if not wf:
                skipped += 1
                continue
            h = calculate_hash(wf)
            pos = f.tell()
            f.write(struct.pack('<I', len(wf)))
            f.write(struct.pack('<I', 0))  # value length (empty)
            f.write(wf)
            b = h & 255
            bucket_h[b].append(h)
            bucket_p[b].append(pos)
            written += 1

        header_pos = [0] * 256
        header_size = [0] * 256

        for b in range(256):
            n = len(bucket_h[b])
            if n == 0:
                continue
            subtable_size = n * 2
            st_pos = f.tell()
            arr = bytearray(subtable_size * 8)  # all zero = empty
            for i in range(n):
                h = bucket_h[b][i]
                pos = bucket_p[b][i]
                slot = (h >> 8) % subtable_size
                while arr[slot * 8 + 4] != 0:  # position field != 0 => occupied
                    slot = (slot + 1) % subtable_size
                struct.pack_into('<II', arr, slot * 8, h, pos)
            f.write(arr)
            header_pos[b] = st_pos
            header_size[b] = subtable_size

        # main header (256 * 8 bytes) at offset 0
        f.seek(0)
        for b in range(256):
            f.write(struct.pack('<II', header_pos[b], header_size[b]))

    sys.stderr.write("Created %s with %d domains (%d skipped)\n"
                     % (output_file, written, skipped))
    sys.exit(0 if written > 0 else 1)


if __name__ == "__main__":
    main()
