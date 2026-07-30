<?php
/**
 * Pure PHP QR Code Generator
 * Generates real, scannable QR codes using PHP + GD only.
 * No internet required. No external libraries.
 */

// ── Public API ────────────────────────────────────────────────────────────────

function generateQRCode(string $registrationNumber, string $outputPath): bool {
    try {
        $matrix = qr_buildMatrix($registrationNumber, 'M');
        return qr_saveAsPng($matrix, $outputPath, 10, 4);
    } catch (Throwable $e) {
        return qr_textFallback($registrationNumber, $outputPath);
    }
}

function generateRegistrationNumber(): string {
    $date   = date('Ymd');
    $random = strtoupper(substr(md5(uniqid((string)rand(), true)), 0, 6));
    return "EVT-{$date}-{$random}";
}

// ── PNG output ────────────────────────────────────────────────────────────────

function qr_saveAsPng(array $matrix, string $path, int $scale = 10, int $quiet = 4): bool {
    $n   = count($matrix);
    $sz  = ($n + $quiet * 2) * $scale;
    $img = imagecreatetruecolor($sz, $sz);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    imagefill($img, 0, 0, $white);
    foreach ($matrix as $r => $row) {
        foreach ($row as $c => $v) {
            if ($v) {
                $x = ($quiet + $c) * $scale;
                $y = ($quiet + $r) * $scale;
                imagefilledrectangle($img, $x, $y, $x + $scale - 1, $y + $scale - 1, $black);
            }
        }
    }
    $ok = imagepng($img, $path);
    imagedestroy($img);
    return (bool)$ok;
}

function qr_textFallback(string $text, string $path): bool {
    if (!extension_loaded('gd')) return false;
    $img   = imagecreatetruecolor(300, 80);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    imagefill($img, 0, 0, $white);
    imagestring($img, 4, 10, 30, $text, $black);
    $ok = imagepng($img, $path);
    imagedestroy($img);
    return (bool)$ok;
}

// ── GF(256) arithmetic ────────────────────────────────────────────────────────

function qr_gfExp(int $n): int {
    static $t = null;
    if ($t === null) {
        $t = []; $v = 1;
        for ($i = 0; $i < 512; $i++) {
            $t[$i] = $v; $v <<= 1; if ($v & 0x100) $v ^= 0x11D;
        }
    }
    return $t[$n % 255];
}

function qr_gfLog(int $n): int {
    static $t = null;
    if ($t === null) {
        $t = array_fill(0, 256, 0); $v = 1;
        for ($i = 0; $i < 255; $i++) { $t[$v] = $i; $v <<= 1; if ($v & 0x100) $v ^= 0x11D; }
    }
    return $t[$n];
}

function qr_gfMul(int $x, int $y): int {
    if ($x === 0 || $y === 0) return 0;
    return qr_gfExp(qr_gfLog($x) + qr_gfLog($y));
}

// ── Reed-Solomon ──────────────────────────────────────────────────────────────

function qr_rsGenerator(int $n): array {
    $g = [1];
    for ($i = 0; $i < $n; $i++) {
        $g2 = array_fill(0, count($g) + 1, 0);
        $f  = qr_gfExp($i);
        foreach ($g as $j => $v) { $g2[$j] ^= $v; $g2[$j+1] ^= qr_gfMul($v, $f); }
        $g = $g2;
    }
    return $g;
}

function qr_rsEncode(array $msg, int $nsym): array {
    $gen = qr_rsGenerator($nsym);
    $res = array_merge($msg, array_fill(0, $nsym, 0));
    for ($i = 0; $i < count($msg); $i++) {
        $c = $res[$i];
        if ($c) for ($j = 0; $j < count($gen); $j++) $res[$i+$j] ^= qr_gfMul($gen[$j], $c);
    }
    return array_slice($res, count($msg));
}

// ── Version / capacity tables ─────────────────────────────────────────────────
// [total data codewords, ec codewords per block, number of blocks]
function qr_versionInfo(int $v, int $ecl): array {
    // ecl: 0=M,1=L,2=H,3=Q
    $t = [
     //  v  => [ [dataCW, ecPerBlk, numBlks] per ecl M,L,H,Q ]
      1 => [[16,10,1],[19,7,1],[9,17,1],[13,13,1]],
      2 => [[28,16,1],[34,10,1],[16,28,1],[22,22,1]],
      3 => [[44,26,1],[55,15,1],[26,44,2],[34,18,2]],
      4 => [[64,18,2],[80,20,1],[36,64,4],[48,26,2]],
      5 => [[86,24,2],[108,26,1],[46,46,2],[62,18,2]],
      6 => [[108,16,4],[136,18,2],[60,43,4],[76,24,4]],
      7 => [[124,19,4],[156,20,4],[66,49,4],[88,18,2]],
      8 => [[154,22,2],[194,24,2],[86,34,4],[110,22,4]],
      9 => [[182,22,3],[232,30,2],[100,15,4],[132,20,4]],
     10 => [[216,26,4],[274,18,4],[122,19,6],[154,24,6]],
    ];
    return $t[$v][$ecl];
}

function qr_pickVersion(int $dataLen, int $ecl): int {
    $caps = [1=>16,2=>28,3=>44,4=>64,5=>86,6=>108,7=>124,8=>154,9=>182,10=>216];
    // caps above are for M(0). For simplicity use M caps:
    $capsM = [1=>16,2=>28,3=>44,4=>64,5=>86,6=>108,7=>124,8=>154,9=>182,10=>216];
    $capsL = [1=>19,2=>34,3=>55,4=>80,5=>108,6=>136,7=>156,8=>194,9=>232,10=>274];
    $capsH = [1=>9, 2=>16,3=>26,4=>36,5=>46, 6=>60, 7=>66, 8=>86, 9=>100,10=>122];
    $capsQ = [1=>13,2=>22,3=>34,4=>48,5=>62, 6=>76, 7=>88, 8=>110,9=>132,10=>154];
    $map   = [0=>$capsM,1=>$capsL,2=>$capsH,3=>$capsQ];
    $c = $map[$ecl];
    foreach ($c as $v => $cap) if ($dataLen <= $cap) return $v;
    throw new \RuntimeException("Data too long (max ".max($c)." bytes for this ECC level)");
}

// ── Main QR matrix builder ────────────────────────────────────────────────────

function qr_buildMatrix(string $data, string $eclChar = 'M'): array {
    $eclMap  = ['M'=>0,'L'=>1,'H'=>2,'Q'=>3];
    $ecl     = $eclMap[strtoupper($eclChar)] ?? 0;
    $bytes   = array_values(unpack('C*', $data));
    $dataLen = count($bytes);
    $version = qr_pickVersion($dataLen, $ecl);
    $size    = $version * 4 + 17;

    [$totalDC, $ecPerBlk, $numBlks] = qr_versionInfo($version, $ecl);

    // Build data bit stream
    $bits = [];
    foreach ([0,1,0,0] as $b) $bits[] = $b; // byte mode
    for ($i = 7; $i >= 0; $i--) $bits[] = ($dataLen >> $i) & 1;
    foreach ($bytes as $byte) for ($i = 7; $i >= 0; $i--) $bits[] = ($byte >> $i) & 1;
    $maxBits = $totalDC * 8;
    for ($i = 0; $i < 4 && count($bits) < $maxBits; $i++) $bits[] = 0;
    while (count($bits) % 8) $bits[] = 0;
    for ($pi = 0; count($bits) < $maxBits; $pi++) {
        $pb = ($pi % 2 === 0) ? 0xEC : 0x11;
        for ($i = 7; $i >= 0; $i--) $bits[] = ($pb >> $i) & 1;
    }

    // Pack to codewords
    $cw = [];
    for ($i = 0; $i < count($bits); $i += 8) {
        $b = 0;
        for ($j = 0; $j < 8; $j++) $b = ($b << 1) | ($bits[$i+$j] ?? 0);
        $cw[] = $b;
    }

    // EC per block
    $blkSize = intdiv($totalDC, $numBlks);
    $ecAll   = [];
    for ($b = 0; $b < $numBlks; $b++) {
        $blk   = array_slice($cw, $b * $blkSize, $blkSize);
        $ecAll = array_merge($ecAll, qr_rsEncode($blk, $ecPerBlk));
    }
    $codewords = array_merge($cw, $ecAll);

    // Build matrix
    $mat = array_fill(0, $size, array_fill(0, $size, -1));

    // Finder patterns + separators
    qr_addFinder($mat, 0, 0);
    qr_addFinder($mat, 0, $size - 7);
    qr_addFinder($mat, $size - 7, 0);

    // Timing
    for ($i = 8; $i < $size - 8; $i++) {
        $v = ($i % 2 === 0) ? 1 : 0;
        if ($mat[6][$i] < 0) $mat[6][$i] = $v;
        if ($mat[$i][6] < 0) $mat[$i][6] = $v;
    }

    // Dark module
    $mat[$size - 8][8] = 1;

    // Alignment patterns
    if ($version >= 2) {
        $ap = qr_alignCoords($version);
        foreach ($ap as $ar) foreach ($ap as $ac) {
            if ($ar <= 8 && $ac <= 8) continue;
            if ($ar <= 8 && $ac >= $size - 9) continue;
            if ($ar >= $size - 9 && $ac <= 8) continue;
            qr_addAlignment($mat, $ar, $ac);
        }
    }

    // Reserve format areas (mark as 2 so mask skips them)
    for ($i = 0; $i <= 8; $i++) {
        if ($mat[8][$i]   < 0) $mat[8][$i]   = 2;
        if ($mat[$i][8]   < 0) $mat[$i][8]   = 2;
    }
    for ($i = $size - 8; $i < $size; $i++) if ($mat[8][$i] < 0) $mat[8][$i] = 2;
    for ($i = $size - 7; $i < $size; $i++) if ($mat[$i][8] < 0) $mat[$i][8] = 2;

    // Place data
    $bi = 0; $upward = true;
    for ($col = $size - 1; $col >= 1; $col -= 2) {
        if ($col === 6) $col = 5;
        for ($k = 0; $k < $size; $k++) {
            $row = $upward ? $size - 1 - $k : $k;
            foreach ([$col, $col - 1] as $c) {
                if ($mat[$row][$c] < 0) {
                    $mat[$row][$c] = $codewords ? (($codewords[intdiv($bi,8)] >> (7 - $bi%8)) & 1) : 0;
                    $bi++;
                }
            }
        }
        $upward = !$upward;
    }

    // Pick best mask
    $best = 0; $bestP = PHP_INT_MAX;
    for ($mk = 0; $mk < 8; $mk++) {
        $tmp = qr_mask($mat, $mk, $size);
        qr_formatBits($tmp, $ecl, $mk, $size);
        $p = qr_pen($tmp, $size);
        if ($p < $bestP) { $bestP = $p; $best = $mk; }
    }
    $final = qr_mask($mat, $best, $size);
    qr_formatBits($final, $ecl, $best, $size);
    return $final;
}

function qr_addFinder(array &$m, int $r, int $c): void {
    $p = [[1,1,1,1,1,1,1],[1,0,0,0,0,0,1],[1,0,1,1,1,0,1],[1,0,1,1,1,0,1],[1,0,1,1,1,0,1],[1,0,0,0,0,0,1],[1,1,1,1,1,1,1]];
    foreach ($p as $dr => $row) foreach ($row as $dc => $v) $m[$r+$dr][$c+$dc] = $v;
    // separator
    $size = count($m);
    for ($i = -1; $i <= 7; $i++) {
        if ($r+7 < $size && $c+$i >= 0 && $c+$i < $size && $m[$r+7][$c+$i] < 0) $m[$r+7][$c+$i] = 0;
        if ($r+$i >= 0 && $r+$i < $size && $c+7 < $size && $m[$r+$i][$c+7] < 0) $m[$r+$i][$c+7] = 0;
    }
}

function qr_addAlignment(array &$m, int $r, int $c): void {
    $p = [[1,1,1,1,1],[1,0,0,0,1],[1,0,1,0,1],[1,0,0,0,1],[1,1,1,1,1]];
    foreach ($p as $dr => $row) foreach ($row as $dc => $v) $m[$r-2+$dr][$c-2+$dc] = $v;
}

function qr_alignCoords(int $v): array {
    $t = [2=>[6,18],3=>[6,22],4=>[6,26],5=>[6,30],6=>[6,34],
          7=>[6,22,38],8=>[6,24,42],9=>[6,26,46],10=>[6,28,50]];
    return $t[$v] ?? [6];
}

function qr_mask(array $mat, int $mk, int $size): array {
    $m = $mat;
    for ($r = 0; $r < $size; $r++) for ($c = 0; $c < $size; $c++) {
        if ($m[$r][$c] > 1) continue;
        $flip = match($mk) {
            0 => ($r+$c)%2===0, 1 => $r%2===0, 2 => $c%3===0,
            3 => ($r+$c)%3===0, 4 => (intdiv($r,2)+intdiv($c,3))%2===0,
            5 => ($r*$c)%2+($r*$c)%3===0,
            6 => (($r*$c)%2+($r*$c)%3)%2===0,
            7 => (($r+$c)%2+($r*$c)%3)%2===0,
        };
        if ($flip) $m[$r][$c] ^= 1;
    }
    return $m;
}

function qr_formatBits(array &$m, int $ecl, int $mk, int $size): void {
    $eclB = [0=>0b00,1=>0b01,2=>0b10,3=>0b11]; // M=00,L=01,H=10,Q=11
    $d = ($eclB[$ecl] << 3) | $mk;
    $g = 0b10100110111;
    $r = $d << 10;
    for ($i = 4; $i >= 0; $i--) if ($r & (1<<($i+10))) $r ^= ($g<<$i);
    $fmt = (($d<<10)|$r) ^ 0b101010000010010;

    $pos = [[8,0],[8,1],[8,2],[8,3],[8,4],[8,5],[8,7],[8,8],[7,8],[5,8],[4,8],[3,8],[2,8],[1,8],[0,8]];
    for ($i = 0; $i < 15; $i++) {
        $bit = ($fmt >> (14-$i)) & 1;
        [$fr,$fc] = $pos[$i];
        $m[$fr][$fc] = $bit;
        if ($i < 7)  $m[8][$size-1-$i]    = $bit;
        else         $m[$size-15+$i][8]    = $bit;
    }
}

function qr_pen(array $m, int $sz): int {
    $p = 0;
    for ($r = 0; $r < $sz; $r++) {
        $run = 1;
        for ($c = 1; $c < $sz; $c++) {
            if ($m[$r][$c]===$m[$r][$c-1]){$run++;if($run===5)$p+=3;elseif($run>5)$p++;}else $run=1;
        }
    }
    for ($c = 0; $c < $sz; $c++) {
        $run = 1;
        for ($r = 1; $r < $sz; $r++) {
            if ($m[$r][$c]===$m[$r-1][$c]){$run++;if($run===5)$p+=3;elseif($run>5)$p++;}else $run=1;
        }
    }
    for ($r = 0; $r < $sz-1; $r++) for ($c = 0; $c < $sz-1; $c++) {
        $v=$m[$r][$c]; if($v===$m[$r][$c+1]&&$v===$m[$r+1][$c]&&$v===$m[$r+1][$c+1])$p+=3;
    }
    return $p;
}
