<?php

function get($key, $default) {
    if(array_key_exists($key, $_GET)) {
        return htmlspecialchars($_GET[$key]);
    }

    return $default;
}

$cur = get('currency', 'EUR');
$iban = $cur == 'EUR' ? 'SK2783300000002601297398' : 'CZ6620100000002502182480';
$price = get('price', 0);
$msg = get('note', '');
$ref = get('reference', '');
$site = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
$data = NULL;

if ($cur == 'CZK') {
  $data = 'SPD*1.0*ACC:'.$iban.'*AM:'.$price.'*CC:'.$cur.'*MSG:'.$msg;
}
else {
	$d = implode("\t", array(
		0 => '',
		1 => '1',
		2 => implode("\t", array(
			true,
			$price,						// SUMA
			$cur,						// JEDNOTKA
			date("Ymd", strtotime('+5 hours')),					// DATUM
			'',							// VARIABILNY SYMBOL
			'',							// KONSTANTNY SYMBOL
			'',							// SPECIFICKY SYMBOL
			$ref,			            // PAYMENT REFERENCE
			$msg,						// POZNAMKA
			'1',
			$iban,						// IBAN
			'FIOBCZPPX',				// SWIFT
			'0',
			'0'
		))
	));

	$d = strrev(hash("crc32b", $d, TRUE)) . $d;
	$x = proc_open("/usr/bin/xz '--format=raw' '--lzma1=lc=3,lp=0,pb=2,dict=128KiB' '-c' '-'", [0 => ["pipe", "r"], 1 => ["pipe", "w"]], $p);
	fwrite($p[0], $d);
	fclose($p[0]);
	$o = stream_get_contents($p[1]);
	fclose($p[1]);
	proc_close($x);
	$d = bin2hex("\x00\x00" . pack("v", strlen($d)) . $o);
	$b = "";
	for ($i = 0;$i < strlen($d);$i++) {
		$b .= str_pad(base_convert($d[$i], 16, 2), 4, "0", STR_PAD_LEFT);
	}
	$l = strlen($b);
	$r = $l % 5;
	if ($r > 0) {
		$p = 5 - $r;
		$b .= str_repeat("0", $p);
		$l += $p;
	}
	$l = $l / 5;
	$d = str_repeat("_", $l);
	for ($i = 0;$i < $l;$i += 1) {
		$d[$i] = "0123456789ABCDEFGHIJKLMNOPQRSTUV"[bindec(substr($b, $i * 5, 5))];
	}

	$data = $d;
	// var_dump($data); die;
}

//echo $data; die;

//header('Content-Type: image/png');

//echo $site.'/camp/qr.php?size=200&e=M&t=default&d='.$data;
//die;
//echo file_get_contents($site.'/camp/qr.php?size=200&e=M&t=default&d='.$data);
//exit;

//	Create the image
$imageSize = 200;

//	Default Image Border
$mib = 0;

//	Path to the data and image files
$path			= __DIR__."/data";		//	You must set path to data files.
$image_path	= __DIR__."/image";	//	You must set path to QRcode frame images.

$qrcode_error_correct = 'm';
$qrcode_module_size = 4;
$qrcode_version = NULL;
$qrcode_image_type = 'png';

$base_image = createQR($data, $imageSize);

$output_image = imagecreate($imageSize, $imageSize);

//	Resize the QR Code 
imagecopyresized($output_image, $base_image, 0, 0, 0, 0, $imageSize, $imageSize, $mib, $mib);

//	Set the correct content header
header('Content-Type: image/png');
//	Send the image to the browser
ImagePng($output_image);

imagedestroy($base_image);		//	Clean up after ourselves!
imagedestroy($output_image);
exit;

function createQR($qrcode_data_string, $qrcode_image_size)
{
	//	Global paramaters
	//$qrcode_data_string, 
	global	$qrcode_error_correct, 
				$qrcode_module_size, 
				$qrcode_version, 
				$qrcode_image_type, 
				//$qrcode_image_size, 
				$qrcode_structureappend_n, 
				$qrcode_structureappend_m, 
				$qrcode_structureappend_parity,
				$qrcode_structureappend_originaldata,
				$mib,
				$image_path,
				$path;

	/*var_dump($qrcode_data_string);
	var_dump($qrcode_error_correct);
	var_dump($qrcode_module_size);
	var_dump($qrcode_version);
	var_dump($qrcode_image_type);
	var_dump($qrcode_image_size);
	var_dump($qrcode_structureappend_n);
	var_dump($qrcode_structureappend_m);
	var_dump($qrcode_structureappend_parity);
	var_dump($qrcode_structureappend_originaldata);
	die;*/

	//	This program supports an upper limit of version 40.
	//	Version 40 has 177 rows and 177 columns, and can encode up to 4,296 alphanumeric characters
	//	See http://code.google.com/apis/chart/infographics/docs/qr_codes.html
	$version_ul	= 40;
	
	
	//	Create the QR Code
	$data_length = strlen($qrcode_data_string);
	
	$data_counter = 0;
	if ($qrcode_structureappend_n>1
	 && $qrcode_structureappend_n<=16
	 && $qrcode_structureappend_m>0
	 && $qrcode_structureappend_m<=16){

		$data_value[0]=3;
		$data_bits[0]=4;

		$data_value[1]=$qrcode_structureappend_m-1;
		$data_bits[1]=4;

		$data_value[2]=$qrcode_structureappend_n-1;
		$data_bits[2]=4;


		$originaldata_length=strlen($qrcode_structureappend_originaldata);
		
		if ($originaldata_length>1){
			$qrcode_structureappend_parity=0;
			$i=0;
			while ($i<$originaldata_length){
				$qrcode_structureappend_parity=($qrcode_structureappend_parity ^ ord(substr($qrcode_structureappend_originaldata,$i,1)));
				$i++;
			}
		}

		$data_value[3]=$qrcode_structureappend_parity;
		$data_bits[3]=8;

		$data_counter=4;
	}

	$data_bits[$data_counter]=4;

	/*  --- determine encode mode */

	if (preg_match("/[^0-9]/",$qrcode_data_string)!=0){
		if (preg_match("/[^0-9A-Z \$\*\%\+\.\/\:\-]/",$qrcode_data_string)!=0) {


		 /*  --- 8bit byte mode */

			$codeword_num_plus = array(	0,0,0,0,0,0,0,0,0,0,
													8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,
													8,8,8,8,8,8,8,8,8,8,8,8,8,8);

			$data_value[$data_counter]=4;
			$data_counter++;
			$data_value[$data_counter]=$data_length;
			$data_bits[$data_counter]=8;   /* #version 1-9 */
			$codeword_num_counter_value=$data_counter;

			$data_counter++;
			$i=0;
			while ($i<$data_length){
				$data_value[$data_counter]=ord(substr($qrcode_data_string,$i,1));
				$data_bits[$data_counter]=8;
				$data_counter++;
				$i++;
			}
		} else {

		/* ---- alphanumeric mode */

			$codeword_num_plus = array(0,0,0,0,0,0,0,0,0,0,
												2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,
												4,4,4,4,4,4,4,4,4,4,4,4,4,4);

			$data_value[$data_counter]=2;
			$data_counter++;
			$data_value[$data_counter]=$data_length;
			$data_bits[$data_counter]=9;  /* #version 1-9 */
			$codeword_num_counter_value=$data_counter;


			$alphanumeric_character_hash = array(
														"0"=>0,"1"=>1,"2"=>2,"3"=>3,"4"=>4,
														"5"=>5,"6"=>6,"7"=>7,"8"=>8,"9"=>9,
														"A"=>10,"B"=>11,"C"=>12,"D"=>13,"E"=>14,
														"F"=>15,"G"=>16,"H"=>17,"I"=>18,"J"=>19,
														"K"=>20,"L"=>21,"M"=>22,"N"=>23,"O"=>24,
														"P"=>25,"Q"=>26,"R"=>27,"S"=>28,"T"=>29,
														"U"=>30,"V"=>31,"W"=>32,"X"=>33,"Y"=>34,
														"Z"=>35," "=>36,"$"=>37,"%"=>38,"*"=>39,
														"+"=>40,"-"=>41,"."=>42,"/"=>43,":"=>44);

			$i=0;
			$data_counter++;
			while ($i<$data_length){
				if (($i %2)==0){
					$data_value[$data_counter]=$alphanumeric_character_hash[substr($qrcode_data_string,$i,1)];
					$data_bits[$data_counter]=6;
				} else {
					$data_value[$data_counter]=$data_value[$data_counter]*45+$alphanumeric_character_hash[substr($qrcode_data_string,$i,1)];
					$data_bits[$data_counter]=11;
					$data_counter++;
				}
				$i++;
			}
		}
	} else {

		/* ---- numeric mode */

		$codeword_num_plus = array (	0,0,0,0,0,0,0,0,0,0,
												2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,
												4,4,4,4,4,4,4,4,4,4,4,4,4,4);

		$data_value[$data_counter]=1;
		$data_counter++;
		$data_value[$data_counter]=$data_length;
		$data_bits[$data_counter]=10;   /* #version 1-9 */
		$codeword_num_counter_value=$data_counter;

		$i=0;
		$data_counter++;
		while ($i<$data_length){
			if (($i % 3)==0){
				$data_value[$data_counter]=substr($qrcode_data_string,$i,1);
				$data_bits[$data_counter]=4;
			} else {
				 $data_value[$data_counter]=$data_value[$data_counter]*10+substr($qrcode_data_string,$i,1);
			 if (($i % 3)==1){
				 $data_bits[$data_counter]=7;
			 } else {
				 $data_bits[$data_counter]=10;
				 $data_counter++;
			 }
			}
			$i++;
		}
	}
	if (@$data_bits[$data_counter]>0) {
		$data_counter++;
	}
	$i=0;
	$total_data_bits=0;
	while($i<$data_counter){
		$total_data_bits+=$data_bits[$i];
		$i++;
	}

	//	Error Correction
	$ecc_character_hash = array (
									"l"=>"1",
									"m"=>"0",
									"q"=>"3",
									"h"=>"2");

	$ec=@$ecc_character_hash[$qrcode_error_correct]; 

	if (!$ec){$ec=0;}

	$max_data_bits_array=array(
									0,128,224,352,512,688,864,992,1232,1456,1728,
									2032,2320,2672,2920,3320,3624,4056,4504,5016,5352,
									5712,6256,6880,7312,8000,8496,9024,9544,10136,10984,
									11640,12328,13048,13800,14496,15312,15936,16816,17728,18672,

									152,272,440,640,864,1088,1248,1552,1856,2192,
									2592,2960,3424,3688,4184,4712,5176,5768,6360,6888,
									7456,8048,8752,9392,10208,10960,11744,12248,13048,13880,
									14744,15640,16568,17528,18448,19472,20528,21616,22496,23648,

									72,128,208,288,368,480,528,688,800,976,
									1120,1264,1440,1576,1784,2024,2264,2504,2728,3080,
									3248,3536,3712,4112,4304,4768,5024,5288,5608,5960,
									6344,6760,7208,7688,7888,8432,8768,9136,9776,10208,

									104,176,272,384,496,608,704,880,1056,1232,
									1440,1648,1952,2088,2360,2600,2936,3176,3560,3880,
									4096,4544,4912,5312,5744,6032,6464,6968,7288,7880,
									8264,8920,9368,9848,10288,10832,11408,12016,12656,13328
								);

	//	Which version of the QR code do we need to generate?
	//	If the user hasn't specified a version, autogenenerate one.
	if (!is_numeric($qrcode_version)){
		$qrcode_version=0;
	}
	if (!$qrcode_version){
		/* #--- auto version select */
		$i=1+40*$ec;
		$j=$i+39;
		$qrcode_version=1; 
		while ($i<=$j){
			if (($max_data_bits_array[$i])>=$total_data_bits+$codeword_num_plus[$qrcode_version]	 ){
				$max_data_bits=$max_data_bits_array[$i];
				break;
			}
		 $i++;
		 $qrcode_version++;
		}
	} else {
		 $max_data_bits=$max_data_bits_array[$qrcode_version+40*$ec];
	}
	if ($qrcode_version>$version_ul){
	  trigger_error("QRcode : too large version.",E_USER_ERROR);
	}

	$total_data_bits+=$codeword_num_plus[$qrcode_version];
		$data_bits[$codeword_num_counter_value]+=$codeword_num_plus[$qrcode_version];

	$max_codewords_array=array(
									0,26,44,70,100,134,172,196,242,
									292,346,404,466,532,581,655,733,815,901,991,1085,1156,
									1258,1364,1474,1588,1706,1828,1921,2051,2185,2323,2465,
									2611,2761,2876,3034,3196,3362,3532,3706);

									
	$max_codewords=$max_codewords_array[$qrcode_version];
	$max_modules_1side=17+($qrcode_version <<2);

	$matrix_remain_bit=array(
								0,0,7,7,7,7,7,0,0,0,0,0,0,0,3,3,3,3,3,3,3,
								4,4,4,4,4,4,4,3,3,3,3,3,3,3,0,0,0,0,0,0);

	/* ---- read version ECC data file */

	$byte_num=$matrix_remain_bit[$qrcode_version]+($max_codewords << 3);
	$filename=$path."/qrv".$qrcode_version."_".$ec.".dat";
	$fp1 = fopen ($filename, "rb");
		$matx=fread($fp1,$byte_num);
		$maty=fread($fp1,$byte_num);
		$masks=fread($fp1,$byte_num);
		$fi_x=fread($fp1,15);
		$fi_y=fread($fp1,15);
		$rs_ecc_codewords=ord(fread($fp1,1));
		$rso=fread($fp1,128);
	fclose($fp1);

	$matrix_x_array=unpack("C*",$matx);
	$matrix_y_array=unpack("C*",$maty);
	$mask_array=unpack("C*",$masks);

	$rs_block_order=unpack("C*",$rso);

	$format_information_x2=unpack("C*",$fi_x);
	$format_information_y2=unpack("C*",$fi_y);

	$format_information_x1=array(0,1,2,3,4,5,7,8,8,8,8,8,8,8,8);
	$format_information_y1=array(8,8,8,8,8,8,8,8,7,5,4,3,2,1,0);

	$max_data_codewords=($max_data_bits >>3);

	$filename = $path."/rsc".$rs_ecc_codewords.".dat";
	$fp0 = fopen ($filename, "rb");
	$i=0;
	while ($i<256) {
		$rs_cal_table_array[$i]=fread ($fp0,$rs_ecc_codewords);
		$i++;
	}
	fclose ($fp0);

	/*  --- set terminator */

	if ($total_data_bits<=$max_data_bits-4){
		$data_value[$data_counter]=0;
		$data_bits[$data_counter]=4;
	} else {
		if ($total_data_bits<$max_data_bits){
		$data_value[$data_counter]=0;
			$data_bits[$data_counter]=$max_data_bits-$total_data_bits;
		} else {
			if ($total_data_bits>$max_data_bits){
			trigger_error("QRcode : Overflow error",E_USER_ERROR);
			exit;
			}
		}
	}

	/* ----divide data by 8bit */

	$i=0;
	$codewords_counter=0;
	$codewords[0]=0;
	$remaining_bits=8;

	while ($i<=$data_counter) {
		$buffer=@$data_value[$i];
		$buffer_bits=@$data_bits[$i];

		$flag=1;
		while ($flag) {
			if ($remaining_bits>$buffer_bits){  
				$codewords[$codewords_counter]=((@$codewords[$codewords_counter]<<$buffer_bits) | $buffer);
				$remaining_bits-=$buffer_bits;
				$flag=0;
			} else {
				$buffer_bits-=$remaining_bits;
				$codewords[$codewords_counter]=(($codewords[$codewords_counter] << $remaining_bits) | ($buffer >> $buffer_bits));

				if ($buffer_bits==0) {
					$flag=0;
				} else {
					$buffer= ($buffer & ((1 << $buffer_bits)-1) );
					$flag=1;   
				}

				$codewords_counter++;
				if ($codewords_counter<$max_data_codewords-1){
					$codewords[$codewords_counter]=0;
				}
				$remaining_bits=8;
			}
		}
		$i++;
	}
	if ($remaining_bits!=8) {
		$codewords[$codewords_counter]=$codewords[$codewords_counter] << $remaining_bits;
	} else {
		$codewords_counter--;
	}

	/* ----  set padding character */

	if ($codewords_counter<$max_data_codewords-1){
		$flag=1;
		while ($codewords_counter<$max_data_codewords-1){
			$codewords_counter++;
			if ($flag==1) {
				$codewords[$codewords_counter]=236;
			} else {
				$codewords[$codewords_counter]=17;
			}
			$flag=$flag*(-1);
		}
	}

	/* ---- RS-ECC prepare */

	$i=0;
	$j=0;
	$rs_block_number=0;
	$rs_temp[0]="";

	while($i<$max_data_codewords){

		$rs_temp[$rs_block_number].=chr($codewords[$i]);
		$j++;

		if ($j>=$rs_block_order[$rs_block_number+1]-$rs_ecc_codewords){
			$j=0;
			$rs_block_number++;
			$rs_temp[$rs_block_number]="";
		}
		$i++;
	}


	/*
	#
	# RS-ECC main
	#
	*/

	$rs_block_number=0;
	$rs_block_order_num=count($rs_block_order);

	while ($rs_block_number<$rs_block_order_num){

		$rs_codewords=$rs_block_order[$rs_block_number+1];
		$rs_data_codewords=$rs_codewords-$rs_ecc_codewords;

		$rstemp=$rs_temp[$rs_block_number].str_repeat(chr(0),$rs_ecc_codewords);
		$padding_data=str_repeat(chr(0),$rs_data_codewords);

		$j=$rs_data_codewords;
		while($j>0){
			$first=ord(substr($rstemp,0,1));

			if ($first){
				$left_chr=substr($rstemp,1);
				$cal=$rs_cal_table_array[$first].$padding_data;
				$rstemp=$left_chr ^ $cal;
			} else {
				$rstemp=substr($rstemp,1);
			}

			$j--;
		}

		$codewords=array_merge($codewords,unpack("C*",$rstemp));

		$rs_block_number++;
	}

	/* ---- flash matrix */

	$i=0;
	while ($i<$max_modules_1side){
		$j=0;
		while ($j<$max_modules_1side){
			$matrix_content[$j][$i]=0;
			$j++;
		}
		$i++;
	}

	/* --- attach data */

	$i=0;
	while ($i<$max_codewords){
		$codeword_i=$codewords[$i];
		$j=8;
		while ($j>=1){
			$codeword_bits_number=($i << 3) +  $j;
			$matrix_content[ $matrix_x_array[$codeword_bits_number] ][ $matrix_y_array[$codeword_bits_number] ]=((255*($codeword_i & 1)) ^ $mask_array[$codeword_bits_number] ); 
			$codeword_i= $codeword_i >> 1;
			$j--;
		}
		$i++;
	}

	$matrix_remain=$matrix_remain_bit[$qrcode_version];
	while ($matrix_remain){
		$remain_bit_temp = $matrix_remain + ( $max_codewords <<3);
		$matrix_content[ $matrix_x_array[$remain_bit_temp] ][ $matrix_y_array[$remain_bit_temp] ]  =  ( 255 ^ $mask_array[$remain_bit_temp] );
		$matrix_remain--;
	}

	#--- mask select

	$min_demerit_score=0;
		$hor_master="";
		$ver_master="";
		$k=0;
		while($k<$max_modules_1side){
			$l=0;
			while($l<$max_modules_1side){
				$hor_master=$hor_master.chr($matrix_content[$l][$k]);
				$ver_master=$ver_master.chr($matrix_content[$k][$l]);
				$l++;
			}
			$k++;
		}
	$i=0;
	$all_matrix=$max_modules_1side * $max_modules_1side; 
	while ($i<8){
		$demerit_n1=0;
		$ptn_temp=array();
		$bit= 1<< $i;
		$bit_r=(~$bit)&255;
		$bit_mask=str_repeat(chr($bit),$all_matrix);
		$hor = $hor_master & $bit_mask;
		$ver = $ver_master & $bit_mask;

		$ver_shift1=$ver.str_repeat(chr(170),$max_modules_1side);
		$ver_shift2=str_repeat(chr(170),$max_modules_1side).$ver;
		$ver_shift1_0=$ver.str_repeat(chr(0),$max_modules_1side);
		$ver_shift2_0=str_repeat(chr(0),$max_modules_1side).$ver;
		$ver_or=chunk_split(~($ver_shift1 | $ver_shift2),$max_modules_1side,chr(170));
		$ver_and=chunk_split(~($ver_shift1_0 & $ver_shift2_0),$max_modules_1side,chr(170));

		$hor=chunk_split(~$hor,$max_modules_1side,chr(170));
		$ver=chunk_split(~$ver,$max_modules_1side,chr(170));
		$hor=$hor.chr(170).$ver;

		$n1_search="/".str_repeat(chr(255),5)."+|".str_repeat(chr($bit_r),5)."+/";
		$n3_search=chr($bit_r).chr(255).chr($bit_r).chr($bit_r).chr($bit_r).chr(255).chr($bit_r);

		$demerit_n3=substr_count($hor,$n3_search)*40;
		$demerit_n4=floor(abs(( (100* (substr_count($ver,chr($bit_r))/($byte_num)) )-50)/5))*10;


		$n2_search1="/".chr($bit_r).chr($bit_r)."+/";
		$n2_search2="/".chr(255).chr(255)."+/";
		$demerit_n2=0;
		preg_match_all($n2_search1,$ver_and,$ptn_temp);
		foreach($ptn_temp[0] as $str_temp){
			$demerit_n2+=(strlen($str_temp)-1);
		}
		$ptn_temp=array();
		preg_match_all($n2_search2,$ver_or,$ptn_temp);
		foreach($ptn_temp[0] as $str_temp){
			$demerit_n2+=(strlen($str_temp)-1);
		}
		$demerit_n2*=3;
	  
		$ptn_temp=array();

		preg_match_all($n1_search,$hor,$ptn_temp);
		foreach($ptn_temp[0] as $str_temp){
			$demerit_n1+=(strlen($str_temp)-2);
		}

		$demerit_score=$demerit_n1+$demerit_n2+$demerit_n3+$demerit_n4;

		if ($demerit_score<=$min_demerit_score || $i==0){
			$mask_number=$i;
			$min_demerit_score=$demerit_score;
		}

	$i++;
	}

	$mask_content=1 << $mask_number;

	# --- format information

	$format_information_value=(($ec << 3) | $mask_number);
	$format_information_array=array("101010000010010","101000100100101",
	"101111001111100","101101101001011","100010111111001","100000011001110",
	"100111110010111","100101010100000","111011111000100","111001011110011",
	"111110110101010","111100010011101","110011000101111","110001100011000",
	"110110001000001","110100101110110","001011010001001","001001110111110",
	"001110011100111","001100111010000","000011101100010","000001001010101",
	"000110100001100","000100000111011","011010101011111","011000001101000",
	"011111100110001","011101000000110","010010010110100","010000110000011",
	"010111011011010","010101111101101");
	$i=0;
	while ($i<15){
		$content=substr($format_information_array[$format_information_value],$i,1);

		$matrix_content[$format_information_x1[$i]][$format_information_y1[$i]]=$content * 255;
		$matrix_content[$format_information_x2[$i+1]][$format_information_y2[$i+1]]=$content * 255;
		$i++;
	}


	$mib=$max_modules_1side+8;

	$output_image =ImageCreate($qrcode_image_size,$qrcode_image_size);

	$image_path=$image_path."/qrv".$qrcode_version.".png";

	$base_image=ImageCreateFromPNG($image_path);

	$col[1]=ImageColorAllocate($base_image,0,0,0);
	$col[0]=ImageColorAllocate($base_image,255,255,255);

	$i=4;
	$mxe=4+$max_modules_1side;
	$ii=0;
	while ($i<$mxe){
		$j=4;
		$jj=0;
		while ($j<$mxe){
			if ($matrix_content[$ii][$jj] & $mask_content){
				ImageSetPixel($base_image,$i,$j,$col[1]); 
			}
			$j++;
			$jj++;
		}
		$i++;
		$ii++;
	}
	
	return $base_image;
}