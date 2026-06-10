<?php
/**
 * Perseverance Rover Parachute Code Decoder
 *
 * This script decodes the binary patterns found on the Perseverance rover's parachute.
 * Decodes the 4-ring parachute pattern:
 * - Rings 1-3: 80 bits each, split into eight 10-bit groups → character codes (1=A, 2=B, etc.)
 * - Ring 4: 80 bits each, split into eight 10-bit groups → raw integers
 *
 * More info e.g. https://www.101computing.net/perseverance-parachute-secret-message-encoder/
 * 
 * c2, 10.6.2026 - assisted by cline using qwen3.6:35b, running on premise @ ollama @ AMD Ryzen AI Max+ 395, 128GB RAM ;-)
 *
 */

function decodeRings($rings) {
    $result = [];

    // Decode rings 1-3 as characters (1=A, 2=B, etc.)
    for ($i = 1; $i <= 3; $i++) {
        $bits  = $rings["ring{$i}"];
        $chars = [];

        for ($j = 0; $j < 8; $j++) {
            $chunk = substr($bits, $j * 10, 10);
            $value = bindec($chunk);
                                      // Convert to character: 1 -> A, 2 -> B, etc.
            $char = chr($value + 64); // ord('A') = 65
            if ($value == 127) {      // Special case for 127 (all bits set), treat as space
                $char = ' ';
            }
            $chars[] = $char;
        }

        $ringKey          = "ring{$i}";
        $result[$ringKey] = [
            'decoded' => implode('', $chars),
            'values'  => array_map(function ($c) {return ord($c) - 64;}, $chars),
            'bits'    => $bits,
        ];
    }

    // Decode ring 4: positions 0,1,2,3,5,6 as raw integers, position 4 and 7 as characters
    $result['ring4'] = [
        'decoded' => '',
        'values'  => [],
        'chars'   => [],
        'bits'    => $rings['ring4'],
    ];

    $charPositions = [4, 8]; // 1-indexed positions that decode to characters

    for ($j = 0; $j < 8; $j++) {
        $chunk                       = substr($rings['ring4'], $j * 10, 10);
        $value                       = bindec($chunk);
        $position                    = $j + 1; // 1-indexed
        $result['ring4']['values'][] = $value;

        if (in_array($position, $charPositions)) {
            $char                        = chr($value + 64);
            $result['ring4']['chars'][]  = $char;
            $result['ring4']['decoded'] .= $char;
        }
    }

    return $result;
}

function printDecoded($result) {
    echo "====================================\n";
    echo "  Perseverance Parachute Decoder\n";
    echo "====================================\n\n";

    for ($i = 1; $i <= 3; $i++) {
        $ringKey = "ring{$i}";
        echo "Ring {$i}:\n";
        echo "  Bits:   {$result[$ringKey]['bits']}\n";
        echo "  Values: " . implode(', ', $result[$ringKey]['values']) . "\n";
        echo "  Text:   {$result[$ringKey]['decoded']}\n\n";
    }

    echo "Ring 4:\n";
    echo "  Bits:   {$result['ring4']['bits']}\n";
    // Build mixed display: integers with characters at positions 4 and 8
    $displayValues = $result['ring4']['values'];
    $charPositions = [4, 8];
    $displayStr    = '';
    for ($k = 0; $k < 8; $k++) {
        $pos = $k + 1;
        if ($pos == 4 || $pos == 8) {
            $displayStr .= $result['ring4']['chars'][($pos == 4) ? 0 : 1];
        } else {
            $displayStr .= $displayValues[$k];
        }
        $displayStr .= ($k < 7) ? ', ' : '';
    }
    echo "  Values: {$displayStr}\n";
}

// ============================================
// Example Usage
// ============================================

// Replace these with your actual 80-bit binary strings
// Each ring must be exactly 80 bits (8 groups of 10 bits)

// Original Message from the parachute - reading startet at the top of the parachute, from innermost ring to outermost, and from left to right in each ring:
$rings_1 = [
    'ring1' => '0000000100 0000000001 0000010010 0000000101 0001111111 0001111111 0001111111 0001111111', // DARE
    'ring2' => '0000010100 0000011001 0001111111 0001111111 0000001101 0000001001 0000000111 0000001000', // TY MIGH
    'ring3' => '0001111111 0001111111 0000010100 0000001000 0000001001 0000001110 0000000111 0000010011', // THINGS
    'ring4' => '0000100010 0000001011 0000111010 0000001110 0001110110 0000001010 0000011111 0000010111', // 34, 11, 58, N, 118, 10, 31, W
];

// Original Message from the parachute - reading for rings 2 and 3 started after end of previous ring
$rings_2 = [
    'ring1' => '0000000100 0000000001 0000010010 0000000101 0001111111 0001111111 0001111111 0001111111', // DARE
    'ring2' => '0000001101 0000001001 0000000111 0000001000 0000010100 0000011001 0001111111 0001111111', // MIGHTY
    'ring3' => '0000010100 0000001000 0000001001 0000001110 0000000111 0000010011 0001111111 0001111111', // THINGS
    'ring4' => '0000100010 0000001011 0000111010 0000001110 0001110110 0000001010 0000011111 0000010111', // 34, 11, 58, N, 118, 10, 31, W
];

$rings = $rings_1; // Change to $rings_2 to test the second reading

foreach ($rings as $key => $ring) {
    $rings[$key] = str_replace(' ', '', $ring); // Remove spaces if any
}

$result = decodeRings($rings);
printDecoded($result);
echo "\n==========================================\n";
echo "Have fun, Chris!";
echo "\n==========================================\n";