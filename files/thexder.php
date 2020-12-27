<?php

	$view_level = $_GET['l'] ?? 0;

	$rom = file_get_contents("thexder.nes");
	
	$offsets = [
		0xb4c0,
		0xc3c0,
		0xd2c0,
		0xe1c0,
		0xf0c0
	];

	$e_offsets = [
		0xad86,
		0xae8f,
		0xafaa,
		0xb182,
		0xb2e2,
	];

	// between data:
	// 3840 bytes (0xf00)
	// 255 * 56 / 4:
	// 3570 bytes
	// 270 left over

	$level_width = 256;
	$level_height = 60;
	$level_size = $level_width * $level_height / 4;

	$img = imagecreate($level_width, $level_height);
	$colors = [];
	for ($i = 0; $i < 4; $i++) {
		$colors[$i] = imagecolorallocate($img, 85 * $i, 85 * $i, 85 * $i);
	}

	$ecolor = imagecolorallocate($img, 255, 100, 100);

	$base = $offsets[$view_level];
	$base = $base - 0x8000 + 0x10; // subtract nes address, add header

	for ($p = 0; $p < $level_size; $p++) {
		$offset = $base + $p;
		// printf("<br>%04x -- ", $offset);
		$byte = ord($rom[$offset]);
		// printf("%02x -- ", $byte);

		$x = floor(($p * 4) / $level_height);

		for ($bm = 0; $bm < 4; $bm++) {
			$y = (($p * 4) + $bm) % $level_height;
			$tile = ($byte >> (6 - $bm * 2)) & 0x03;
			// print $tile ." ";
			imagesetpixel($img, $x, $y, $colors[$tile]);
		}
	}

	$mul = 8;
	$img2 = imagecreatetruecolor($level_width * $mul, $level_height * $mul);
	imagecopyresized($img2, $img, 0, 0, 0, 0, $level_width * $mul, $level_height * $mul, $level_width, $level_height);
	$img = $img2;

	$base = $e_offsets[$view_level];
	$base = $base - 0x8000 + 0x10; // subtract nes address, add header

	$ofs = 0;
	while (($y = ord($rom[$base + $ofs])) !== 0x3F && $ofs <= 0x200) {

		$y = ord($rom[$base + $ofs]);
		$x = ord($rom[$base + $ofs + 1]);
		$t = ord($rom[$base + $ofs + 2]);

		imagefilledrectangle($img, $x * $mul, $y * $mul, $x * $mul + $mul - 1, $y * $mul + $mul - 1, 0xc00000);
		imagestring($img, 3, $x * $mul + 1, $y * $mul - 3, sprintf("%X", $t), 0xFFFFFF);

		$ofs += 3;
	}

	header("Content-type: image/png");
	imagepng($img);

