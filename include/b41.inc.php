<?php

/* Base 41 encode and decode.
 *
 * Base 41 is the first size that gets a 16 bit value in 3 characters.
 * By only using 41 characters (rather than eg base64) we also get to
 * restrict the character set to those that are easy to distinguish.
 */
define('B41_CHARS', '0123456789abcdefghjkmnpqtABCDEFGHJKLMNPQT');

function b41_encode($in)
{
	$len = strlen($in);
	if ($len & 1) {
		$data = unpack('n*/Cbyte', $in);
		$byte = $data['byte'];
		unset($data['byte']);
	} else {
		$data = unpack('n*', $in);
	}

	$s = '';
	foreach ($data as $word) {
		$s .= B41_CHARS[(int)floor($word/1681)];
		$s .= B41_CHARS[(int)floor($word/41) % 41];
		$s .= B41_CHARS[$word % 41];
	}

	if ($len & 1) {
		$s .= B41_CHARS[(int)floor($byte/41)];
		$s .= B41_CHARS[$byte % 41];
	}

	return $s;
}

function b41_decode($s)
{
	$out = '';
	$l = strlen($s);
	switch ($l % 3) {
		case 1:
			return null;
		case 2:
			$odd = true;
			break;
		case 0:
			$odd = false;
			break;
	}

	$word_count = (int)floor($l / 3);

	for ($i=0; $i<$word_count; $i++) {
		$val = 0;
		for ($j = $i*3; $j < ($i*3 + 3); $j++) {
			$x = strpos(B41_CHARS, $s[$j]);
			if ($x === false)
				return null;
			$val = $x + 41 * $val;
		}
		$out .= pack('n', $val);
	}

	if ($odd) {
		$x = strpos(B41_CHARS, $s[$word_count*3]);
		$y = strpos(B41_CHARS, $s[$word_count*3 + 1]);
		if ($x === false || $y === false)
			return null;
		$val = $x*41 + $y;
		$out .= pack('C', $val);
	}

	return $out;
}

/* Verify all characters in $s are in the B41 character set.
 * Optionally check length is equal to $length.
 */
function b41_check($s, $length=0)
{
	$len = strlen($s);
	if ($length && ($len != $length))
		return false;

	/* Length must be 3n or 3n+2 */
	if (!$len || ($len % 3 == 1))
		return false;

	if (strspn($s, B41_CHARS) != $len)
		return false;

	return true;
}

